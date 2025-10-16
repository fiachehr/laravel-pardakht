<?php

namespace Fiachehr\Pardakht\Tests\Unit\ValueObjects;

use Fiachehr\Pardakht\Tests\TestCase;
use Fiachehr\Pardakht\ValueObjects\PaymentRequest;
use InvalidArgumentException;

class PaymentRequestTest extends TestCase
{
    /** @test */
    public function it_can_create_payment_request_with_valid_data()
    {
        $request = new PaymentRequest(
            amount: 100000,
            orderId: 'ORDER-123',
            callbackUrl: 'https://example.com/callback',
            description: 'Test Payment',
            mobile: '09123456789',
            email: 'test@example.com',
            metadata: ['user_id' => 1]
        );

        $this->assertEquals(100000, $request->amount);
        $this->assertEquals('ORDER-123', $request->orderId);
        $this->assertEquals('https://example.com/callback', $request->callbackUrl);
        $this->assertEquals('Test Payment', $request->description);
        $this->assertEquals('09123456789', $request->mobile);
        $this->assertEquals('test@example.com', $request->email);
        $this->assertEquals(['user_id' => 1], $request->metadata);
    }

    /** @test */
    public function it_throws_exception_for_invalid_amount()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment amount must be greater than zero');

        new PaymentRequest(
            amount: 0,
            orderId: 'ORDER-123',
            callbackUrl: 'https://example.com/callback'
        );
    }

    /** @test */
    public function it_throws_exception_for_negative_amount()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment amount must be greater than zero');

        new PaymentRequest(
            amount: -100,
            orderId: 'ORDER-123',
            callbackUrl: 'https://example.com/callback'
        );
    }

    /** @test */
    public function it_throws_exception_for_empty_order_id()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Order ID cannot be empty');

        new PaymentRequest(
            amount: 100000,
            orderId: '',
            callbackUrl: 'https://example.com/callback'
        );
    }

    /** @test */
    public function it_throws_exception_for_empty_callback_url()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Callback URL cannot be empty');

        new PaymentRequest(
            amount: 100000,
            orderId: 'ORDER-123',
            callbackUrl: ''
        );
    }

    /** @test */
    public function it_throws_exception_for_invalid_callback_url_format()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid callback URL format');

        new PaymentRequest(
            amount: 100000,
            orderId: 'ORDER-123',
            callbackUrl: 'not-a-valid-url'
        );
    }

    /** @test */
    public function it_throws_exception_for_invalid_mobile_format()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid mobile number format');

        new PaymentRequest(
            amount: 100000,
            orderId: 'ORDER-123',
            callbackUrl: 'https://example.com/callback',
            mobile: '123456789'
        );
    }

    /** @test */
    public function it_throws_exception_for_invalid_email_format()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        new PaymentRequest(
            amount: 100000,
            orderId: 'ORDER-123',
            callbackUrl: 'https://example.com/callback',
            email: 'invalid-email'
        );
    }

    /** @test */
    public function it_can_create_from_array()
    {
        $data = [
            'amount' => 100000,
            'order_id' => 'ORDER-123',
            'callback_url' => 'https://example.com/callback',
            'description' => 'Test',
            'mobile' => '09123456789',
            'email' => 'test@example.com',
            'metadata' => ['key' => 'value'],
        ];

        $request = PaymentRequest::fromArray($data);

        $this->assertEquals(100000, $request->amount);
        $this->assertEquals('ORDER-123', $request->orderId);
        $this->assertEquals(['key' => 'value'], $request->metadata);
    }

    /** @test */
    public function it_can_convert_to_array()
    {
        $request = new PaymentRequest(
            amount: 100000,
            orderId: 'ORDER-123',
            callbackUrl: 'https://example.com/callback',
            description: 'Test',
            metadata: ['key' => 'value']
        );

        $array = $request->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(100000, $array['amount']);
        $this->assertEquals('ORDER-123', $array['order_id']);
        $this->assertEquals('https://example.com/callback', $array['callback_url']);
        $this->assertEquals(['key' => 'value'], $array['metadata']);
    }

    /** @test */
    public function it_accepts_valid_mobile_number()
    {
        $request = new PaymentRequest(
            amount: 100000,
            orderId: 'ORDER-123',
            callbackUrl: 'https://example.com/callback',
            mobile: '09123456789'
        );

        $this->assertEquals('09123456789', $request->mobile);
    }

    /** @test */
    public function it_accepts_valid_email()
    {
        $request = new PaymentRequest(
            amount: 100000,
            orderId: 'ORDER-123',
            callbackUrl: 'https://example.com/callback',
            email: 'user@example.com'
        );

        $this->assertEquals('user@example.com', $request->email);
    }
}
