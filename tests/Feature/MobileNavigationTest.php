<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the mobile shell for each authenticated role', function (string $role, string $route, array $labels) {
    $user = User::factory()->create([
        'role' => $role,
        'nim' => $role === 'mahasiswa' ? '2021004592' : null,
    ]);

    $response = $this->actingAs($user)->get(route($route));

    $response
        ->assertOk()
        ->assertSee('class="mobile-bottom-nav"', false)
        ->assertSee('data-mobile-menu-open', false)
        ->assertSee('id="mobile-more-menu"', false);

    foreach ($labels as $label) {
        $response->assertSee($label);
    }
})->with([
    'superadmin' => ['superadmin', 'superadmin.dashboard', ['Dashboard', 'Users', 'Pendaftaran']],
    'kaprodi' => ['kaprodi', 'kaprodi.dashboard', ['Dashboard', 'Monitoring', 'Master Mahasiswa']],
    'dosen' => ['dosen', 'dosen.dashboard', ['Dashboard', 'Tugas Akhir', 'Proposal']],
    'mahasiswa' => ['mahasiswa', 'mahasiswa.skripsi.index', ['Tugas Akhir']],
]);

it('does not render the authenticated mobile shell for guests', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('mobile-bottom-nav');
});

it('renders superadmin users with mobile record-card semantics', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $this->actingAs($superadmin)
        ->get(route('superadmin.users.index'))
        ->assertOk()
        ->assertSee('user-record-card', false)
        ->assertSee('data-mobile-label="Peran"', false)
        ->assertSee('data-mobile-label="Status"', false)
        ->assertSee('data-mobile-label="Login terakhir"', false)
        ->assertSee('user-record-card__login-details', false)
        ->assertSee('user-record-card__mobile-secondary', false);
});
