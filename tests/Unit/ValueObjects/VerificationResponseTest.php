<?php

namespace Fiachehr\Pardakht\Tests\Unit\ValueObjects;

use Fiachehr\Pardakht\Tests\TestCase;
use Fiachehr\Pardakht\ValueObjects\VerificationResponse;

class VerificationResponseTest extends TestCase
{
    /** @test */
    public function it_can_create_successful_verification_response()
    {
        $response = new VerificationResponse(
            success: true,
            referenceId: 'REF-123',
            cardNumber: '1234567890123456',
            amount: 100000,
            transactionId: 'TXN-123',
            message: 'Verified',
            rawResponse: ['status' => 'verified']
        );

        $this->assertTrue($response->isSuccessful());
        $this->assertFalse($response->isFailed());
        $this->assertEquals('REF-123', $response->referenceId);
        $this->assertEquals('1234567890123456', $response->cardNumber);
        $this->assertEquals(100000, $response->amount);
        $this->assertEquals('TXN-123', $response->transactionId);
        $this->assertEquals('Verified', $response->message);
    }

    /** @test */
    public function it_can_create_failed_verification_response()
    {
        $response = new VerificationResponse(
            success: false,
            message: 'Verification failed'
        );

        $this->assertFalse($response->isSuccessful());
        $this->assertTrue($response->isFailed());
    }

    /** @test */
    public function it_can_mask_card_number()
    {
        $response = new VerificationResponse(
            success: true,
            cardNumber: '1234567890123456'
        );

        $this->assertEquals('1234********3456', $response->getMaskedCardNumber());
    }

    /** @test */
    public function it_returns_null_when_card_number_is_not_set()
    {
        $response = new VerificationResponse(
            success: true
        );

        $this->assertNull($response->getMaskedCardNumber());
    }

    /** @test */
    public function it_can_convert_to_array()
    {
        $response = new VerificationResponse(
            success: true,
            referenceId: 'REF-123',
            cardNumber: '1234567890123456',
            amount: 100000,
            transactionId: 'TXN-123',
            message: 'Verified',
            rawResponse: ['status' => 'verified']
        );

        $array = $response->toArray();

        $this->assertIsArray($array);
        $this->assertTrue($array['success']);
        $this->assertEquals('REF-123', $array['reference_id']);
        $this->assertEquals('1234567890123456', $array['card_number']);
        $this->assertEquals(100000, $array['amount']);
        $this->assertEquals('TXN-123', $array['transaction_id']);
        $this->assertEquals('Verified', $array['message']);
        $this->assertEquals(['status' => 'verified'], $array['raw_response']);
    }
}
