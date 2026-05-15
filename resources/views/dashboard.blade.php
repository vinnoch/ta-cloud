@extends('layouts.app')

@section('content')
    <section class="hero-panel">
        <div class="hero-panel__copy">
            <p class="eyebrow">TA Cloud Frontend Preview</p>
            <h2>Workflow skripsi digital yang rapi, terukur, dan siap dikembangkan ke backend.</h2>
            <p>
                Frontend ini menerjemahkan PRD ke dalam portal akademik desktop-first dengan fokus pada fase skripsi,
                historis bimbingan, penilaian dosen, dan monitoring progres yang mudah dipahami.
            </p>
        </div>
        <div class="hero-panel__stats">
            @foreach ($dashboardStats as $stat)
                <article class="metric-card">
                    <span class="metric-card__label">{{ $stat['label'] }}</span>
                    <strong>{{ $stat['value'] }}</strong>
                    <small>{{ $stat['hint'] }}</small>
                </article>
            @endforeach
        </div>
    </section>

    <section class="content-grid">
        <article class="card">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Core Modules</p>
                    <h3>Prioritas frontend dari PRD</h3>
                </div>
            </div>
            <div class="feature-grid">
                @foreach ($featureCards as $feature)
                    <article class="feature-card">
                        <span class="feature-card__tag">{{ $feature['tag'] }}</span>
                        <h4>{{ $feature['title'] }}</h4>
                        <p>{{ $feature['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </article>

        <article class="card">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Role Flow</p>
                    <h3>Interaksi utama tiap aktor</h3>
                </div>
            </div>
            <div class="role-list">
                @foreach ($roleFlows as $role)
                    <article class="role-item">
                        <div class="role-item__header">
                            <strong>{{ $role['role'] }}</strong>
                            <span>{{ $role['phase'] }}</span>
                        </div>
                        <p>{{ $role['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </article>
    </section>

    <section class="content-grid">
        <article class="card">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Prototype Screens</p>
                    <h3>Halaman yang sudah dibuat</h3>
                </div>
            </div>
            <div class="screen-links">
                <a class="screen-link" href="{{ route('skripsi.detail') }}">
                    <div>
                        <strong>Detail Skripsi Mahasiswa</strong>
                        <p>Ringkasan mahasiswa, progress fase, histori bimbingan, dan panel validasi.</p>
                    </div>
                    <span>Lihat layar</span>
                </a>
                <a class="screen-link" href="{{ route('penilaian.sidang') }}">
                    <div>
                        <strong>Penilaian Sidang Skripsi</strong>
                        <p>Rubrik objektif, skor total, catatan penguji, dan konfirmasi pengiriman nilai.</p>
                    </div>
                    <span>Lihat layar</span>
                </a>
            </div>
        </article>

        <article class="card card--accent">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Implementation Notes</p>
                    <h3>Cakupan frontend saat ini</h3>
                </div>
            </div>
            <ul class="note-list">
                <li>Server-rendered Blade sesuai arah PRD.</li>
                <li>Styling mengikuti Figma: paper background, cobalt accent, kartu editorial, sidebar tetap.</li>
                <li>Data masih berupa dummy state supaya backend bisa disambungkan bertahap.</li>
                <li>Komponen dibuat reusable untuk fase, tabel histori, rubric item, dan action panels.</li>
            </ul>
        </article>
    </section>
@endsection
