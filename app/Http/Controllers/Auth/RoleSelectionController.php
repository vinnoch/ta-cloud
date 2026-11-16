<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleSelectionController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $roles = $request->user()->availableRoleNames();

        if (count($roles) === 1) {
            return $this->activate($request, $roles[0]);
        }

        return view('auth.select-role', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $roles = $request->user()->availableRoleNames();
        $role = $request->validate(['role' => ['required', Rule::in($roles)]])['role'];

        return $this->activate($request, $role);
    }

    private function activate(Request $request, string $role): RedirectResponse
    {
        $request->session()->put('active_role', $role);

        return redirect()->to(AuthenticatedSessionController::dashboardRouteForRole($role));
    }
}
