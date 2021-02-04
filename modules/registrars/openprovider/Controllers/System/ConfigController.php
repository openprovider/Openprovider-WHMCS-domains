<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\APIConfig;
use OpenProvider\API\JsonAPI;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;

/**
 * Class ConfigController
 */
class ConfigController extends BaseController
{
    /**
     * @var API
     */
    private $API;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, JsonAPI $API)
    {
        parent::__construct($core);

        $this->API = $API;
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
        if($areCredentialsExist)
        {
            $configarray = $this->checkCredentials($configarray, $params);
        }
        return $configarray;
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
        $x = explode(DIRECTORY_SEPARATOR, $_SERVER['SCRIPT_FILENAME']);
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

        return array ($configarray, $params);
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
                "FriendlyName"  => "Module Version",
                "Type"          => "text",
                "Description"   => APIConfig::getModuleVersion() . "<style>input[name='version']{display: none;}</style>",
            ),
            "Username"          => array
            (
                "FriendlyName"  => "Username",
                "Type"          => "text",
                "Size"          => "20",
                "Description"   => "Openprovider login",
            ),
            "Password"          => array
            (
                "FriendlyName"  => "Password",
                "Type"          => "password",
                "Size"          => "20",
                "Description"   => "Openprovider password",
            ),
            "test_mode"   => array
            (
                "FriendlyName"  => "Enable Openprovider Test mode",
                "Type"          => "yesno",
                "Description"   => "Choose this option if you are using CTE credentials and want to connect to the test API.",
                "Default"       => "no"
            ),
        );
    }

    /**
     * Generate a login error message.
     *
     * @param array $configarray
     * @param bool $wrongMode
     * @return mixed
     */
    protected function generateLoginError(array $configarray, $wrongMode = false)
    {
        $loginFailed = [
            'FriendlyName' => '<b><strong style="color:Tomato;">Login Unsuccessful:</strong></b>',
        ];
        if ($wrongMode) {
            $loginFailed['Description'] = '<b><strong style="color:#ff6347;">Please ensure environment is correct</strong></b>';
            $configarray['test_mode']['FriendlyName'] = '<b><strong style="color:Tomato;">*Openprovider Test mode</strong></b>';
        } else {
            $loginFailed['Description'] = '<b><strong style="color:Tomato;">Please ensure credentials are correct</strong></b>';
            $configarray['Username']['FriendlyName'] = '<b><strong style="color:Tomato;">*Username</strong></b>';
            $configarray['Password']['FriendlyName'] = '<b><strong style="color:Tomato;">*Password</strong></b>';
        }

        // Create a separate array to put the warning at the top as well.
        $firstArray[] = $loginFailed;

        //warn user that login failed at the end.
//        $configarray['loginFailed'] = $loginFailed;

        return array_merge($firstArray, $configarray);
    }

    protected function checkCredentials($configarray, $params)
    {
        try {
            $this->API->setParams($params);
            // Try to login and fetch the DNS template data.
            if ($this->API->checkCredentials())
                return $configarray;
        } catch (\Exception $ex) {}

        // Failed to login. Generate a warning.
        $isTestMode = $params['test_mode'] == 'on';

        if ($isTestMode) {
            $params['test_mode'] = '';
            if ($this->_checkCredentials($params))
                // Incorrect mode
                $configarray = $this->generateLoginError($configarray, true);
            else
                $configarray = $this->generateLoginError($configarray);
        } else {
            $params['test_mode'] = 'on';
            if ($this->_checkCredentials($params))
                // Incorrect mode
                $configarray = $this->generateLoginError($configarray, true);
            else
                $configarray = $this->generateLoginError($configarray);
        }

        return $configarray;
    }

    private function _checkCredentials($params)
    {
        try {
            $this->API->setParams($params);

            // Incorrect mode
            if ($this->API->checkCredentials())
                return true;
            return false;
        } catch (\Exception $e) {
            // Incorrect credentials
            return false;
        }
    }
}