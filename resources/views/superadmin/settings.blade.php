@extends('layouts.app')

@section('content')
    @if (session('status'))<div class="notice notice--success" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="notice notice--error" role="alert">Periksa kembali data pengaturan.</div>@endif

    <section class="acss-crud-card acss-crud-card--topflush">
        <div class="acss-crud-head">
            <div>
                <h1 class="acss-page-title">Setting</h1>
            </div>
        </div>
        <div class="acss-crud-body">
            <form method="POST" action="{{ route('superadmin.settings.update') }}" enctype="multipart/form-data" class="acss-superadmin-settings-grid">
                @csrf
                @method('PUT')
                <div class="acss-master-form-shell">
                    <label class="form-field">
                        <span>Judul Aplikasi</span>
                        <input name="application_name" value="{{ old('application_name', $settings->application_name) }}" maxlength="80" required @error('application_name') aria-invalid="true" aria-describedby="application-name-error" @enderror>
                        @error('application_name')<small class="form-error" id="application-name-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="form-field">
                        <span>Logo Aplikasi</span>
                        <input name="logo" type="file" accept="image/png,image/jpeg,image/webp" @error('logo') aria-invalid="true" aria-describedby="logo-error" @enderror>
                        <small class="acss-muted">PNG, JPG, atau WebP. Maksimum 2 MB dan 2000 × 2000 piksel.</small>
                        @error('logo')<small class="form-error" id="logo-error">{{ $message }}</small>@enderror
                    </label>
                    <div class="form-actions form-actions--inline">
                        <button class="button button--primary" type="submit">Simpan Pengaturan</button>
                    </div>
                </div>
                <aside class="acss-brand-preview" aria-label="Pratinjau logo saat ini">
                    <span class="acss-muted">Logo Saat Ini</span>
                    <img src="{{ $branding['logo_url'] }}" alt="Logo {{ $branding['name'] }} saat ini">
                    <strong>{{ $settings->application_name }}</strong>
                </aside>
            </form>
        </div>
    </section>
@endsection
