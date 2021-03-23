<?php

namespace OpenProvider\API;

use Openprovider\Api\Rest\Client\Base\Configuration;
use GuzzleHttp6\Client as HttpClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

class ApiV1 implements ApiInterface
{
    /**
     * @var ApiConfiguration
     */
    private $apiConfiguration;
    /**
     * @var Configuration
     */
    private $configuration;
    /**
     * @var CommandMapping
     */
    private $commandMapping;
    /**
     * @var HttpClient
     */
    private $httpClient;
    /**
     * @var CamelCaseToSnakeCaseNameConverter
     */
    private $camelCaseToSnakeCaseNameConverter;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ApiV1 constructor.
     * @param LoggerInterface $logger
     * @param CamelCaseToSnakeCaseNameConverter $camelCaseToSnakeCaseNameConverter
     */
    public function __construct(LoggerInterface $logger, CamelCaseToSnakeCaseNameConverter $camelCaseToSnakeCaseNameConverter)
    {
        $this->camelCaseToSnakeCaseNameConverter = $camelCaseToSnakeCaseNameConverter;
        $this->logger = $logger;

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

        $this->configuration->setHost($this->apiConfiguration->getHost());
        $this->configuration->setAccessToken($this->apiConfiguration->getToken());

        $requestParameters = $this->convertRequestKeysToSnakeCase($args);

        try {
            if ($requestParametersType == CommandMapping::PARAMS_TYPE_VIA_COMMA) {
                $reflectionMethod = new \ReflectionMethod($service, $apiMethod);
                $neededArgumentsToMethod = array_values(json_decode(json_encode($reflectionMethod->getParameters()), true));
                $requestedArguments = [];
                foreach ($neededArgumentsToMethod as $element) {
                    $requestedArguments[] = $element['name'];
                }
                $filledArguments = $this->fillEmptyArguments($requestParameters, $requestedArguments);
                $reply = $service->$apiMethod(...$filledArguments);
            } else if ($requestParametersType == CommandMapping::PARAMS_TYPE_BODY) {
                $reply = $service->$apiMethod($requestParameters);
            }
        } catch (\Exception $e) {
            $response->setCode($e->getCode());
            $response->setMessage($e->getMessage());

            $this->log($cmd, $args, $response);

            return $response;
        }

        $data = json_decode($reply->getData(), true);
        $data = $this->convertReplyKeysToCamelCase($data);

        if (isset($data['total'])) {
            $response->setTotal($data['total']);
            unset($data['total']);
        }

        $response->setData($data);
        $response->setCode($reply->getCode());

        $this->log($cmd, $args, $response);

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
    private function convertReplyKeysToCamelCase(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $result[$this->camelCaseToSnakeCaseNameConverter->denormalize($key)] = is_array($value) ?
                $this->convertReplyKeysToCamelCase($value) :
                $value;
        }

        return $result;
    }

    /**
     * @param array $data
     * @return array
     */
    private function convertRequestKeysToSnakeCase(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $result[$this->camelCaseToSnakeCaseNameConverter->normalize($key)] = is_array($value) ?
                $this->convertRequestKeysToSnakeCase($value) :
                $value;
        }

        return $result;
    }

    /**
     * @param string $cmd
     * @param array $request
     * @param Response $response
     */
    private function log(string $cmd, array $request, Response $response): void
    {
        $logInfo = [
            'request' => $request,
            'response' => [
                'code' => $response->getCode(),
                'message' => $response->getMessage(),
                'total' => $response->getTotal(),
                'data' => $response->getData(),
            ],
        ];
        $this->logger->info($cmd, $logInfo);
    }
}
