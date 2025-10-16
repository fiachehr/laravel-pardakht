<?php

namespace Fiachehr\Pardakht\Tests\Unit\ValueObjects;

use Fiachehr\Pardakht\Tests\TestCase;
use Fiachehr\Pardakht\ValueObjects\VerificationRequest;
use InvalidArgumentException;

class VerificationRequestTest extends TestCase
{
    /** @test */
    public function it_can_create_verification_request()
    {
        $gatewayData = ['RefId' => '123', 'Status' => 'OK'];

        $request = new VerificationRequest(
            trackingCode: 'TRACK-123',
            gatewayData: $gatewayData
        );

        $this->assertEquals('TRACK-123', $request->trackingCode);
        $this->assertEquals($gatewayData, $request->gatewayData);
    }

    /** @test */
    public function it_throws_exception_for_empty_tracking_code()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tracking code cannot be empty');

        new VerificationRequest(
            trackingCode: '',
            gatewayData: []
        );
    }

    /** @test */
    public function it_can_get_gateway_data()
    {
        $request = new VerificationRequest(
            trackingCode: 'TRACK-123',
            gatewayData: ['RefId' => '123', 'Status' => 'OK']
        );

        $this->assertEquals('123', $request->getGatewayData('RefId'));
        $this->assertEquals('OK', $request->getGatewayData('Status'));
        $this->assertNull($request->getGatewayData('NonExistent'));
    }

    /** @test */
    public function it_can_get_gateway_data_with_default_value()
    {
        $request = new VerificationRequest(
            trackingCode: 'TRACK-123',
            gatewayData: ['Status' => 'OK']
        );

        $this->assertEquals('default', $request->getGatewayData('NonExistent', 'default'));
    }
}
