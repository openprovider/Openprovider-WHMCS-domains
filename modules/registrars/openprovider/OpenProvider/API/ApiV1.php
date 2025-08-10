<?php

namespace OpenProvider\API;

use Openprovider\Api\Rest\Client\Base\Configuration;
use GuzzleHttp6\Client as HttpClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

use Carbon\Carbon;
use WHMCS\Database\Capsule;

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
     * @var \idna_convert
     */
    private $idn;

    /**
     * ApiV1 constructor.
     * @param LoggerInterface $logger
     * @param CamelCaseToSnakeCaseNameConverter $camelCaseToSnakeCaseNameConverter
     */
    public function __construct(
        LoggerInterface $logger,
        CamelCaseToSnakeCaseNameConverter $camelCaseToSnakeCaseNameConverter,
        \idna_convert $idn
    ) {
        $this->camelCaseToSnakeCaseNameConverter = $camelCaseToSnakeCaseNameConverter;
        $this->logger = $logger;
        $this->serializer = new Serializer([new ObjectNormalizer()]);
        $this->idn = $idn;

        $this->apiConfiguration = new ApiConfiguration();
        $this->configuration = new Configuration();
        $this->commandMapping = new CommandMapping();
        $this->paramsCreator = new ParamsCreator();

        $this->httpClient = new HttpClient([
            'headers' => [
                'X-Client' => APIConfig::$moduleVersion . '-' . APIConfig::getInitiator()
            ]
        ]);
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
            $responseData = $this->serializer->normalize(
                json_decode(substr($e->getMessage(), strpos($e->getMessage(), 'response:') + strlen('response:')))
            ) ?? $e->getMessage();

            $response = $this->failedResponse(
                $response,
                $responseData['desc'] ?? $e->getMessage(),
                $responseData['code'] ?? $e->getCode()
            );
            $this->log($cmd, $args, $response);

            return $response;
        }

        if (isset($reply['warnings'])) {
            $this->addWarning($reply['warnings'], $cmd, $args);
        }

        $data = $this->serializer->normalize($reply->getData());
        $response = $this->successResponse($response, $data);
        $this->log($cmd, $args, $response);

        return $response;
    }

    /**
     * @param array $warnings
     * @param string $cmd
     * @param array $args
     */
    private function addWarning($warnings, $cmd, $args)
    {
        $title = "Warning: ";
        $cmd = preg_replace('/(?<!\s)([A-Z])/', ' $1', $cmd); // Insert space before capital letters
        $cmd = ucwords($cmd); // Capitalize words
        $title = "{$title}:{$cmd} Command.";

        if (isset($args['domain'])) {
            $domainName = $args['domain']['name'] ?? null;
            $extension = $args['domain']['extension'] ?? null;
            $fullDomain = $domainName . '.' . $extension;
            $title = "{$title} Domain: {$domainName}.{$extension}";
        }else if(isset($args['id'])) {
            $domain = Capsule::table('tbldomains')->where('id', $args['id'])->first();
            if ($domain) {
                $fullDomain = $domain->domain ?? '';
                $title = "{$title} Domain: {$fullDomain}";
            } 
        }

        $description = "";
        $index = 1;
        foreach ($warnings as $warn) {
            if ($warn['code'] != 0 && $warn['code'] != 250) {
                $description .= "Warning {$index}:\nDomain: {$fullDomain}\nCode: {$warn["code"]} \nDescription: {$warn["desc"]} \nData: {$warn["data"]}\n ";
                $index++;
            }
        }

        if (!empty($description) && !empty($title)) {
            if ($this->shouldUseModuleQueue($cmd)&& isset($args['id'])) {
                $this->addToModuleQueue(
                    'openprovider',
                    'warning',
                    'domain',
                    $args['id'] ?? 0,
                    $description,  
                );
            }else {
                $this->addToDo($title, $description);
            }
        }
    }
    private function shouldUseModuleQueue(string $cmd): bool
    {
    return in_array($cmd, [
        'restoreDomainRequest',
        'modifyDomainRequest',
    ]);
    }
    private function addToModuleQueue(
        string $module,
        string $moduleAction,
        string $type,
        int $domainid,
        string $error,
    ): void {
    
        Capsule::table('tblmodulequeue')->insert([
            'service_type'       => $type,
            'service_id'         => $domainid,
            'module_name'        => $module,
            'module_action'      => $moduleAction,
            'last_attempt'       => date('Y-m-d H:i:s'),
            'last_attempt_error' => $error,
            'num_retries'        => 0,
            'completed'          => 0,
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);
    }
    /**
     * Create New To-do item
     * @param $title
     * @param $description
     */
    private function addToDo($title, $description)
    {
        $today = Carbon::now();
        $todo = [
            'date' => Carbon::now(),
            'title' => $title,
            'description' => $description,
            'admin' => 0,
            'status' => 'Pending',
            'duedate' => $today->addDay()
        ];

        Capsule::table('tbltodolist')->insert($todo);
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


        // Avoid data part of the log to be too big.
        if ($response->getTotal() > 1000) {
            $logInfo = [
                'request' => $request,
                'response' => [
                    'code' => $response->getCode(),
                    'message' => "data is not displayed in the log due to the total being greater than 1000.",
                    'total' => $response->getTotal(),
                    'data' => "",
                ],
            ];
         }
        

        // Check if Message contains "Invalid country code" phrases
        if (
            strpos($response->getMessage(), "Invalid country code") !== false
        ) {
             $logInfo = [
                'request' => $request,
                'response' => [
                  'message' => "Invalid country code! List of supported country codes: https://support.openprovider.eu/hc/en-us/articles/13344317042450-Supported-country-codes-for-registration",
                  'total' => $response->getTotal(),
                  'data' => $response->getData(),
              ],
            ];          
        }

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

    /**
     * @param string $domainName
     * @return string
     */
    private function idnaConvertDomainName(string $domainName): string
    {
        $convertedDomainName = $domainName;
        if (!preg_match('//u', $convertedDomainName)) {
            $convertedDomainName = utf8_encode($convertedDomainName);
        }

        return $this->idn->encode($convertedDomainName);
    }
}
