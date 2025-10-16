<?php

namespace Fiachehr\Pardakht\ValueObjects;

/**
 * Class VerificationResponse
 *
 * Value object representing a payment verification response
 */
class VerificationResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $referenceId = null,
        public readonly ?string $cardNumber = null,
        public readonly ?int $amount = null,
        public readonly ?string $transactionId = null,
        public readonly ?string $message = null,
        public readonly array $rawResponse = []
    ) {}

    /**
     * Check if verification was successful
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Check if verification failed
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return !$this->success;
    }

    /**
     * Get masked card number
     *
     * @return string|null
     */
    public function getMaskedCardNumber(): ?string
    {
        if (!$this->cardNumber) {
            return null;
        }

        $length = strlen($this->cardNumber);
        if ($length < 8) {
            return $this->cardNumber;
        }

        return substr($this->cardNumber, 0, 4) . str_repeat('*', $length - 8) . substr($this->cardNumber, -4);
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
            'reference_id' => $this->referenceId,
            'card_number' => $this->cardNumber,
            'amount' => $this->amount,
            'transaction_id' => $this->transactionId,
            'message' => $this->message,
            'raw_response' => $this->rawResponse,
        ];
    }
}
