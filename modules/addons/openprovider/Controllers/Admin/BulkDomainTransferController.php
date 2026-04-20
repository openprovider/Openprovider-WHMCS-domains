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
        $batches = [
            [
                'reference' => 'BT-2026-000184',
                'submittedAt' => '10 Apr 2026, 10:22',
                'status' => 'Processing',
                'processed' => 68,
                'total' => 100,
                'success' => 60,
                'failed' => 8,
                'lastUpdated' => '10 Apr 2026, 10:41',
            ],
            [
                'reference' => 'BT-2026-000183',
                'submittedAt' => '09 Apr 2026, 16:08',
                'status' => 'Completed with errors',
                'processed' => 100,
                'total' => 100,
                'success' => 94,
                'failed' => 6,
                'lastUpdated' => '09 Apr 2026, 16:31',
            ],
            [
                'reference' => 'BT-2026-000182',
                'submittedAt' => '09 Apr 2026, 11:05',
                'status' => 'Completed',
                'processed' => 40,
                'total' => 40,
                'success' => 40,
                'failed' => 0,
                'lastUpdated' => '09 Apr 2026, 11:14',
            ],
            [
                'reference' => 'BT-2026-000181',
                'submittedAt' => '08 Apr 2026, 18:45',
                'status' => 'Queued',
                'processed' => 0,
                'total' => 55,
                'success' => 0,
                'failed' => 0,
                'lastUpdated' => '08 Apr 2026, 18:45',
            ],
        ];

        return $this->view('bulk_domain_transfer/batch_list', [
            'LANG' => $params['_lang'],
            'batches' => $batches,
        ]);
    }

    public function batchDetails($params)
    {
        $batchReference = $params['batchReference'] ?? ($_GET['batchReference'] ?? '');

        $batch = [
            'reference' => $batchReference ?: 'BT-2026-000184',
            'submittedAt' => '10 Apr 2026, 10:22',
            'lastUpdated' => '10 Apr 2026, 10:41',
            'status' => 'Processing',
            'totalDomains' => 100,
            'processed' => 68,
            'successful' => 60,
            'failed' => 8,
            'progressPercentage' => 68,
        ];

        $domains = [
            [
                'domain' => 'example1.com',
                'status' => 'Completed',
                'message' => 'Transfer submitted successfully.',
                'lastUpdated' => '10 Apr 2026, 10:39',
            ],
            [
                'domain' => 'example2.net',
                'status' => 'Transfer in progress',
                'message' => 'Transfer request is being processed.',
                'lastUpdated' => '10 Apr 2026, 10:40',
            ],
            [
                'domain' => 'example3.org',
                'status' => 'Validation failed',
                'message' => 'Authorization code is missing or invalid. Update the auth code and retry.',
                'lastUpdated' => '10 Apr 2026, 10:36',
            ],
            [
                'domain' => 'example4.io',
                'status' => 'Failed',
                'message' => 'Domain is locked at the current registrar. Unlock the domain and retry.',
                'lastUpdated' => '10 Apr 2026, 10:35',
            ],
            [
                'domain' => 'example5.co',
                'status' => 'Queued',
                'message' => 'Waiting to be processed.',
                'lastUpdated' => '10 Apr 2026, 10:22',
            ],
        ];

        return $this->view('bulk_domain_transfer/batch_details', [
            'LANG' => $params['_lang'],
            'batch' => $batch,
            'domains' => $domains,
        ]);
    }

}
