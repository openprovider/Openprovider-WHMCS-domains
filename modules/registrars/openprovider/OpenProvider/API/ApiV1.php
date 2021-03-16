<?php

namespace OpenProvider\API;

use Openprovider\Api\Rest\Client\Base\Configuration;
use GuzzleHttp6\Client as HttpClient;
use OpenProvider\WhmcsRegistrar\helpers\SnakeCaseUnderscore;

class ApiV1 implements ApiInterface
{
    /**
     * @var ApiConfiguration
     */
    private ApiConfiguration $apiConfiguration;
    /**
     * @var Configuration
     */
    private Configuration $configuration;
    /**
     * @var CommandMapping
     */
    private CommandMapping $commandMapping;
    /**
     * @var HttpClient
     */
    private HttpClient $httpClient;

    /**
     * ApiV1 constructor.
     */
    public function __construct()
    {
        $this->apiConfiguration = new ApiConfiguration();
        $this->configuration = new Configuration();
        $this->commandMapping = new CommandMapping();
        $this->httpClient = new HttpClient();
    }

    /**
     * @param string $cmd
     * @param array $args
     * @return ResponseInterface
     */
    public function call(string $cmd, array $args = []): ResponseInterface
    {
        $response = new Response();

        $apiClass = $this->commandMapping->getCommandMapping($cmd, CommandMapping::COMMAND_MAP_CLASS);
        $apiMethod = $this->commandMapping->getCommandMapping($cmd, CommandMapping::COMMAND_MAP_METHOD);
        $requestParametersType = $this->commandMapping->getCommandMapping($cmd, CommandMapping::COMMAND_MAP_PARAMETERS_TYPE);
        $service = new $apiClass($this->httpClient, $this->configuration);
        $this->setupConfiguration();

        try {
            if ($requestParametersType == CommandMapping::PARAMS_TYPE_VIA_COMMA) {
                $reflectionMethod = new \ReflectionMethod($service, $apiMethod);
                $neededArgumentsToMethod = array_values(json_decode(json_encode($reflectionMethod->getParameters()), true));
                $requestedArguments = [];
                foreach ($neededArgumentsToMethod as $element) {
                    $requestedArguments[] = $element['name'];
                }
                $filledArguments = $this->fillEmptyArguments($args, $requestedArguments);
                $reply = $service->$apiMethod(...$filledArguments);
            } else if ($requestParametersType == CommandMapping::PARAMS_TYPE_BODY) {
                $reply = $service->$apiMethod($args);
            }
        } catch (\Exception $e) {
            $response->setCode($e->getCode());
            $response->setMessage($e->getMessage());

            return $response;
        }

        $data = $this->convertReplyFromObjectToArray($reply->getData());
        $data = $this->convertReplyKeysToSnakeKeys($data);
        $response->setData($data);
        $response->setCode($reply->getCode());

        if (method_exists(get_class($reply), 'getTotal')) {
            $response->setTotal($reply->getTotal());
        }

        return $response;
    }

    /**
     * @return ConfigurationInterface
     */
    public function getConfiguration(): ConfigurationInterface
    {
        return $this->apiConfiguration;
    }

    /**
     * @return void
     */
    private function setupConfiguration(): void
    {
        $this->configuration->setHost($this->apiConfiguration->getHost());
        if ($this->apiConfiguration->getToken()) {
            $this->configuration->setAccessToken($this->apiConfiguration->getToken());
        }
    }

    /**
     * @param $response
     * @return array
     */
    private function convertReplyFromObjectToArray($response): array
    {
        return json_decode($response, true);
    }

    /**
     * @param array $givenArgs
     * @param array $neededArgs
     * @return array
     */
    private function fillEmptyArguments(array $givenArgs, array $neededArgs): array
    {
        $result = [];
        foreach ($neededArgs as $argument) {
            $result[] = $givenArgs[$argument] ?? null;
        }

        return $result;
    }

    /**
     * @param array $data
     * @return array
     */
    private function convertReplyKeysToSnakeKeys(array $data): array
    {
        if (!is_array($data))
        {
            return [];
        }

        $result = [];

        foreach ($data as $key => $value) {
            $result[SnakeCaseUnderscore::underscoreToSnakeCase($key)] = is_array($value) ?
                $this->convertReplyKeysToSnakeKeys($value) :
                $value;
        }

        return $result;
    }
}
