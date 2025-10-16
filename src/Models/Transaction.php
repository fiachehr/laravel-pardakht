<?php

namespace Fiachehr\Pardakht\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class Transaction
 *
 * Model representing a payment transaction
 */
class Transaction extends Model
{
    protected $fillable = [
        'tracking_code',
        'reference_id',
        'transaction_id',
        'gateway',
        'amount',
        'order_id',
        'description',
        'mobile',
        'email',
        'callback_url',
        'status',
        'card_number',
        'verified_at',
        'verification_message',
        'verification_data',
        'metadata',
        'payable_type',
        'payable_id',
    ];

    protected $casts = [
        'amount' => 'integer',
        'verified_at' => 'datetime',
        'verification_data' => 'array',
        'metadata' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('pardakht.transaction_table', 'pardakht_transactions'));
    }

    /**
     * Get the payable model
     *
     * @return MorphTo
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if transaction is successful
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if transaction is pending
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction has failed
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get masked card number
     *
     * @return string|null
     */
    public function getMaskedCardNumber(): ?string
    {
        if (!$this->card_number) {
            return null;
        }

        $length = strlen($this->card_number);
        if ($length < 8) {
            return $this->card_number;
        }

        return substr($this->card_number, 0, 4) . str_repeat('*', $length - 8) . substr($this->card_number, -4);
    }

    /**
     * Scope for successful transactions
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope for failed transactions
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for pending transactions
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for specific gateway
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $gateway
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }
}
