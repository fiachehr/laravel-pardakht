<?php

namespace Fiachehr\Pardakht\Tests\Unit\Models;

use Fiachehr\Pardakht\Tests\TestCase;
use Fiachehr\Pardakht\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_transaction()
    {
        $transaction = Transaction::create([
            'tracking_code' => 'TRACK-123',
            'reference_id' => 'REF-123',
            'gateway' => 'mellat',
            'amount' => 100000,
            'order_id' => 'ORDER-123',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('pardakht_transactions', [
            'tracking_code' => 'TRACK-123',
            'gateway' => 'mellat',
            'amount' => 100000,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_can_filter_by_gateway()
    {
        Transaction::create([
            'tracking_code' => 'TRACK-1',
            'gateway' => 'mellat',
            'amount' => 100000,
            'order_id' => 'ORDER-1',
            'status' => 'pending',
        ]);

        Transaction::create([
            'tracking_code' => 'TRACK-2',
            'gateway' => 'zarinpal',
            'amount' => 200000,
            'order_id' => 'ORDER-2',
            'status' => 'pending',
        ]);

        $mellatTransactions = Transaction::gateway('mellat')->get();

        $this->assertCount(1, $mellatTransactions);
        $this->assertEquals('mellat', $mellatTransactions->first()->gateway);
    }

    /** @test */
    public function it_can_filter_successful_transactions()
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

        $successfulTransactions = Transaction::successful()->get();

        $this->assertCount(1, $successfulTransactions);
        $this->assertEquals('success', $successfulTransactions->first()->status);
    }

    /** @test */
    public function it_can_filter_failed_transactions()
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

        $failedTransactions = Transaction::failed()->get();

        $this->assertCount(1, $failedTransactions);
        $this->assertEquals('failed', $failedTransactions->first()->status);
    }

    /** @test */
    public function it_can_filter_pending_transactions()
    {
        Transaction::create([
            'tracking_code' => 'TRACK-1',
            'gateway' => 'mellat',
            'amount' => 100000,
            'order_id' => 'ORDER-1',
            'status' => 'pending',
        ]);

        Transaction::create([
            'tracking_code' => 'TRACK-2',
            'gateway' => 'mellat',
            'amount' => 200000,
            'order_id' => 'ORDER-2',
            'status' => 'success',
        ]);

        $pendingTransactions = Transaction::pending()->get();

        $this->assertCount(1, $pendingTransactions);
        $this->assertEquals('pending', $pendingTransactions->first()->status);
    }

    /** @test */
    public function it_can_check_if_transaction_is_successful()
    {
        $transaction = Transaction::create([
            'tracking_code' => 'TRACK-1',
            'gateway' => 'mellat',
            'amount' => 100000,
            'order_id' => 'ORDER-1',
            'status' => 'success',
        ]);

        $this->assertTrue($transaction->isSuccessful());
        $this->assertFalse($transaction->isFailed());
        $this->assertFalse($transaction->isPending());
    }

    /** @test */
    public function it_can_check_if_transaction_is_failed()
    {
        $transaction = Transaction::create([
            'tracking_code' => 'TRACK-1',
            'gateway' => 'mellat',
            'amount' => 100000,
            'order_id' => 'ORDER-1',
            'status' => 'failed',
        ]);

        $this->assertTrue($transaction->isFailed());
        $this->assertFalse($transaction->isSuccessful());
        $this->assertFalse($transaction->isPending());
    }

    /** @test */
    public function it_can_check_if_transaction_is_pending()
    {
        $transaction = Transaction::create([
            'tracking_code' => 'TRACK-1',
            'gateway' => 'mellat',
            'amount' => 100000,
            'order_id' => 'ORDER-1',
            'status' => 'pending',
        ]);

        $this->assertTrue($transaction->isPending());
        $this->assertFalse($transaction->isSuccessful());
        $this->assertFalse($transaction->isFailed());
    }

    /** @test */
    public function it_casts_metadata_to_array()
    {
        $transaction = Transaction::create([
            'tracking_code' => 'TRACK-1',
            'gateway' => 'mellat',
            'amount' => 100000,
            'order_id' => 'ORDER-1',
            'status' => 'pending',
            'metadata' => ['user_id' => 1, 'product_id' => 5],
        ]);

        $this->assertIsArray($transaction->metadata);
        $this->assertEquals(['user_id' => 1, 'product_id' => 5], $transaction->metadata);
    }
}
