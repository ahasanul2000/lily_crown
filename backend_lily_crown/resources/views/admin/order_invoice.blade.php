<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        body { font-family: -apple-system, Segoe UI, Roboto, Arial, sans-serif; color: #1f2937; margin: 0; padding: 32px; }
        .wrap { max-width: 760px; margin: 0 auto; }
        header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0b4d3c; padding-bottom: 16px; }
        h1 { margin: 0; color: #0b4d3c; font-size: 26px; }
        .muted { color: #6b7280; font-size: 13px; }
        .grid { display: flex; gap: 32px; margin: 24px 0; }
        .grid > div { flex: 1; }
        h3 { font-size: 12px; text-transform: uppercase; letter-spacing: .08em; color: #6b7280; margin: 0 0 6px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
        .r { text-align: right; }
        tfoot td { border: none; }
        .total td { font-weight: 700; font-size: 16px; border-top: 2px solid #0b4d3c; }
        .cod { margin-top: 24px; padding: 12px; background: #fef3c7; border-radius: 6px; font-weight: 600; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
<div class="wrap">
    <p class="no-print"><button onclick="window.print()">Print</button></p>
    <header>
        <div>
            <h1>{{ \App\Models\SiteSetting::getValue('brand_name', config('app.name')) }}</h1>
            <div class="muted">{{ \App\Models\SiteSetting::getValue('contact_address') }}</div>
            <div class="muted">{{ \App\Models\SiteSetting::getValue('contact_phone') }}</div>
        </div>
        <div style="text-align:right">
            <h1 style="font-size:20px">INVOICE</h1>
            <div><strong>{{ $order->order_number }}</strong></div>
            <div class="muted">{{ $order->created_at?->format('d M Y, h:i A') }}</div>
            <div class="muted">Status: {{ $order->status_label }}</div>
        </div>
    </header>

    <div class="grid">
        <div>
            <h3>Deliver to</h3>
            <div><strong>{{ $order->customer_name }}</strong></div>
            <div>{{ $order->customer_phone }}</div>
            <div>{{ $order->shipping_address }}</div>
            <div>{{ $order->city }} — {{ $areas[$order->shipping_area] ?? $order->shipping_area }}</div>
        </div>
        <div>
            <h3>Note</h3>
            <div>{{ $order->note ?: '—' }}</div>
        </div>
    </div>

    <table>
        <thead><tr><th>Item</th><th>Size</th><th>Colour</th><th class="r">Price</th><th class="r">Qty</th><th class="r">Total</th></tr></thead>
        <tbody>
        @foreach ($order->orderItems as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->size ?: '—' }}</td>
                <td>{{ $item->color ?: '—' }}</td>
                <td class="r">৳ {{ number_format($item->unit_price) }}</td>
                <td class="r">{{ $item->quantity }}</td>
                <td class="r">৳ {{ number_format($item->line_total) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="5" class="r">Subtotal</td><td class="r">৳ {{ number_format($order->subtotal) }}</td></tr>
            @if ($order->discount > 0)
                <tr><td colspan="5" class="r">Discount {{ $order->coupon_code ? '('.$order->coupon_code.')' : '' }}</td><td class="r">− ৳ {{ number_format($order->discount) }}</td></tr>
            @endif
            <tr><td colspan="5" class="r">Delivery charge</td><td class="r">৳ {{ number_format($order->shipping_cost) }}</td></tr>
            <tr class="total"><td colspan="5" class="r">Total</td><td class="r">৳ {{ number_format($order->total) }}</td></tr>
        </tfoot>
    </table>

    @if ($order->payment_method === 'cod' && $order->payment_status !== 'paid')
        <div class="cod">Cash on Delivery — collect ৳ {{ number_format($order->total) }}</div>
    @endif
</div>
</body>
</html>
