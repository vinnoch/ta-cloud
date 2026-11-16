<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RejectRegistrationRequest;
use App\Models\User;
use App\Services\PrivilegedAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegistrationApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', Rule::in(['pending', 'active', 'rejected'])]])['status'] ?? 'pending';
        $registrations = User::query()
            ->with('studyProgram')
            ->where('registration_method', 'google')
            ->where('account_status', $status)
            ->when($request->user()->role === 'kaprodi', fn ($query) => $query->where('study_program_id', $request->user()->study_program_id))
            ->latest()
            ->paginate(25);

        return view('kaprodi.registrations.index', compact('registrations', 'status'));
    }

    public function approve(Request $request, User $registration): RedirectResponse
    {
        $this->authorizeDecision($request, $registration);

        DB::transaction(function () use ($request, $registration): void {
            $user = User::query()->lockForUpdate()->findOrFail($registration->id);
            $this->authorizeDecision($request, $user);

            if ($user->account_status !== 'pending') {
                return;
            }

            $user->forceFill([
                'account_status' => 'active',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ])->save();
            PrivilegedAudit::record('registration.approved', $user, ['account_status' => 'pending'], ['account_status' => 'active'], $request);
        });

        return back()->with('success', 'Pendaftaran disetujui.');
    }

    public function reject(RejectRegistrationRequest $request, User $registration): RedirectResponse
    {
        $this->authorizeDecision($request, $registration);

        DB::transaction(function () use ($request, $registration): void {
            $user = User::query()->lockForUpdate()->findOrFail($registration->id);
            $this->authorizeDecision($request, $user);

            if ($user->account_status !== 'pending') {
                return;
            }

            $user->forceFill([
                'account_status' => 'rejected',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => $request->validated('rejection_reason'),
            ])->save();
            PrivilegedAudit::record('registration.rejected', $user, ['account_status' => 'pending'], ['account_status' => 'rejected'], $request);
        });

        return back()->with('success', 'Pendaftaran ditolak.');
    }

    private function authorizeDecision(Request $request, User $registration): void
    {
        $actor = $request->user();
        abort_if($actor->is($registration), 403);
        abort_unless(in_array($actor->role, ['superadmin', 'admin', 'kaprodi'], true), 403);
        abort_if($actor->role === 'kaprodi' && (int) $actor->study_program_id !== (int) $registration->study_program_id, 403);
        abort_unless($registration->registration_method === 'google' && $registration->role === 'mahasiswa', 404);
    }
}
