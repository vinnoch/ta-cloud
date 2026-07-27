<?php

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates scoped notifications and normalizes links', function () {
    $user = User::factory()->mahasiswa()->create();

    app(NotificationService::class)->send([$user, $user], [
        'type' => 'test',
        'title' => 'Workflow update',
        'message' => 'Ready',
        'url' => 'https://tacloud.test/mahasiswa/skripsi?tab=final',
    ]);

    $this->assertDatabaseCount('notifications', 1);

    $this->actingAs($user)
        ->getJson(route('notifications.index'))
        ->assertOk()
        ->assertJsonPath('unread_count', 1)
        ->assertJsonPath('items.0.url', '/mahasiswa/skripsi?tab=final');
});

it('prevents users from reading another users notification', function () {
    $owner = User::factory()->mahasiswa()->create();
    $other = User::factory()->mahasiswa()->create();
    app(NotificationService::class)->send([$owner], [
        'title' => 'Private update',
        'message' => 'Owner only',
        'url' => '/mahasiswa/skripsi',
    ]);
    $notification = $owner->notifications()->sole();

    $this->actingAs($other)
        ->postJson(route('notifications.read', $notification->id))
        ->assertNotFound();

    expect($notification->fresh()->read_at)->toBeNull();
});

it('marks one or all own notifications as read', function () {
    $user = User::factory()->dosen()->create();
    $service = app(NotificationService::class);
    $service->send([$user], ['title' => 'One', 'message' => 'First']);
    $service->send([$user], ['title' => 'Two', 'message' => 'Second']);
    $first = $user->notifications()->oldest()->firstOrFail();

    $this->actingAs($user)
        ->postJson(route('notifications.read', $first->id))
        ->assertOk()
        ->assertJsonPath('unread_count', 1);

    $this->postJson(route('notifications.read-all'))
        ->assertOk()
        ->assertJsonPath('unread_count', 0);

    expect($user->unreadNotifications()->count())->toBe(0);
});
