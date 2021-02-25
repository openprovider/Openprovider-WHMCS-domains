<?php


namespace OpenProvider\WhmcsRegistrar\Controllers\System;


use OpenProvider\OpenProvider;
use OpenProvider\API\Domain;
use Punic\Exception;
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
    public function __construct(Core $core, OpenProvider $openProvider, Domain $domain)
    {
        parent::__construct($core);

        $this->openProvider = $openProvider;
        $this->domain       = $domain;
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

        $api = $this->openProvider->api;

        // getting Email
        try {
            $domain            = new Domain();
            $domain->name      = $params['domainObj']->getSecondLevel();
            $domain->extension = $params['domainObj']->getTopLevel();
            $ownerInfo         = $api->getContactDetails($domain);
            $ownerEmail        = $ownerInfo['Owner']['Email Address'];
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

        try {
            $api->sendRequest('restartCustomerEmailVerificationRequest', $args);
        } catch (\Exception $e) {
            $success      = false;
            $errorMessage = $e->getMessage();
        }

        if ($success) {
            return ['success' => $success];
        }
        return ['error' => $errorMessage];
    }
}