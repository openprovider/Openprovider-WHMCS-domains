<?php
// Require any libraries needed for the module to function.

use Carbon\Carbon;
use OpenProvider\API\API;
use OpenProvider\API\ApiHelper;
use OpenProvider\API\ApiInterface;
use OpenProvider\API\XmlApiAdapter;
use OpenProvider\Logger;
use OpenProvider\WhmcsRegistrar\src\Configuration;
use OpenProvider\API\ApiV1;
use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use WeDevelopCoffee\wPower\Core\Core;
use WeDevelopCoffee\wPower\Models\Registrar;
use WHMCS\Database\Capsule;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/classes/idna_convert.class.php';

const SESSION_EXPIRATION_LIFE_TIME = 300;

/**
 * Configure and launch the system
 */
function openprovider_registrar_launch($level = 'hooks')
{
    $core = openprovider_registrar_core($level);
    return $core->launch();
}

/**
 * Configure and launch the system
 */
function openprovider_registrar_core($level = 'hooks')
{
    $core = new Core();

    $core->setModuleName('openprovider');
    $core->setModuleType('registrar');
    $core->setNamespace('\OpenProvider\WhmcsRegistrar');
    $core->setLevel($level);
    return $core;
}

function openprovider_bind_required_classes($launcher)
{
    $params = (new Registrar())->getRegistrarData()['openprovider'];

    $host = $params['test_mode'] == 'on' ?
        Configuration::get('api_url_cte') :
        Configuration::get('api_url');

    $useApiV1 = true;

    $launcher->set(LoggerInterface::class, function (ContainerInterface $c) {
        return new Logger();
    });

    $launcher->set(Session::class, function (ContainerInterface $c) {
        return new Session();
    });

    $launcher->set(CamelCaseToSnakeCaseNameConverter::class, function (ContainerInterface $e) {
        return new CamelCaseToSnakeCaseNameConverter();
    });

    $launcher->set(\idna_convert::class, function (ContainerInterface $e) {
        return new \idna_convert();
    });

    $launcher->set(ApiV1::class, function (ContainerInterface $c) use ($params, $host) {
        $session = $c->get(Session::class);
        $camelCaseToSnakeCaseNameConverter = $c->get(CamelCaseToSnakeCaseNameConverter::class);
        $logger = $c->get(LoggerInterface::class);
        $idn = $c->get(\idna_convert::class);
        $client = new ApiV1($logger, $camelCaseToSnakeCaseNameConverter, $idn);
        $client->getConfiguration()->setHost($host);

        $token_result = [];

        if (Capsule::schema()->hasTable('reseller_tokens')) {
            $token_result = Capsule::table('reseller_tokens')->where('username', $params['Username'])->orderBy('created_at', 'desc')->get();
        } else {
            Capsule::schema()->create(
                'reseller_tokens',
                function ($table) {
                    /** @var \Illuminate\Database\Schema\Blueprint $table */
                    $table->increments('id');
                    $table->string('username');
                    $table->string('token');
                    $table->string('expire_at');
                    $table->string('created_at');
                }
            );
        }

        $token = "";
        $expireTime = count($token_result) > 0 ? new Carbon($token_result[0]->expire_at) : null;

        if (count($token_result) > 0 && Carbon::now()->diffInSeconds($expireTime, false) > 0) {
            $token = $token_result[0]->token;
        } else {
            $token = $client->call('generateAuthTokenRequest', [
                'username' => $params['Username'],
                'password' => $params['Password']
            ])->getData()['token'];

            Capsule::table('reseller_tokens')->where('username', $params['Username'])->delete();

            Capsule::connection()->transaction(
                function ($connectionManager) use ($token, $params) {
                    $connectionManager->table('reseller_tokens')->insert(
                        [
                            'username' => $params['Username'],
                            'token' => $token,
                            'expire_at' => Carbon::now()->addDays(2)->toDateTimeString(),
                            'created_at' => Carbon::now()->toDateTimeString()
                        ]
                    );
                }
            );

            $session->getMetadataBag()->stampNew(SESSION_EXPIRATION_LIFE_TIME);
        }
        $client->getConfiguration()->setToken($token);

        return $client;
    });

    $launcher->set(XmlApiAdapter::class, function (ContainerInterface $c) use ($params, $host) {
        $xmlApi = new API();

        $client = new XmlApiAdapter($xmlApi);
        $client->getConfiguration()->setUserName($params['Username']);
        $client->getConfiguration()->setPassword($params['Password']);
        $client->getConfiguration()->setHost($host);

        return $client;
    });

    $launcher->set(ApiInterface::class, function (ContainerInterface $c) use ($useApiV1) {
        if ($useApiV1) {
            return $c->get(ApiV1::class);
        }

        return $c->get(XmlApiAdapter::class);
    });

    $launcher->set(ApiHelper::class, function (ContainerInterface $c) {
        $apiClient = $c->get(ApiInterface::class);
        return new ApiHelper($apiClient);
    });

    return $launcher;
}
