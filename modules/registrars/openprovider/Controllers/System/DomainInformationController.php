<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\ApiInterface;
use OpenProvider\API\ApiHelper;
use OpenProvider\WhmcsRegistrar\helpers\ArrayFromFileExtractor;
use WeDevelopCoffee\wPower\Core\Path;
use WHMCS\Carbon;
use WHMCS\Domain\Registrar\Domain;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\Domain as api_domain;
use OpenProvider\WhmcsRegistrar\helpers\Cache;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use WHMCS\Authentication\CurrentUser;
use OpenProvider\WhmcsHelpers\Domain as WHMCS_domain;
use OpenProvider\WhmcsRegistrar\helpers\ApiResponse;
use OpenProvider\WhmcsRegistrar\enums\WHMCSApiActionType;
/**
 * Class DomainInformationController
 */
class DomainInformationController extends BaseController
{
    const CC_TLD_LENGTH = 2;

    /**
     * @var api_domain
     */
    private $api_domain;
    /**
     * @var ApiInterface
     */
    private $apiClient;
    /**
     * @var ApiHelper
     */
    private $apiHelper;
    /**
     * @var Path
     */
    private $path;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, api_domain $api_domain, ApiInterface $apiClient, ApiHelper $apiHelper, Path $path)
    {
        parent::__construct($core);

        $this->apiClient  = $apiClient;
        $this->apiHelper = $apiHelper;
        $this->api_domain = $api_domain;
        $this->path = $path;
    }

    /**
     * Get the nameservers.
     *
     * @param $params
     *
     * @return array
     *
     * @throws \Exception
     */
    function get($params)
    {
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();

        $domain = $this->api_domain;
        try {
            $domain->load(array(
                'name' => $params['sld'],
                'extension' => $params['tld']
            ));
        } catch (\Exception $e) {
            return array(
                'error' => $e->getMessage(),
            );
        }

        // Get the data
        try {
            $op_domain = $this->apiHelper->getDomain($domain);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }

        if (!$op_domain) {
            return (new Domain)
                ->setDomain($domain);
        }

        $response                           = [];
        $response['domain']                 = $op_domain['domain']['name'] . '.' . $op_domain['domain']['extension'];
        $response['tld']                    = $op_domain['domain']['extension'];
        $response['nameservers']            = $this->getNameservers($op_domain['nameServers'] ?: []);
        $response['status']                 = api_domain::convertOpStatusToWhmcs($op_domain['status']);
        $response['transferlock']           = $op_domain['isLocked'];        
        $response['addons']['hasidprotect'] = $op_domain['isPrivateWhoisEnabled'];

        if(Configuration::getOrDefault('renewalDateSync', true)) {
            $response['expirydate']         = $op_domain['renewalDate'];
        }else{
            $response['expirydate']         = $op_domain['expirationDate'];
        }

        if (!Cache::has($op_domain['domain']['extension'])) {
            if (($op_domain['isLockable'] ?? false)) {
                Cache::set($op_domain['domain']['extension'], true);
            } else {
                Cache::set($op_domain['domain']['extension'], false);
            }
        }

        $isCcTld = $this->isCcTld($response['tld']);

        $result = (new Domain)
            // domain part
            ->setDomain($domain)
            ->setNameservers($response['nameservers'])
            ->setRegistrationStatus($response['status'])
            ->setTransferLock($response['transferlock'])
            ->setExpiryDate(Carbon::createFromFormat('Y-m-d H:i:s', $response['expirydate']), 'Europe/Amsterdam') // $response['expirydate'] = YYYY-MM-DD
            ->setIdProtectionStatus($response['addons']['hasidprotect']);

        if ($isCcTld) {
            return $result;
        }

        // getting verification data
        $args = [
            'email'  => $op_domain['verificationEmailName'] ?? '',
            'domain' => $response['domain']
        ];

        $emailVerification = $this->apiClient->call('searchEmailVerificationDomainRequest', $args)->getData()['results'][0] ?? false;
        $verification = [];
        if (!$emailVerification) {
            $reply = $this->apiClient->call('startCustomerEmailVerificationRequest', $args)->getData();
            if (isset($reply['id'])) {
                $verification['status']         = 'in progress';
                $verification['isSuspended']    = false;
                $verification['expirationDate'] = false;
            }
        } else {
            $verification = $emailVerification;
        }

        $verification = $this->getIrtpVerificationEmailOptions($verification);

        $result->setIsIrtpEnabled($verification['is_irtp_enabled'])
            ->setIrtpOptOutStatus($verification['irtp_opt_status'])
            ->setIrtpTransferLock($verification['irtp_transfer_lock'])
            ->setDomainContactChangePending($verification['domain_contact_change_pending'])
            ->setPendingSuspension($verification['pending_suspension'])
            ->setIrtpVerificationTriggerFields($verification['irtp_verification_trigger_fields']);

        if ($verification['domain_contact_change_expiry_date']) {
            $result->setDomainContactChangeExpiryDate(Carbon::createFromFormat('Y-m-d H:i:s', $verification['domain_contact_change_expiry_date']));
        }

        return $result;
    }

    /**
     * @param array $nameservers
     * @return array
     */
    private function getNameservers(array $nameservers): array
    {
        $return = array();
        $i = 1;

        foreach ($nameservers as $ns) {
            $return['ns' . $i] = $ns['name'] ?? $ns['ip'];
            $i++;
        }

        return $return;
    }

    /**
     * Function return array of parameters for irtp domain part to setup email verification
     *
     * @param array $verification data from result of searchEmailVerificationRequest
     * @return array
     * @see https://developers.whmcs.com/domain-registrars/transfer-policy-management/
     */
    private function getIrtpVerificationEmailOptions($verification): array
    {
        $allowedStatusesForPending = ['in progress', 'failed', 'not verified'];

        $result = [
            'is_irtp_enabled'                   => true,
            'irtp_opt_status'                   => true,
            'irtp_transfer_lock'                => false,
            'domain_contact_change_pending'     => false,
            'pending_suspension'                => false,
            'domain_contact_change_expiry_date' => false,
            'irtp_verification_trigger_fields'  => [
                'Registrant' => [
                    'First Name',
                    'Last Name',
                    'Organization Name',
                    'Email Address',
                ],
            ],
        ];

        if ($verification) {
            $result['domain_contact_change_pending']     = in_array($verification['status'], $allowedStatusesForPending);
            $result['pending_suspension']                = !!$verification['isSuspended'];
            try {
                $result['domain_contact_change_expiry_date'] = (
                    isset($verification['expirationDate']) && !empty($verification['expirationDate'])
                    ? Carbon::createFromFormat('Y-m-d H:i:s', $verification['expirationDate'])
                    : false
                );
                if ($result['domain_contact_change_expiry_date'] && $result['domain_contact_change_expiry_date']->year < 1)
                    $result['domain_contact_change_expiry_date'] = false;
            } catch (\Exception $e) {
            }
        }

        return $result;
    }

    private function isCcTld($tld): bool
    {
        $tldArray = explode('.', $tld);
        $lastTld = end($tldArray);

        return strlen($lastTld) == self::CC_TLD_LENGTH || in_array($lastTld, $this->getPunyCodesCcTlds());
    }

    /**
     * @return array
     */
    private function getPunyCodesCcTlds(): array
    {
        $modulePath = $this->path->getModulePath() ?? __DIR__ . '/../../';
        $arrayFromFileExtractor = new ArrayFromFileExtractor($modulePath);
        return $arrayFromFileExtractor->extract(ArrayFromFileExtractor::PUNY_CODE_CC_TLDS_PATH);
    }

    public function importDomain($params)
    {
        try{
            $currentUser        = new CurrentUser();
            $authUser           = $currentUser->admin();
            if (!$currentUser->isAuthenticatedAdmin()) {
                return ApiResponse::error(400, 'You are have no authority to make this request.');
            }

            $userId             = $authUser->id;
            $clientId           = $params["clientId"];
            $paymentMethod      = $params["paymentMethod"];
            $registrar          = $params["registrar"];
            $domainListStr      = $params["domainList"]; //String of domains separate by new lines
            
            $domainList         = explode("\n", $domainListStr);
            $validationResult   = $this->validateDomains($domainList);
            $domains            = $validationResult["valid"];
            $existingDomains    = $validationResult["existing"];     
            

            if(empty($domains) && !empty($existingDomains)){
                return ApiResponse::error(400, 'No valid domains found. The following domains already exist in WHMCS: '.implode(", ", $existingDomains));
            }

            if(empty($domains)){
                return ApiResponse::error(400, 'No valid domains found');
            }

            if(empty($clientId)){
                return ApiResponse::error(400, 'No client found');
            }

            if(empty($paymentMethod)){
                return ApiResponse::error(400, 'No payment method found');
            }            

            
            $orderCreateResult     = $this->createOrder($clientId, $paymentMethod,$domains);

            if($orderCreateResult["result"] == "success"){
                $orderId           = $orderCreateResult["orderid"];
                $orderAcceptResult = $this->acceptOrder($orderId, $registrar);
                if($orderAcceptResult["result"] != "success"){
                    logModuleCall('openprovider', 'bulk import', 'Order accept failed.', $orderAcceptResult, null,null);
                    return ApiResponse::error(400, 'Order accept failed. Please manually accept the order. Order ID: '.$orderId);
                }
            }else{
                logModuleCall('openprovider', 'bulk import', 'Order creation failed.', $orderCreateResult, null,null);
                return ApiResponse::error(400, 'Order creation failed');
            }

            if(!empty($existingDomains)){
                return ApiResponse::success(['message' => 'Domains imported successfully. The following domains already exist in WHMCS: '.implode(", ", $existingDomains)]);
            }

            return ApiResponse::success(['message' => 'Domains imported successfully']);
        }catch(\Exception $e){
            logModuleCall('openprovider', 'bulk import', 'Domain import failed.', $e->getMessage(), null,null);
            return ApiResponse::error(400, 'Domain import failed. Please check the module logs for more details');
        }
    }

    // Create Order by WHMCS Internal API
    private function createOrder($clientId, $paymentMethod, $domains): array
    {
        $domainTypes= array_map(function($domain){
            return "transfer";
        }, $domains);

        $command    = WHMCSApiActionType::AddOrder;
        $postData   = array(
            'clientid'      => $clientId,
            'paymentmethod' => $paymentMethod,
            'domain'        => $domains,
            'domaintype'    => $domainTypes,
            'noinvoice'     => 1,
            'noinvoiceemail'=> 1,
            'noemail'       => 1,
        );

        $results    = localAPI($command, $postData);
        return $results;
    }

    // Accept Order by WHMCS Internal API
    private function acceptOrder($orderId, $registrar): array
    {
        $command    = WHMCSApiActionType::AcceptOrder;
        $postData   = array(
            'orderid' => $orderId,
        );
        if(!empty($registrar)){
            $postData['registrar'] = $registrar;
        }
        $results    = localAPI($command, $postData);
        return $results;
    }

    // Validate an array of domain names.
    private function validateDomains($domains) 
    {
        // Regex to match valid domain names (standard domain structure)
        $domainPattern = '/^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$/';
        
        $validDomains = [];
        $invalidDomains = [];
        $existingDomains = [];

        foreach ($domains as $domain) {
            // Trim any surrounding whitespace
            $domain = trim($domain);
            
            // Check if the domain matches the regex pattern
            if (preg_match($domainPattern, $domain) && !empty($domain)) {
                //try to get WHMCS ID
                $whmcsId = WHMCS_domain::getDomainId($domain);
                if($whmcsId == null){
                    $validDomains[] = $domain;
                }else{
                    $existingDomains[] = $domain;
                }
            } else {
                $invalidDomains[] = $domain;
            }
        }

        if(!empty($invalidDomains)){
            logModuleCall('openprovider', 'bulk import', 'Invalid domains found', $invalidDomains, null, null);
        }

        //Remove duplicates in valid domains
        $validDomains = array_unique($validDomains);

        return [
            "valid" => $validDomains,
            "existing" => $existingDomains
        ];
    }
}
