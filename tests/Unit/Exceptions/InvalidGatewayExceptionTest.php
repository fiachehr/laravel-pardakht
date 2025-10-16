<?php

namespace Fiachehr\Pardakht\Tests\Unit\Exceptions;

use Fiachehr\Pardakht\Tests\TestCase;
use Fiachehr\Pardakht\Exceptions\InvalidGatewayException;

class InvalidGatewayExceptionTest extends TestCase
{
    /** @test */
    public function it_can_create_not_found_exception()
    {
        $exception = InvalidGatewayException::notFound('custom_gateway');

        $this->assertInstanceOf(InvalidGatewayException::class, $exception);
        $this->assertStringContainsString('custom_gateway', $exception->getMessage());
        $this->assertStringContainsString('not found', strtolower($exception->getMessage()));
    }

    /** @test */
    public function it_can_create_driver_not_found_exception()
    {
        $exception = InvalidGatewayException::driverNotFound('custom_driver');

        $this->assertInstanceOf(InvalidGatewayException::class, $exception);
        $this->assertStringContainsString('custom_driver', $exception->getMessage());
        $this->assertStringContainsString('driver', strtolower($exception->getMessage()));
    }
}
