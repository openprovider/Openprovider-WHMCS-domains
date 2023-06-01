<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\ApiHelper;
use OpenProvider\API\ApiInterface;
use OpenProvider\API\Domain;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;

class IRTPVerificationEmailController extends BaseController
{
    /**
     * @var ApiHelper
     */
    private $apiHelper;
    /**
     * @var ApiInterface
     */
    private $apiClient;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, ApiHelper $apiHelper, ApiInterface $apiClient)
    {
        parent::__construct($core);

        $this->apiHelper = $apiHelper;
        $this->apiClient = $apiClient;
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

        // getting Email
        try {
            $domain = new Domain();
            $domain->name = $params['domainObj']->getSecondLevel();
            $domain->extension = $params['domainObj']->getTopLevel();
            $ownerInfo = $this->apiHelper->getDomainContacts($domain);
            $ownerEmail = $ownerInfo['Owner']['Email Address'];
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            return ['error' => $errorMessage];
        }

        if (!$ownerEmail) {
            return ['error' => 'No owner email'];
        }

        $args = [
            'email' => $ownerEmail,
        ];

        $restartCustomerEmailVerificationResponse = $this->apiClient->call('restartCustomerEmailVerificationRequest', $args);
        if (!$restartCustomerEmailVerificationResponse->isSuccess()) {
            $success = false;
            $errorMessage = $restartCustomerEmailVerificationResponse->getMessage();
        }

        if ($success) {
            return ['success' => $success];
        }
        return ['error' => $errorMessage];
    }
}
