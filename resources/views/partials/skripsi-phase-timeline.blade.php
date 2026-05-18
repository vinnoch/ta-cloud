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
            ['key' => 'revisi_sidang_skripsi', 'label' => 'Revisi Sidang Skripsi'],
            ['key' => 'review_dokumen_final', 'label' => 'Review Dokumen Final'],
            ['key' => 'skripsi_selesai', 'label' => 'Skripsi Selesai'],
        ];
        $phaseGroups = [
            [['key' => 'proposal', 'label' => 'Proposal'], ['key' => 'sidang_proposal', 'label' => 'Sidang Proposal']],
            [['key' => 'bimbingan_skripsi', 'label' => 'Bimbingan Skripsi']],
            [['key' => 'sidang_skripsi', 'label' => 'Sidang Skripsi'], ['key' => 'revisi_sidang_skripsi', 'label' => 'Revisi Sidang Skripsi']],
            [['key' => 'review_dokumen_final', 'label' => 'Review Dokumen Final'], ['key' => 'skripsi_selesai', 'label' => 'Skripsi Selesai']],
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
            @foreach ($phaseGroups as $group)
                @php
                    $groupIndexes = collect($group)->map(
                        fn($phaseItem) => collect($phaseTimeline)->search(
                            fn($item) => $item['key'] === $phaseItem['key'],
                        ),
                    );
                    $groupStateClass = $groupIndexes->contains($currentPhaseIndex)
                        ? 'is-current'
                        : ($groupIndexes->max() < $currentPhaseIndex ? 'is-complete' : '');
                @endphp
                <div class="acss-phase-lane {{ $groupStateClass }}">
                    <span class="acss-phase-lane__status">
                        {{ $groupIndexes->contains($currentPhaseIndex) ? 'Fase aktif' : ($groupIndexes->max() < $currentPhaseIndex ? 'Selesai' : 'Menunggu') }}
                    </span>
                    <div class="acss-phase-lane__items">
                        @foreach ($group as $phaseItem)
                            @php
                                $index = collect($phaseTimeline)->search(
                                    fn($item) => $item['key'] === $phaseItem['key'],
                                );
                                $stateClass = $index < $currentPhaseIndex ? 'is-complete' : ($index === $currentPhaseIndex ? 'is-current' : '');
                            @endphp
                            <div class="acss-phase-chip {{ $stateClass }}">
                                <div class="acss-phase-chip__icon">{{ $index + 1 }}</div>
                                <div>
                                    <strong class="acss-phase-chip__title">{{ $phaseItem['label'] }}</strong>
                                    <span class="acss-phase-chip__meta">{{ $index < $currentPhaseIndex ? 'Selesai' : ($index === $currentPhaseIndex ? 'Sedang berjalan' : 'Belum dimulai') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
