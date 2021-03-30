<?php

namespace OpenProvider\API;

use Openprovider\Api\Rest\Client\Base\Configuration;
use GuzzleHttp6\Client as HttpClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

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
     * @var ParamsCreator 
     */
    private $paramsCreator;
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * ApiV1 constructor.
     * @param LoggerInterface $logger
     * @param CamelCaseToSnakeCaseNameConverter $camelCaseToSnakeCaseNameConverter
     */
    public function __construct(
        LoggerInterface $logger,
        CamelCaseToSnakeCaseNameConverter $camelCaseToSnakeCaseNameConverter
    )
    {
        $this->camelCaseToSnakeCaseNameConverter = $camelCaseToSnakeCaseNameConverter;
        $this->logger = $logger;
        $this->serializer = new Serializer([new ObjectNormalizer()]);

        $this->apiConfiguration = new ApiConfiguration();
        $this->configuration = new Configuration();
        $this->commandMapping = new CommandMapping();
        $this->httpClient = new HttpClient();
        $this->paramsCreator = new ParamsCreator();
    }

    /**
     * @param string $cmd
     * @param array $args
     * @return ResponseInterface
     */
    public function call(string $cmd, array $args = []): ResponseInterface
    {
        $response = new Response();

        try {
            $apiClass = $this->commandMapping->getCommandMapping($cmd, CommandMapping::COMMAND_MAP_CLASS);
            $apiMethod = $this->commandMapping->getCommandMapping($cmd, CommandMapping::COMMAND_MAP_METHOD);
        } catch (\Exception $e) {
            $response = $this->failedResponse($response, $e->getMessage(), $e->getCode());
            $this->log($cmd, $args, $response);

            return $response;
        }

        $service = new $apiClass($this->httpClient, $this->configuration);

        $service->getConfig()->setHost($this->apiConfiguration->getHost());

        if ($this->apiConfiguration->getToken()) {
            $service->getConfig()->setAccessToken($this->apiConfiguration->getToken());
        }

        try {
            $requestParameters = $this->paramsCreator->createParameters($args, $service, $apiMethod);
            $reply = $service->$apiMethod(...$requestParameters);
        } catch (\Exception $e) {
            $response = $this->failedResponse($response, $e->getMessage(), $e->getCode());
            $this->log($cmd, $args, $response);

            return $response;
        }

        $data = $this->serializer->normalize($reply->getData());
        $response = $this->successResponse($response, $data);

        $this->log($cmd, $this->serializer->normalize($requestParameters), $response);

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

    /**
     * @param ResponseInterface $response
     * @param string $message
     * @param int $code
     * @return ResponseInterface
     */
    private function failedResponse(ResponseInterface $response, string $message, int $code): ResponseInterface
    {
        $response->setMessage($message);
        $response->setCode($code);

        return $response;
    }

    /**
     * @param ResponseInterface $response
     * @param array $data
     * @return ResponseInterface
     */
    private function successResponse(ResponseInterface $response, array $data): ResponseInterface
    {
        $data = $this->convertReplyKeysToCamelCase($data);

        $response->setTotal($data['total'] ?? 0);
        unset($data['total']);

        $response->setCode($data['code'] ?? 0);
        unset($data['code']);

        $response->setData($data);

        return $response;
    }
}
