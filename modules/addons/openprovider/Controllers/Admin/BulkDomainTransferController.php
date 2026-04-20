<?php
namespace OpenProvider\WhmcsDomainAddon\Controllers\Admin;

use Illuminate\Database\QueryException;
use WHMCS\Config\Setting;
use WeDevelopCoffee\wPower\Controllers\ViewBaseController;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Validator\Validator;
use WeDevelopCoffee\wPower\View\View;
use OpenProvider\WhmcsDomainAddon\Services\BulkTransfer\BulkTransferProcessor;
use WHMCS\Database\Capsule;
use WHMCS\Input\Sanitize;


/**
 * Client controller dispatcher.
 */
class BulkDomainTransferController extends ViewBaseController
{
    /**
     * @var BulkTransferProcessor
     */
    protected $bulkTransferProcessor;

    /**
     * ViewBaseController constructor.
     */
    public function __construct(
        Core $core,
        View $view,
        Validator $validator,
        BulkTransferProcessor $bulkTransferProcessor
    ) {
        parent::__construct($core, $view, $validator);
        $this->bulkTransferProcessor = $bulkTransferProcessor;
    }

    /**
     * Show page for bulk domain transfers.
     *
     * @return string
     */
    public function show($params)
    {
        $domains = isset($_POST['domains']) ? trim($_POST['domains']) : '';
        $submissionError = null;
        $validationErrors = [];
        $bulkReference = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_token('WHMCS.admin.default');

            $validationResult = $this->validateDomainsFromTextarea($domains);
            $validationErrors = $validationResult['validationErrors'];

            if (empty($validationErrors)) {
                try {
                    $bulkReference = $this->generateBulkReference();

                    $batch = $this->bulkTransferProcessor->createBatch(
                        $validationResult['validDomains'],
                        $bulkReference,
                        null,
                        isset($_SESSION['adminid']) ? (int) $_SESSION['adminid'] : null,
                        'Bulk transfer request from admin bulk transfer page',

                    );

                    if (!empty($batch->bulk_reference)) {
                        $bulkReference = $batch->bulk_reference;
                    }
                } catch (\Throwable $e) {
                    $bulkReference = null;
                    $submissionError = $this->buildSubmissionErrorMessage($e);
                    $this->logSubmissionFailure($validationResult['validDomains'], $e);
                }
            }
        }

        $systemUrl = rtrim(Setting::getValue('SystemURL'), '/');
        $sampleCsvUrl = $systemUrl . '/modules/addons/openprovider/resources/assets/sample_bulk_transfer.csv';

        return $this->view('bulk_domain_transfer/index', [
            'domains' => $domains,
            'submissionError' => $submissionError,
            'validationErrors' => $validationErrors,
            'bulkReference' => $bulkReference,
            'LANG' => $params['_lang'],
            'sampleCsvUrl' => $sampleCsvUrl,
        ]);
    }

    private function buildSubmissionErrorMessage(\Throwable $exception): string
    {
        $message = trim((string) $exception->getMessage());

        if ($this->isMissingBulkTransferTableException($exception, $message)) {
            return 'The bulk transfer tables are not available yet. Run the Openprovider addon migrations and try again.';
        }

        if ($this->isDuplicateBulkReferenceException($message)) {
            return 'A temporary reference conflict occurred while saving the request. Please submit it again.';
        }

        if ($exception instanceof QueryException) {
            return 'The request could not be saved. Please try again or check the module logs for more details.';
        }

        if ($exception instanceof \InvalidArgumentException) {
            return 'The bulk transfer request is not valid. Please review the submitted domains and try again.';
        }

        return 'Please try again or check the module logs for more details.';
    }

    private function isMissingBulkTransferTableException(\Throwable $exception, string $message): bool
    {
        if (!$exception instanceof QueryException) {
            return false;
        }

        $normalizedMessage = strtolower($message);
        $referencesBulkTransferTables = strpos($normalizedMessage, 'mod_op_bulk_transfer_batches') !== false
            || strpos($normalizedMessage, 'mod_op_bulk_transfer_items') !== false;
        $indicatesMissingTable = strpos($normalizedMessage, 'base table or view not found') !== false
            || strpos($normalizedMessage, 'doesn\'t exist') !== false
            || strpos($normalizedMessage, 'no such table') !== false;

        return $referencesBulkTransferTables && $indicatesMissingTable;
    }

    private function isDuplicateBulkReferenceException(string $message): bool
    {
        $normalizedMessage = strtolower($message);

        return strpos($normalizedMessage, 'duplicate entry') !== false
            && (
                strpos($normalizedMessage, 'bulk_reference') !== false
                || strpos($normalizedMessage, 'uniq_bulk_reference') !== false
            );
    }

    private function logSubmissionFailure(array $domains, \Throwable $exception): void
    {
        if (!function_exists('logModuleCall')) {
            return;
        }

        logModuleCall(
            'openprovider',
            'bulk_transfer_submission_failed',
            [
                'domains' => $domains,
                'domain_count' => count($domains),
            ],
            [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'exception' => get_class($exception),
            ],
            null,
            []
        );
    }

    private function generateBulkReference(): string
    {
        return 'BT-' . date('Ymd-His') . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function validateDomainsFromTextarea(string $domainsText): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $domainsText);
        $validDomains = [];
        $validationErrors = [];

        foreach ($lines as $index => $line) {
            $domain = trim($line);
            $lineNumber = $index + 1;

            if ($domain === '') {
                continue;
            }

            if (!$this->isValidDomain($domain)) {
                $validationErrors[] = "Line {$lineNumber}: Invalid domain '{$domain}'.";
                continue;
            }

            $validDomains[] = strtolower($domain);
        }

        $validDomains = array_values(array_unique($validDomains));

        if (empty($validDomains) && empty($validationErrors)) {
            $validationErrors[] = 'Please enter at least one valid domain.';
        }

        return [
            'validDomains' => $validDomains,
            'validationErrors' => $validationErrors,
        ];
    }

    private function isValidDomain(string $domain): bool
    {
        if (strpos($domain, ' ') !== false) {
            return false;
        }

        if (strpos($domain, ',') !== false) {
            return false;
        }

        if (stripos($domain, 'http://') === 0 || stripos($domain, 'https://') === 0) {
            return false;
        }

        return (bool) preg_match(
            '/^(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])$/',
            $domain
        );
    }
    public function showStatusPage($params)
    {
        return "Bulk Domain Transfer Status page loaded successfully.";
    }

    // public function showStatusPage()
    // {
    //     $selectedReference = isset($_GET['bulk_reference']) ? trim($_GET['bulk_reference']) : '';
    //     $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    //     $page = max(1, $page);

    //     $perPage = 10;

    //     /**
    //      * TODO: Replace with backend response / DB query once bulk transfer backend is implemented.
    //      * This is temporary mock data for UI development only.
    //      */
    //     $mockBulkTransfers = [
    //         'BT-20260408-001' => [
    //             ['domain_name' => 'example1.com', 'status' => 'Pending', 'message' => 'Transfer submitted', 'updated_at' => '2026-04-08 10:00:00'],
    //             ['domain_name' => 'example2.net', 'status' => 'Completed', 'message' => 'Transfer completed successfully', 'updated_at' => '2026-04-08 10:05:00'],
    //             ['domain_name' => 'example3.org', 'status' => 'Failed', 'message' => 'Auth code invalid', 'updated_at' => '2026-04-08 10:10:00'],
    //             ['domain_name' => 'example4.eu', 'status' => 'Pending', 'message' => 'Waiting for registry response', 'updated_at' => '2026-04-08 10:15:00'],
    //             ['domain_name' => 'example5.info', 'status' => 'Completed', 'message' => 'Transfer completed successfully', 'updated_at' => '2026-04-08 10:20:00'],
    //             ['domain_name' => 'example6.biz', 'status' => 'Pending', 'message' => 'Transfer submitted', 'updated_at' => '2026-04-08 10:25:00'],
    //             ['domain_name' => 'example7.co', 'status' => 'Failed', 'message' => 'Domain locked', 'updated_at' => '2026-04-08 10:30:00'],
    //             ['domain_name' => 'example8.io', 'status' => 'Completed', 'message' => 'Transfer completed successfully', 'updated_at' => '2026-04-08 10:35:00'],
    //             ['domain_name' => 'example9.app', 'status' => 'Pending', 'message' => 'Waiting for losing registrar', 'updated_at' => '2026-04-08 10:40:00'],
    //             ['domain_name' => 'example10.dev', 'status' => 'Completed', 'message' => 'Transfer completed successfully', 'updated_at' => '2026-04-08 10:45:00'],
    //             ['domain_name' => 'example11.xyz', 'status' => 'Pending', 'message' => 'Transfer submitted', 'updated_at' => '2026-04-08 10:50:00'],
    //         ],
    //         'BT-20260408-002' => [
    //             ['domain_name' => 'mydomain1.com', 'status' => 'Completed', 'message' => 'Transfer completed successfully', 'updated_at' => '2026-04-08 11:00:00'],
    //             ['domain_name' => 'mydomain2.net', 'status' => 'Pending', 'message' => 'Waiting for registry response', 'updated_at' => '2026-04-08 11:05:00'],
    //         ],
    //     ];

    //     /**
    //      * TODO: Replace with unique bulk reference numbers returned by backend.
    //      */
    //     $bulkReferences = array_keys($mockBulkTransfers);

    //     if (empty($selectedReference) && !empty($bulkReferences)) {
    //         $selectedReference = $bulkReferences[0];
    //     }

    //     /**
    //      * TODO: Replace with paginated backend/domain status response for selected bulk reference.
    //      */
    //     $allDomains = isset($mockBulkTransfers[$selectedReference]) ? $mockBulkTransfers[$selectedReference] : [];

    //     $totalDomains = count($allDomains);
    //     $totalPages = $totalDomains > 0 ? (int) ceil($totalDomains / $perPage) : 0;
    //     $offset = ($page - 1) * $perPage;
    //     $domains = array_slice($allDomains, $offset, $perPage);

    //     return $this->view('bulk_domain_transfer/status', [
    //         'bulkReferences' => $bulkReferences,
    //         'selectedReference' => $selectedReference,
    //         'domains' => $domains,
    //         'currentPage' => $page,
    //         'totalPages' => $totalPages,
    //         'totalDomains' => $totalDomains,
    //     ]);
    // }

    /**
     * Get unique bulk reference numbers for dropdown.
     *
     * @return array
     */
    // protected function getBulkReferences()
    // {
    //     return Capsule::table('mod_openprovider_bulk_transfers')
    //         ->select('bulk_reference')
    //         ->distinct()
    //         ->orderBy('bulk_reference', 'desc')
    //         ->pluck('bulk_reference')
    //         ->toArray();
    // }

    /**
     * Get total domain count for selected bulk reference.
     *
     * @param string $bulkReference
     * @return int
     */
    // protected function getDomainCountByReference($bulkReference)
    // {
    //     return Capsule::table('mod_openprovider_bulk_transfers')
    //         ->where('bulk_reference', $bulkReference)
    //         ->count();
    // }

    /**
     * Get paginated domains and statuses for selected bulk reference.
     *
     * @param string $bulkReference
     * @param int $limit
     * @param int $offset
     * @return array
     */
    // protected function getDomainsByReference($bulkReference, $limit, $offset)
    // {
    //     return Capsule::table('mod_openprovider_bulk_transfers')
    //         ->select('domain_name', 'status', 'message', 'updated_at')
    //         ->where('bulk_reference', $bulkReference)
    //         ->orderBy('domain_name', 'asc')
    //         ->offset($offset)
    //         ->limit($limit)
    //         ->get()
    //         ->map(function ($item) {
    //             return [
    //                 'domain_name' => $item->domain_name,
    //                 'status'      => $item->status,
    //                 'message'     => $item->message,
    //                 'updated_at'  => $item->updated_at,
    //             ];
    //         })
    //         ->toArray();
    // }

}
