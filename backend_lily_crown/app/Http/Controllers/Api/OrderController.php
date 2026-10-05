<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(private CheckoutService $checkout) {}

    /** Server-side price check for the checkout page (subtotal, coupon, shipping, total). */
    public function quote(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1|max:50',
            'items.*.product_id' => 'required|integer',
            'items.*.size' => 'nullable|string|max:50',
            'items.*.color' => 'nullable|string|max:50',
            'items.*.quantity' => 'required|integer|min:1|max:100',
            'coupon_code' => 'nullable|string|max:50',
            'shipping_area' => ['nullable', Rule::in(array_keys(CheckoutService::SHIPPING_AREAS))],
        ]);

        $quote = $this->checkout->quote($data['items'], $data['coupon_code'] ?? null, $data['shipping_area'] ?? null);

        return response()->json([
            'data' => [
                'items' => collect($quote['lines'])->map(fn ($l) => collect($l)->except('product'))->values(),
                'errors' => $quote['errors'],
                'coupon_code' => $quote['coupon']?->code,
                'coupon_error' => $quote['coupon_error'],
                'subtotal' => $quote['subtotal'],
                'discount' => $quote['discount'],
                'shipping_cost' => $quote['shipping_cost'],
                'total' => $quote['total'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => 'required|string|max:120',
            'customer_phone' => ['required', 'string', 'regex:/^(\+?88)?01[3-9]\d{8}$/'],
            'customer_email' => 'nullable|email|max:150',
            'shipping_address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'shipping_area' => ['required', Rule::in(array_keys(CheckoutService::SHIPPING_AREAS))],
            'note' => 'nullable|string|max:1000',
            'coupon_code' => 'nullable|string|max:50',
            'items' => 'required|array|min:1|max:50',
            'items.*.product_id' => 'required|integer',
            'items.*.size' => 'nullable|string|max:50',
            'items.*.color' => 'nullable|string|max:50',
            'items.*.quantity' => 'required|integer|min:1|max:100',
        ], [
            'customer_phone.regex' => 'Please enter a valid Bangladeshi mobile number (e.g. 01712345678).',
        ]);

        // Guests can order too; a valid customer token links the order to their account.
        $order = $this->checkout->placeOrder(
            collect($data)->except(['items', 'coupon_code'])->put('user_id', $request->user('sanctum')?->id)->all(),
            $data['items'],
            $data['coupon_code'] ?? null,
        );

        return response()->json([
            'message' => 'Order placed successfully',
            'data' => $order->toCustomerArray(),
        ], 201);
    }

    /** Customers track an order with its number plus the phone number used to place it. */
    public function track(Request $request)
    {
        $data = $request->validate([
            'order_number' => 'required|string|max:30',
            'phone' => 'required|string|max:20',
        ]);

        $phone = preg_replace('/\D/', '', $data['phone']);
        $order = Order::with('orderItems')
            ->where('order_number', strtoupper(trim($data['order_number'])))
            ->first();

        // Compare on the last 11 digits so "+8801..." and "01..." both match.
        $normalise = fn ($p) => substr(preg_replace('/\D/', '', (string) $p), -11);
        if (! $order || $normalise($order->customer_phone) !== $normalise($phone)) {
            return response()->json(['message' => 'No order found with that number and phone.'], 404);
        }

        return response()->json(['data' => $order->toCustomerArray()]);
    }
}
