@extends('layouts.app')

@section('content')
    <main class="content-shell">
        <h1>Privileged Audit</h1>
        <table><thead><tr><th>Time</th><th>Actor</th><th>Action</th><th>Target</th></tr></thead><tbody>
        @foreach ($logs as $log)
            <tr><td>{{ $log->created_at }}</td><td>{{ $log->actor_email ?? 'operator command' }}</td><td>{{ $log->action }}</td><td>{{ $log->target_id ?? 'system' }}</td></tr>
        @endforeach
        </tbody></table>
        {{ $logs->links() }}
    </main>
@endsection
