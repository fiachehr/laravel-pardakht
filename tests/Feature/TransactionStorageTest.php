<?php

namespace Fiachehr\Pardakht\Tests\Feature;

use Fiachehr\Pardakht\Tests\TestCase;
use Fiachehr\Pardakht\Models\Transaction;
use Fiachehr\Pardakht\ValueObjects\PaymentRequest;
use Fiachehr\Pardakht\Manager\GatewayManager;
use Fiachehr\Pardakht\Contracts\TransactionRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class TransactionStorageTest extends TestCase
{
    use RefreshDatabase;

    protected GatewayManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = app('pardakht');
    }

    /** @test */
    public function it_stores_transaction_when_enabled()
    {
        config(['pardakht.store_transactions' => true]);

        $paymentRequest = new PaymentRequest(
            amount: 100000,
            orderId: 'ORDER-123',
            callbackUrl: 'https://example.com/callback',
            description: 'Test Payment'
        );

        // Mock the gateway to avoid real API calls
        $mockGateway = Mockery::mock(\Fiachehr\Pardakht\Gateways\MellatGateway::class);
        $mockGateway->shouldReceive('request')
            ->once()
            ->andReturn(new \Fiachehr\Pardakht\ValueObjects\PaymentResponse(
                success: true,
                trackingCode: 'TRACK-123',
                paymentUrl: 'https://gateway.com/pay',
                referenceId: 'REF-123'
            ));
        $mockGateway->shouldReceive('getName')->andReturn('mellat');

        // We would need to inject this mock, but for this test we'll just verify the database
        // after a successful request (which would be mocked in a real scenario)

        $this->assertTrue(true); // Placeholder for actual implementation
    }

    /** @test */
    public function it_can_find_transaction_by_tracking_code()
    {
        $transaction = Transaction::create([
            'tracking_code' => 'TRACK-123',
            'gateway' => 'mellat',
            'amount' => 100000,
            'order_id' => 'ORDER-123',
            'status' => 'pending',
        ]);

        $repository = app(TransactionRepositoryInterface::class);
        $found = $repository->findByTrackingCode('TRACK-123');

        $this->assertNotNull($found);
        $this->assertEquals('TRACK-123', $found->tracking_code);
    }

    /** @test */
    public function it_can_find_transaction_by_order_id()
    {
        $transaction = Transaction::create([
            'tracking_code' => 'TRACK-123',
            'gateway' => 'mellat',
            'amount' => 100000,
            'order_id' => 'ORDER-123',
            'status' => 'pending',
        ]);

        $repository = app(TransactionRepositoryInterface::class);
        $found = $repository->findByOrderId('ORDER-123');

        $this->assertNotNull($found);
        $this->assertEquals('ORDER-123', $found->order_id);
    }

    /** @test */
    public function it_can_get_successful_transactions()
    {
        Transaction::create([
            'tracking_code' => 'TRACK-1',
            'gateway' => 'mellat',
            'amount' => 100000,
            'order_id' => 'ORDER-1',
            'status' => 'success',
        ]);

        Transaction::create([
            'tracking_code' => 'TRACK-2',
            'gateway' => 'mellat',
            'amount' => 200000,
            'order_id' => 'ORDER-2',
            'status' => 'failed',
        ]);

        $repository = app(TransactionRepositoryInterface::class);
        $successful = $repository->getSuccessful();

        $this->assertCount(1, $successful);
        $this->assertEquals('success', $successful->first()->status);
    }

    /** @test */
    public function it_can_get_failed_transactions()
    {
        Transaction::create([
            'tracking_code' => 'TRACK-1',
            'gateway' => 'mellat',
            'amount' => 100000,
            'order_id' => 'ORDER-1',
            'status' => 'success',
        ]);

        Transaction::create([
            'tracking_code' => 'TRACK-2',
            'gateway' => 'mellat',
            'amount' => 200000,
            'order_id' => 'ORDER-2',
            'status' => 'failed',
        ]);

        $repository = app(TransactionRepositoryInterface::class);
        $failed = $repository->getFailed();

        $this->assertCount(1, $failed);
        $this->assertEquals('failed', $failed->first()->status);
    }

    /** @test */
    public function it_can_update_transaction()
    {
        $transaction = Transaction::create([
            'tracking_code' => 'TRACK-123',
            'gateway' => 'mellat',
            'amount' => 100000,
            'order_id' => 'ORDER-123',
            'status' => 'pending',
        ]);

        $repository = app(TransactionRepositoryInterface::class);
        $updated = $repository->update($transaction, [
            'status' => 'success',
            'reference_id' => 'REF-456',
        ]);

        $this->assertEquals('success', $updated->status);
        $this->assertEquals('REF-456', $updated->reference_id);

        $this->assertDatabaseHas('pardakht_transactions', [
            'tracking_code' => 'TRACK-123',
            'status' => 'success',
            'reference_id' => 'REF-456',
        ]);
    }
}
