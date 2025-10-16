<?php

namespace Fiachehr\Pardakht\Tests\Feature;

use Fiachehr\Pardakht\Tests\TestCase;
use Fiachehr\Pardakht\Facades\Pardakht;
use Fiachehr\Pardakht\ValueObjects\PaymentRequest;
use Fiachehr\Pardakht\ValueObjects\VerificationRequest;
use Fiachehr\Pardakht\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function payment_request_can_be_created()
    {
        $paymentRequest = new PaymentRequest(
            amount: 100000,
            orderId: 'ORDER-123',
            callbackUrl: 'https://example.com/callback',
            description: 'Test payment'
        );

        $this->assertInstanceOf(PaymentRequest::class, $paymentRequest);
        $this->assertEquals(100000, $paymentRequest->amount);
    }

    /** @test */
    public function verification_request_can_be_created()
    {
        $verificationRequest = new VerificationRequest(
            trackingCode: 'TRACK-123',
            gatewayData: ['status' => 'OK']
        );

        $this->assertInstanceOf(VerificationRequest::class, $verificationRequest);
        $this->assertEquals('TRACK-123', $verificationRequest->trackingCode);
    }

    /** @test */
    public function gateway_can_be_accessed_via_facade()
    {
        $gateway = Pardakht::gateway('mellat');

        $this->assertNotNull($gateway);
        $this->assertEquals('mellat', $gateway->getName());
    }

    /** @test */
    public function available_gateways_can_be_retrieved()
    {
        $available = Pardakht::available();

        $this->assertIsArray($available);
        $this->assertContains('mellat', $available);
        $this->assertContains('mabna', $available);
        $this->assertContains('zarinpal', $available);
    }

    /** @test */
    public function multiple_gateways_can_be_instantiated()
    {
        $mellat = Pardakht::gateway('mellat');
        $mabna = Pardakht::gateway('mabna');
        $zarinpal = Pardakht::gateway('zarinpal');

        $this->assertEquals('mellat', $mellat->getName());
        $this->assertEquals('mabna', $mabna->getName());
        $this->assertEquals('zarinpal', $zarinpal->getName());
    }

    /** @test */
    public function gateway_instances_are_cached()
    {
        $gateway1 = Pardakht::gateway('mellat');
        $gateway2 = Pardakht::gateway('mellat');

        $this->assertSame($gateway1, $gateway2);
    }

    /** @test */
    public function payment_request_can_convert_to_array()
    {
        $request = new PaymentRequest(
            amount: 100000,
            orderId: 'ORDER-123',
            callbackUrl: 'https://example.com/callback'
        );

        $array = $request->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(100000, $array['amount']);
        $this->assertEquals('ORDER-123', $array['order_id']);
    }

    /** @test */
    public function verification_request_can_get_gateway_data()
    {
        $request = new VerificationRequest(
            trackingCode: 'TRACK-123',
            gatewayData: [
                'status' => 'OK',
                'reference_id' => 'REF-123'
            ]
        );

        $this->assertEquals('OK', $request->getGatewayData('status'));
        $this->assertEquals('REF-123', $request->getGatewayData('reference_id'));
        $this->assertNull($request->getGatewayData('non_existent'));
    }
}
