<?php

namespace Fiachehr\Pardakht\Tests\Unit\Gateways;

use Fiachehr\Pardakht\Tests\TestCase;
use Fiachehr\Pardakht\Gateways\ZarinPalGateway;
use Fiachehr\Pardakht\Exceptions\GatewayException;

class ZarinPalGatewayTest extends TestCase
{
    protected ZarinPalGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $config = [
            'merchant_id' => 'test_merchant',
            'callback_url' => 'https://example.com/callback',
            'description' => 'Test payment',
            'sandbox' => true,
        ];

        $this->gateway = new ZarinPalGateway($config);
    }

    /** @test */
    public function it_has_correct_name()
    {
        $this->assertEquals('zarinpal', $this->gateway->getName());
    }

    /** @test */
    public function it_is_in_sandbox_mode()
    {
        $this->assertTrue($this->gateway->isSandbox());
    }

    /** @test */
    public function it_throws_exception_for_missing_merchant_id()
    {
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('merchant_id');

        new ZarinPalGateway([
            'callback_url' => 'https://example.com/callback',
            'sandbox' => true,
        ]);
    }

    /** @test */
    public function it_throws_exception_for_missing_callback_url()
    {
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('callback_url');

        new ZarinPalGateway([
            'merchant_id' => 'test',
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
        $this->assertEquals('Incomplete data submitted', $method->invoke($this->gateway, -1));
        $this->assertEquals('Invalid IP or merchant code', $method->invoke($this->gateway, -2));
        $this->assertEquals('Request not found', $method->invoke($this->gateway, -11));
        $this->assertEquals('Transaction unsuccessful', $method->invoke($this->gateway, -22));
        $this->assertEquals('Operation successful', $method->invoke($this->gateway, 100));
        $this->assertEquals('Payment successful and already verified', $method->invoke($this->gateway, 101));
        $this->assertStringContainsString('Unknown error', $method->invoke($this->gateway, -999));
    }
}
