{{-- Status filter pills above the order list (Backpack's filters are a PRO add-on). --}}
@php
    $counts = \App\Models\Order::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
    $current = request('status');
@endphp
<div class="d-flex flex-wrap gap-2 mb-3 lc-status-tabs">
    <a href="{{ backpack_url('order') }}" class="btn btn-sm {{ $current ? 'btn-outline-primary' : 'btn-primary' }}">
        All <span class="badge bg-white text-dark ms-1">{{ $counts->sum() }}</span>
    </a>
    @foreach (\App\Models\Order::STATUSES as $key => $label)
        <a href="{{ backpack_url('order?status='.$key) }}" class="btn btn-sm {{ $current === $key ? 'btn-primary' : 'btn-outline-primary' }}">
            {{ $label }} <span class="badge {{ $current === $key ? 'bg-white text-dark' : 'bg-secondary-lt' }} ms-1">{{ $counts[$key] ?? 0 }}</span>
        </a>
    @endforeach
</div>
