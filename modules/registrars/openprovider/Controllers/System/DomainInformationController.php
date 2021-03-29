<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\ApiInterface;
use OpenProvider\API\ApiHelper;
use WHMCS\Carbon;
use WHMCS\Domain\Registrar\Domain;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\Domain as api_domain;

/**
 * Class DomainInformationController
 */
class DomainInformationController extends BaseController
{
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
     * ConfigController constructor.
     */
    public function __construct(Core $core, api_domain $api_domain, ApiInterface $apiClient, ApiHelper $apiHelper)
    {
        parent::__construct($core);

        $this->apiClient  = $apiClient;
        $this->apiHelper = $apiHelper;
        $this->api_domain = $api_domain;
    }

    /**
     * Get the nameservers.
     *
     * @param $params
     * @return array
     */
    function get($params)
    {
        $params['sld'] = $params['original']['domainObj']->getSecondLevel();
        $params['tld'] = $params['original']['domainObj']->getTopLevel();

        $domain = $this->api_domain;
        try {
            $domain->load(array (
                'name' => $params['sld'],
                'extension' => $params['tld']
            ));
        } catch (\Exception $e) {
            return array
            (
                'error' => $e->getMessage(),
            );
        }

        // Get the data
        $op_domain = $this->apiHelper->getDomain($domain);

        if (!$op_domain) {
            return (new Domain)
                ->setDomain($domain);
        }

        $response                           = [];
        $response['domain']                 = $op_domain['domain']['name'] . '.' . $op_domain['domain']['extension'];
        $response['tld']                    = $op_domain['domain']['extension'];
        $response['nameservers']            = $this->getNameservers($op_domain['nameServers']);
        $response['status']                 = api_domain::convertOpStatusToWhmcs($op_domain['status']);
        $response['transferlock']           = $op_domain['isLocked'];
        $response['expirydate']             = $op_domain['expirationDate'];
        $response['addons']['hasidprotect'] = $op_domain['isPrivateWhoisEnabled'];

        // getting verification data
        $ownerEmail = '';
        $args = [
            'email'  => $op_domain['verificationEmailName'] ?? '',
            'domain' => $response['domain']
        ];
        $emailVerification = $this->apiClient->call('searchEmailVerificationDomainRequest', $args)->getData()['results'][0] ?? false;

        $verification = [];
        if (!$emailVerification) {
            $args['email'] = $ownerEmail;
            $reply = $this->apiClient->call('startCustomerEmailVerificationRequest', $args)->getData();
            if (isset($reply['id'])) {
                $verification['status']         = 'in progress';
                $verification['isSuspended']    = false;
                $verification['expirationDate'] = false;
            }
        }

        $verification = $this->getIrtpVerificationEmailOptions($verification);

        $result = (new Domain)
            // domain part
            ->setDomain($domain)
            ->setNameservers($response['nameservers'])
            ->setRegistrationStatus($response['status'])
            ->setTransferLock($response['transferlock'])
            ->setExpiryDate(Carbon::createFromFormat('Y-m-d H:i:s', $response['expirydate']), 'Europe/Amsterdam') // $response['expirydate'] = YYYY-MM-DD
            ->setIdProtectionStatus($response['addons']['hasidprotect'])
            // irtp part
            ->setIsIrtpEnabled($verification['is_irtp_enabled'])
            ->setIrtpOptOutStatus($verification['irtp_opt_status'])
            ->setIrtpTransferLock($verification['irtp_transfer_lock'])
            ->setDomainContactChangePending($verification['domain_contact_change_pending'])
            ->setPendingSuspension($verification['pending_suspension'])
            ->setIrtpVerificationTriggerFields($verification['irtp_verification_trigger_fields']);

        if ($verification['domain_contact_change_expiry_date'])
            $result->setDomainContactChangeExpiryDate(Carbon::createFromFormat('Y-m-d H:i:s', $verification['domain_contact_change_expiry_date']));

        return $result;
    }

    /**
     * @param array $nameservers
     * @return array
     */
    private function getNameservers(array $nameservers): array
    {
        $return = array ();
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
            } catch (\Exception $e) {}
        }

        return $result;
    }
}
