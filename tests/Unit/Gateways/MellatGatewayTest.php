<?php

namespace Fiachehr\Pardakht\Tests\Unit\Gateways;

use Fiachehr\Pardakht\Tests\TestCase;
use Fiachehr\Pardakht\Gateways\MellatGateway;
use Fiachehr\Pardakht\ValueObjects\PaymentRequest;
use Fiachehr\Pardakht\ValueObjects\VerificationRequest;
use Fiachehr\Pardakht\Exceptions\GatewayException;

class MellatGatewayTest extends TestCase
{
    protected MellatGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $config = [
            'terminal_id' => 'test_terminal',
            'username' => 'test_user',
            'password' => 'test_pass',
            'callback_url' => 'https://example.com/callback',
            'sandbox' => true,
        ];

        $this->gateway = new MellatGateway($config);
    }

    /** @test */
    public function it_has_correct_name()
    {
        $this->assertEquals('mellat', $this->gateway->getName());
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

        new MellatGateway([
            'username' => 'test',
            'password' => 'test',
            'callback_url' => 'https://example.com/callback',
            'sandbox' => true,
        ]);
    }

    /** @test */
    public function it_throws_exception_for_missing_username()
    {
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('username');

        new MellatGateway([
            'terminal_id' => 'test',
            'password' => 'test',
            'callback_url' => 'https://example.com/callback',
            'sandbox' => true,
        ]);
    }

    /** @test */
    public function it_throws_exception_for_missing_password()
    {
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('password');

        new MellatGateway([
            'terminal_id' => 'test',
            'username' => 'test',
            'callback_url' => 'https://example.com/callback',
            'sandbox' => true,
        ]);
    }

    /** @test */
    public function it_throws_exception_for_missing_callback_url()
    {
        $this->expectException(GatewayException::class);
        $this->expectExceptionMessage('callback_url');

        new MellatGateway([
            'terminal_id' => 'test',
            'username' => 'test',
            'password' => 'test',
            'sandbox' => true,
        ]);
    }

    /** @test */
    public function it_returns_correct_error_messages()
    {
        $reflection = new \ReflectionClass($this->gateway);
        $method = $reflection->getMethod('getErrorMessage');
        $method->setAccessible(true);

        // Test some error codes
        $this->assertEquals('Transaction failed', $method->invoke($this->gateway, -1));
        $this->assertEquals('Transaction successful', $method->invoke($this->gateway, 0));
        $this->assertEquals('Invalid card number', $method->invoke($this->gateway, 11));
        $this->assertEquals('Insufficient balance', $method->invoke($this->gateway, 12));
        $this->assertEquals('Transaction cancelled by user', $method->invoke($this->gateway, 17));
        $this->assertStringContainsString('Unknown error', $method->invoke($this->gateway, 9999));
    }
}
