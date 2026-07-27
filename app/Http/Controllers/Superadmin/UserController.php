<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PrivilegedAudit;
use App\Services\RoleNavigationService;
use App\Services\SuperadminAccounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $navigation = app(RoleNavigationService::class);

        return view('superadmin.users.index', [
            'title' => 'Users',
            'heading' => 'Users',
            'crumbs' => 'SUPERADMIN • USERS',
            'navItems' => $navigation->superadminNavItems(),
            'navFooterItems' => $navigation->footerItems(),
            'navRole' => 'superadmin',
            'primaryCta' => null,
            'users' => User::query()->withTrashed()->orderBy('name')->paginate(25),
            'roles' => User::ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(User::ROLES)],
        ]);
        $domain = Str::lower((string) config('services.google.allowed_domain'));
        $data['email'] = Str::lower($data['email']);

        abort_unless($domain !== '' && Str::afterLast($data['email'], '@') === $domain, 422, 'Institutional email required.');

        $user = User::query()->create($data + ['password' => Str::random(64)]);
        PrivilegedAudit::record('user.invited', $user, [], ['email' => $user->email, 'role' => $user->role], $request);

        return back()->with('status', 'Akun dibuat dan siap digunakan untuk masuk melalui Google.');
    }

    public function update(Request $request, User $user, SuperadminAccounts $accounts): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::in(User::ROLES)]]);
        $before = ['role' => $user->role];
        $accounts->updateRole($user, $data['role']);
        PrivilegedAudit::record('user.role_changed', $user, $before, ['role' => $data['role']], $request);

        return back()->with('status', 'Role updated.');
    }

    public function destroy(Request $request, User $user, SuperadminAccounts $accounts): RedirectResponse
    {
        $before = ['email' => $user->email, 'role' => $user->role];
        $accounts->deactivate($user);
        PrivilegedAudit::record('user.deactivated', $user, $before, [], $request);

        return back()->with('status', 'Account deactivated.');
    }

    public function restore(Request $request, int $user, SuperadminAccounts $accounts): RedirectResponse
    {
        $restored = $accounts->reactivate($user);
        PrivilegedAudit::record('user.reactivated', $restored, [], ['email' => $restored->email, 'role' => $restored->role], $request);

        return back()->with('status', 'Account reactivated.');
    }
}
