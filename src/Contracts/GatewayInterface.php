<?php

namespace Fiachehr\Pardakht\Contracts;

use Fiachehr\Pardakht\ValueObjects\PaymentRequest;
use Fiachehr\Pardakht\ValueObjects\PaymentResponse;
use Fiachehr\Pardakht\ValueObjects\VerificationRequest;
use Fiachehr\Pardakht\ValueObjects\VerificationResponse;

/**
 * Interface GatewayInterface
 *
 * Defines the contract that all payment gateway drivers must implement.
 * This ensures consistency across different payment providers.
 */
interface GatewayInterface
{
    /**
     * Initialize a payment request and get the payment URL/token
     *
     * @param PaymentRequest $request
     * @return PaymentResponse
     */
    public function request(PaymentRequest $request): PaymentResponse;

    /**
     * Verify a payment after user returns from gateway
     *
     * @param VerificationRequest $request
     * @return VerificationResponse
     */
    public function verify(VerificationRequest $request): VerificationResponse;

    /**
     * Get the gateway name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Check if gateway is in sandbox mode
     *
     * @return bool
     */
    public function isSandbox(): bool;
}
