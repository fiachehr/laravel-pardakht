<?php

namespace Fiachehr\Pardakht\ValueObjects;

use InvalidArgumentException;

/**
 * Class VerificationRequest
 *
 * Value object representing a payment verification request
 */
class VerificationRequest
{
    public function __construct(
        public readonly string $trackingCode,
        public readonly array $gatewayData = []
    ) {
        if (empty($this->trackingCode)) {
            throw new InvalidArgumentException('Tracking code cannot be empty');
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
            trackingCode: $data['tracking_code'],
            gatewayData: $data['gateway_data'] ?? []
        );
    }

    /**
     * Get a specific gateway data value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getGatewayData(string $key, mixed $default = null): mixed
    {
        return $this->gatewayData[$key] ?? $default;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'tracking_code' => $this->trackingCode,
            'gateway_data' => $this->gatewayData,
        ];
    }
}
