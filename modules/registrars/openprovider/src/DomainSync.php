<?php

namespace OpenProvider\WhmcsRegistrar\src;

use Carbon\Carbon;
use OpenProvider\API\ApiHelper;
use OpenProvider\WhmcsRegistrar\helpers\DomainFullNameToDomainObject;
use OpenProvider\WhmcsHelpers\Domain;
use WeDevelopCoffee\wPower\Models\Domain as DomainModel;
use OpenProvider\API\Domain as api_domain;
use OpenProvider\WhmcsHelpers\Activity;
use OpenProvider\WhmcsHelpers\General;

/**
 * Helper to synchronize the domain status and expiry date.
 * OpenProvider Registrar module
 */
class DomainSync
{
    /**
     * The domains that need to get processed
     *
     * @var DomainModel[]
     **/
    private $domains;
    /**
     * The WHMCS domain
     *
     * @var DomainModel
     **/
    private $objectDomain;
    /**
     * The op domain object
     *
     * @var \OpenProvider\API\Domain
     **/
    private $op_domain_obj;

    /**
     * The op domain info
     *
     * @var array
     **/
    private $op_domain;

    /**
     * Update the domain data
     *
     * @var array
     **/
    private $update_domain_data;

    /**
     * @var ApiHelper
     */
    private $apiHelper;
    /**
     * @var \idna_convert
     */
    private $idn;

    /**
     * Init the class
     *
     * @return void
     **/
    public function __construct(ApiHelper $apiHelper, \idna_convert $idn, $limit = false)
    {
        $this->apiHelper = $apiHelper;
        $this->idn = $idn;

        $this->domain = new DomainModel();

        // Get all unprocessed domains
        if($limit === false)
        {
            $limit = Configuration::getOrDefault('domainProcessingLimit', 200);
        }

        $this->get_unprocessed_domains($limit);
    }

    /**
     * Get the unprocessed domains
     *
     * @return void
     **/
    private function get_unprocessed_domains($limit)
    {
        $domains = helper_DomainSync::get_unprocessed_domains('openprovider', $limit);

        // Save the domains
        $this->domains = $domains;
    }

    /**
     * Process the domains
     *
     * @return void
     **/
    public function process_domains()
    {
        $this->printDebug('PROCESSING DOMAINS...');

        foreach($this->domains as $domain)
        {
            try
            {
                $this->objectDomain = $domain;
                $this->op_domain_obj = DomainFullNameToDomainObject::convert($this->idn->encode($domain->domain));
                $this->op_domain = $this->apiHelper->getDomain($this->op_domain_obj);

                // Update expiry date and status
                $this->process_expiry_date();
                $this->process_domain_status();

            }
            catch (\Exception $ex)
            {
                $this->printDebug('Error processing domain: ' . $domain->domain . ' - ' . $ex->getMessage());
                continue;
            }

            // Save the updated domain data
            Domain::save($this->objectDomain->id, $this->update_domain_data, 'openprovider');
        }

        $this->printDebug('DONE PROCESSING DOMAINS');
    }

    /**
     * Process and update the expiry date.
     *
     * @return void
     **/
    private function process_expiry_date()
    {
        $this->printDebug('PROCESSING EXPIRY DATE FOR ' . $this->objectDomain->domain .' (WAS ' . $this->objectDomain->expirydate . ')');

        // Sync the expiry date from OpenProvider
        $whmcs_expiry_date = new Carbon($this->objectDomain->expirydate);

        // Check if we have a valid timestamp.
        if($whmcs_expiry_date->getTimestamp() > 0)
        {
            $expiry_date_result = General::compare_dates($whmcs_expiry_date->toDateString(), $this->op_domain['renewalDate'], '0', 'Y-m-d H:i:s');
        }
        else
        {
            // There is no valid timestamp.
            $expiry_date_result = [
                'date' => $this->op_domain['renewalDate']
            ];
        }

        if($expiry_date_result != 'correct')
        {
            $this->printDebug('UPDATING EXPIRY DATE FOR ' . $this->objectDomain->domain .' TO ' . $expiry_date_result['date'] . ')');
            $this->update_domain_data['expirydate'] = $expiry_date_result['date'];
        }
    }

    /**
     * Process and update the domain status.
     *
     * @param string $status Optional status to set
     * @return void
     **/
    private function process_domain_status($status = null)
    {
        if (!$status) {
            $status = api_domain::convertOpStatusToWhmcs($this->op_domain['status']);
        }

        if ($status && $this->objectDomain->status != $status) {
            $this->printDebug('UPDATING STATUS FOR ' . $this->objectDomain->domain .' TO ' . $status . ')');
            $this->update_domain_data['status'] = $status;
        }
    }

    public function printDebug($message)
    {
        if(defined('OP_REG_DEBUG'))
            echo $message . "\n";
    }
}
