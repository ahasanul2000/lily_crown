@extends(backpack_view('blank'))

@php
    use App\Models\Order;
    use App\Models\OrderItem;
    use App\Models\Product;
    use App\Models\User;
    use Illuminate\Support\Str;

    $img = fn ($v) => $v ? (Str::startsWith($v, ['http://', 'https://']) ? $v : \Storage::disk('public')->url($v)) : null;
    $taka = fn ($n) => '৳ '.number_format((float) $n);

    // Orders that still count as sales (not cancelled/returned).
    $live = fn () => Order::whereNotIn('status', Order::RESTOCKED_STATUSES);

    $todayOrders = $live()->whereDate('created_at', today());
    $stats = [
        [
            'label' => "Today's sales", 'value' => $taka((clone $todayOrders)->sum('total')),
            'sub' => (clone $todayOrders)->count().' orders today', 'icon' => 'la-shopping-bag', 'tone' => 'emerald',
            'link' => backpack_url('order'),
        ],
        [
            'label' => 'Needs action', 'value' => Order::where('status', 'pending')->count(),
            'sub' => 'pending orders to confirm', 'icon' => 'la-bell', 'tone' => 'gold',
            'link' => backpack_url('order?status=pending'),
        ],
        [
            'label' => 'This month', 'value' => $taka($live()->where('created_at', '>=', now()->startOfMonth())->sum('total')),
            'sub' => $live()->where('created_at', '>=', now()->startOfMonth())->count().' orders · '.now()->format('F'),
            'icon' => 'la-chart-line', 'tone' => 'blue', 'link' => backpack_url('order'),
        ],
        [
            'label' => 'Customers', 'value' => User::where('is_admin', false)->count(),
            'sub' => User::where('is_admin', false)->where('created_at', '>=', now()->subDays(30))->count().' joined in 30 days',
            'icon' => 'la-users', 'tone' => 'maroon', 'link' => backpack_url('customer'),
        ],
    ];

    // Sales per day for the last 14 days.
    $from = today()->subDays(13);
    $perDay = $live()->where('created_at', '>=', $from)->get(['total', 'created_at'])
        ->groupBy(fn ($o) => $o->created_at->toDateString())
        ->map(fn ($g) => $g->sum('total'));
    $days = collect(range(0, 13))->map(function ($i) use ($from, $perDay) {
        $date = $from->copy()->addDays($i);

        return ['date' => $date, 'total' => (float) ($perDay[$date->toDateString()] ?? 0)];
    });
    $maxDay = max(1, $days->max('total'));
    $periodTotal = $days->sum('total');

    $statusCounts = Order::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
    $statusTotal = max(1, $statusCounts->sum());
    $statusColors = [
        'pending' => 'bg-warning', 'confirmed' => 'bg-info', 'processing' => 'bg-primary', 'shipped' => 'bg-azure',
        'delivered' => 'bg-success', 'cancelled' => 'bg-danger', 'returned' => 'bg-secondary',
    ];

    $topProducts = OrderItem::selectRaw('product_id, product_name, product_image, sum(quantity) as qty, sum(line_total) as revenue')
        ->whereHas('order', fn ($q) => $q->whereNotIn('status', Order::RESTOCKED_STATUSES))
        ->groupBy('product_id', 'product_name', 'product_image')
        ->orderByDesc('qty')->limit(5)->get();

    $lowStock = Product::where('is_active', true)->get()
        ->filter(fn ($p) => $p->total_stock <= 5 || collect($p->variants)->contains(fn ($v) => $v['stock'] <= 2))
        ->sortBy('total_stock')->take(6);

    $recent = Order::withCount('orderItems')->latest('id')->limit(7)->get();
    $hour = (int) now()->format('H');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
@endphp

@section('content')
<div class="lc-welcome mb-4">
    <div>
        <h2>{{ $greeting }}, {{ Str::before(backpack_user()->name, ' ') }}</h2>
        <p>{{ now()->format('l, j F Y') }} · Here's how Lily Crown is doing.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ backpack_url('product/create') }}" class="btn"><i class="la la-plus me-1"></i> Add product</a>
        <a href="{{ backpack_url('order?status=pending') }}" class="btn"><i class="la la-clipboard-check me-1"></i> Pending orders</a>
        <a href="{{ config('backpack.ui.developer_link') }}" target="_blank" class="btn"><i class="la la-external-link-alt me-1"></i> View shop</a>
    </div>
</div>

<div class="row row-cards mb-4">
    @foreach ($stats as $stat)
        <div class="col-sm-6 col-xl-3">
            <a href="{{ $stat['link'] }}" class="card lc-stat">
                <span class="lc-stat-icon lc-tone-{{ $stat['tone'] }}"><i class="la {{ $stat['icon'] }}"></i></span>
                <span class="min-w-0">
                    <span class="lc-stat-label d-block">{{ $stat['label'] }}</span>
                    <span class="lc-stat-value d-block">{{ $stat['value'] }}</span>
                    <span class="lc-stat-sub d-block text-truncate">{{ $stat['sub'] }}</span>
                </span>
            </a>
        </div>
    @endforeach
</div>

<div class="row row-cards mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <div>
                    <h3 class="card-title mb-0">Sales — last 14 days</h3>
                    <div class="text-secondary small">{{ $taka($periodTotal) }} from orders that weren't cancelled</div>
                </div>
            </div>
            <div class="card-body">
                <div class="lc-chart" role="img" aria-label="Daily sales for the last 14 days">
                    @foreach ($days as $day)
                        <div class="lc-chart-col">
                            <div class="lc-chart-bar {{ $day['date']->isToday() ? 'is-today' : '' }}"
                                 style="height: {{ max(2, round($day['total'] / $maxDay * 100)) }}%"
                                 data-label="{{ $day['date']->format('j M') }}: {{ $taka($day['total']) }}"></div>
                            <span class="lc-chart-day">{{ $day['date']->format($loop->first || $day['date']->day === 1 ? 'j M' : 'j') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Orders by status</h3></div>
            <div class="card-body">
                @foreach (Order::STATUSES as $key => $label)
                    @php $count = $statusCounts[$key] ?? 0; @endphp
                    <a href="{{ backpack_url('order?status='.$key) }}" class="lc-status-row text-reset text-decoration-none">
                        <span style="width: 82px" class="small">{{ $label }}</span>
                        <div class="progress"><div class="progress-bar {{ $statusColors[$key] }}" style="width: {{ $count / $statusTotal * 100 }}%"></div></div>
                        <span class="fw-bold small" style="width: 24px; text-align: right">{{ $count }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="row row-cards mb-4">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h3 class="card-title">Recent orders</h3>
                <a href="{{ backpack_url('order') }}" class="btn btn-sm btn-outline-primary">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table mb-0">
                    <thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($recent as $order)
                        <tr>
                            <td>
                                <a href="{{ backpack_url('order/'.$order->id.'/show') }}" class="fw-bold">{{ $order->order_number }}</a>
                                <div class="text-secondary small">{{ $order->created_at?->diffForHumans() }}</div>
                            </td>
                            <td>{{ $order->customer_name }}<div class="text-secondary small">{{ $order->customer_phone }} · {{ $order->city }}</div></td>
                            <td>{{ $order->order_items_count }}</td>
                            <td class="fw-bold">{{ $taka($order->total) }}</td>
                            <td><span class="badge {{ $statusColors[$order->status] ?? 'bg-secondary' }} text-white">{{ $order->status_label }}</span></td>
                            <td class="text-end">
                                <a href="{{ backpack_url('order/'.$order->id.'/edit') }}" class="btn btn-sm btn-outline-primary">Update</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-secondary text-center py-5">No orders yet — they'll appear here as soon as customers check out.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Quick actions</h3></div>
            <div class="card-body">
                <div class="row g-2 lc-quick">
                    <div class="col-4"><a href="{{ backpack_url('product/create') }}"><i class="la la-tshirt"></i>New product</a></div>
                    <div class="col-4"><a href="{{ backpack_url('coupon/create') }}"><i class="la la-tags"></i>New coupon</a></div>
                    <div class="col-4"><a href="{{ backpack_url('hero-slide') }}"><i class="la la-image"></i>Banners</a></div>
                    <div class="col-4"><a href="{{ backpack_url('category') }}"><i class="la la-th-large"></i>Categories</a></div>
                    <div class="col-4"><a href="{{ backpack_url('customer') }}"><i class="la la-users"></i>Customers</a></div>
                    <div class="col-4"><a href="{{ backpack_url('site-setting') }}"><i class="la la-truck"></i>Delivery fees</a></div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Best sellers</h3></div>
            <div class="list-group list-group-flush">
                @forelse ($topProducts as $item)
                    <div class="list-group-item d-flex align-items-center gap-3">
                        @if ($src = $img($item->product_image))<img src="{{ $src }}" class="lc-thumb" alt="">@endif
                        <div class="flex-fill min-w-0">
                            <div class="text-truncate">{{ $item->product_name }}</div>
                            <div class="text-secondary small">{{ $item->qty }} sold · {{ $taka($item->revenue) }}</div>
                        </div>
                    </div>
                @empty
                    <div class="list-group-item text-secondary">No sales yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
        <h3 class="card-title">Low stock</h3>
        <a href="{{ backpack_url('product') }}" class="btn btn-sm btn-outline-primary">All products</a>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table mb-0">
            <thead><tr><th>Product</th><th>Sizes left</th><th>Total</th><th></th></tr></thead>
            <tbody>
            @forelse ($lowStock as $product)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            @if ($src = $img($product->image))<img src="{{ $src }}" class="lc-thumb" alt="">@endif
                            <span>{{ $product->name }}</span>
                        </div>
                    </td>
                    <td class="small">
                        @if ($product->hasVariants())
                            @foreach ($product->variants as $v)
                                <span class="lc-size {{ $v['stock'] == 0 ? 'is-out' : ($v['stock'] <= 2 ? 'is-low' : '') }}">{{ $v['size'] }}: {{ $v['stock'] }}</span>
                            @endforeach
                        @else
                            <span class="text-secondary">One size</span>
                        @endif
                    </td>
                    <td><span class="badge {{ $product->total_stock === 0 ? 'bg-danger' : 'bg-warning' }} text-white">{{ $product->total_stock }}</span></td>
                    <td class="text-end"><a href="{{ backpack_url('product/'.$product->id.'/edit') }}" class="btn btn-sm btn-outline-primary">Restock</a></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-secondary text-center py-4">All products are well stocked.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
