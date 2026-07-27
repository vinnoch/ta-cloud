@extends('layouts.app')

@section('content')
    <main class="content-shell">
        <h1>Technical Operations</h1>
        <p>Accounts: {{ $userCount }}</p>
        <p>Audit events: {{ $auditCount }}</p>
        <p>Database: {{ $databaseHealthy ? 'Healthy' : 'Unavailable' }}</p>
        <a href="{{ route('superadmin.users.index') }}">Manage accounts</a>
        <a href="{{ route('superadmin.settings.edit') }}">Global settings</a>
        <a href="{{ route('superadmin.audit.index') }}">Privileged audit</a>
    </main>
@endsection
