<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

test('verified google user registers awaits approval then logs in', function () {
    $departmentId = DB::table('departments')->insertGetId([
        'code' => 'FT', 'name' => 'Fakultas Teknik', 'is_active' => true,
    ]);
    $program = StudyProgram::query()->create([
        'department_id' => $departmentId,
        'code' => 'SI',
        'name' => 'Sistem Informasi',
        'degree_level' => 'S1',
        'is_active' => true,
    ]);
    $googleUser = new SocialiteUser;
    $googleUser->map([
        'id' => 'google-registration-flow',
        'name' => 'New Student',
        'email' => 'new.student@widyakarya.ac.id',
        'avatar' => 'https://avatar.example.test/new.png',
    ]);

    Socialite::shouldReceive('driver->user')->once()->andReturn($googleUser);
    $this->get(route('auth.google.callback'))->assertRedirect(route('register'));

    $this->post(route('register.store'), [
        'role' => 'mahasiswa',
        'nim' => '2026000001',
        'study_program_id' => $program->id,
    ])->assertRedirect(route('register.status'));

    $registration = User::query()->where('email', 'new.student@widyakarya.ac.id')->sole();
    expect($registration->account_status)->toBe('pending')
        ->and($registration->email_verified_at)->not->toBeNull();
    $this->assertGuest();

    $kaprodi = User::factory()->kaprodi()->create(['study_program_id' => $program->id]);
    $this->actingAs($kaprodi)
        ->patch(route('registrations.approve', $registration))
        ->assertSessionHas('success', 'Pendaftaran disetujui.');
    expect($registration->fresh()->account_status)->toBe('active');

    auth()->logout();
    Socialite::shouldReceive('driver->user')->once()->andReturn($googleUser);
    $this->get(route('auth.google.callback'))
        ->assertRedirect(AuthenticatedSessionController::dashboardRouteForRole('mahasiswa'));

    $this->assertAuthenticatedAs($registration);
    expect($registration->fresh()->last_login_at)->not->toBeNull();
});
