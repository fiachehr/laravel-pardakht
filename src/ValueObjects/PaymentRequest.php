<?php

namespace Fiachehr\Pardakht\ValueObjects;

use InvalidArgumentException;

/**
 * Class PaymentRequest
 *
 * Value object representing a payment request
 */
class PaymentRequest
{
    public function __construct(
        public readonly int $amount,
        public readonly string $orderId,
        public readonly string $callbackUrl,
        public readonly ?string $description = null,
        public readonly ?string $mobile = null,
        public readonly ?string $email = null,
        public readonly array $metadata = []
    ) {
        if ($this->amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero');
        }

        if (empty($this->orderId)) {
            throw new InvalidArgumentException('Order ID cannot be empty');
        }

        if (empty($this->callbackUrl)) {
            throw new InvalidArgumentException('Callback URL cannot be empty');
        }

        if (!filter_var($this->callbackUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid callback URL format');
        }

        if ($this->mobile && !preg_match('/^09\d{9}$/', $this->mobile)) {
            throw new InvalidArgumentException('Invalid mobile number format');
        }

        if ($this->email && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }
    }

    /**
     * Create instance from array
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            amount: $data['amount'],
            orderId: $data['order_id'],
            callbackUrl: $data['callback_url'],
            description: $data['description'] ?? null,
            mobile: $data['mobile'] ?? null,
            email: $data['email'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'order_id' => $this->orderId,
            'callback_url' => $this->callbackUrl,
            'description' => $this->description,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'metadata' => $this->metadata,
        ];
    }
}
