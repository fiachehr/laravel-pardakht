<?php

namespace Fiachehr\Pardakht\Exceptions;

/**
 * Class GatewayException
 *
 * Exception thrown when a gateway operation fails
 */
class GatewayException extends PardakhtException
{
    public static function requestFailed(string $gateway, string $message, int $code = 0): self
    {
        $exception = new self("Gateway [{$gateway}] request failed: {$message}");
        $exception->setGatewayCode($code);
        return $exception;
    }

    public static function verificationFailed(string $gateway, string $message, int $code = 0): self
    {
        $exception = new self("Gateway [{$gateway}] verification failed: {$message}");
        $exception->setGatewayCode($code);
        return $exception;
    }

    public static function invalidConfiguration(string $gateway, string $parameter): self
    {
        return new self("Gateway [{$gateway}] configuration is invalid: Missing or invalid [{$parameter}]");
    }

    public static function connectionFailed(string $gateway, string $message): self
    {
        return new self("Gateway [{$gateway}] connection failed: {$message}");
    }
}
