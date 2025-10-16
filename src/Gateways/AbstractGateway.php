<?php

namespace Fiachehr\Pardakht\Gateways;

use Fiachehr\Pardakht\Contracts\GatewayInterface;
use Fiachehr\Pardakht\Exceptions\GatewayException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class AbstractGateway
 *
 * Base class for all gateway implementations
 */
abstract class AbstractGateway implements GatewayInterface
{
    protected Client $httpClient;
    protected bool $sandbox;
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->sandbox = $config['sandbox'] ?? false;
        $this->httpClient = new Client([
            'timeout' => 30,
            'verify' => !$this->sandbox,
        ]);

        $this->validateConfig();
    }

    /**
     * Validate gateway configuration
     *
     * @return void
     * @throws GatewayException
     */
    abstract protected function validateConfig(): void;

    /**
     * Check if gateway is in sandbox mode
     *
     * @return bool
     */
    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    /**
     * Make HTTP request
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @return mixed
     * @throws GatewayException
     */
    protected function makeHttpRequest(string $method, string $url, array $options = []): mixed
    {
        try {
            $response = $this->httpClient->request($method, $url, $options);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw GatewayException::connectionFailed($this->getName(), $e->getMessage());
        }
    }

    /**
     * Make SOAP request
     *
     * @param string $wsdl
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws GatewayException
     */
    protected function makeSoapRequest(string $wsdl, string $method, array $parameters): mixed
    {
        try {
            $soapOptions = [
                'encoding' => 'UTF-8',
                'trace' => true,
                'exceptions' => true,
                'connection_timeout' => 30,
            ];

            if ($this->sandbox) {
                $soapOptions['stream_context'] = stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ]
                ]);
            }

            $client = new \SoapClient($wsdl, $soapOptions);
            return $client->__soapCall($method, $parameters);
        } catch (\SoapFault $e) {
            throw GatewayException::connectionFailed($this->getName(), $e->getMessage());
        }
    }

    /**
     * Get configuration value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Generate unique tracking code
     *
     * @return string
     */
    protected function generateTrackingCode(): string
    {
        return uniqid($this->getName() . '_', true);
    }
}
