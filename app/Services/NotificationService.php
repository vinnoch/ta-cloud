<?php

namespace App\Services;

use App\Events\RealtimeNotificationCreated;
use App\Notifications\ThesisWorkflowNotification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Throwable;

class NotificationService
{
    private function normalizeUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            return $url;
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $path . $query . $fragment;
    }

    public function send(iterable $recipients, array $payload): void
    {
        $payload['url'] = $this->normalizeUrl($payload['url'] ?? null);

        $users = Collection::make($recipients)
            ->filter()
            ->unique(fn ($user) => $user->getAuthIdentifier())
            ->values();

        foreach ($users as $user) {
            $user->notify(new ThesisWorkflowNotification($payload));

            $notification = $user->notifications()->latest()->first();

            try {
                event(new RealtimeNotificationCreated(
                    userId: (int) $user->getAuthIdentifier(),
                    title: (string) $payload['title'],
                    message: (string) $payload['message'],
                    url: $payload['url'] ?? null,
                    unreadCount: $user->unreadNotifications()->count(),
                    notificationId: $notification?->id,
                    notificationType: $payload['type'] ?? 'workflow',
                    actor: $payload['actor'] ?? null,
                    createdAt: optional($notification?->created_at)->toIso8601String(),
                    meta: $payload['meta'] ?? [],
                ));
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
