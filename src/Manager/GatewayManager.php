<?php

namespace Fiachehr\Pardakht\Manager;

use Fiachehr\Pardakht\Contracts\GatewayInterface;
use Fiachehr\Pardakht\Contracts\TransactionRepositoryInterface;
use Fiachehr\Pardakht\Exceptions\InvalidGatewayException;
use Fiachehr\Pardakht\Gateways\MabnaGateway;
use Fiachehr\Pardakht\Gateways\MellatGateway;
use Fiachehr\Pardakht\Gateways\ZarinPalGateway;
use Fiachehr\Pardakht\ValueObjects\PaymentRequest;
use Fiachehr\Pardakht\ValueObjects\PaymentResponse;
use Fiachehr\Pardakht\ValueObjects\VerificationRequest;
use Fiachehr\Pardakht\ValueObjects\VerificationResponse;
use Illuminate\Support\Facades\Config;

/**
 * Class GatewayManager
 *
 * Manages payment gateway instances using Factory pattern
 * Implements Singleton-like behavior through Laravel's service container
 */
class GatewayManager
{
    protected array $gateways = [];
    protected array $drivers = [
        'mellat' => MellatGateway::class,
        'mabna' => MabnaGateway::class,
        'zarinpal' => ZarinPalGateway::class,
    ];

    public function __construct(
        protected ?TransactionRepositoryInterface $transactionRepository = null
    ) {}

    /**
     * Get a gateway instance by name
     *
     * @param string|null $name
     * @return GatewayInterface
     * @throws InvalidGatewayException
     */
    public function gateway(?string $name = null): GatewayInterface
    {
        $name = $name ?? $this->getDefaultGateway();

        // Return cached instance if exists
        if (isset($this->gateways[$name])) {
            return $this->gateways[$name];
        }

        // Create new instance
        $config = $this->getGatewayConfig($name);
        $driver = $config['driver'] ?? $name;

        if (!isset($this->drivers[$driver])) {
            throw InvalidGatewayException::driverNotFound($driver);
        }

        $gatewayClass = $this->drivers[$driver];
        $this->gateways[$name] = new $gatewayClass($config);

        return $this->gateways[$name];
    }

    /**
     * Make a payment request through specified gateway
     *
     * @param PaymentRequest $request
     * @param string|null $gateway
     * @return PaymentResponse
     */
    public function request(PaymentRequest $request, ?string $gateway = null): PaymentResponse
    {
        $gatewayInstance = $this->gateway($gateway);
        $response = $gatewayInstance->request($request);

        // Store transaction if enabled
        if ($this->shouldStoreTransactions() && $response->isSuccessful()) {
            $this->storeTransaction($request, $response, $gatewayInstance->getName());
        }

        return $response;
    }

    /**
     * Verify a payment through specified gateway
     *
     * @param VerificationRequest $request
     * @param string|null $gateway
     * @return VerificationResponse
     */
    public function verify(VerificationRequest $request, ?string $gateway = null): VerificationResponse
    {
        $gatewayInstance = $this->gateway($gateway);
        $response = $gatewayInstance->verify($request);

        // Update transaction if enabled
        if ($this->shouldStoreTransactions()) {
            $this->updateTransaction($request->trackingCode, $response);
        }

        return $response;
    }

    /**
     * Register a custom gateway driver
     *
     * @param string $name
     * @param string $class
     * @return $this
     */
    public function extend(string $name, string $class): self
    {
        $this->drivers[$name] = $class;
        return $this;
    }

    /**
     * Get all available gateway names
     *
     * @return array
     */
    public function available(): array
    {
        return array_keys(Config::get('pardakht.gateways', []));
    }

    /**
     * Get the default gateway name
     *
     * @return string
     */
    protected function getDefaultGateway(): string
    {
        return Config::get('pardakht.default', 'mellat');
    }

    /**
     * Get gateway configuration
     *
     * @param string $name
     * @return array
     * @throws InvalidGatewayException
     */
    protected function getGatewayConfig(string $name): array
    {
        $config = Config::get("pardakht.gateways.{$name}");

        if (!$config) {
            throw InvalidGatewayException::notFound($name);
        }

        return $config;
    }

    /**
     * Check if transactions should be stored
     *
     * @return bool
     */
    protected function shouldStoreTransactions(): bool
    {
        return Config::get('pardakht.store_transactions', true) && $this->transactionRepository !== null;
    }

    /**
     * Store a new transaction
     *
     * @param PaymentRequest $request
     * @param PaymentResponse $response
     * @param string $gateway
     * @return void
     */
    protected function storeTransaction(PaymentRequest $request, PaymentResponse $response, string $gateway): void
    {
        if (!$this->transactionRepository) {
            return;
        }

        $this->transactionRepository->create([
            'tracking_code' => $response->trackingCode,
            'reference_id' => $response->referenceId,
            'gateway' => $gateway,
            'amount' => $request->amount,
            'order_id' => $request->orderId,
            'description' => $request->description,
            'mobile' => $request->mobile,
            'email' => $request->email,
            'callback_url' => $request->callbackUrl,
            'status' => 'pending',
            'metadata' => $request->metadata,
        ]);
    }

    /**
     * Update transaction after verification
     *
     * @param string $trackingCode
     * @param VerificationResponse $response
     * @return void
     */
    protected function updateTransaction(string $trackingCode, VerificationResponse $response): void
    {
        if (!$this->transactionRepository) {
            return;
        }

        $transaction = $this->transactionRepository->findByTrackingCode($trackingCode);

        if ($transaction) {
            $this->transactionRepository->update($transaction, [
                'status' => $response->isSuccessful() ? 'success' : 'failed',
                'reference_id' => $response->referenceId ?? $transaction->reference_id,
                'transaction_id' => $response->transactionId,
                'card_number' => $response->cardNumber,
                'verified_at' => now(),
                'verification_message' => $response->message,
                'verification_data' => $response->rawResponse,
            ]);
        }
    }

    /**
     * Dynamically call methods on the default gateway
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->gateway()->$method(...$parameters);
    }
}
