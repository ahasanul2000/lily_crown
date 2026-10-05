<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use CrudTrait;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'max_discount',
        'usage_limit',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'value' => 'float',
        'min_order_amount' => 'float',
        'max_discount' => 'float',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(fn (Coupon $coupon) => $coupon->code = strtoupper(trim($coupon->code)));
    }

    /** Returns an error message if the coupon can't be used for this subtotal, or null if it can. */
    public function validationError(float $subtotal): ?string
    {
        if (! $this->is_active) {
            return 'This coupon is not active.';
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'This coupon is not active yet.';
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return 'This coupon has expired.';
        }
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return 'This coupon has reached its usage limit.';
        }
        if ($subtotal < $this->min_order_amount) {
            return 'Minimum order amount for this coupon is ৳'.number_format($this->min_order_amount).'.';
        }

        return null;
    }

    public function discountFor(float $subtotal): float
    {
        $discount = $this->type === 'fixed'
            ? $this->value
            : $subtotal * $this->value / 100;

        if ($this->max_discount !== null && $this->max_discount > 0) {
            $discount = min($discount, $this->max_discount);
        }

        return round(min($discount, $subtotal), 2);
    }
}
