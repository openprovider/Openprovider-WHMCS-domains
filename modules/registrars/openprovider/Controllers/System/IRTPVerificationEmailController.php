<?php


namespace OpenProvider\WhmcsRegistrar\Controllers\System;


use OpenProvider\API\Domain;
use OpenProvider\OpenProvider;

use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;

class IRTPVerificationEmailController extends BaseController
{
    /**
     * @var OpenProvider
     */
    private $openProvider;
    /**
     * @var Domain
     */
    private $domain;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, Domain $domain)
    {
        parent::__construct($core);

        $this->openProvider = new OpenProvider();
        $this->domain = $domain;
    }

    /**
     * Resend verification email
     * @param $params
     * @return array
     */
    public function resend($params)
    {
        $success      = true;
        $errorMessage = '';
        $ownerEmail   = false;

        $api = $this->openProvider->getApi();

        // getting Email
        try {
            $domain = $this->openProvider->domain($params['domain']);
            $ownerInfo = $api->getDomainContactsRequest($domain);
            $ownerEmail = $ownerInfo['Owner']['Email Address'];
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return ['error' => $errorMessage];
        }

        if (!$ownerEmail) {
            return ['error' => 'No owner email'];
        }

        try {
            $api->restartCustomersVerificationsEmailsRequest($ownerEmail);
        } catch (\Exception $e) {
            $success = false;
            $errorMessage = $e->getMessage();
        }

        if ($success) {
            return ['success' => $success];
        }
        return ['error' => $errorMessage];
    }
}