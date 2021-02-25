<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\OpenProvider;
use OpenProvider\API\APIConfig;
use OpenProvider\WhmcsRegistrar\enums\OpenproviderErrorType;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;

/**
 * Class ConfigController
 */
class ConfigController extends BaseController
{
    const ERROR_INCORRECT_CREDENTIALS = 'Please ensure credentials are correct';
    const ERROR_INCORRECT_INVIRONMENT = 'Please ensure environment is correct';
    const ERROR_NOT_SIGNED_AGREEMENT  = 'Account is blocked because of non-signed agreement. Log in to the control panel for more information.';
    const ERROR_NOT_HAVE_AUTHORITY    = 'This ip address does not have authority to make API calls with this account. Please check the IP whitelist and blacklist of your account.';

    /**
     * @var API
     */
    private $openProvider;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, OpenProvider $openProvider)
    {
        parent::__construct($core);

        $this->openprovider = $openProvider;
    }

    /**
     * Generate the configuration array.
     * @param $params
     * @return array|mixed
     */
    public function getConfig($params)
    {
        // Get the basic data.
        $configarray = $this->getConfigArray();

        // Process any updated data.
        list($configarray, $params) = $this->parsePostInput($params, $configarray);

        // If we have some login data, let's try to login.
        $areCredentialsExist = isset($params['Password']) && isset($params['Username'])
            && (!empty($params['Password']) || !empty($params['Username']));
        if ($areCredentialsExist) {
            $configarray = $this->checkCredentials($configarray, $params);
        }
        return $configarray;
    }

    /**
     * The configuration array base.
     *
     * @return array
     */
    public function getConfigArray()
    {
        return array
        (
            "version"   => array
            (
                "FriendlyName" => "Module Version",
                "Type"         => "text",
                "Description"  => APIConfig::getModuleVersion() . "<style>input[name='version']{display: none;}</style>",
            ),
            "Username"  => array
            (
                "FriendlyName" => "Username",
                "Type"         => "text",
                "Size"         => "20",
                "Description"  => "Openprovider login",
            ),
            "Password"  => array
            (
                "FriendlyName" => "Password",
                "Type"         => "password",
                "Size"         => "20",
                "Description"  => "Openprovider password",
            ),
            "test_mode" => array
            (
                "FriendlyName" => "Enable Openprovider Test mode",
                "Type"         => "yesno",
                "Description"  => "Choose this option if you are using CTE credentials and want to connect to the test API.",
                "Default"      => "no"
            ),
        );
    }

    /**
     * Process the latest post information as WHMCS does not provide the latest information by default.
     *
     * @param $params
     * @param array $configarray
     * @return array
     */
    protected function parsePostInput($params, array $configarray)
    {
        $x        = explode(DIRECTORY_SEPARATOR, $_SERVER['SCRIPT_FILENAME']);
        $filename = end($x);
        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'save' && $filename == 'configregistrars.php') {
            foreach ($_REQUEST as $key => $val) {
                if (isset($configarray[$key])) {
                    // Prevent that we will overwrite the actual password with the stars.
                    if (substr($val, 0, 3) != '***') {
                        $params[$key] = $val;
                    }
                }
            }
        }

        return array($configarray, $params);
    }

    /**
     * Generate a login error message.
     *
     * @param array $configarray
     * @param bool $wrongMode
     * @return mixed
     */
    protected function generateLoginError(array $configarray, $error)
    {
        $loginFailed                = [
            'FriendlyName' => '<b><strong style="color:Tomato;">Login Unsuccessful:</strong></b>',
        ];
        $loginFailed['Description'] = "<b><strong style='color:#ff6347;'>$error</strong></b>";
        switch ($error) {
            case self::ERROR_INCORRECT_CREDENTIALS:
            case self::ERROR_NOT_SIGNED_AGREEMENT:
            case self::ERROR_NOT_HAVE_AUTHORITY:
                $configarray['Username']['FriendlyName'] = '<b><strong style="color:Tomato;">*Username</strong></b>';
                $configarray['Password']['FriendlyName'] = '<b><strong style="color:Tomato;">*Password</strong></b>';
                break;
            case self::ERROR_INCORRECT_INVIRONMENT:
                $configarray['test_mode']['FriendlyName'] = '<b><strong style="color:Tomato;">*Openprovider Test mode</strong></b>';
                break;
        }
        // Create a separate array to put the warning at the top as well.
        $firstArray[] = $loginFailed;

        //warn user that login failed at the end.
//        $configarray['loginFailed'] = $loginFailed;

        return array_merge($firstArray, $configarray);
    }

    protected function checkCredentials($configarray, $params)
    {
        $api = $this->openprovider->api;
        try {
            // Try to login and fetch the DNS template data.
            $uselessApiCall = $api->sendRequest('retrieveUpdateMessageRequest');
            return $configarray;
        } catch (\Exception $ex) {
            if (
                $ex->getCode() == OpenproviderErrorType::ResellerNotHaveAuthority
                || $ex->getCode() == OpenproviderErrorType::ResellerNotHaveAuthorityCTE
            ) {
                return $this->generateLoginError($configarray, self::ERROR_NOT_HAVE_AUTHORITY);
            } elseif ($ex->getCode() == OpenproviderErrorType::NonSignedAgreement) {
                return $this->generateLoginError($configarray, self::ERROR_NOT_SIGNED_AGREEMENT);
            }
        }

        $params['test_mode'] = $params['test_mode'] == 'on'
            ? ''
            : 'on';

        $api->setParams($params);
        try {
            $uselessApiCall = $api->sendRequest('retrieveUpdateMessageRequest');
            // Incorrect mode
            $configarray = $this->generateLoginError($configarray, self::ERROR_INCORRECT_INVIRONMENT);
        } catch (\Exception $e) {
            if (
                $ex->getCode() == OpenproviderErrorType::ResellerNotHaveAuthority
                || $ex->getCode() == OpenproviderErrorType::ResellerNotHaveAuthorityCTE
                || $ex->getCode() == OpenproviderErrorType::NonSignedAgreement
            ) {
                $configarray = $this->generateLoginError($configarray, self::ERROR_INCORRECT_INVIRONMENT);
            } else {
                $configarray = $this->generateLoginError($configarray, self::ERROR_INCORRECT_CREDENTIALS);
            }
        }

        return $configarray;
    }
}