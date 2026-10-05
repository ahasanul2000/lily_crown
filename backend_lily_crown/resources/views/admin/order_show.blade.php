@extends(backpack_view('blank'))

{{-- Order detail page: items on the left, customer / delivery / status actions on the right. --}}
@php
    use App\Models\Order;
    use App\Services\CheckoutService;
    use Illuminate\Support\Str;

    $order = $entry->loadMissing('orderItems', 'user');
    $img = fn ($v) => $v ? (Str::startsWith($v, ['http://', 'https://']) ? $v : \Storage::disk('public')->url($v)) : null;
    $taka = fn ($n) => '৳ '.number_format((float) $n);
    $statusColors = [
        'pending' => 'bg-warning', 'confirmed' => 'bg-info', 'processing' => 'bg-primary', 'shipped' => 'bg-azure',
        'delivered' => 'bg-success', 'cancelled' => 'bg-danger', 'returned' => 'bg-secondary',
    ];
    // The usual next step(s) from each status, shown as one-click buttons.
    $nextSteps = [
        'pending' => ['confirmed' => 'Confirm order', 'cancelled' => 'Cancel'],
        'confirmed' => ['processing' => 'Start packing', 'cancelled' => 'Cancel'],
        'processing' => ['shipped' => 'Mark shipped', 'cancelled' => 'Cancel'],
        'shipped' => ['delivered' => 'Mark delivered', 'returned' => 'Returned'],
        'delivered' => ['returned' => 'Mark returned'],
        'cancelled' => ['pending' => 'Reopen order'],
        'returned' => [],
    ][$order->status] ?? [];
    $phoneLink = preg_replace('/\D/', '', (string) $order->customer_phone);
    $waNumber = Str::startsWith($phoneLink, '880') ? $phoneLink : '88'.$phoneLink;

    $breadcrumbs = [
        trans('backpack::crud.admin') => backpack_url('dashboard'),
        'Orders' => url($crud->route),
        $order->order_number => false,
    ];
@endphp

@section('header')
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center gap-3 my-3 d-print-none">
        <section class="header-operation d-flex flex-wrap align-items-center gap-3">
            <h1 class="mb-0">{{ $order->order_number }}</h1>
            <span class="badge {{ $statusColors[$order->status] ?? 'bg-secondary' }} text-white fs-5">{{ $order->status_label }}</span>
            <span class="text-secondary">Placed {{ $order->created_at?->format('j M Y, g:i A') }} · {{ $order->created_at?->diffForHumans() }}</span>
        </section>
        <div class="d-flex gap-2">
            <a href="{{ url($crud->route) }}" class="btn btn-outline-primary"><i class="la la-angle-left"></i> All orders</a>
            <a href="{{ url($crud->route.'/'.$order->id.'/invoice') }}" target="_blank" class="btn btn-outline-primary"><i class="la la-print"></i> Invoice</a>
            <a href="{{ url($crud->route.'/'.$order->id.'/edit') }}" class="btn btn-primary"><i class="la la-edit"></i> Edit</a>
        </div>
    </div>
@endsection

@section('content')
<div class="row row-cards">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Items ({{ $order->orderItems->sum('quantity') }})</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table mb-0">
                    <thead><tr><th>Product</th><th>Size</th><th>Colour</th><th class="text-end">Price</th><th class="text-end">Qty</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    @foreach ($order->orderItems as $item)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if ($src = $img($item->product_image))<img src="{{ $src }}" class="lc-thumb" alt="">@endif
                                    @if ($item->product_id)
                                        <a href="{{ backpack_url('product/'.$item->product_id.'/edit') }}">{{ $item->product_name }}</a>
                                    @else
                                        {{ $item->product_name }}
                                    @endif
                                </div>
                            </td>
                            <td>{!! $item->size ? '<span class="lc-size">'.e($item->size).'</span>' : '—' !!}</td>
                            <td>{{ $item->color ?: '—' }}</td>
                            <td class="text-end">{{ $taka($item->unit_price) }}</td>
                            <td class="text-end">{{ $item->quantity }}</td>
                            <td class="text-end fw-bold">{{ $taka($item->line_total) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body border-top">
                <div class="ms-auto" style="max-width: 320px">
                    <div class="d-flex justify-content-between py-1"><span class="text-secondary">Subtotal</span><span>{{ $taka($order->subtotal) }}</span></div>
                    @if ($order->discount > 0)
                        <div class="d-flex justify-content-between py-1"><span class="text-secondary">Discount @if($order->coupon_code)<span class="badge bg-gold-lt">{{ $order->coupon_code }}</span>@endif</span><span class="text-danger">− {{ $taka($order->discount) }}</span></div>
                    @endif
                    <div class="d-flex justify-content-between py-1"><span class="text-secondary">Delivery ({{ CheckoutService::SHIPPING_AREAS[$order->shipping_area] ?? '—' }})</span><span>{{ $order->shipping_cost > 0 ? $taka($order->shipping_cost) : 'Free' }}</span></div>
                    <div class="d-flex justify-content-between pt-2 mt-2 border-top fs-3 fw-bold"><span>Total</span><span>{{ $taka($order->total) }}</span></div>
                    <div class="text-secondary small text-end">Cash on Delivery · {{ Order::PAYMENT_STATUSES[$order->payment_status] ?? $order->payment_status }}</div>
                </div>
            </div>
        </div>

        @if ($order->note || $order->admin_note)
            <div class="card mb-4">
                <div class="card-body">
                    @if ($order->note)
                        <h4 class="mb-1"><i class="la la-comment text-secondary"></i> Customer note</h4>
                        <p class="mb-3">{{ $order->note }}</p>
                    @endif
                    @if ($order->admin_note)
                        <h4 class="mb-1"><i class="la la-lock text-secondary"></i> Admin note</h4>
                        <p class="mb-0">{{ $order->admin_note }}</p>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Update order</h3></div>
            <div class="card-body">
                @if ($nextSteps)
                    <div class="d-grid gap-2 mb-3">
                        @foreach ($nextSteps as $status => $label)
                            <form method="POST" action="{{ url($crud->route.'/'.$order->id.'/status') }}"
                                  @if (in_array($status, Order::RESTOCKED_STATUSES)) onsubmit="return confirm('{{ $label }} {{ $order->order_number }}? The items will go back into stock.')" @endif>
                                @csrf
                                <input type="hidden" name="status" value="{{ $status }}">
                                <button class="btn w-100 {{ in_array($status, Order::RESTOCKED_STATUSES) ? 'btn-outline-danger' : 'btn-primary' }}">{{ $label }}</button>
                            </form>
                        @endforeach
                    </div>
                @endif
                <form method="POST" action="{{ url($crud->route.'/'.$order->id.'/status') }}">
                    @csrf
                    <input type="hidden" name="payment_status" value="{{ $order->payment_status === 'paid' ? 'unpaid' : 'paid' }}">
                    <button class="btn w-100 {{ $order->payment_status === 'paid' ? 'btn-outline-secondary' : 'btn-success' }}">
                        <i class="la la-money-bill me-1"></i>
                        {{ $order->payment_status === 'paid' ? 'Mark as unpaid' : 'Cash received — mark paid' }}
                    </button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Customer</h3></div>
            <div class="card-body">
                <div class="fw-bold fs-4">{{ $order->customer_name }}</div>
                <div class="text-secondary mb-3">
                    @if ($order->user)
                        <a href="{{ backpack_url('customer/'.$order->user_id.'/show') }}"><i class="la la-user-check"></i> Registered customer</a>
                    @else
                        Guest checkout
                    @endif
                </div>
                <div class="d-flex flex-column gap-2">
                    <a href="tel:{{ $order->customer_phone }}" class="btn btn-outline-primary justify-content-start"><i class="la la-phone me-2"></i> {{ $order->customer_phone }}</a>
                    <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode('Assalamu Alaikum '.$order->customer_name.', your Lily Crown order '.$order->order_number.' is '.strtolower($order->status_label).'.') }}" target="_blank" class="btn btn-outline-success justify-content-start"><i class="la la-whatsapp me-2"></i> WhatsApp</a>
                    @if ($order->customer_email)
                        <a href="mailto:{{ $order->customer_email }}" class="btn btn-outline-secondary justify-content-start text-truncate"><i class="la la-envelope me-2"></i> {{ $order->customer_email }}</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Delivery address</h3></div>
            <div class="card-body">
                <p class="mb-1">{{ $order->shipping_address }}</p>
                <p class="mb-0 text-secondary">{{ $order->city }} · {{ CheckoutService::SHIPPING_AREAS[$order->shipping_area] ?? '' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
