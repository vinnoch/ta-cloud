@extends('layouts.app')

@section('content')
    @if (session('status'))<div class="notice notice--success" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="notice notice--error" role="alert">Periksa kembali pengaturan Log Sistem.</div>@endif

    <section class="acss-crud-card acss-crud-card--topflush">
        <div class="acss-crud-head acss-crud-head--inline">
            <div>
                <h1 class="acss-page-title">Setting</h1>
            </div>
            <a class="button button--muted button--inline" href="{{ route('superadmin.audit.index') }}">Lihat Log Sistem</a>
        </div>

        <div class="acss-crud-body">
            <nav class="acss-settings-tabs" aria-label="Bagian pengaturan">
                <a href="{{ route('superadmin.settings.edit') }}">Aplikasi</a>
                <a class="is-active" href="{{ route('superadmin.audit.settings') }}" aria-current="page">Log Sistem</a>
            </nav>

            <div class="acss-settings-panel">
                <div class="acss-settings-row">
                    <div class="acss-settings-row__label">
                        <strong>Masa penyimpanan</strong>
                        <small>Log yang melewati masa simpan akan dihapus otomatis.</small>
                    </div>
                    <form method="POST" action="{{ route('superadmin.audit.retention.update') }}" class="acss-settings-row__control" onsubmit="return confirm('Perubahan masa simpan dapat menghapus log yang sudah kedaluwarsa. Lanjutkan?')">
                        @csrf
                        @method('PUT')
                        <label class="form-field">
                            <span>Simpan Log untuk (hari)</span>
                            <input type="number" name="log_retention_days" value="{{ old('log_retention_days', $retentionDays) }}" min="1" max="65535" step="1" inputmode="numeric" required>
                        </label>
                        <button class="button button--primary button--inline" type="submit">Simpan</button>
                    </form>
                </div>

                <div class="acss-settings-row acss-settings-row--danger">
                    <div class="acss-settings-row__label">
                        <strong>Hapus semua aktivitas</strong>
                        <small>Semua log dihapus permanen. Satu catatan penghapusan tetap disimpan.</small>
                    </div>
                    <form method="POST" action="{{ route('superadmin.audit.destroy') }}" onsubmit="return confirm('Hapus seluruh Log Sistem? Tindakan ini tidak dapat dibatalkan.')">
                        @csrf
                        @method('DELETE')
                        <button class="button button--danger button--inline" type="submit">Hapus Log Sistem</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
