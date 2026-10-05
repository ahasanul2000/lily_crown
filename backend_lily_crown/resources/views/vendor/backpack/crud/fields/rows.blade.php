{{--
    Lightweight repeatable rows (Backpack's `repeatable` field is PRO-only).
    Usage: CRUD::field('variants')->type('rows')->columns(['size' => 'Size', 'stock' => ['label' => 'Stock', 'type' => 'number']])
    Submits as field[i][column]; an empty hidden input means "no rows" when every row is removed.
--}}
@php
    $columns = collect($field['columns'] ?? ['value' => 'Value'])->map(
        fn ($c, $key) => is_array($c) ? $c + ['label' => $key, 'type' => 'text'] : ['label' => $c, 'type' => 'text']
    );
    $value = old(square_brackets_to_dots($field['name'])) ?? $field['value'] ?? $field['default'] ?? [];
    if (is_string($value)) {
        $value = json_decode($value, true) ?: [];
    }
    $rows = collect($value)->map(fn ($r) => is_array($r) ? $r : [$columns->keys()->first() => $r])->values();
    $uid = 'rows_'.str_replace(['[', ']'], '_', $field['name']).'_'.uniqid();
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>

    <div id="{{ $uid }}" data-name="{{ $field['name'] }}">
        <input type="hidden" name="{{ $field['name'] }}" value="">
        <table class="table table-sm table-bordered mb-2">
            <thead>
                <tr>
                    @foreach ($columns as $col)
                        <th>{{ $col['label'] }}</th>
                    @endforeach
                    <th style="width: 1%"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $i => $row)
                    <tr>
                        @foreach ($columns as $key => $col)
                            <td>
                                <input type="{{ $col['type'] }}" class="form-control form-control-sm"
                                       data-col="{{ $key }}" @if($col['type'] === 'number') min="0" @endif
                                       name="{{ $field['name'] }}[{{ $i }}][{{ $key }}]" value="{{ $row[$key] ?? '' }}">
                            </td>
                        @endforeach
                        <td><button type="button" class="btn btn-sm btn-outline-danger" data-remove>&times;</button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <template>
            <tr>
                @foreach ($columns as $key => $col)
                    <td>
                        <input type="{{ $col['type'] }}" class="form-control form-control-sm"
                               data-col="{{ $key }}" @if($col['type'] === 'number') min="0" value="0" @endif>
                    </td>
                @endforeach
                <td><button type="button" class="btn btn-sm btn-outline-danger" data-remove>&times;</button></td>
            </tr>
        </template>
        <button type="button" class="btn btn-sm btn-outline-primary" data-add>
            <i class="la la-plus"></i> {{ $field['add_label'] ?? 'Add row' }}
        </button>
    </div>

    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

<script>
    (function () {
        const root = document.getElementById(@json($uid));
        const tbody = root.querySelector('tbody');
        const name = root.dataset.name;

        function renumber() {
            tbody.querySelectorAll('tr').forEach((tr, i) => {
                tr.querySelectorAll('[data-col]').forEach((input) => {
                    input.name = `${name}[${i}][${input.dataset.col}]`;
                });
            });
        }

        root.querySelector('[data-add]').addEventListener('click', () => {
            tbody.appendChild(root.querySelector('template').content.cloneNode(true));
            renumber();
            tbody.querySelector('tr:last-child input')?.focus();
        });

        tbody.addEventListener('click', (e) => {
            if (e.target.closest('[data-remove]')) {
                e.target.closest('tr').remove();
                renumber();
            }
        });
    })();
</script>
