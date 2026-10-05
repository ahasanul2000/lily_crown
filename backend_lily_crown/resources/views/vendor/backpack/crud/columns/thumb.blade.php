{{-- Thumbnail for an image stored either as a full URL or as a path on the public disk. --}}
@php
    $value = data_get($entry, $column['name']);
    if (is_array($value)) {
        $value = $value[0] ?? null;
    }
    $src = $value ? (\Illuminate\Support\Str::startsWith($value, ['http://', 'https://']) ? $value : \Storage::disk('public')->url($value)) : null;
@endphp
<span>
    @if ($src)
        <a href="{{ $src }}" target="_blank"><img src="{{ $src }}" style="height: {{ $column['height'] ?? '48px' }}; width: auto; border-radius: 4px; object-fit: cover;" alt=""></a>
    @else
        -
    @endif
</span>
