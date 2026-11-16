<?php

use App\Models\User;
use App\Models\UserLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets a multi-role user activate only an assigned role', function () {
    $user = User::factory()->state(['role' => 'superadmin'])->create();
    $dosenLevel = UserLevel::query()->firstOrCreate(['users_level' => 'dosen']);
    $user->assignedRoles()->syncWithoutDetaching([$user->users_id, $dosenLevel->users_id]);

    $this->actingAs($user)
        ->post(route('role-selection.store'), ['role' => 'dosen'])
        ->assertRedirect(route('dosen.dashboard'))
        ->assertSessionHas('active_role', 'dosen');

    $this->post(route('role-selection.store'), ['role' => 'kaprodi'])
        ->assertSessionHasErrors('role');
});
