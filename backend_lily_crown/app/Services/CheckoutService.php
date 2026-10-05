<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Prices a cart entirely from the database so the client can never set its own prices,
 * and turns a priced cart into an order with stock and coupon usage reserved atomically.
 */
class CheckoutService
{
    public const SHIPPING_AREAS = [
        'inside_dhaka' => 'Inside Dhaka',
        'outside_dhaka' => 'Outside Dhaka',
    ];

    /**
     * @param  array<int, array{product_id:int, size?:string|null, color?:string|null, quantity:int}>  $items
     */
    public function quote(array $items, ?string $couponCode, ?string $shippingArea, bool $lockRows = false): array
    {
        $lines = [];
        $errors = [];

        $productIds = collect($items)->pluck('product_id')->unique()->all();
        $query = Product::with('category')->whereIn('id', $productIds);
        if ($lockRows) {
            $query->lockForUpdate();
        }
        $products = $query->get()->keyBy('id');

        // Total requested per product+size, so two cart lines can't jointly oversell.
        $requested = [];

        foreach ($items as $index => $item) {
            $product = $products->get($item['product_id']);
            $qty = max(1, (int) $item['quantity']);
            $size = $item['size'] ?? null;
            $color = $item['color'] ?? null;

            if (! $product || ! $product->is_active) {
                $errors["items.$index"] = 'A product in your bag is no longer available.';

                continue;
            }

            if ($product->hasVariants()) {
                if (! $size || ! collect($product->variants)->contains('size', $size)) {
                    $errors["items.$index"] = "Please choose a size for {$product->name}.";

                    continue;
                }
            } else {
                $size = null;
            }

            $colorNames = collect($product->colors ?? [])->pluck('name');
            if ($colorNames->isNotEmpty() && $color && ! $colorNames->contains($color)) {
                $errors["items.$index"] = "Selected colour is not available for {$product->name}.";

                continue;
            }

            $key = $product->id.'|'.$size;
            $requested[$key] = ($requested[$key] ?? 0) + $qty;
            $available = $product->stockFor($size);

            if ($requested[$key] > $available) {
                $label = $product->name.($size ? " ($size)" : '');
                $errors["items.$index"] = $available > 0
                    ? "Only $available left of $label."
                    : "$label is out of stock.";

                continue;
            }

            $unit = $product->final_price;
            $lines[] = [
                'product' => $product,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_image' => $product->image,
                'size' => $size,
                'color' => $color,
                'unit_price' => $unit,
                'quantity' => $qty,
                'line_total' => round($unit * $qty, 2),
            ];
        }

        $subtotal = round(collect($lines)->sum('line_total'), 2);

        $discount = 0.0;
        $coupon = null;
        $couponError = null;
        if (filled($couponCode)) {
            $coupon = Coupon::where('code', strtoupper(trim($couponCode)))->first();
            if (! $coupon) {
                $couponError = 'Invalid coupon code.';
            } elseif ($error = $coupon->validationError($subtotal)) {
                $couponError = $error;
                $coupon = null;
            } else {
                $discount = $coupon->discountFor($subtotal);
            }
        }

        $shipping = $this->shippingCost($shippingArea, $subtotal - $discount);

        return [
            'lines' => $lines,
            'errors' => $errors,
            'coupon' => $coupon,
            'coupon_error' => $couponError,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping_cost' => $shipping,
            'total' => round(max(0, $subtotal - $discount) + $shipping, 2),
        ];
    }

    public function shippingCost(?string $area, float $amount): float
    {
        $threshold = (float) SiteSetting::getValue('free_shipping_threshold', 0);
        if ($threshold > 0 && $amount >= $threshold) {
            return 0.0;
        }

        return $area === 'outside_dhaka'
            ? (float) SiteSetting::getValue('shipping_outside_dhaka', 130)
            : (float) SiteSetting::getValue('shipping_inside_dhaka', 70);
    }

    public function placeOrder(array $customer, array $items, ?string $couponCode): Order
    {
        return DB::transaction(function () use ($customer, $items, $couponCode) {
            $quote = $this->quote($items, $couponCode, $customer['shipping_area'], lockRows: true);

            if ($quote['errors']) {
                throw ValidationException::withMessages($quote['errors']);
            }
            if ($quote['coupon_error']) {
                throw ValidationException::withMessages(['coupon_code' => $quote['coupon_error']]);
            }
            if (! $quote['lines']) {
                throw ValidationException::withMessages(['items' => 'Your bag is empty.']);
            }

            $order = Order::create([
                ...$customer,
                'subtotal' => $quote['subtotal'],
                'discount' => $quote['discount'],
                'shipping_cost' => $quote['shipping_cost'],
                'total' => $quote['total'],
                'coupon_code' => $quote['coupon']?->code,
                'payment_method' => 'cod',
                'payment_status' => 'unpaid',
                'status' => 'pending',
            ]);

            foreach ($quote['lines'] as $line) {
                $order->orderItems()->create(collect($line)->except('product')->all());
                $line['product']->adjustStock($line['size'], -$line['quantity']);
            }

            if ($quote['coupon']) {
                // Re-check the limit atomically so concurrent orders can't exceed it.
                $claimed = Coupon::whereKey($quote['coupon']->id)
                    ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
                    ->increment('used_count');
                if (! $claimed) {
                    throw ValidationException::withMessages(['coupon_code' => 'This coupon has reached its usage limit.']);
                }
            }

            return $order->load('orderItems');
        });
    }
}
