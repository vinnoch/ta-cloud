<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function kaprodiUser(): User
{
    return User::query()->create([
        'name' => 'Kaprodi Sistem Informasi',
        'email' => 'kaprodi.crud@example.test',
        'password' => 'password',
        'role' => 'kaprodi',
    ]);
}

test('kaprodi can view paginated dosen list', function () {
    $kaprodi = kaprodiUser();

    User::query()->create([
        'name' => 'Dr. Sarah Wijaya',
        'email' => 'sarah.crud@example.test',
        'password' => 'password',
        'role' => 'dosen',
    ]);

    $this->actingAs($kaprodi)
        ->get(route('kaprodi.dosen.index'))
        ->assertOk()
        ->assertSee('Dr. Sarah Wijaya')
        ->assertSee('Daftar Dosen')
        ->assertSee('Hapus')
        ->assertSee('class="stack-list"', false)
        ->assertDontSee('<aside class="stack-list">', false);
});

test('kaprodi can view dosen detail with mahasiswa bimbingan list', function () {
    $kaprodi = kaprodiUser();

    $dosen = User::query()->create([
        'name' => 'Dr. Sarah Wijaya',
        'email' => 'sarah.detail@example.test',
        'password' => 'password',
        'role' => 'dosen',
    ]);

    User::query()->create([
        'name' => 'Adrian Sterling',
        'email' => 'adrian.detail@example.test',
        'password' => 'password',
        'role' => 'mahasiswa',
        'nim' => '2021004592',
    ]);

    $this->actingAs($kaprodi)
        ->get(route('kaprodi.dosen.show', $dosen))
        ->assertOk()
        ->assertSee('Mahasiswa Bimbingan')
        ->assertSee('Adrian Sterling')
        ->assertSee('2021004592')
        ->assertSee('Hapus Dosen');
});

test('kaprodi create dosen form shows password toggle controls', function () {
    $kaprodi = kaprodiUser();

    $this->actingAs($kaprodi)
        ->get(route('kaprodi.dosen.create'))
        ->assertOk()
        ->assertSee('data-password-toggle', false)
        ->assertSee('dosen-password', false)
        ->assertSee('dosen-password-confirmation', false);
});

test('kaprodi can create dosen account', function () {
    $kaprodi = kaprodiUser();

    $this->actingAs($kaprodi)
        ->post(route('kaprodi.dosen.store'), [
            'name' => 'Dr. Bima Prakoso',
            'email' => 'bima.crud@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('kaprodi.dosen.index'));

    $this->assertDatabaseHas('users', [
        'name' => 'Dr. Bima Prakoso',
        'email' => 'bima.crud@example.test',
        'role' => 'dosen',
    ]);
});

test('kaprodi can update dosen account', function () {
    $kaprodi = kaprodiUser();

    $dosen = User::query()->create([
        'name' => 'Dr. Retno Ayu',
        'email' => 'retno.old@example.test',
        'password' => 'password',
        'role' => 'dosen',
    ]);

    $this->actingAs($kaprodi)
        ->put(route('kaprodi.dosen.update', $dosen), [
            'name' => 'Dr. Retno Ayu Pratiwi',
            'email' => 'retno.new@example.test',
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertRedirect(route('kaprodi.dosen.show', $dosen));

    $this->assertDatabaseHas('users', [
        'id' => $dosen->id,
        'name' => 'Dr. Retno Ayu Pratiwi',
        'email' => 'retno.new@example.test',
        'role' => 'dosen',
    ]);
});

test('kaprodi can delete dosen account', function () {
    $kaprodi = kaprodiUser();

    $dosen = User::query()->create([
        'name' => 'Dr. Dosen Hapus',
        'email' => 'hapus.dosen@example.test',
        'password' => 'password',
        'role' => 'dosen',
    ]);

    $this->actingAs($kaprodi)
        ->delete(route('kaprodi.dosen.destroy', $dosen))
        ->assertRedirect(route('kaprodi.dosen.index'));

    $this->assertDatabaseMissing('users', [
        'id' => $dosen->id,
    ]);
});

test('non kaprodi cannot access dosen crud', function () {
    $mahasiswa = User::query()->create([
        'name' => 'Adrian Sterling',
        'email' => 'adrian.crud@example.test',
        'password' => 'password',
        'role' => 'mahasiswa',
        'nim' => '2021004592',
    ]);

    $this->actingAs($mahasiswa)
        ->get(route('kaprodi.dosen.index'))
        ->assertForbidden();
});
