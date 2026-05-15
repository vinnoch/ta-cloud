<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login', [
            'title' => 'Masuk TA Cloud',
            'testAccounts' => app()->environment(['local', 'testing']) ? $this->buildTestAccounts() : [],
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended($this->dashboardRouteFor($request->user()->role));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function buildTestAccounts(): array
    {
        $accounts = [];

        $kaprodi = User::query()->where('email', 'kaprodi@tacloud.test')->first();
        if ($kaprodi) {
            $accounts[] = $this->accountRow('ROLE • KAPRODI', $kaprodi->name, $kaprodi->email);
        }

        $dosenPembimbing = User::query()
            ->whereHas('reviewerAssignments', fn ($query) => $query->where('role_type', 'pembimbing_1'))
            ->orderBy('id')
            ->first()
            ?? User::query()->where('email', 'sarah.wijaya@tacloud.test')->first();
        if ($dosenPembimbing) {
            $accounts[] = $this->accountRow('ROLE • DOSEN PEMBIMBING', $dosenPembimbing->name, $dosenPembimbing->email);
        }

        $dosenPenguji = User::query()
            ->whereHas('reviewerAssignments', fn ($query) => $query->where('role_type', 'penguji_1'))
            ->orderBy('id')
            ->first()
            ?? User::query()->where('email', 'dosen3@tacloud.test')->first();
        if ($dosenPenguji) {
            $accounts[] = $this->accountRow('ROLE • DOSEN PENGUJI', $dosenPenguji->name, $dosenPenguji->email);
        }

        $mahasiswaBaru = User::query()->where('email', 'mahasiswa.baru@tacloud.test')->first();
        if ($mahasiswaBaru) {
            $accounts[] = $this->accountRow('FASE • BARU', $mahasiswaBaru->name, $mahasiswaBaru->email);
        }

        $phaseLabels = [
            'proposal' => 'FASE • PROPOSAL',
            'sidang_proposal' => 'FASE • SIDANG PROPOSAL',
            'bimbingan_skripsi' => 'FASE • BIMBINGAN',
            'sidang_skripsi' => 'FASE • SIDANG SKRIPSI',
            'revisi_sidang_skripsi' => 'FASE • REVISI SIDANG',
            'review_dokumen_final' => 'FASE • REVIEW FINAL',
            'skripsi_selesai' => 'FASE • SELESAI',
        ];

        $skripsis = Skripsi::query()
            ->with('student')
            ->whereIn('current_phase', array_keys($phaseLabels))
            ->orderBy('id')
            ->get()
            ->unique('current_phase');

        foreach ($phaseLabels as $phase => $label) {
            $skripsi = $skripsis->firstWhere('current_phase', $phase);
            $student = $skripsi?->student;

            if ($student) {
                $accounts[] = $this->accountRow($label, $student->name, $student->email);
            }
        }

        return $accounts;
    }

    private function accountRow(string $role, string $name, string $email): array
    {
        return [
            'role' => $role,
            'name' => $name,
            'email' => $email,
            'password' => 'password',
        ];
    }

    private function dashboardRouteFor(string $role): string
    {
        return match ($role) {
            'mahasiswa' => route('mahasiswa.dashboard'),
            'dosen' => route('dosen.dashboard'),
            'kaprodi' => route('kaprodi.dashboard'),
            default => route('dashboard.index'),
        };
    }
}
