<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'order_id',
        'status',
        'payment_method',
        'amount',
        'transaction_id',
        'gateway_response',
        'failure_reason',
        'processed_at',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'processed_at'     => 'datetime',
        'amount'           => 'decimal:2',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isSuccessful(): bool
    {
        return $this->status === 'successful';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
