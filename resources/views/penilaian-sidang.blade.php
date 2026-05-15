@extends('layouts.app')

@section('content')
    <section class="editorial-header">
        <p class="eyebrow">Portal Akademik • Form Dosen</p>
        <h2>Penilaian Sidang Skripsi</h2>
        <p>
            Silakan mengisi form penilaian berikut berdasarkan performa mahasiswa selama proses presentasi dan
            kualitas dokumen tugas akhir yang diserahkan.
        </p>
    </section>

    <section class="detail-grid">
        <article class="card">
            <div class="identity-card">
                <div>
                    <span class="panel-label">Judul Skripsi</span>
                    <h3>{{ $grading['title'] }}</h3>
                </div>
                <div class="identity-card__meta">
                    <div>
                        <span class="panel-label">Nama Mahasiswa</span>
                        <strong>{{ $grading['student'] }}</strong>
                    </div>
                    <div>
                        <span class="panel-label">Nomor Induk Mahasiswa</span>
                        <strong>{{ $grading['nim'] }}</strong>
                    </div>
                </div>
            </div>
        </article>

        <aside class="card card--soft score-card">
            <span class="panel-label">Skor Total Akhir</span>
            <div class="score-card__value">
                <strong>88</strong>
                <small>.5</small>
            </div>
            <span class="badge badge--emerald">Predikat: A</span>
        </aside>
    </section>

    <section class="card">
        <div class="section-heading">
            <div class="heading-inline">
                <span class="section-heading__icon">@include('partials.icons.clipboard')</span>
                <h3>Rubrik Penilaian Objektif</h3>
            </div>
            <span class="muted">Skala 0 - 100</span>
        </div>

        <div class="rubric-list">
            @foreach ($rubrics as $rubric)
                <article class="rubric-item">
                    <div class="rubric-item__content">
                        <h4>{{ $rubric['title'] }}</h4>
                        <p>{{ $rubric['description'] }}</p>
                    </div>
                    <div class="rubric-item__score">
                        <span class="score-weight">{{ $rubric['weight'] }}</span>
                        <label class="score-box">
                            <span class="sr-only">{{ $rubric['title'] }}</span>
                            <input type="text" value="{{ $rubric['score'] }}" readonly />
                        </label>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="detail-grid detail-grid--grading">
        <article class="card">
            <div class="section-heading">
                <div class="heading-inline">
                    <span class="section-heading__icon">@include('partials.icons.clipboard')</span>
                    <h3>Catatan &amp; Saran Penguji</h3>
                </div>
            </div>
            <textarea class="editor-textarea" rows="7" placeholder="Tuliskan masukan konstruktif untuk perbaikan revisi mahasiswa..."></textarea>
        </article>

        <aside class="stacked-aside">
            <article class="card card--confirm">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">Konfirmasi Penilaian</p>
                        <h3>Pastikan nilai sudah benar</h3>
                    </div>
                </div>
                <p class="muted">
                    Nilai yang dikirim akan masuk ke rekapitulasi sidang prodi dan tidak bisa diubah tanpa proses revisi.
                </p>
                <button class="button" type="button">
                    <span class="button__icon">@include('partials.icons.send')</span>
                    <span>Simpan &amp; Kirim Nilai</span>
                </button>
                <button class="button button--muted" type="button">Publish Nilai</button>
            </article>

            <article class="card card--notice">
                <span class="badge badge--emerald">Info Penting</span>
                <p>Batas akhir penginputan nilai adalah 1x24 jam setelah sidang berakhir.</p>
            </article>
        </aside>
    </section>

    <footer class="mini-footer">
        <div>
            <strong>tacloud</strong>
            <span>Powered by academic intelligence systems</span>
        </div>
        <div>
            <span>Security Protocol</span>
            <span>Privacy Policy</span>
            <span>Lecturer Support</span>
        </div>
    </footer>
@endsection
