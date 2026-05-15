<?php

use App\Events\RealtimeNotificationCreated;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notify:test {userId} {title=Realtime update} {message=New notification received.} {url?}', function () {
    $user = User::query()->findOrFail((int) $this->argument('userId'));

    event(new RealtimeNotificationCreated(
        userId: (int) $user->getAuthIdentifier(),
        title: (string) $this->argument('title'),
        message: (string) $this->argument('message'),
        url: $this->argument('url') ? (string) $this->argument('url') : null,
        unreadCount: 1,
    ));

    $this->info("Notification broadcast sent to user {$user->getAuthIdentifier()}.");
})->purpose('Broadcast a realtime notification to a single user');
