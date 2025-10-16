<?php

namespace Fiachehr\Pardakht\Exceptions;

use Exception;

/**
 * Class PardakhtException
 *
 * Base exception class for all package-related exceptions
 */
class PardakhtException extends Exception
{
    protected int $gatewayCode = 0;

    /**
     * Set the gateway error code
     *
     * @param int $code
     * @return $this
     */
    public function setGatewayCode(int $code): self
    {
        $this->gatewayCode = $code;
        return $this;
    }

    /**
     * Get the gateway error code
     *
     * @return int
     */
    public function getGatewayCode(): int
    {
        return $this->gatewayCode;
    }
}
