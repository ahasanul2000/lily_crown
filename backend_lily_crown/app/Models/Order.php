<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use CrudTrait;

    public const STATUSES = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'returned' => 'Returned',
    ];

    public const PAYMENT_STATUSES = [
        'unpaid' => 'Unpaid',
        'paid' => 'Paid',
        'refunded' => 'Refunded',
    ];

    /** Statuses in which the ordered stock is back on the shelf. */
    public const RESTOCKED_STATUSES = ['cancelled', 'returned'];

    protected $fillable = [
        'user_id',
        'order_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'city',
        'shipping_area',
        'note',
        'admin_note',
        'subtotal',
        'discount',
        'shipping_cost',
        'total',
        'coupon_code',
        'payment_method',
        'payment_status',
        'status',
        'items',
    ];

    protected $casts = [
        'items' => 'array',
        'subtotal' => 'float',
        'discount' => 'float',
        'shipping_cost' => 'float',
        'total' => 'float',
    ];

    protected static function booted(): void
    {
        static::created(function (Order $order) {
            if (! $order->order_number) {
                $order->order_number = 'LC'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);
                $order->saveQuietly();
            }
        });

        // Put stock back when an order is cancelled/returned, and take it again if reopened.
        static::updated(function (Order $order) {
            if (! $order->wasChanged('status')) {
                return;
            }

            $wasRestocked = in_array($order->getOriginal('status'), self::RESTOCKED_STATUSES, true);
            $isRestocked = in_array($order->status, self::RESTOCKED_STATUSES, true);

            if ($wasRestocked === $isRestocked) {
                return;
            }

            $direction = $isRestocked ? 1 : -1;

            foreach ($order->orderItems()->with('product')->get() as $item) {
                $item->product?->adjustStock($item->size, $direction * $item->quantity);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    /** Shape shown to the customer (confirmation, tracking, account history). */
    public function toCustomerArray(): array
    {
        $order = $this->loadMissing('orderItems');

        return [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_label' => $order->status_label,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'shipping_address' => $order->shipping_address,
            'city' => $order->city,
            'shipping_area' => $order->shipping_area,
            'subtotal' => $order->subtotal,
            'discount' => $order->discount,
            'shipping_cost' => $order->shipping_cost,
            'total' => $order->total,
            'coupon_code' => $order->coupon_code,
            'created_at' => $order->created_at?->toIso8601String(),
            'items' => $order->orderItems->map->only([
                'product_id', 'product_name', 'product_image', 'size', 'color', 'unit_price', 'quantity', 'line_total',
            ])->values(),
        ];
    }
}
