<?php


namespace OpenProvider\WhmcsRegistrar\Controllers\System;


use OpenProvider\API\API;
use OpenProvider\API\Domain;
use OpenProvider\API\JsonAPI;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;

class IRTPVerificationEmailController extends BaseController
{
    /**
     * @var API
     */
    private $API;
    /**
     * @var Domain
     */
    private $domain;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, JsonAPI $API, Domain $domain)
    {
        parent::__construct($core);

        $this->API = $API;
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

        $api = $this->API;
        $api->setParams($params);

        // getting Email
        try {
            $domain = new Domain();
            $domain->name = $params['domainObj']->getSecondLevel();
            $domain->extension = $params['domainObj']->getTopLevel();
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