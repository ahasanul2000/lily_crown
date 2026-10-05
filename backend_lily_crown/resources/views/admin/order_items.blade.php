{{-- Line items + totals for an order. Used in the admin show/edit screens. --}}
@php
    $order = $entry;
    $img = fn ($v) => $v ? (\Illuminate\Support\Str::startsWith($v, ['http://', 'https://']) ? $v : \Storage::disk('public')->url($v)) : null;
@endphp
<div class="table-responsive">
    <table class="table table-sm table-vcenter mb-0">
        <thead>
            <tr><th></th><th>Product</th><th>Size</th><th>Colour</th><th class="text-end">Price</th><th class="text-end">Qty</th><th class="text-end">Total</th></tr>
        </thead>
        <tbody>
            @foreach ($order->orderItems as $item)
                <tr>
                    <td style="width:56px">@if ($src = $img($item->product_image))<img src="{{ $src }}" style="height:48px;border-radius:4px" alt="">@endif</td>
                    <td>
                        @if ($item->product_id)
                            <a href="{{ backpack_url('product/'.$item->product_id.'/show') }}">{{ $item->product_name }}</a>
                        @else
                            {{ $item->product_name }}
                        @endif
                    </td>
                    <td>{{ $item->size ?: '—' }}</td>
                    <td>{{ $item->color ?: '—' }}</td>
                    <td class="text-end">৳ {{ number_format($item->unit_price) }}</td>
                    <td class="text-end">{{ $item->quantity }}</td>
                    <td class="text-end">৳ {{ number_format($item->line_total) }}</td>
                </tr>
            @endforeach
            @if ($order->orderItems->isEmpty() && is_array($order->items))
                <tr><td colspan="7"><pre class="mb-0">{{ json_encode($order->items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></td></tr>
            @endif
        </tbody>
        <tfoot>
            <tr><td colspan="6" class="text-end">Subtotal</td><td class="text-end">৳ {{ number_format($order->subtotal) }}</td></tr>
            @if ($order->discount > 0)
                <tr><td colspan="6" class="text-end">Discount {{ $order->coupon_code ? '('.$order->coupon_code.')' : '' }}</td><td class="text-end text-danger">− ৳ {{ number_format($order->discount) }}</td></tr>
            @endif
            <tr><td colspan="6" class="text-end">Delivery</td><td class="text-end">৳ {{ number_format($order->shipping_cost) }}</td></tr>
            <tr><td colspan="6" class="text-end fw-bold">Total (collect on delivery)</td><td class="text-end fw-bold">৳ {{ number_format($order->total) }}</td></tr>
        </tfoot>
    </table>
</div>
