<?php

namespace Fiachehr\Pardakht\ValueObjects;

/**
 * Class PaymentResponse
 *
 * Value object representing a payment response from gateway
 */
class PaymentResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $trackingCode = null,
        public readonly ?string $paymentUrl = null,
        public readonly ?string $referenceId = null,
        public readonly ?string $message = null,
        public readonly array $rawResponse = [],
        public readonly array $formParams = []
    ) {}

    /**
     * Check if payment request was successful
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Check if payment request failed
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return !$this->success;
    }

    /**
     * Get redirect URL for payment
     *
     * @return string|null
     */
    public function getPaymentUrl(): ?string
    {
        return $this->paymentUrl;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'tracking_code' => $this->trackingCode,
            'payment_url' => $this->paymentUrl,
            'reference_id' => $this->referenceId,
            'message' => $this->message,
            'raw_response' => $this->rawResponse,
            'form_params' => $this->formParams,
        ];
    }
}
