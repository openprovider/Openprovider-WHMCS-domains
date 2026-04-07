<?php
namespace OpenProvider\WhmcsDomainAddon\Controllers\Admin;
use WeDevelopCoffee\wPower\Controllers\ViewBaseController;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Validator\Validator;
use WeDevelopCoffee\wPower\View\View;
use WHMCS\Database\Capsule;
use WHMCS\Input\Sanitize;


/**
 * Client controller dispatcher.
 */
class BulkDomainTransferController extends ViewBaseController {

    /**
     * ViewBaseController constructor.
     */
    public function __construct(Core $core, View $view, Validator $validator)
    {
        parent::__construct($core, $view, $validator);
    }

    /**
     * Show page for bulk domain transfers.
     * 
     * @return string
     */
    public function show($params)
    {
        return "Bulk Domain Transfer page loaded successfully.";
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
