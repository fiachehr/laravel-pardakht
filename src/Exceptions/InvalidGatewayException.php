<?php

namespace Fiachehr\Pardakht\Exceptions;

/**
 * Class InvalidGatewayException
 *
 * Exception thrown when an invalid gateway is requested
 */
class InvalidGatewayException extends PardakhtException
{
    public static function notFound(string $gateway): self
    {
        return new self("Gateway [{$gateway}] is not configured or does not exist");
    }

    public static function driverNotFound(string $driver): self
    {
        return new self("Gateway driver [{$driver}] is not supported");
    }
}
