@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="notice notice--danger">{{ $errors->first() }}</div>
    @endif

    <section class="acss-page-card mb-4">
        <div class="acss-page-card__body">
            <h1 class="acss-page-title">Form Penilaian Sidang</h1>
            <p class="acss-muted mt-1">Input nilai sidang skripsi untuk mahasiswa yang Anda bimbing atau uji.</p>
        </div>
    </section>

    <section class="card card--profile">
        <div class="profile-card">
            <div class="profile-card__avatar">{{ mb_substr($skripsi->student?->name ?? 'M', 0, 1) }}</div>
            <div class="profile-card__main">
                <div class="profile-card__meta">
                    <div>
                        <h2>{{ $skripsi->student?->name ?? 'Mahasiswa' }}</h2>
                        <p>{{ $skripsi->student?->nim ?? '-' }} • {{ $skripsi->periode?->name ?? '-' }}</p>
                        <div class="acss-grade-title">{{ $skripsi->title ?: 'Tanpa Judul' }}</div>
                    </div>
                    <div class="flex gap-2 flex-wrap justify-end">
                        <span class="pill pill--blue">{{ str($assignment->role_type)->replace('_', ' ')->title() }}</span>
                        @if ($grade?->status)<span class="pill">{{ strtoupper($grade->status) }}</span>@endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="acss-section-card mt-4">
        <div class="acss-section-card__head">
            <div>
                <h3 class="acss-card-title">Item Penilaian</h3>
                <p class="acss-muted mt-1">Isi seluruh skor sesuai bobot format nilai sidang.</p>
            </div>
        </div>
        <div class="acss-section-card__body">
            <form method="POST" action="{{ route('dosen.penilaian.store', $skripsi) }}" class="acss-form-stack-tight">
                @csrf

                <div class="rubric-list">
                    @foreach ($format->items as $item)
                        <article class="rubric-item">
                            <div class="rubric-item__content">
                                <h4>{{ $item->nama }}</h4>
                            </div>
                            <div class="rubric-item__score">
                                <span class="score-weight">{{ $item->bobot }}% Bobot</span>
                                <label class="score-box">
                                    <input
                                        type="number"
                                        name="scores[{{ $item->id }}]"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        value="{{ old('scores.' . $item->id, $itemScores[$item->id] ?? '') }}"
                                        required
                                    />
                                </label>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="form-actions form-actions--inline mt-4">
                    <button class="button button--inline" type="submit">Publish Nilai</button>
                </div>
            </form>
        </div>
    </section>
@endsection
