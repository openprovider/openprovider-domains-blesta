<?php

require_once __DIR__ . DS . 'response.php';
require_once __DIR__ . DS . 'command_mapping.php';
require_once __DIR__ . DS . 'api_configuration.php';
require_once __DIR__ . DS . 'params_creator.php';

use Openprovider\Api\Rest\Client\Base\Configuration;
use GuzzleHttp\Client as HttpClient;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class OpenProviderApi
{
    const API_CLIENT_NAME = 'blesta';
    const API_URL = 'https://api.openprovider.eu';
    const API_CTE_URL = 'https://api.cte.openprovider.eu';

    /**
     * @var Configuration
     */
    private $configuration;
    /**
     * @var HttpClient
     */
    private $http_client;
    /**
     * @var CommandMapping
     */
    private $command_mapping;
    /**
     * @var ApiConfig
     */
    private $api_config;
    /**
     * @var ParamsCreator
     */
    private $params_creator;
    /**
     * @var Serializer
     */
    private $serializer;
    /**
     * @var array ['cmd' => string, 'args' => array]
     */
    private $last_request;
    /**
     * @var Response
     */
    private $last_response;

    public function __construct()
    {
        $this->configuration = new Configuration();
        $this->command_mapping = new CommandMapping();
        $this->api_config = new ApiConfig();
        $this->params_creator = new ParamsCreator();
        $this->serializer = new Serializer([new ObjectNormalizer()]);
        $this->last_request = ['cmd' => '', 'args' => []];
        $this->http_client = new HttpClient([
            'headers' => [
                'X-Client' => self::API_CLIENT_NAME
            ]
        ]);
    }

    public function call(string $cmd, array $args = [])
    {
        $response = new Response();

        try {
            $apiClass = $this->command_mapping->getCommandMapping($cmd, CommandMapping::COMMAND_MAP_CLASS);
            $apiMethod = $this->command_mapping->getCommandMapping($cmd, CommandMapping::COMMAND_MAP_METHOD);
        } catch (\Exception $e) {
            return $this->failedResponse($response, $e->getMessage(), $e->getCode());
        }

        $service = new $apiClass($this->http_client, $this->configuration);

        $service->getConfig()->setHost($this->api_config->getHost());

        if ($this->api_config->getToken()) {
            $service->getConfig()->setAccessToken($this->api_config->getToken());
        }

        $this->last_request = [
            'cmd' => $cmd,
            'args' => $args
        ];

        try {
            $requestParameters = $this->params_creator->createParameters($args, $service, $apiMethod);
            $reply = $service->$apiMethod(...$requestParameters);
        } catch (\Exception $e) {
            $responseData = $this->serializer->normalize(
                    json_decode(substr($e->getMessage(), strpos($e->getMessage(), 'response:') + strlen('response:')))
                ) ?? $e->getMessage();

            $return = $this->failedResponse(
                $response,
                $responseData['desc'] ?? $e->getMessage(),
                $responseData['code'] ?? $e->getCode()
            );
            $this->last_response = $return;

            return $return;
        }

        $data = $this->serializer->normalize($reply->getData());

        $return = $this->successResponse($response, $data);
        $this->last_response = $return;

        return $return;
    }

    public function getConfig()
    {
        return $this->api_config;
    }

    /**
     * @param Response $response
     * @param array $data
     * @return Response
     */
    private function successResponse(Response $response, array $data)
    {
        $response->setTotal($data['total'] ?? 0);
        unset($data['total']);

        $response->setCode($data['code'] ?? 0);
        unset($data['code']);

        $response->setData($data);

        return $response;
    }

    /**
     * @param Response $response
     * @param string $message
     * @param int $code
     * @return Response
     */
    private function failedResponse(Response $response, string $message, int $code)
    {
        $response->setMessage($message);
        $response->setCode($code);

        return $response;
    }

    /**
     * @return string|null
     */
    public function getLastRequest()
    {
        return $this->last_request;
    }

    public function getLastResponse()
    {
        return $this->last_response;
    }
}
