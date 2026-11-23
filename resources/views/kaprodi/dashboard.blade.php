@extends('layouts.app')

@section('content')
    <section class="card card--profile acss-skripsi-detail-topcompact">
        <div class="profile-card acss-dashboard-header">
            <div>
                <h1 class="acss-page-title">Dashboard Kaprodi</h1>
            </div>
            <form method="GET" action="{{ route('kaprodi.dashboard') }}" id="periode-switcher-form" class="acss-dashboard-header__form">
                <label class="form-field acss-field-tight">
                    <span>Periode Akademik</span>
                    <select name="periode_id" onchange="this.form.submit()">
                        @foreach ($periodes as $period)
                            <option value="{{ $period->id }}" {{ (int) $selectedPeriodeId === (int) $period->id ? 'selected' : '' }}>
                                {{ $period->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </form>
        </div>
    </section>

    <section class="acss-dashboard-metric-grid">
        @foreach ($stats as $index => $stat)
            @include('kaprodi.partials.dashboard-stat-card', [
                'label' => $stat['label'],
                'value' => $stat['value'],
                'hint' => $stat['hint'] ?? null,
                'featured' => $index === 0,
                'href' => $stat['href'] ?? null,
            ])
        @endforeach
    </section>

    @php
        $totalVal = collect($chartData)->sum('value');
        $phaseColors = [
            'proposal' => '#94a3b8',
            'bimbingan' => '#2563eb',
            'sidang' => '#60a5fa',
            'review_final' => '#1d4ed8',
            'selesai' => '#0f172a',
        ];

        $polarToCartesian = function (float $centerX, float $centerY, float $radius, float $angleInDegrees): array {
            $angleInRadians = deg2rad($angleInDegrees - 90);
            return [
                'x' => $centerX + ($radius * cos($angleInRadians)),
                'y' => $centerY + ($radius * sin($angleInRadians)),
            ];
        };

        $describeArc = function (float $x, float $y, float $radius, float $startAngle, float $endAngle) use ($polarToCartesian): string {
            $start = $polarToCartesian($x, $y, $radius, $endAngle);
            $end = $polarToCartesian($x, $y, $radius, $startAngle);
            $largeArcFlag = ($endAngle - $startAngle) <= 180 ? '0' : '1';
            return sprintf('M %s %s L %s %s A %s %s 0 %s 0 %s %s Z', $x, $y, $start['x'], $start['y'], $radius, $radius, $largeArcFlag, $end['x'], $end['y']);
        };

        $slices = [];
        if ($totalVal > 0) {
            $currentAngle = 0;
            foreach ($chartData as $data) {
                if ($data['value'] <= 0) {
                    continue;
                }
                $angle = ($data['value'] / $totalVal) * 360;
                $slices[] = [
                    'label' => $data['label'],
                    'key' => $data['key'],
                    'type_label' => $data['type_label'],
                    'value' => $data['value'],
                    'color' => $phaseColors[str($data['key'])->after(':')->toString()] ?? '#eee',
                    'path' => $describeArc(60, 60, 52, $currentAngle, $currentAngle + $angle),
                    'href' => $data['href'],
                ];
                $currentAngle += $angle;
            }
        }
    @endphp

    <section class="acss-section-card">
        <div class="acss-section-card__head">
            <div>
                <h3 class="acss-card-title">Distribusi Tugas Akhir Aktif</h3>
                <p class="acss-muted ">Distribusi fase Skripsi dan Non-Skripsi pada periode aktif.</p>
            </div>
        </div>

        <div class="acss-section-card__body py-8">
            @if ($totalVal > 0)
                <div class="acss-dashboard-chart-layout">
                    <div class="acss-dashboard-chart-wrap" id="pie-chart-wrap">
                        <svg viewBox="0 0 120 120" class="w-full h-full acss-pie-chart" aria-label="Distribusi tugas akhir aktif">
                            @foreach ($slices as $slice)
                                <a href="{{ $slice['href'] }}" class="acss-pie-slice-link" data-pie-hint="Klik untuk lihat data fase {{ $slice['label'] }}">
                                    <path d="{{ $slice['path'] }}" fill="{{ $slice['color'] }}" class="acss-pie-slice" data-slice-key="{{ $slice['key'] }}" data-slice-label="{{ $slice['label'] }}" data-slice-value="{{ $slice['value'] }}" />
                                </a>
                            @endforeach
                            <circle cx="60" cy="60" r="23" fill="#fff" />
                            <text x="60" y="55" text-anchor="middle" class="acss-pie-center-value">{{ $totalVal }}</text>
                            <text x="60" y="67" text-anchor="middle" class="acss-pie-center-label">Aktif</text>
                        </svg>
                        <div class="acss-dashboard-chart-hint" id="pie-chart-hint">Klik chart untuk lihat data fase.</div>
                    </div>

                    <div class="acss-dashboard-legend table-shell">
                        <small class="acss-dashboard-legend__meta">Periode {{ $periodes->firstWhere('id', $selectedPeriodeId)?->name ?? '-' }}</small>
                        <div class="table-shell__head table-shell__grid" style="--table-cols:minmax(8rem,1.5fr) minmax(7rem,1fr) minmax(4rem,.5fr);">
                            <span>Fase</span><span>Tipe</span><span>Jumlah</span>
                        </div>
                        @foreach ($chartData as $item)
                            @if (($item['value'] ?? 0) > 0)
                                <a href="{{ $item['href'] }}" class="table-shell__row table-shell__grid acss-dashboard-legend__item" style="--table-cols:minmax(8rem,1.5fr) minmax(7rem,1fr) minmax(4rem,.5fr);" data-legend-key="{{ $item['key'] }}" data-legend-label="{{ $item['label'] }}" data-legend-value="{{ $item['value'] }}">
                                    <span class="table-shell__cell acss-dashboard-legend__text">{{ $item['label'] }}</span>
                                    <span class="table-shell__cell"><span class="pill">{{ $item['type_label'] }}</span></span>
                                    <strong class="table-shell__cell acss-dashboard-legend__value">{{ $item['value'] }}</strong>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @else
                <div class="empty-state">Belum ada data tugas akhir aktif pada periode ini.</div>
            @endif
        </div>
    </section>

    <script>
        (() => {
            const wrap = document.getElementById('pie-chart-wrap');
            const hint = document.getElementById('pie-chart-hint');
            const legendItems = document.querySelectorAll('[data-legend-label]');
            const slices = document.querySelectorAll('[data-slice-key]');
            
            if (!wrap || !hint) return;

            const setHoverState = (key, label, value) => {
                // Update slices
                slices.forEach(s => {
                    const isActive = s.dataset.sliceKey === key;
                    s.classList.toggle('is-hovered', isActive);
                    if (isActive) {
                        hint.textContent = `${value} ${label}`;
                    }
                });
                // Update legend items
                legendItems.forEach(item => {
                    item.classList.toggle('is-hovered', item.dataset.legendKey === key);
                });
            };

            const resetHoverState = () => {
                slices.forEach(s => s.classList.remove('is-hovered'));
                legendItems.forEach(item => item.classList.remove('is-hovered'));
                hint.textContent = 'Klik chart untuk lihat data.';
            };

            wrap.addEventListener('mouseover', (event) => {
                const link = event.target.closest('[data-pie-hint]');
                if (link) {
                    const slice = link.querySelector('[data-slice-label]');
                    setHoverState(slice.dataset.sliceKey, slice.dataset.sliceLabel, slice.dataset.sliceValue);
                }
            });

            wrap.addEventListener('mouseleave', resetHoverState);

            legendItems.forEach(item => {
                item.addEventListener('mouseenter', () => {
                    setHoverState(item.dataset.legendKey, item.dataset.legendLabel, item.dataset.legendValue);
                });
                item.addEventListener('mouseleave', resetHoverState);
            });
        })();
    </script>
@endsection
