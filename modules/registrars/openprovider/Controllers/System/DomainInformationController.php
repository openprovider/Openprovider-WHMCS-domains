<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\JsonAPI;
use WHMCS\Carbon;
use WHMCS\Domain\Registrar\Domain;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use OpenProvider\API\API;
use OpenProvider\API\Domain as api_domain;

/**
 * Class DomainInformationController
 */
class DomainInformationController extends BaseController
{
    /**
     * @var JsonAPI
     */
    private $API;
    /**
     * @var Domain
     */
    private $api_domain;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, JsonAPI $jsonAPI, api_domain $api_domain)
    {
        parent::__construct($core);

        $this->API = $jsonAPI;
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

        // Launch API
        $api    = $this->API;
        $domain = $this->api_domain;

        $api->setParams($params);

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
        $args = [
            'name'                    => $params['sld'],
            'extension'               => $params['tld'],
            'with_verification_email' => 'true',
        ];

        // Get the data
        $op_domain = $api->getDomainRequest($args);

        $response                           = [];
        $response['domain']                 = $op_domain['domain']['name'] . '.' . $op_domain['domain']['extension'];
        $response['tld']                    = $op_domain['domain']['extension'];
        $response['nameservers']            = $this->getNameservers($op_domain['name_servers']);
        $response['status']                 = api_domain::convertOpStatusToWhmcs($op_domain['status']);
        $response['transferlock']           = ($op_domain['is_locked']);
        $response['expirydate']             = $op_domain['expiration_date'];
        $response['addons']['hasidprotect'] = ($op_domain['is_private_whois_enabled']);

        // check email verification status and choose options depend on it
        $verification = [
            'status' => $op_domain['verification_email_status'],
            'expiration_date'  => $op_domain['verification_email_exp_date'],
            'email'  => $op_domain['verification_email_name'],
            'handle' => $op_domain['owner_handle'],
        ];
        if ($verification['status'] == 'not verified') {
            try {
                $reply = $api->startCustomersVerificationsEmailsRequest($verification['email'], $verification['handle']);
                if (isset($reply['id'])) {
                    $verification['status']         = 'in progress';
                    $verification['is_suspended']    = false;
                    $verification['expiration_date'] = false;
                }

            } catch (\Exception $e) {}
        }

        $verificationResponse = $this->getIrtpVerificationEmailOptions($verification);

        $result = (new Domain)
            // domain part
            ->setDomain($domain)
            ->setNameservers($response['nameservers'])
            ->setRegistrationStatus($response['status'])
            ->setTransferLock($response['transferlock'])
            ->setExpiryDate(Carbon::createFromFormat('Y-m-d H:i:s', $response['expirydate']), 'Europe/Amsterdam') // $response['expirydate'] = YYYY-MM-DD
            ->setIdProtectionStatus($response['addons']['hasidprotect'])
            // irtp part
            ->setIsIrtpEnabled($verificationResponse['is_irtp_enabled'])
            ->setIrtpOptOutStatus($verificationResponse['irtp_opt_status'])
            ->setIrtpTransferLock($verificationResponse['irtp_transfer_lock'])
            ->setDomainContactChangePending($verificationResponse['domain_contact_change_pending'])
            ->setPendingSuspension($verificationResponse['pending_suspension'])
            ->setIrtpVerificationTriggerFields($verificationResponse['irtp_verification_trigger_fields']);

        if ($verificationResponse['domain_contact_change_expiry_date'])
            $result->setDomainContactChangeExpiryDate(Carbon::createFromFormat('Y-m-d H:i:s', $verificationResponse['domain_contact_change_expiry_date']));

        return $result;
    }

    /**
     * @param API $api
     * @param Domain $domain
     * @return array
     */
    private function getNameservers($nameservers): array
    {
        $i = 1;

        $return = [];
        foreach ($nameservers as $ns) {
            $return['ns' . $i] = $ns['name'];
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
            $result['pending_suspension']                = !!$verification['is_suspended'];
            try {
                $result['domain_contact_change_expiry_date'] = (
                isset($verification['expiration_date']) && !empty($verification['expiration_date'])
                    ? Carbon::createFromFormat('Y-m-d H:i:s', $verification['expiration_date'])
                    : false
                );
                if ($result['domain_contact_change_expiry_date'] && $result['domain_contact_change_expiry_date']->year < 1)
                    $result['domain_contact_change_expiry_date'] = false;
            } catch (\Exception $e) {}
        }

        return $result;
    }
}