<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialOrder extends Model
{
    public const STATUSES = ['created' => 'Awaiting payment', 'confirmed' => 'Confirmed', 'packed' => 'Packed', 'shipped' => 'Out for delivery', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'];

    protected $guarded = [];
    protected $casts = ['subtotal' => 'float', 'bulk_saving' => 'float', 'discount' => 'float', 'delivery_fee' => 'float', 'total' => 'float',
        'paid_at' => 'datetime', 'cancelled_at' => 'datetime', 'delivered_at' => 'datetime'];

    public function items(): HasMany
    {
        return $this->hasMany(MaterialOrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRouteKeyName(): string
    {
        return 'order_no';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    /** Customers can cancel until the order is packed (a paid order is then refunded by the owner). */
    public function cancellable(): bool
    {
        return in_array($this->status, ['created', 'confirmed'], true);
    }
}
