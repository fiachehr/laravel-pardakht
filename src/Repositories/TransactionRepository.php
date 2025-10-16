<?php

namespace Fiachehr\Pardakht\Repositories;

use Fiachehr\Pardakht\Contracts\TransactionRepositoryInterface;
use Fiachehr\Pardakht\Models\Transaction;
use Illuminate\Support\Collection;

/**
 * Class TransactionRepository
 *
 * Repository implementation for transaction storage and retrieval
 */
class TransactionRepository implements TransactionRepositoryInterface
{
    public function __construct(
        protected Transaction $model
    ) {}

    /**
     * @inheritDoc
     */
    public function create(array $data): Transaction
    {
        return $this->model->create($data);
    }

    /**
     * @inheritDoc
     */
    public function update(Transaction $transaction, array $data): Transaction
    {
        $transaction->update($data);
        return $transaction->fresh();
    }

    /**
     * @inheritDoc
     */
    public function findByTrackingCode(string $trackingCode): ?Transaction
    {
        return $this->model->where('tracking_code', $trackingCode)->first();
    }

    /**
     * @inheritDoc
     */
    public function findByReferenceId(string $referenceId): ?Transaction
    {
        return $this->model->where('reference_id', $referenceId)->first();
    }

    /**
     * @inheritDoc
     */
    public function findByOrderId(string|int $orderId): ?Transaction
    {
        return $this->model->where('order_id', $orderId)->first();
    }

    /**
     * @inheritDoc
     */
    public function getByPayable(string $payableType, int $payableId): Collection
    {
        return $this->model
            ->where('payable_type', $payableType)
            ->where('payable_id', $payableId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * @inheritDoc
     */
    public function getSuccessful(): Collection
    {
        return $this->model
            ->successful()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * @inheritDoc
     */
    public function getFailed(): Collection
    {
        return $this->model
            ->failed()
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
