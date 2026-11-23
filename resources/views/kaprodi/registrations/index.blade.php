@extends('layouts.app')

@section('content')
    @php $registrations = $registrations ?? collect(); @endphp
    @if (session('success'))
        <div class="notice notice--success" role="status" aria-live="polite">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="notice notice--danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="card registration-review-card" id="registration-list-root" data-endpoint="{{ route('registrations.index') }}">
        <div class="section-heading acss-crud-head--inline">
            <div class="acss-crud-head__title-group">
                <p class="registration-eyebrow">Manajemen Akun</p>
                <h1 class="acss-page-title">Pendaftaran pengguna</h1>
                <p class="acss-muted" id="count-text">{{ $registrations->total() }} pendaftaran ditemukan.</p>
            </div>
            <a class="button button--muted button--inline" href="{{ route('home') }}">Kembali</a>
        </div>

        <form class="filter-bar acss-filter-form-relative" id="filter-form" method="GET" action="{{ route('registrations.index') }}" autocomplete="off">
            <label class="form-field">
                <span>Status</span>
                <select name="status" id="status-select">
                    @foreach (['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $value => $statusLabel)
                        <option value="{{ $value }}" @selected(($status ?? 'pending') === $value)>{{ $statusLabel }}</option>
                    @endforeach
                </select>
            </label>
            <label class="form-field">
                <span>User Level</span>
                <select name="role" id="role-select">
                    <option value="">Semua User Level</option>
                    @foreach (($reviewableRoles ?? []) as $roleValue)
                        <option value="{{ $roleValue }}" @selected(($role ?? '') === $roleValue)>{{ str($roleValue)->title() }}</option>
                    @endforeach
                </select>
            </label>
        </form>

        <div id="table-wrapper">@include('kaprodi.registrations.partials.list')</div>
        <div id="pagination-wrapper" class="acss-pagination-spacer">@include('kaprodi.registrations.partials.pagination')</div>
    </section>

    @include('partials.ajax-list-script', [
        'rootId' => 'registration-list-root',
        'formId' => 'filter-form',
        'statusSelectId' => 'status-select',
        'tableWrapperId' => 'table-wrapper',
        'paginationWrapperId' => 'pagination-wrapper',
        'countTextId' => 'count-text',
    ])
@endsection
