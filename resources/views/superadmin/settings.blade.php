@extends('layouts.app')

@section('content')
    <main class="content-shell">
        @if (session('status'))<p role="status">{{ session('status') }}</p>@endif
        <h1>Global Settings</h1>
        <form method="POST" action="{{ route('superadmin.settings.update') }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <label>Application title <input name="application_name" value="{{ old('application_name', $settings->application_name) }}" maxlength="80" required></label>
            <label>Logo <input name="logo" type="file" accept="image/png,image/jpeg,image/webp"></label>
            <button type="submit">Save settings</button>
        </form>
    </main>
@endsection
