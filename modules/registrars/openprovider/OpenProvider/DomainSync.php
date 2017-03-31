<?php
namespace OpenProvider;
use WHMCS\Database\Capsule;
use \OpenProvider\WhmcsHelpers\DomainSync as helper_DomainSync;
use \OpenProvider\WhmcsHelpers\Domain;
use \OpenProvider\WhmcsHelpers\General;

/**
 * Helper to synchronize the domain info.
 *
 * @package default
 * @license  Licensed to OpenProvider by Yourwebhoster.eu
 **/
class DomainSync
{
	/**
	 * The domains that need to get processed
	 *
	 * @var string
	 **/
	private $domains;

	/**
	 * The WHMCS domain
	 *
	 * @var array
	 **/
	private $domain;

	/**
	 * The OpenProvider object
	 *
	 * @var object
	 **/
	private $OpenProvider;

	/**
	 * The op domain object
	 *
	 * @var object
	 **/
	private $op_domain_obj;

	/**
	 * The op domain info
	 *
	 * @var object
	 **/
	private $op_domain;

	/**
	 * Update the domain data
	 *
	 * @var array
	 **/
	private $update_domain_data;
	
	/**
	 * Init the class
	 *
	 * @return void
	 **/
	public function __construct($limit = 200)
	{
		// Check if there are domains missing from the DomainSync table
		helper_DomainSync::sync_DomainSync_table('openprovider');

		// Get all unprocessed domains
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
	 * Check if there are domains to process
	 *
	 * @return boolean true when there are domains to process
	 **/
	public function has_domains_to_proccess()
	{
		if(empty($this->domains))
			return false;
		else
			return true;
	}

	/**
	 * Process the domains
	 *
	 * @return void
	 **/
	public function process_domains()
	{
		$this->OpenProvider = new OpenProvider;

		foreach($this->domains as $domain)
		{
			try
			{
				$this->domain 			= $domain;
				$this->op_domain_obj 	= $this->OpenProvider->domain($domain->domain);
				$this->op_domain   		= $this->OpenProvider->api->retrieveDomainRequest($this->op_domain_obj);

				// Set the expire and due date -> openprovider is leading
				$this->process_expiry_and_due_date();

				// Active or pending? -> openprovider is leading
				$this->process_domain_status();

				// auto renew on or not? -> WHMCS is leading.
				$this->process_auto_renew();
			}
		    catch (\Exception $ex)
		    {
                if($ex->getMessage() == 'This action is prohibitted for current domain status.') {
                    // Set the status to expired.
                    $this->process_domain_status('Expired');
                }
		    }

		    // Save
		    Domain::save($this->domain->id, $this->update_domain_data, 'openprovider');
		    $this->update_domain_data = null;
		}
	}

	/**
	 * Process and match the expiry date.
	 *
	 * @return void
	 **/
	private function process_expiry_and_due_date()
	{
		// Sync the expiry date
		$expiry_date_result = General::compare_dates($this->domain->expirydate, $this->op_domain['expirationDate'], '0', 'Y-m-d H:i:s');

		if($expiry_date_result != 'correct')
		{
			$this->update_domain_data['expirydate'] = $expiry_date_result;
		}

		// Sync the next due date
		$next_due_date_result = General::compare_dates($this->domain->nextduedate, $this->op_domain['renewalDate'], '0', 'Y-m-d H:i:s');

		if($next_due_date_result != 'correct')
		{
			$this->update_domain_data['nextduedate'] 		= $next_due_date_result;
			$this->update_domain_data['nextinvoicedate'] 	= $next_due_date_result;
		}
	}

	/**
	 * Process the Domain status setting
	 *
	 * @param  string $status Set the domain status
	 * @return void
	 **/
	private function process_domain_status($status = null)
	{
		if($status == 'Expired' && $this->domain->status != $status)
		{
			$this->update_domain_data['status'] = $status;
			return;
		}

		$op_status_converter = [
			'ACT' => 'Active',		// ACT	The domain name is active
			'DEL' => 'Expired',		// DEL	The domain name has been deleted, but may still be restored.
			/* Leave FAI out of the array as we need to make the if statement fail in order to log an error. 'FAI' => 'error',	// FAI	The domain name request has failed.*/
			'PEN' => 'Pending',		// PEN	The domain name request is pending further information before the process can continue.
			'REQ' => 'Pending',		// REQ	The domain name request has been placed, but not yet finished.
			'RRQ' => 'Pending',		// RRQ	The domain name restore has been requested, but not yet completed.
			'SCH' => 'Pending',		// SCH	The domain name is scheduled for transfer in the future.
		];

		if(isset($op_status_converter[ $this->op_domain['status'] ]))
		{
			$op_domain_status = $op_status_converter[ $this->op_domain['status'] ];

			// Check if the status matches
			if($this->domain->status != $op_domain_status)
			{
				// It does not, let's update the data.
				$this->update_domain_data['status'] = $op_domain_status;
			}
		}
		else
		{
			logModuleCall('openprovider', 'update_domain_status', '', $this->op_domain, 'OpenProvider domain status: '.$this->op_domain['status'], null);
		}
	}

	/**
	 * Process the Domain autorenew setting
	 *
	 * @return void
	 **/
	private function process_auto_renew()
	{
		$this->OpenProvider->toggle_autorenew($this->domain, $this->op_domain);
	}
	


} // END class DomainSync