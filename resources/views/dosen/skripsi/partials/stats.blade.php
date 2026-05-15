<div class="stats-row">
    @foreach ($chartData as $stat)
        <div class="stat-card acss-stat-card-inline" data-filter-status="{{ str($stat['label'])->lower()->replace(' ', '_') }}">
            <span class="stat-card__label">{{ $stat['label'] }}</span>
            <span class="stat-card__value">{{ $stat['value'] }}</span>
        </div>
    @endforeach
</div>
