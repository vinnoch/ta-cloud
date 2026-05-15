<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ThesisWorkflowNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly array $payload)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->payload['type'] ?? 'workflow',
            'title' => $this->payload['title'],
            'message' => $this->payload['message'],
            'url' => $this->payload['url'] ?? null,
            'actor' => $this->payload['actor'] ?? null,
            'meta' => $this->payload['meta'] ?? [],
        ];
    }
}
