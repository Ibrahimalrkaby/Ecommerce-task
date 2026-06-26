<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_amount',
        'status',
        'payment_method',
        'payment_status',
        'transaction_id',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_items')
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
    // ─── Business Rules ───────────────────────────────────────────────────────

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function hasPayments(): bool
    {
        return $this->payments()->exists();
    }

    // ─── Boot: Prevent deletion if order has payments ─────────────────────────

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (Order $order) {
            if ($order->hasPayments()) {
                throw new \Exception(
                    "Cannot delete order #{$order->id} because it has associated payments."
                );
            }
        });
    }
}
