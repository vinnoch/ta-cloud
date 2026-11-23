<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps superadmin navigation on the registration page', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $this->actingAs($superadmin)
        ->get(route('registrations.index'))
        ->assertOk()
        ->assertViewHas('navRole', 'superadmin')
        ->assertViewHas('navItems', fn (array $items) => collect($items)->pluck('label')->all() === [
            'Dashboard',
            'Users',
            'Pendaftaran',
            'Skripsi',
            'Non-Skripsi',
            'Setting',
            'Log Sistem',
        ]);
});

it('maps the approved registration filter to active accounts', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $approved = User::factory()->mahasiswa()->create([
        'registration_method' => 'google',
        'account_status' => 'active',
    ]);
    $pending = User::factory()->mahasiswa()->create([
        'registration_method' => 'google',
        'account_status' => 'pending',
    ]);

    $this->actingAs($superadmin)
        ->get(route('registrations.index', ['status' => 'approved']))
        ->assertOk()
        ->assertViewHas('status', 'approved')
        ->assertViewHas('registrations', fn ($registrations) => $registrations->pluck('id')->contains($approved->id)
            && ! $registrations->pluck('id')->contains($pending->id));
});

it('lists and filters every publicly reviewable account role', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $mahasiswa = User::factory()->mahasiswa()->create([
        'registration_method' => 'google',
        'account_status' => 'pending',
    ]);
    $dosen = User::factory()->dosen()->create([
        'registration_method' => 'google',
        'account_status' => 'pending',
    ]);
    $kaprodi = User::factory()->kaprodi()->create([
        'registration_method' => 'google',
        'account_status' => 'pending',
        'nidn_nip' => '1234567890',
    ]);

    $this->actingAs($superadmin)
        ->get(route('registrations.index'))
        ->assertOk()
        ->assertSee('Pendaftaran pengguna')
        ->assertSee($mahasiswa->name)
        ->assertSee($dosen->name)
        ->assertSee($kaprodi->name)
        ->assertSee('Status')
        ->assertSee('User Level')
        ->assertSee('Semua User Level')
        ->assertSee('id="filter-form"', false)
        ->assertSee('id="status-select"', false)
        ->assertSee('id="role-select"', false);

    $this->get(route('registrations.index', ['role' => 'dosen']))
        ->assertOk()
        ->assertSee($dosen->name)
        ->assertDontSee($mahasiswa->name)
        ->assertDontSee($kaprodi->name);
});

it('returns AJAX registration fragments for status and user level filters', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $dosen = User::factory()->dosen()->create([
        'registration_method' => 'google',
        'account_status' => 'pending',
    ]);
    $mahasiswa = User::factory()->mahasiswa()->create([
        'registration_method' => 'google',
        'account_status' => 'pending',
    ]);

    $response = $this->actingAs($superadmin)->getJson(route('registrations.index', [
        'status' => 'pending',
        'role' => 'dosen',
    ]), ['X-Requested-With' => 'XMLHttpRequest']);

    $response->assertOk()
        ->assertJsonPath('count_text', '1 pendaftaran ditemukan.');

    expect($response->json('table_html'))
        ->toContain($dosen->name)
        ->not->toContain($mahasiswa->name);
});
