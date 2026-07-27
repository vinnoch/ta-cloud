@extends('layouts.app')

@section('content')
    <main class="content-shell">
        @if (session('status'))<p role="status">{{ session('status') }}</p>@endif
        <h1>Accounts</h1>
        <form method="POST" action="{{ route('superadmin.users.store') }}">
            @csrf
            <label>Name <input name="name" required maxlength="255"></label>
            <label>Institutional email <input name="email" type="email" required></label>
            <label>Role <select name="role">@foreach ($roles as $role)<option>{{ $role }}</option>@endforeach</select></label>
            <button type="submit">Invite</button>
        </form>
        <table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Action</th></tr></thead><tbody>
        @foreach ($users as $user)
            <tr><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->role }}</td><td>{{ $user->trashed() ? 'Inactive' : 'Active' }}</td><td>
                @if ($user->trashed())
                    <form method="POST" action="{{ route('superadmin.users.restore', $user->id) }}">@csrf <button>Reactivate</button></form>
                @else
                    <form method="POST" action="{{ route('superadmin.users.update', $user) }}">@csrf @method('PUT')
                        <select name="role">@foreach ($roles as $role)<option @selected($user->role === $role)>{{ $role }}</option>@endforeach</select><button>Update role</button>
                    </form>
                    <form method="POST" action="{{ route('superadmin.users.destroy', $user) }}">@csrf @method('DELETE')<button>Deactivate</button></form>
                @endif
            </td></tr>
        @endforeach
        </tbody></table>
        {{ $users->links() }}
    </main>
@endsection
