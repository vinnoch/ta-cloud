@extends('layouts.app')

@section('content')
    @if ($activeSkripsi)
        <section class="card card--profile">
            <div class="profile-card">
                <div class="profile-card__avatar">{{ mb_substr($activeSkripsi->student->name ?? 'M', 0, 1) }}</div>
                <div class="profile-card__main">
                    <div class="profile-card__meta">
                        <div>
                            <h2>{{ $activeSkripsi->student->name ?? '-' }}</h2>
                            <p>{{ $activeSkripsi->student->nim ?? '-' }} • {{ $activeSkripsi->periode?->name ?? ($activeSkripsi->periode?->kode_periode ?? '-') }}</p>
                            <div class="acss-quote-title">{{ $activeSkripsi->title }}</div>
                        </div>
                        <div class="acss-profile-badges">
                            <span class="status-pill">{{ str($activeSkripsi->current_phase)->replace(['_', '-'], ' ')->upper() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @include('partials.skripsi-phase-timeline', ['skripsiTimelineRecord' => $activeSkripsi, 'timelineTitle' => 'Timeline Fase Tugas Akhir'])

        <div class="acss-stack-sections ">
            <section class="acss-section-card">
                <div class="acss-section-card__head">
                    <div><h3 class="acss-card-title">Histori Bimbingan Terakhir</h3></div>
                </div>
                <div class="acss-section-card__body">
                    <div class="table-shell">
                        @forelse($activeSkripsi->bimbingans()->with(['reviewer', 'reviewedVersion'])->latest('meeting_date')->limit(5)->get() as $bimbingan)
                            @if ($loop->first)
                                <div class="table-shell__head table-shell__grid acss-table-cols-mhs-skripsi-bimbingan">
                                    <span>Tanggal</span>
                                    <span>Dosen</span>
                                    <span>Catatan</span>
                                    <span>Dokumen</span>
                                </div>
                            @endif
                            <div class="table-shell__row table-shell__grid acss-table-cols-mhs-skripsi-bimbingan acss-hover-row-group">
                                <div class="table-shell__cell">
                                    <strong>{{ $bimbingan->meeting_date->format('d/m/Y') }}</strong>
                                    <small class="acss-time-sub">{{ $bimbingan->meeting_date->format('H:i') }}</small>

                                </div>
                                <div class="table-shell__cell">{{ $bimbingan->reviewer->name }}</div>
                                <div class="table-shell__cell">{{ Str::limit($bimbingan->lecturer_notes ?? '-', 60) }}</div>
                                <div class="table-shell__cell">
                                    @if($bimbingan->has_revision_file)
                                        <button class="text-link acss-action-link acss-preview-link-inline" type="button" data-preview-open data-preview-url="{{ $bimbingan->revision_file_url }}" data-preview-title="{{ $bimbingan->reviewedVersion?->file_path ? basename($bimbingan->reviewedVersion->file_path) : 'Dokumen' }}">
                                            @include('partials.icons.file')
                                            <span>Dokumen</span>
                                        </button>
                                    @else
                                        <span class="acss-muted text-xs italic">Mahasiswa belum submit</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">Belum ada histori bimbingan.</div>
                        @endforelse
                    </div>
                    <div class="acss-link-gap-top">
                        <a href="{{ route('mahasiswa.skripsi.bimbingan.index', $activeSkripsi) }}" class="acss-link-subtle">Lihat Semua Histori Bimbingan</a>
                    </div>
                </div>
            </section>

            <section class="acss-section-card">
                <div class="acss-section-card__head">
                    <div><h3 class="acss-card-title">Dosen Pembimbing</h3></div>
                </div>
                <div class="acss-section-card__body">
                    <div class="table-shell">
                        @forelse($activeSkripsi->assignments()->with('lecturer')->get() as $assignment)
                            @if ($loop->first)
                                <div class="table-shell__head table-shell__grid acss-table-cols-mhs-skripsi-reviewers">
                                    <span>Peran</span>
                                    <span>Nama Dosen</span>
                                    <span>Assigned</span>
                                </div>
                            @endif
                            <div class="table-shell__row table-shell__grid acss-table-cols-mhs-skripsi-reviewers acss-hover-row-group">
                                <div class="table-shell__cell">
                                    <strong>{{ str($assignment->role_type)->replace('_', ' ')->title() }}</strong>
                                    <div class="acss-row-actions">
                                        <a href="{{ route('mahasiswa.skripsi.bimbingan.index', ['skripsi' => $activeSkripsi->id, 'reviewer_id' => $assignment->lecturer_id]) }}" class="acss-action-link">
                                            @include('partials.icons.eye')
                                            <span>Histori Bimbingan</span>
                                        </a>
                                    </div>
                                </div>
                                <div class="table-shell__cell">{{ $assignment->lecturer->name }}</div>
                                <div class="table-shell__cell">
                                    <strong>{{ $assignment->created_at?->format('d/m/Y') ?? '-' }}</strong>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">Belum ada reviewer ditetapkan.</div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    @else
        @if (isset($completedSkripsi) && $completedSkripsi)
            <div class="notice notice--success">Selamat! Skripsi Anda telah selesai.</div>

            <section class="card card--profile">
                <div class="profile-card">
                    <div class="profile-card__avatar">{{ mb_substr($completedSkripsi->student->name ?? 'M', 0, 1) }}</div>
                    <div class="profile-card__main">
                        <div class="profile-card__meta">
                            <div>
                                <h2>{{ $completedSkripsi->student->name ?? '-' }}</h2>
                                <p>{{ $completedSkripsi->student->nim ?? '-' }} • {{ $completedSkripsi->periode?->name ?? ($completedSkripsi->periode?->kode_periode ?? '-') }}</p>
                                <div class="acss-quote-title">{{ $completedSkripsi->title }}</div>
                            </div>
                            <div class="acss-profile-badges">
                                <span class="status-pill">{{ str($completedSkripsi->current_phase)->replace(['_', '-'], ' ')->upper() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="acss-form-actions">
                <a href="{{ route('mahasiswa.skripsi.show', $completedSkripsi) }}" class="button button--primary button--inline">Lihat Detail Skripsi</a>
            </div>
        @else
            <section class="acss-section-card">
                <div class="acss-section-card__head">
                    <div>
                        <h1 class="acss-page-title">Skripsi Tidak Ditemukan</h1>
                        <p class="acss-muted">Silakan buat pengajuan skripsi baru untuk memulai.</p>
                    </div>
                </div>
                <div class="acss-section-card__body">
                    <div class="acss-form-actions">
                        <a href="{{ route('mahasiswa.skripsi.create') }}" class="button button--success button--inline">Ajukan Skripsi Baru</a>
                    </div>
                </div>
            </section>
        @endif
    @endif

    @include('partials.pdf-viewer-modal')
    @include('mahasiswa.bimbingan.partials.revision-upload-script', ['readOnly' => true])
@endsection
