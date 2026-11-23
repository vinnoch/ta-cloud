<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('topbar shows the Google avatar when available', function () {
    $user = User::factory()->dosen()->create([
        'name' => 'Google User',
        'google_avatar' => 'https://lh3.googleusercontent.com/avatar.jpg',
    ]);

    $this->actingAs($user)
        ->get(route('dosen.dashboard'))
        ->assertOk()
        ->assertSee('src="https://lh3.googleusercontent.com/avatar.jpg"', false)
        ->assertSee('referrerpolicy="no-referrer"', false);
});

test('topbar keeps initials when no Google avatar is available', function () {
    $user = User::factory()->dosen()->create([
        'name' => 'Demo Dosen',
        'google_avatar' => null,
    ]);

    $this->actingAs($user)
        ->get(route('dosen.dashboard'))
        ->assertOk()
        ->assertSee('DD');
});
