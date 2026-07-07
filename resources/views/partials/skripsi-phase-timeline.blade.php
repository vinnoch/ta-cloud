@php
    $timelineSkripsi = $skripsiTimelineRecord ?? $skripsi ?? $activeSkripsi ?? null;
@endphp

@if ($timelineSkripsi)
    @php
        $phaseTimeline = [
            ['key' => 'proposal', 'label' => 'Proposal'],
            ['key' => 'sidang_proposal', 'label' => 'Sidang Proposal'],
            ['key' => 'bimbingan_skripsi', 'label' => 'Bimbingan Skripsi'],
            ['key' => 'sidang_skripsi', 'label' => 'Sidang Skripsi'],
            ['key' => 'revisi_sidang_skripsi', 'label' => 'Revisi Skripsi'],
            ['key' => 'review_dokumen_final', 'label' => 'Dokumen Final'],
            ['key' => 'skripsi_selesai', 'label' => 'Skripsi Selesai'],
        ];
        $normalizedPhase = str((string) ($timelineSkripsi->current_phase ?? ''))
            ->lower()
            ->replace(['-', ' '], '_')
            ->toString();
        $currentPhaseKey = match (true) {
            in_array($normalizedPhase, ['proposal', 'pengajuan_proposal'], true) => 'proposal',
            $normalizedPhase === 'sidang_proposal' => 'sidang_proposal',
            in_array($normalizedPhase, ['bimbingan', 'bimbingan_skripsi'], true) => 'bimbingan_skripsi',
            $normalizedPhase === 'sidang_skripsi' => 'sidang_skripsi',
            $normalizedPhase === 'revisi_sidang_skripsi' => 'revisi_sidang_skripsi',
            $normalizedPhase === 'review_dokumen_final' => 'review_dokumen_final',
            $normalizedPhase === 'skripsi_selesai' => 'skripsi_selesai',
            default => 'proposal',
        };

        $currentPhaseIndex = collect($phaseTimeline)->search(fn($item) => $item['key'] === $currentPhaseKey);
        $currentPhaseIndex = $currentPhaseIndex === false ? 0 : $currentPhaseIndex;
        $timelineTitle = $timelineTitle ?? 'Timeline Fase Skripsi';
    @endphp

    <section class="card">
        <div class="section-heading">
            <div>
                <h3>{{ $timelineTitle }}</h3>
            </div>
        </div>
        <div class="acss-phase-lanes">
            @foreach ($phaseTimeline as $index => $phaseItem)
                @php
                    $stateClass = $index < $currentPhaseIndex ? 'is-complete' : ($index === $currentPhaseIndex ? 'is-current' : '');
                @endphp
                <div class="acss-phase-lane {{ $stateClass }}">
                    <span class="acss-phase-lane__status">
                        {{ $index < $currentPhaseIndex ? 'Selesai' : ($index === $currentPhaseIndex ? 'Fase aktif' : 'Menunggu') }}
                    </span>
                    <div class="acss-phase-lane__items">
                        <div class="acss-phase-chip {{ $stateClass }}">
                            <div class="acss-phase-chip__icon">
                                @if ($index < $currentPhaseIndex)
                                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4.5 10.5 8 14l7.5-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </div>
                            <div>
                                <strong class="acss-phase-chip__title">{{ $phaseItem['label'] }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
