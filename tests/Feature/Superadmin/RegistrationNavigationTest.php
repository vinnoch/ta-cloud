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
            'Setting',
            'Log Sistem',
        ]);
});
