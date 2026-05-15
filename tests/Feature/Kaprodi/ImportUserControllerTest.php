<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function kaprodiImportUser(): User
{
    return User::query()->create([
        'name' => 'Kaprodi Import',
        'email' => 'kaprodi.import@example.test',
        'password' => 'password',
        'role' => 'kaprodi',
    ]);
}

it('shows import dosen page with latest template info', function () {
    $kaprodi = kaprodiImportUser();

    $this->actingAs($kaprodi)
        ->get(route('kaprodi.import.dosen'))
        ->assertOk()
        ->assertSee('Import Dosen')
        ->assertSee('nidn_nip')
        ->assertSee('template_dosen.csv');
});

it('imports dosen csv with nidn nip', function () {
    $kaprodi = kaprodiImportUser();

    $csv = "name,nidn_nip,email,password\nDr. Sarah Wijaya,0412345678,sarah.import@example.test,password123\n";

    $file = UploadedFile::fake()->createWithContent('dosen.csv', $csv);

    $this->actingAs($kaprodi)
        ->post(route('kaprodi.import.dosen.store'), ['file' => $file])
        ->assertRedirect();

    $this->assertDatabaseHas('users', [
        'email' => 'sarah.import@example.test',
        'role' => 'dosen',
        'nidn_nip' => '0412345678',
        'nim' => null,
    ]);
});

it('imports mahasiswa csv with nim', function () {
    $kaprodi = kaprodiImportUser();

    $csv = "name,email,nim,password\nAdrian Sterling,adrian.import@example.test,2021004592,password123\n";

    $file = UploadedFile::fake()->createWithContent('mahasiswa.csv', $csv);

    $this->actingAs($kaprodi)
        ->post(route('kaprodi.import.mahasiswa.store'), ['file' => $file])
        ->assertRedirect();

    $this->assertDatabaseHas('users', [
        'email' => 'adrian.import@example.test',
        'role' => 'mahasiswa',
        'nim' => '2021004592',
    ]);
});

it('updates existing user by email during import', function () {
    $kaprodi = kaprodiImportUser();

    User::query()->create([
        'name' => 'Old Name',
        'email' => 'same@example.test',
        'password' => 'password',
        'role' => 'dosen',
        'nidn_nip' => '0001',
    ]);

    $csv = "name,nidn_nip,email,password\nDr. New Name,9999,same@example.test,password123\n";

    $file = UploadedFile::fake()->createWithContent('dosen.csv', $csv);

    $this->actingAs($kaprodi)
        ->post(route('kaprodi.import.dosen.store'), ['file' => $file])
        ->assertRedirect()
        ->assertSessionHas('importSummary');

    $this->assertDatabaseHas('users', [
        'email' => 'same@example.test',
        'name' => 'Dr. New Name',
        'nidn_nip' => '9999',
    ]);
});

it('skips duplicate nim or nidn nip owned by another user', function () {
    $kaprodi = kaprodiImportUser();

    User::query()->create([
        'name' => 'Existing Dosen',
        'email' => 'existing.dosen@example.test',
        'password' => 'password',
        'role' => 'dosen',
        'nidn_nip' => '12345',
    ]);

    User::query()->create([
        'name' => 'Existing Mhs',
        'email' => 'existing.mhs@example.test',
        'password' => 'password',
        'role' => 'mahasiswa',
        'nim' => '2021000001',
    ]);

    $dosenCsv = "name,nidn_nip,email,password\nDr. Another,12345,another.dosen@example.test,password123\n";
    $mhsCsv = "name,email,nim,password\nAnother Mhs,another.mhs@example.test,2021000001,password123\n";

    $this->actingAs($kaprodi)
        ->post(route('kaprodi.import.dosen.store'), ['file' => UploadedFile::fake()->createWithContent('dosen.csv', $dosenCsv)])
        ->assertRedirect()
        ->assertSessionHas('importSummary');

    $this->actingAs($kaprodi)
        ->post(route('kaprodi.import.mahasiswa.store'), ['file' => UploadedFile::fake()->createWithContent('mahasiswa.csv', $mhsCsv)])
        ->assertRedirect()
        ->assertSessionHas('importSummary');

    $this->assertDatabaseMissing('users', ['email' => 'another.dosen@example.test']);
    $this->assertDatabaseMissing('users', ['email' => 'another.mhs@example.test']);
});
