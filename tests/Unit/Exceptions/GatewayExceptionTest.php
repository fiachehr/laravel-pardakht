<?php

namespace Fiachehr\Pardakht\Tests\Unit\Exceptions;

use Fiachehr\Pardakht\Tests\TestCase;
use Fiachehr\Pardakht\Exceptions\GatewayException;

class GatewayExceptionTest extends TestCase
{
    /** @test */
    public function it_can_create_connection_failed_exception()
    {
        $exception = GatewayException::connectionFailed('mellat', 'Connection timeout');

        $this->assertInstanceOf(GatewayException::class, $exception);
        $this->assertEquals('Gateway [mellat] connection failed: Connection timeout', $exception->getMessage());
        $this->assertEquals('mellat', $exception->getGatewayName());
    }

    /** @test */
    public function it_can_create_request_failed_exception()
    {
        $exception = GatewayException::requestFailed('mellat', 'Invalid request', 400);

        $this->assertInstanceOf(GatewayException::class, $exception);
        $this->assertEquals('Gateway [mellat] request failed: Invalid request', $exception->getMessage());
        $this->assertEquals('mellat', $exception->getGatewayName());
        $this->assertEquals(400, $exception->getGatewayCode());
    }

    /** @test */
    public function it_can_create_verification_failed_exception()
    {
        $exception = GatewayException::verificationFailed('mellat', 'Verification failed', 500);

        $this->assertInstanceOf(GatewayException::class, $exception);
        $this->assertEquals('Gateway [mellat] verification failed: Verification failed', $exception->getMessage());
        $this->assertEquals('mellat', $exception->getGatewayName());
        $this->assertEquals(500, $exception->getGatewayCode());
    }

    /** @test */
    public function it_can_create_invalid_configuration_exception()
    {
        $exception = GatewayException::invalidConfiguration('mellat', 'terminal_id');

        $this->assertInstanceOf(GatewayException::class, $exception);
        $this->assertStringContainsString('terminal_id', $exception->getMessage());
        $this->assertEquals('mellat', $exception->getGatewayName());
    }
}
