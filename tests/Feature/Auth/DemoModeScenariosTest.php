<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(fn () => File::delete(storage_path('app/demo-mode.enabled')));
afterEach(fn () => File::delete(storage_path('app/demo-mode.enabled')));

it('creates local multi-role and new-member demo scenarios', function () {
    $this->artisan('demo:enable')->assertSuccessful();

    $this->post(route('demo.login', 'multi-role'))
        ->assertRedirect(route('role-selection.create'));

    auth()->logout();

    $this->post(route('demo.register'))
        ->assertRedirect(route('register'))
        ->assertSessionHas('registration.google_identity.email', 'demo.new-member@widyakarya.ac.id');
});
