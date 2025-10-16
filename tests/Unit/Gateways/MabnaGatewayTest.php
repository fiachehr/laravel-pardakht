<?php

namespace Fiachehr\Pardakht\Tests\Unit\Gateways;

use Fiachehr\Pardakht\Tests\TestCase;
use Fiachehr\Pardakht\Gateways\MabnaGateway;
use Fiachehr\Pardakht\Exceptions\GatewayException;

class MabnaGatewayTest extends TestCase
{
    protected MabnaGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $config = [
            'terminal_id' => 'test_terminal',
            'callback_url' => 'https://example.com/callback',
            'sandbox' => true,
        ];

        $this->gateway = new MabnaGateway($config);
    }

    /** @test */
    public function it_has_correct_name()
    {
        $this->assertEquals('mabna', $this->gateway->getName());
    }

    /** @test */
    public function it_is_in_sandbox_mode()
    {
        $this->assertTrue($this->gateway->isSandbox());
    }

    /** @test */
    public function it_throws_exception_for_missing_terminal_id()
    {
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('terminal_id');

        new MabnaGateway([
            'callback_url' => 'https://example.com/callback',
            'sandbox' => true,
        ]);
    }

    /** @test */
    public function it_throws_exception_for_missing_callback_url()
    {
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('callback_url');

        new MabnaGateway([
            'terminal_id' => 'test',
            'sandbox' => true,
        ]);
    }

    /** @test */
    public function it_returns_correct_error_messages()
    {
        $reflection = new \ReflectionClass($this->gateway);
        $method = $reflection->getMethod('getErrorMessage');
        $method->setAccessible(true);

        // Test error codes
        $this->assertEquals('Transaction successful', $method->invoke($this->gateway, 0));
        $this->assertEquals('System error', $method->invoke($this->gateway, -1));
        $this->assertEquals('Invalid input parameters', $method->invoke($this->gateway, -2));
        $this->assertEquals('Terminal is inactive', $method->invoke($this->gateway, -3));
        $this->assertEquals('Invalid transaction amount', $method->invoke($this->gateway, -4));
        $this->assertEquals('Duplicate order number', $method->invoke($this->gateway, -5));
        $this->assertEquals('Transaction cancelled by user', $method->invoke($this->gateway, -11));
        $this->assertStringContainsString('Unknown error', $method->invoke($this->gateway, -999));
    }
}
