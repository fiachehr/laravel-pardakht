<?php

namespace Fiachehr\Pardakht\Contracts;

use Fiachehr\Pardakht\Models\Transaction;

/**
 * Interface TransactionRepositoryInterface
 *
 * Repository contract for transaction storage and retrieval
 */
interface TransactionRepositoryInterface
{
    /**
     * Create a new transaction
     *
     * @param array $data
     * @return Transaction
     */
    public function create(array $data): Transaction;

    /**
     * Update a transaction
     *
     * @param Transaction $transaction
     * @param array $data
     * @return Transaction
     */
    public function update(Transaction $transaction, array $data): Transaction;

    /**
     * Find transaction by tracking code
     *
     * @param string $trackingCode
     * @return Transaction|null
     */
    public function findByTrackingCode(string $trackingCode): ?Transaction;

    /**
     * Find transaction by reference id
     *
     * @param string $referenceId
     * @return Transaction|null
     */
    public function findByReferenceId(string $referenceId): ?Transaction;

    /**
     * Find transaction by order id
     *
     * @param string|int $orderId
     * @return Transaction|null
     */
    public function findByOrderId(string|int $orderId): ?Transaction;

    /**
     * Get all transactions for a payable
     *
     * @param string $payableType
     * @param int $payableId
     * @return \Illuminate\Support\Collection
     */
    public function getByPayable(string $payableType, int $payableId);

    /**
     * Get successful transactions
     *
     * @return \Illuminate\Support\Collection
     */
    public function getSuccessful();

    /**
     * Get failed transactions
     *
     * @return \Illuminate\Support\Collection
     */
    public function getFailed();
}
