@extends('layouts.app')

@section('content')
    <section class="acss-page-card">
        <div class="acss-page-card__body acss-dashboard-header">
            <div>
                <h1 class="acss-page-title">Dashboard Kaprodi</h1>
                <p class="acss-muted ">Ringkasan monitoring dan fase skripsi mahasiswa.</p>
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
        $phaseMapping = [
            'Proposal' => route('kaprodi.skripsi.index', array_filter(['status' => 'Proposal', 'periode_id' => $selectedPeriodeId ?: null])),
            'Bimbingan Skripsi' => route('kaprodi.skripsi.index', array_filter(['status' => 'Bimbingan Skripsi', 'periode_id' => $selectedPeriodeId ?: null])),
            'Sidang Skripsi' => route('kaprodi.skripsi.index', array_filter(['status' => 'Sidang Skripsi', 'periode_id' => $selectedPeriodeId ?: null])),
            'Review Dokumen Final' => route('kaprodi.skripsi.index', array_filter(['status' => 'Review Dokumen Final', 'periode_id' => $selectedPeriodeId ?: null])),
            'Skripsi Selesai' => route('kaprodi.skripsi.index', array_filter(['status' => 'Skripsi Selesai', 'periode_id' => $selectedPeriodeId ?: null])),
        ];
        $phaseColors = [
            'Proposal' => '#94a3b8',
            'Bimbingan Skripsi' => '#2563eb',
            'Sidang Skripsi' => '#60a5fa',
            'Review Dokumen Final' => '#1d4ed8',
            'Skripsi Selesai' => '#0f172a',
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
                    'value' => $data['value'],
                    'color' => $phaseColors[$data['label']] ?? '#eee',
                    'path' => $describeArc(60, 60, 52, $currentAngle, $currentAngle + $angle),
                    'href' => $phaseMapping[$data['label']] ?? '#',
                ];
                $currentAngle += $angle;
            }
        }
    @endphp

    <section class="acss-section-card">
        <div class="acss-section-card__head">
            <div>
                <h3 class="acss-card-title">Distribusi Skripsi Aktif</h3>
                <p class="acss-muted ">Periode aktif dan distribusi fase skripsi saat ini.</p>
            </div>
        </div>

        <div class="acss-section-card__body py-8">
            @if ($totalVal > 0)
                <div class="acss-dashboard-chart-layout">
                    <div class="acss-dashboard-chart-wrap" id="pie-chart-wrap">
                        <svg viewBox="0 0 120 120" class="w-full h-full acss-pie-chart" aria-label="Distribusi skripsi aktif">
                            @foreach ($slices as $slice)
                                <a href="{{ $slice['href'] }}" class="acss-pie-slice-link" data-pie-hint="Klik untuk lihat data fase {{ $slice['label'] }}">
                                    <path d="{{ $slice['path'] }}" fill="{{ $slice['color'] }}" class="acss-pie-slice" data-slice-label="{{ $slice['label'] }}" data-slice-value="{{ $slice['value'] }}" />
                                </a>
                            @endforeach
                            <circle cx="60" cy="60" r="23" fill="#fff" />
                            <text x="60" y="55" text-anchor="middle" class="acss-pie-center-value">{{ $totalVal }}</text>
                            <text x="60" y="67" text-anchor="middle" class="acss-pie-center-label">Aktif</text>
                        </svg>
                        <div class="acss-dashboard-chart-hint" id="pie-chart-hint">Klik chart untuk lihat data fase.</div>
                    </div>

                    <div class="acss-dashboard-legend">
                        <small class="acss-dashboard-legend__meta">Periode {{ $periodes->firstWhere('id', $selectedPeriodeId)?->name ?? '-' }}</small>
                        @foreach ($chartData as $item)
                            @if (($item['value'] ?? 0) > 0)
                                <a href="{{ $phaseMapping[$item['label']] ?? '#' }}" class="acss-dashboard-legend__item" data-legend-label="{{ $item['label'] }}" data-legend-value="{{ $item['value'] }}">
                                    <span class="acss-dashboard-legend__dot" style="background-color: {{ $phaseColors[$item['label']] ?? '#eee' }}"></span>
                                    <span class="acss-dashboard-legend__text">{{ $item['label'] }}</span>
                                    <strong class="acss-dashboard-legend__value">{{ $item['value'] }}</strong>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @else
                <div class="empty-state">Belum ada data skripsi aktif pada periode ini.</div>
            @endif
        </div>
    </section>

    <script>
        (() => {
            const wrap = document.getElementById('pie-chart-wrap');
            const hint = document.getElementById('pie-chart-hint');
            const legendItems = document.querySelectorAll('[data-legend-label]');
            const slices = document.querySelectorAll('[data-slice-label]');
            
            if (!wrap || !hint) return;

            const setHoverState = (label, value) => {
                // Update slices
                slices.forEach(s => {
                    const isActive = s.dataset.sliceLabel === label;
                    s.classList.toggle('is-hovered', isActive);
                    if (isActive) {
                        hint.textContent = `${value} ${label}`;
                    }
                });
                // Update legend items
                legendItems.forEach(item => {
                    item.classList.toggle('is-hovered', item.dataset.legendLabel === label);
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
                    setHoverState(slice.dataset.sliceLabel, slice.dataset.sliceValue);
                }
            });

            wrap.addEventListener('mouseleave', resetHoverState);

            legendItems.forEach(item => {
                item.addEventListener('mouseenter', () => {
                    setHoverState(item.dataset.legendLabel, item.dataset.legendValue);
                });
                item.addEventListener('mouseleave', resetHoverState);
            });
        })();
    </script>
@endsection
