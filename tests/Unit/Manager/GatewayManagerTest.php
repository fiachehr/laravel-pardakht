<?php

namespace Fiachehr\Pardakht\Tests\Unit\Manager;

use Fiachehr\Pardakht\Tests\TestCase;
use Fiachehr\Pardakht\Manager\GatewayManager;
use Fiachehr\Pardakht\Gateways\MellatGateway;
use Fiachehr\Pardakht\Gateways\MabnaGateway;
use Fiachehr\Pardakht\Gateways\ZarinPalGateway;
use Fiachehr\Pardakht\Exceptions\InvalidGatewayException;
use Fiachehr\Pardakht\Contracts\GatewayInterface;

class GatewayManagerTest extends TestCase
{
    protected GatewayManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = app('pardakht');
    }

    /** @test */
    public function it_can_get_default_gateway()
    {
        $gateway = $this->manager->gateway();

        $this->assertInstanceOf(GatewayInterface::class, $gateway);
        $this->assertInstanceOf(MellatGateway::class, $gateway);
    }

    /** @test */
    public function it_can_get_mellat_gateway()
    {
        $gateway = $this->manager->gateway('mellat');

        $this->assertInstanceOf(MellatGateway::class, $gateway);
        $this->assertEquals('mellat', $gateway->getName());
    }

    /** @test */
    public function it_can_get_mabna_gateway()
    {
        $gateway = $this->manager->gateway('mabna');

        $this->assertInstanceOf(MabnaGateway::class, $gateway);
        $this->assertEquals('mabna', $gateway->getName());
    }

    /** @test */
    public function it_can_get_zarinpal_gateway()
    {
        $gateway = $this->manager->gateway('zarinpal');

        $this->assertInstanceOf(ZarinPalGateway::class, $gateway);
        $this->assertEquals('zarinpal', $gateway->getName());
    }

    /** @test */
    public function it_throws_exception_for_non_existent_gateway()
    {
        $this->expectException(InvalidGatewayException::class);

        $this->manager->gateway('non_existent');
    }

    /** @test */
    public function it_returns_same_instance_on_multiple_calls()
    {
        $gateway1 = $this->manager->gateway('mellat');
        $gateway2 = $this->manager->gateway('mellat');

        $this->assertSame($gateway1, $gateway2);
    }

    /** @test */
    public function it_can_get_available_gateways()
    {
        $available = $this->manager->available();

        $this->assertIsArray($available);
        $this->assertContains('mellat', $available);
        $this->assertContains('mabna', $available);
        $this->assertContains('zarinpal', $available);
    }

    /** @test */
    public function it_can_extend_with_custom_gateway()
    {
        $customGatewayClass = new class (['sandbox' => true]) extends \Fiachehr\Pardakht\Gateways\AbstractGateway {
            public function __construct(array $config)
            {
                parent::__construct($config);
            }

            public function getName(): string
            {
                return 'custom';
            }

            public function request(\Fiachehr\Pardakht\ValueObjects\PaymentRequest $request): \Fiachehr\Pardakht\ValueObjects\PaymentResponse
            {
                return new \Fiachehr\Pardakht\ValueObjects\PaymentResponse(
                    success: true,
                    trackingCode: 'test',
                    paymentUrl: 'https://test.com'
                );
            }

            public function verify(\Fiachehr\Pardakht\ValueObjects\VerificationRequest $request): \Fiachehr\Pardakht\ValueObjects\VerificationResponse
            {
                return new \Fiachehr\Pardakht\ValueObjects\VerificationResponse(
                    success: true
                );
            }

            protected function validateConfig(): void
            {
                // No validation needed for test
            }
        };

        $this->manager->extend('custom', get_class($customGatewayClass));

        // Configure the custom gateway
        config(['pardakht.gateways.custom' => [
            'driver' => 'custom',
        ]]);

        $gateway = $this->manager->gateway('custom');

        $this->assertInstanceOf(GatewayInterface::class, $gateway);
        $this->assertEquals('custom', $gateway->getName());
    }
}
