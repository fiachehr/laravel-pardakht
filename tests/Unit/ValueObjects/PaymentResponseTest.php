<?php

namespace Fiachehr\Pardakht\Tests\Unit\ValueObjects;

use Fiachehr\Pardakht\Tests\TestCase;
use Fiachehr\Pardakht\ValueObjects\PaymentResponse;

class PaymentResponseTest extends TestCase
{
    /** @test */
    public function it_can_create_successful_payment_response()
    {
        $response = new PaymentResponse(
            success: true,
            trackingCode: 'TRACK-123',
            paymentUrl: 'https://gateway.com/pay',
            referenceId: 'REF-123',
            message: 'Success',
            rawResponse: ['status' => 'ok']
        );

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isFailed());
        $this->assertEquals('TRACK-123', $response->trackingCode);
        $this->assertEquals('https://gateway.com/pay', $response->paymentUrl);
        $this->assertEquals('REF-123', $response->referenceId);
        $this->assertEquals('Success', $response->message);
    }

    /** @test */
    public function it_can_create_failed_payment_response()
    {
        $response = new PaymentResponse(
            success: false,
            message: 'Failed'
        );

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isFailed());
    }

    /** @test */
    public function it_can_get_payment_url()
    {
        $response = new PaymentResponse(
            success: true,
            paymentUrl: 'https://gateway.com/pay'
        );

        $this->assertEquals('https://gateway.com/pay', $response->getPaymentUrl());
    }

    /** @test */
    public function it_can_convert_to_array()
    {
        $response = new PaymentResponse(
            success: true,
            trackingCode: 'TRACK-123',
            paymentUrl: 'https://gateway.com/pay',
            referenceId: 'REF-123',
            message: 'Success',
            rawResponse: ['status' => 'ok']
        );

        $array = $response->toArray();

        $this->assertIsArray($array);
        $this->assertTrue($array['success']);
        $this->assertEquals('TRACK-123', $array['tracking_code']);
        $this->assertEquals('https://gateway.com/pay', $array['payment_url']);
        $this->assertEquals('REF-123', $array['reference_id']);
        $this->assertEquals('Success', $array['message']);
        $this->assertEquals(['status' => 'ok'], $array['raw_response']);
    }
}
