<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreGoogleRegistrationRequest;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GoogleRegistrationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $identity = $this->identity($request);

        if (! $identity) {
            return redirect()->route('login')->with('status', 'Mulai pendaftaran melalui Google.');
        }

        return view('auth.register', [
            'title' => 'Pendaftaran TA Cloud',
            'googleIdentity' => $identity,
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreGoogleRegistrationRequest $request): RedirectResponse
    {
        $identity = $this->identity($request);
        abort_unless($identity, 419);
        $data = $request->validated();

        $user = DB::transaction(function () use ($identity, $data): User {
            $user = User::query()->withTrashed()->where('email', $identity['email'])->lockForUpdate()->first();
            abort_if($user?->trashed(), 422, 'Akun ini dinonaktifkan. Hubungi administrator.');

            if ($user && $user->google_id && ! hash_equals((string) $user->google_id, (string) $identity['google_id'])) {
                abort(403, 'Identitas Google tidak cocok.');
            }

            $user ??= new User(['email' => $identity['email']]);
            $user->forceFill([
                'name' => $identity['name'],
                'google_id' => $identity['google_id'],
                'google_avatar' => $identity['avatar'],
                'email_verified_at' => now(),
                'password' => Str::random(64),
                'role' => 'mahasiswa',
                'nim' => $data['nim'],
                'study_program_id' => $data['study_program_id'],
                'account_status' => 'pending',
                'registration_method' => 'google',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
            ])->save();

            return $user;
        });

        $request->session()->forget('registration.google_identity');
        $request->session()->put('registration.status_identity', [
            'email' => $user->email,
            'google_id' => $user->google_id,
        ]);

        return redirect()->route('register.status');
    }

    public function status(Request $request): View|RedirectResponse
    {
        $identity = $request->session()->get('registration.status_identity');
        $user = is_array($identity)
            ? User::query()->where('email', $identity['email'] ?? '')->where('google_id', $identity['google_id'] ?? '')->first()
            : null;

        if (! $user || $user->account_status === 'active') {
            return redirect()->route('login');
        }

        return view('auth.registration-status', [
            'registrationStatus' => $user->account_status,
            'rejectionReason' => $user->rejection_reason,
            'maskedEmail' => Str::mask($user->email, '*', 2, max(strlen($user->email) - 12, 1)),
        ]);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget(['registration.google_identity', 'registration.status_identity']);

        return redirect()->route('login');
    }

    private function identity(Request $request): ?array
    {
        $identity = $request->session()->get('registration.google_identity');

        if (! is_array($identity) || now()->timestamp > (int) ($identity['expires_at'] ?? 0)) {
            $request->session()->forget('registration.google_identity');

            return null;
        }

        return $identity;
    }
}
