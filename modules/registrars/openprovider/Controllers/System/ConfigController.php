<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\System;

use Carbon\Carbon;
use OpenProvider\API\APIConfig;
use OpenProvider\API\ApiInterface;
use OpenProvider\API\ResponseInterface;
use OpenProvider\WhmcsRegistrar\enums\OpenproviderErrorType;
use OpenProvider\WhmcsRegistrar\helpers\Cache;
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
    public function __construct(Core $core, Session $session, ApiInterface $apiClient)
    {
        parent::__construct($core);

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
            Capsule::table('reseller_tokens')->where('username', $oldParams['Username'])->delete();
        }

        // If we have some login data, let's try to login.
        if ( isset($params['Password'])
            && isset($params['Username'])
            && !empty($params['Password'])
            && !empty($params['Username'])
            && isset($_GET['action'])
            && $_GET['action'] == 'save'
            && isset($_GET['module'])
            && $_GET['module'] == 'openprovider'
        ) {
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

        return array($configarray, $params);
    }

    /**
     * The configuration array base.
     *
     * @return array
     */
    public function getConfigArray()
    {
        $configs = [];

        $configs["version"] = [
            "FriendlyName"  => "Module Version",
            "Type"          => "text",
            "Description"   => APIConfig::getModuleVersion() . "<style>input[name='version']{display: none;}</style>",
        ];

        $configs["Username"] = [
            "FriendlyName"  => "Username",
            "Type"          => "text",
            "Size"          => "20",
            "Description"   => "Openprovider login",
        ];

        $configs["Password"] = [
            "FriendlyName"  => "Password",
            "Type"          => "password",
            "Size"          => "20",
            "Description"   => "Openprovider password",
        ];

        $configs["test_mode"] = [
            "FriendlyName"  => "Enable Openprovider Test mode",
            "Type"          => "yesno",
            "Description"   => "Choose this option if you are using Openprovider <a href='https://support.openprovider.eu/hc/en-us/articles/8095671860114-Sandbox-Testing-Environment' target='_blank'>Sandbox credentials</a> and want to test the module operations.",
            "Default"       => "no"
        ];

        return $configs;
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
            Configuration::get('api_url_cte') :
            Configuration::get('api_url');


        $tokenResult = null;

        if (Capsule::schema()->hasTable('reseller_tokens')) {
            $tokenResult = Capsule::table('reseller_tokens')->where('username', $params['Username'])->orderBy('created_at', 'desc')->first();
        }

        //Check expiration time
        $expireTime = $tokenResult ? new Carbon($tokenResult->expire_at) : false;
        $isAlive = $expireTime && Carbon::now()->diffInSeconds($expireTime, false) > 0;

        if ($isAlive) {
            //Check token is still valid for RCP API server
            $checkingTokenRequest = $this->checkRequest();
            if ($checkingTokenRequest->isSuccess()) {
                Cache::delete('op_auth_generate');
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

        // if token doesn't valid (expired or changed) we try to get it from Openprovider
        $this->apiClient->getConfiguration()->setHost($differentHost);

        if (!Cache::has('op_auth_generate')) {

            $reply = $this->apiClient->call('generateAuthTokenRequest', [
                'username' => $params['Username'],
                'password' => $params['Password']
            ]);

            Cache::set('op_auth_generate', $reply);

            $replyData = $reply->getData();

            if (isset($replyData['token']) && $replyData['token']) {
                $token = $replyData['token'];
                if ($token) {
                    Capsule::table('reseller_tokens')->where('username', $params['Username'])->delete();

                    Capsule::table('reseller_tokens')->insert([
                        'username' => $params['Username'],
                        'token' => $token,
                        'expire_at' => Carbon::now()->addDays(AUTH_TOKEN_EXPIRATION_LIFE_TIME)->toDateTimeString(),
                        'created_at' => Carbon::now()->toDateTimeString()
                    ]);
                    $this->session->getMetadataBag()->stampNew(SESSION_EXPIRATION_LIFE_TIME);
                    $this->apiClient->getConfiguration()->setToken($token);
                    return $configarray;
                }

                return $this->generateLoginError($configarray, self::ERROR_INCORRECT_INVIRONMENT);
            }
        }

        return $this->generateLoginError($configarray, self::ERROR_INCORRECT_CREDENTIALS);
    }

    /**
     * Check token validity by API call
     * @return ResponseInterface
     */
    private function checkRequest(): ResponseInterface
    {

        if (Cache::has('op_check_request')) {
            return Cache::get('op_check_request');
        }
        $args = [];
        if ($this->apiClient->getConfiguration()->getHost() == Configuration::get('api_url')) {
            $commandToCheckAccess = 'searchPromoMessageRequest';
        } else {
            $commandToCheckAccess = 'searchContactRequest';
            $args['limit'] = 1;
        }

        $data = $this->apiClient->call($commandToCheckAccess, $args);

        return Cache::set('op_check_request', $data);
    }
}
