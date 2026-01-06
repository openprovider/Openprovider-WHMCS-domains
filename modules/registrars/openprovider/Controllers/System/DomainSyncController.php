<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\ApiHelper;
use OpenProvider\WhmcsRegistrar\helpers\DomainFullNameToDomainObject;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use WHMCS\Carbon;
use OpenProvider\WhmcsHelpers\DomainSync;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\Domain as api_domain;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Models\Domain;

/**
 * Class DomainSynController
 * @package OpenProvider\WhmcsRegistrar\Controllers\System
 */
class DomainSyncController extends BaseController
{
    /**
     * @var api_domain
     */
    private $api_domain;
    /**
     * @var Domain
     */
    private $domain;
    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     */
    public function __construct(Core $core, api_domain $api_domain, Domain $domain, ApiHelper $apiHelper)
    {
        parent::__construct($core);

        $this->api_domain = $api_domain;
        $this->domain = $domain;
        $this->apiHelper = $apiHelper;
    }

    /**
     * Synchronize domain status and expiry date.
     *
     * @param $params
     * @return array
     */
    public function sync($params)
    {
        // Find the domain in WHMCS
        $this->domain = $this->domain->find($params['domainid']);

        try {
            // Convert the domain to an OpenProvider domain object
            $this->api_domain = DomainFullNameToDomainObject::convert($this->domain->domain);

            // Get domain data from OpenProvider
            $domainOp = $this->apiHelper->getDomain($this->api_domain);

            // Update expiry date from OpenProvider
            $expiration_date = Carbon::createFromFormat('Y-m-d H:i:s', $domainOp['renewalDate'], 'Europe/Amsterdam')
                ->toDateString();

            // Determine domain status based on OpenProvider data
            $status = $this->mapDomainStatus($domainOp['status']);
            // save the status and expiry date
            $this->updateDomainStatusAndExpiry($status, $expiration_date, $params['domainid']);

            // Refresh the page with a cleaned URL (avoiding regaction & ac params)
            $url = $_SERVER['HTTP_REFERER'];
            $url_decoded = html_entity_decode($url);
            $parsedUrl = parse_url($url_decoded);
            parse_str($parsedUrl['query'] ?? '', $query);
            unset($query['regaction'], $query['ac'], $query['token']); // Remove problematic params
            $newQuery = http_build_query($query);
            $cleanUrl = $parsedUrl['path'] . ($newQuery ? '?' . $newQuery : '');
            header("Location: $cleanUrl");

            return [
                'expirydate' => $expiration_date, // Format: YYYY-MM-DD
                'active' => $status === 'Active',
                'cancelled' => $status === 'Cancelled',
                'transferredAway' => $status === 'Transferred Away',
            ];
        } catch (\Exception $ex) {
            return [
                'error' =>  $ex->getMessage()
            ];
        }
    }
    private function updateDomainStatusAndExpiry($status, $expiry_date, $domainId){
        $command = 'UpdateClientDomain';
        try {
            $postData = array(
                'domainid' => $domainId,
                'status' => $status,
                'expirydate' => $expiry_date,
            );
            $results = localAPI($command, $postData);
            logModuleCall('WHMCS internal', $command, "{'domainid':$domainId,'status':$status, 'expirydate' => $expiry_date}", $results, null, null);
        } catch (\Exception $e) {
            logModuleCall('WHMCS internal', $command, null, "Failed to update domain. id: " . $domainId . ", msg: " . $e->getMessage(), null, null);
        }
    }
    /**
     * Map OpenProvider status to WHMCS status.
     *
     * @param string $opStatus
     * @return string
     */
    private function mapDomainStatus($opStatus)
    {
        if (in_array($opStatus, ['ACT'])) {
            return 'Active';
        } elseif (in_array($opStatus, ['FAI', 'DEL'])) {
            return 'Cancelled';
        } elseif ($opStatus === 'TRAN') {
            return 'Transferred Away';
        } else {
            return 'Inactive';
        }
    }
}
