<?php

namespace Fiachehr\Pardakht\Tests\Unit\Repositories;

use Fiachehr\Pardakht\Tests\TestCase;
use Fiachehr\Pardakht\Models\Transaction;
use Fiachehr\Pardakht\Repositories\TransactionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TransactionRepository(new Transaction());
    }

    /** @test */
    public function it_can_create_transaction()
    {
        $data = [
            'tracking_code' => 'TRACK-123',
            'reference_id' => 'REF-123',
            'gateway' => 'mellat',
            'amount' => 100000,
            'order_id' => 'ORDER-123',
            'status' => 'pending',
        ];

        $transaction = $this->repository->create($data);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals('TRACK-123', $transaction->tracking_code);
        $this->assertEquals('mellat', $transaction->gateway);
        $this->assertEquals(100000, $transaction->amount);
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

        $updated = $this->repository->update($transaction, [
            'status' => 'success',
            'reference_id' => 'REF-456',
        ]);

        $this->assertEquals('success', $updated->status);
        $this->assertEquals('REF-456', $updated->reference_id);
    }

    /** @test */
    public function it_can_find_by_tracking_code()
    {
        Transaction::create([
            'tracking_code' => 'TRACK-123',
            'gateway' => 'mellat',
            'amount' => 100000,
            'order_id' => 'ORDER-123',
            'status' => 'pending',
        ]);

        $transaction = $this->repository->findByTrackingCode('TRACK-123');

        $this->assertNotNull($transaction);
        $this->assertEquals('TRACK-123', $transaction->tracking_code);
    }

    /** @test */
    public function it_returns_null_when_tracking_code_not_found()
    {
        $transaction = $this->repository->findByTrackingCode('NON-EXISTENT');

        $this->assertNull($transaction);
    }

    /** @test */
    public function it_can_find_by_reference_id()
    {
        Transaction::create([
            'tracking_code' => 'TRACK-123',
            'reference_id' => 'REF-123',
            'gateway' => 'mellat',
            'amount' => 100000,
            'order_id' => 'ORDER-123',
            'status' => 'success',
        ]);

        $transaction = $this->repository->findByReferenceId('REF-123');

        $this->assertNotNull($transaction);
        $this->assertEquals('REF-123', $transaction->reference_id);
    }

    /** @test */
    public function it_can_find_by_order_id()
    {
        Transaction::create([
            'tracking_code' => 'TRACK-123',
            'gateway' => 'mellat',
            'amount' => 100000,
            'order_id' => 'ORDER-123',
            'status' => 'pending',
        ]);

        $transaction = $this->repository->findByOrderId('ORDER-123');

        $this->assertNotNull($transaction);
        $this->assertEquals('ORDER-123', $transaction->order_id);
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

        Transaction::create([
            'tracking_code' => 'TRACK-3',
            'gateway' => 'mellat',
            'amount' => 300000,
            'order_id' => 'ORDER-3',
            'status' => 'success',
        ]);

        $successful = $this->repository->getSuccessful();

        $this->assertCount(2, $successful);
        $this->assertTrue($successful->every(fn($t) => $t->status === 'success'));
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

        $failed = $this->repository->getFailed();

        $this->assertCount(1, $failed);
        $this->assertEquals('failed', $failed->first()->status);
    }

    /** @test */
    public function it_can_get_transactions_by_payable()
    {
        Transaction::create([
            'tracking_code' => 'TRACK-1',
            'gateway' => 'mellat',
            'amount' => 100000,
            'order_id' => 'ORDER-1',
            'status' => 'success',
            'payable_type' => 'App\Models\Order',
            'payable_id' => 1,
        ]);

        Transaction::create([
            'tracking_code' => 'TRACK-2',
            'gateway' => 'mellat',
            'amount' => 200000,
            'order_id' => 'ORDER-2',
            'status' => 'success',
            'payable_type' => 'App\Models\Order',
            'payable_id' => 1,
        ]);

        Transaction::create([
            'tracking_code' => 'TRACK-3',
            'gateway' => 'mellat',
            'amount' => 300000,
            'order_id' => 'ORDER-3',
            'status' => 'success',
            'payable_type' => 'App\Models\Order',
            'payable_id' => 2,
        ]);

        $transactions = $this->repository->getByPayable('App\Models\Order', 1);

        $this->assertCount(2, $transactions);
        $this->assertTrue($transactions->every(fn($t) => $t->payable_id === 1));
    }
}
