<?php

namespace Fiachehr\Pardakht\Tests\Feature;

use Fiachehr\Pardakht\Tests\TestCase;
use Fiachehr\Pardakht\Facades\Pardakht;
use Fiachehr\Pardakht\Gateways\MellatGateway;
use Fiachehr\Pardakht\Contracts\GatewayInterface;

class PardakhtFacadeTest extends TestCase
{
    /** @test */
    public function it_can_access_gateway_through_facade()
    {
        $gateway = Pardakht::gateway('mellat');

        $this->assertInstanceOf(GatewayInterface::class, $gateway);
        $this->assertInstanceOf(MellatGateway::class, $gateway);
    }

    /** @test */
    public function it_can_get_available_gateways_through_facade()
    {
        $available = Pardakht::available();

        $this->assertIsArray($available);
        $this->assertNotEmpty($available);
    }

    /** @test */
    public function it_can_extend_gateways_through_facade()
    {
        $customGatewayClass = new class extends \Fiachehr\Pardakht\Gateways\AbstractGateway {
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
                // No validation
            }
        };

        Pardakht::extend('custom', get_class($customGatewayClass));

        // This should not throw an exception
        $this->assertTrue(true);
    }
}
