@php
    $colsClass = match ($cols ?? '') {
        '0.45fr 0.95fr 1fr 0.95fr 0.7fr 0.9fr' => 'acss-table-cols-docs',
        '1.4fr 1.2fr 0.8fr 0.8fr' => 'acss-table-cols-library',
        '1fr 1fr 0.8fr 0.8fr' => 'acss-table-cols-admin-four',
        '1.5fr 1fr 0.9fr 0.9fr' => 'acss-table-cols-queue',
        '1fr 0.8fr 0.8fr 0.8fr' => 'acss-table-cols-admin-template',
        '1fr 1fr 0.8fr' => 'acss-table-cols-admin-three',
        '1fr 1fr 1fr 0.8fr' => 'acss-table-cols-admin-access',
        '0.8fr 1fr 0.8fr 0.8fr' => 'acss-table-cols-periode',
        default => 'acss-table-auto-cols',
    };
@endphp
<div class="table-shell">
    @if (! isset($rows) || count($rows) > 0)
    <div class="table-shell__head table-shell__grid {{ $colsClass }}">
        @foreach ($columns as $column)
            <span>{{ $column }}</span>
        @endforeach
    </div>
    @foreach ($rows as $row)
        <div class="table-shell__row table-shell__grid {{ $colsClass }}">
            @foreach ($row as $cell)
                <div class="table-shell__cell">{!! $cell !!}</div>
            @endforeach
        </div>
    @endforeach
</div>
