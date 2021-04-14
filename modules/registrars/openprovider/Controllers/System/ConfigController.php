<?php
namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use OpenProvider\API\API;
use OpenProvider\API\APIConfig;
use OpenProvider\API\ApiInterface;
use OpenProvider\API\ResponseInterface;
use OpenProvider\WhmcsRegistrar\enums\OpenproviderErrorType;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use Symfony\Component\HttpFoundation\Session\Session;
use WeDevelopCoffee\wPower\Controllers\BaseController;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Models\Registrar;
use WHMCS\Database\Capsule;

/**
 * Class ConfigController
 */
class ConfigController extends BaseController
{
    const ERROR_INCORRECT_CREDENTIALS = 'Please ensure credentials are correct';
    const ERROR_INCORRECT_INVIRONMENT = 'The credentials do not appear to match the environment you have selected. You have provided live credentials and selected test mode, or vice-versa';
    const ERROR_NOT_SIGNED_AGREEMENT  = 'Account is blocked because of non-signed agreement. Log in to the control panel for more information.';
    const ERROR_NOT_HAVE_AUTHORITY    = 'This ip address does not have authority to make API calls with this account. Please check the IP whitelist and blacklist of your account.';

    /**
     * @var API
     */
    private $API;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var ApiInterface
     */
    private $apiClient;

    /**
     * ConfigController constructor.
     */
    public function __construct(Core $core, API $API, Session $session, ApiInterface $apiClient)
    {
        parent::__construct($core);

        $this->API = $API;
        $this->apiClient = $apiClient;
        $this->session = $session;
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

        $oldParams = (new Registrar())->getRegistrarData()['openprovider'];

        if (
            $params['Password'] != $oldParams['Password'] ||
            $params['Username'] != $oldParams['Username'] ||
            $params['test_mode'] != $oldParams['test_mode']
        ) {
            $this->session->remove('AUTH_TOKEN');
        }
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
    protected function generateLoginError(array $configarray, $error)
    {
        $loginFailed = [
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
        $differentHost = $params['test_mode'] == 'on' ?
            Configuration::get('api_url') :
            Configuration::get('api_url_cte');

        if ($this->session->has('AUTH_TOKEN')) {
            $checkingTokenRequest = $this->checkRequest();
            if ($checkingTokenRequest->isSuccess()) {
                return $configarray;
            } else if (
                $checkingTokenRequest->getCode() == OpenproviderErrorType::ResellerNotHaveAuthority ||
                $checkingTokenRequest->getCode() == OpenproviderErrorType::ResellerNotHaveAuthorityCTE
            ) {
                return $this->generateLoginError($configarray, self::ERROR_NOT_HAVE_AUTHORITY);
            } else if ($checkingTokenRequest->getCode() == OpenproviderErrorType::NonSignedAgreement) {
                return $this->generateLoginError($configarray, self::ERROR_NOT_SIGNED_AGREEMENT);
            }
        }

        // if token doesn't exist we try to get it from openprovider
        $this->apiClient->getConfiguration()->setHost($differentHost);

        $reply = $this->apiClient->call('generateAuthTokenRequest', [
            'username' => $params['Username'],
            'password' => $params['Password']
        ]);

        $replyData = $reply->getData();

        if (isset($replyData['token']) && $replyData['token']) {
            return $this->generateLoginError($configarray, self::ERROR_INCORRECT_INVIRONMENT);
        }

        return $this->generateLoginError($configarray, self::ERROR_INCORRECT_CREDENTIALS);
    }

    /**
     * @return ResponseInterface
     */
    private function checkRequest(): ResponseInterface
    {
        return $this->apiClient->call('retrieveExtensionRequest', ['name' => 'com']);
    }
}
