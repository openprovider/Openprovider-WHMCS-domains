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

    public function batchList($params)
    {
        $batches = [];

        // this is a temporary code to build mock data. remove when DB is integrated.
        for ($i = 184; $i >= 170; $i--) {
            $total = rand(20, 120);
            $processed = rand(0, $total);
            $success = rand(0, $processed);
            $failed = $processed - $success;

            $statuses = ['Processing', 'Completed', 'Completed with errors', 'Queued'];
            $status = $statuses[array_rand($statuses)];

            $batches[] = [
                'reference' => 'BT-2026-00' . $i,
                'submittedAt' => date('d M Y, H:i', strtotime("-{$i} minutes")),
                'status' => $status,
                'processed' => $processed,
                'total' => $total,
                'success' => $success,
                'failed' => $failed,
                'lastUpdated' => date('d M Y, H:i', strtotime("-" . ($i - 2) . " minutes")),
            ];
        }

        $currentPage = $this->getCurrentPage('page');
        $perPage = 10;
        $pagination = $this->paginateArray($batches, $currentPage, $perPage);

        return $this->view('bulk_domain_transfer/batch_list', [
            'LANG' => $params['_lang'],
            'batches' => $pagination['items'],
            'batchPagination' => $pagination,
        ]);
    }

    public function batchDetails($params)
    {
        $batchReference = $params['batchReference'] ?? ($_GET['batchReference'] ?? '');

        // temporary mock data. remove when DB is integrated.
        $batch = [
            'reference' => $batchReference ?: 'BT-2026-000184',
            'submittedAt' => '10 Apr 2026, 10:22',
            'lastUpdated' => '10 Apr 2026, 10:41',
            'status' => 'Processing',
            'totalDomains' => 100,
            'processed' => 68,
            'successful' => 60,
            'failed' => 8,
        ];

        // calculate progress
        $progressPercentage = $batch['totalDomains'] > 0
            ? round(($batch['processed'] / $batch['totalDomains']) * 100)
            : 0;

        $batch['progressPercentage'] = $progressPercentage;
        
        // temporary mock data. remove when DB is integrated.
        $domains = [
            ['domain' => 'example1.com', 'status' => 'Completed', 'message' => 'Transfer submitted successfully.', 'lastUpdated' => '10 Apr 2026, 10:39'],
            ['domain' => 'example2.net', 'status' => 'Transfer in progress', 'message' => 'Transfer request is being processed.', 'lastUpdated' => '10 Apr 2026, 10:40'],
            ['domain' => 'example3.org', 'status' => 'Validation failed', 'message' => 'Authorization code is missing or invalid.', 'lastUpdated' => '10 Apr 2026, 10:36'],
            ['domain' => 'example4.io', 'status' => 'Failed', 'message' => 'Domain is locked at the current registrar.', 'lastUpdated' => '10 Apr 2026, 10:35'],
            ['domain' => 'example5.co', 'status' => 'Queued', 'message' => 'Waiting to be processed.', 'lastUpdated' => '10 Apr 2026, 10:22'],
            ['domain' => 'example6.xyz', 'status' => 'Completed', 'message' => 'Transfer submitted successfully.', 'lastUpdated' => '10 Apr 2026, 10:21'],
            ['domain' => 'example7.info', 'status' => 'Queued', 'message' => 'Waiting to be processed.', 'lastUpdated' => '10 Apr 2026, 10:20'],
            ['domain' => 'example8.dev', 'status' => 'Transfer in progress', 'message' => 'Transfer request is being processed.', 'lastUpdated' => '10 Apr 2026, 10:19'],
            ['domain' => 'example9.app', 'status' => 'Completed', 'message' => 'Transfer submitted successfully.', 'lastUpdated' => '10 Apr 2026, 10:18'],
            ['domain' => 'example10.io', 'status' => 'Failed', 'message' => 'Domain is locked at the current registrar.', 'lastUpdated' => '10 Apr 2026, 10:17'],

            ['domain' => 'example11.com', 'status' => 'Queued', 'message' => 'Waiting to be processed.', 'lastUpdated' => '10 Apr 2026, 10:16'],
            ['domain' => 'example12.net', 'status' => 'Completed', 'message' => 'Transfer submitted successfully.', 'lastUpdated' => '10 Apr 2026, 10:15'],
            ['domain' => 'example13.org', 'status' => 'Validation failed', 'message' => 'Authorization code is missing or invalid.', 'lastUpdated' => '10 Apr 2026, 10:14'],
            ['domain' => 'example14.io', 'status' => 'Transfer in progress', 'message' => 'Transfer request is being processed.', 'lastUpdated' => '10 Apr 2026, 10:13'],
            ['domain' => 'example15.co', 'status' => 'Completed', 'message' => 'Transfer submitted successfully.', 'lastUpdated' => '10 Apr 2026, 10:12'],
            ['domain' => 'example16.xyz', 'status' => 'Queued', 'message' => 'Waiting to be processed.', 'lastUpdated' => '10 Apr 2026, 10:11'],
            ['domain' => 'example17.info', 'status' => 'Failed', 'message' => 'Domain is locked at the current registrar.', 'lastUpdated' => '10 Apr 2026, 10:10'],
            ['domain' => 'example18.dev', 'status' => 'Completed', 'message' => 'Transfer submitted successfully.', 'lastUpdated' => '10 Apr 2026, 10:09'],
            ['domain' => 'example19.app', 'status' => 'Transfer in progress', 'message' => 'Transfer request is being processed.', 'lastUpdated' => '10 Apr 2026, 10:08'],
            ['domain' => 'example20.io', 'status' => 'Queued', 'message' => 'Waiting to be processed.', 'lastUpdated' => '10 Apr 2026, 10:07'],

            ['domain' => 'example21.com', 'status' => 'Completed', 'message' => 'Transfer submitted successfully.', 'lastUpdated' => '10 Apr 2026, 10:06'],
            ['domain' => 'example22.net', 'status' => 'Validation failed', 'message' => 'Authorization code is missing or invalid.', 'lastUpdated' => '10 Apr 2026, 10:05'],
            ['domain' => 'example23.org', 'status' => 'Failed', 'message' => 'Domain is locked at the current registrar.', 'lastUpdated' => '10 Apr 2026, 10:04'],
            ['domain' => 'example24.io', 'status' => 'Transfer in progress', 'message' => 'Transfer request is being processed.', 'lastUpdated' => '10 Apr 2026, 10:03'],
            ['domain' => 'example25.co', 'status' => 'Queued', 'message' => 'Waiting to be processed.', 'lastUpdated' => '10 Apr 2026, 10:02'],
            ['domain' => 'example26.xyz', 'status' => 'Completed', 'message' => 'Transfer submitted successfully.', 'lastUpdated' => '10 Apr 2026, 10:01'],
            ['domain' => 'example27.info', 'status' => 'Transfer in progress', 'message' => 'Transfer request is being processed.', 'lastUpdated' => '10 Apr 2026, 10:00'],
            ['domain' => 'example28.dev', 'status' => 'Queued', 'message' => 'Waiting to be processed.', 'lastUpdated' => '10 Apr 2026, 09:59'],
            ['domain' => 'example29.app', 'status' => 'Completed', 'message' => 'Transfer submitted successfully.', 'lastUpdated' => '10 Apr 2026, 09:58'],
            ['domain' => 'example30.io', 'status' => 'Failed', 'message' => 'Domain is locked at the current registrar.', 'lastUpdated' => '10 Apr 2026, 09:57'],

            ['domain' => 'example31.com', 'status' => 'Queued', 'message' => 'Waiting to be processed.', 'lastUpdated' => '10 Apr 2026, 09:56'],
            ['domain' => 'example32.net', 'status' => 'Completed', 'message' => 'Transfer submitted successfully.', 'lastUpdated' => '10 Apr 2026, 09:55'],
            ['domain' => 'example33.org', 'status' => 'Validation failed', 'message' => 'Authorization code is missing or invalid.', 'lastUpdated' => '10 Apr 2026, 09:54'],
            ['domain' => 'example34.io', 'status' => 'Transfer in progress', 'message' => 'Transfer request is being processed.', 'lastUpdated' => '10 Apr 2026, 09:53'],
            ['domain' => 'example35.co', 'status' => 'Completed', 'message' => 'Transfer submitted successfully.', 'lastUpdated' => '10 Apr 2026, 09:52'],
        ];

        $currentPage = $this->getCurrentPage('domainPage');
        $perPage = 10;
        $pagination = $this->paginateArray($domains, $currentPage, $perPage);

        return $this->view('bulk_domain_transfer/batch_details', [
            'LANG' => $params['_lang'],
            'batch' => $batch,
            'domains' => $pagination['items'],
            'domainPagination' => $pagination,
        ]);
    }

    private function getCurrentPage(string $key = 'page'): int
    {
        $page = isset($_GET[$key]) ? (int) $_GET[$key] : 1;
        return max(1, $page);
    }

    private function paginateArray(array $items, int $page, int $perPage): array
    {
        $totalItems = count($items);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        return [
            'items' => array_slice($items, $offset, $perPage),
            'currentPage' => $page,
            'perPage' => $perPage,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages,
            'hasPreviousPage' => $page > 1,
            'hasNextPage' => $page < $totalPages,
            'previousPage' => $page > 1 ? $page - 1 : 1,
            'nextPage' => $page < $totalPages ? $page + 1 : $totalPages,
        ];
    }

}
