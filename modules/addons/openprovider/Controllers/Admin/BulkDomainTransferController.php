<?php
namespace OpenProvider\WhmcsDomainAddon\Controllers\Admin;

use Illuminate\Database\QueryException;
use WHMCS\Config\Setting;
use WeDevelopCoffee\wPower\Controllers\ViewBaseController;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Validator\Validator;
use WeDevelopCoffee\wPower\View\View;
use OpenProvider\WhmcsDomainAddon\Models\BulkTransferBatch;
use OpenProvider\WhmcsDomainAddon\Models\BulkTransferItem;
use OpenProvider\WhmcsDomainAddon\Services\BulkTransfer\BulkTransferProcessor;

/**
 * Admin controller for bulk domain transfer pages.
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
        $currentPage = $this->getCurrentPage('page');
        $perPage = 10;

        $totalItems = BulkTransferBatch::count();
        $rows = BulkTransferBatch::orderBy('created_at', 'desc')
            ->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get();

        $batches = $rows->map(function ($batch) {
            return [
                'reference'   => $batch->bulk_reference,
                'submittedAt' => $this->formatTimestamp($batch->created_at),
                'status'      => $batch->status,
                'processed'   => (int) $batch->processed_domains,
                'total'       => (int) $batch->total_domains,
                'success'     => (int) $batch->success_domains,
                'failed'      => (int) $batch->failed_domains,
                'lastUpdated' => $this->formatTimestamp($batch->updated_at),
            ];
        })->toArray();

        $pagination = $this->buildPaginationMeta($batches, $currentPage, $perPage, $totalItems);

        return $this->view('bulk_domain_transfer/batch_list', [
            'LANG'            => $params['_lang'],
            'batches'         => $pagination['items'],
            'batchPagination' => $pagination,
        ]);
    }

    public function batchDetails($params)
    {
        $batchReference = $params['batchReference'] ?? ($_GET['batchReference'] ?? '');

        $batchModel = BulkTransferBatch::where('bulk_reference', $batchReference)->first();

        if (!$batchModel) {
            return $this->view('bulk_domain_transfer/batch_details', [
                'LANG'             => $params['_lang'],
                'batch'            => null,
                'batchNotFound'    => true,
                'batchReference'   => $batchReference,
                'domains'          => [],
                'domainPagination' => null,
            ]);
        }

        $totalDomains  = (int) $batchModel->total_domains;
        $processedDomains = (int) $batchModel->processed_domains;

        $batch = [
            'reference'          => $batchModel->bulk_reference,
            'submittedAt'        => $this->formatTimestamp($batchModel->created_at),
            'lastUpdated'        => $this->formatTimestamp($batchModel->updated_at),
            'status'             => $batchModel->status,
            'totalDomains'       => $totalDomains,
            'processed'          => $processedDomains,
            'successful'         => (int) $batchModel->success_domains,
            'failed'             => (int) $batchModel->failed_domains,
            'progressPercentage' => $totalDomains > 0
                ? round(($processedDomains / $totalDomains) * 100)
                : 0,
        ];

        $currentPage = $this->getCurrentPage('domainPage');
        $perPage = 10;

        $totalItems = BulkTransferItem::where('batch_id', $batchModel->id)->count();
        $itemRows = BulkTransferItem::where('batch_id', $batchModel->id)
            ->orderBy('id', 'asc')
            ->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get();

        $domains = $itemRows->map(function ($item) {
            $message = $item->last_status_message;
            if (!$message && $item->failure_reason) {
                $message = $item->failure_reason;
            }
            return [
                'domain'      => $item->domain,
                'status'      => $item->transfer_status,
                'message'     => (string) ($message ?? ''),
                'lastUpdated' => $this->formatTimestamp($item->updated_at),
            ];
        })->toArray();

        $domainPagination = $this->buildPaginationMeta($domains, $currentPage, $perPage, $totalItems);

        return $this->view('bulk_domain_transfer/batch_details', [
            'LANG'             => $params['_lang'],
            'batch'            => $batch,
            'domains'          => $domainPagination['items'],
            'domainPagination' => $domainPagination,
        ]);
    }

    private function getCurrentPage(string $key = 'page'): int
    {
        $page = isset($_GET[$key]) ? (int) $_GET[$key] : 1;
        return max(1, $page);
    }

    private function buildPaginationMeta(array $items, int $page, int $perPage, int $totalItems): array
    {
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $page = min($page, $totalPages);

        return [
            'items'           => $items,
            'currentPage'     => $page,
            'perPage'         => $perPage,
            'totalItems'      => $totalItems,
            'totalPages'      => $totalPages,
            'hasPreviousPage' => $page > 1,
            'hasNextPage'     => $page < $totalPages,
            'previousPage'    => $page > 1 ? $page - 1 : 1,
            'nextPage'        => $page < $totalPages ? $page + 1 : $totalPages,
        ];
    }

    private function formatTimestamp($timestamp): string
    {
        if (!$timestamp) {
            return '';
        }
        return date('d M Y, H:i', strtotime((string) $timestamp));
    }

}
