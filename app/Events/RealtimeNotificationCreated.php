<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RealtimeNotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $title,
        public readonly string $message,
        public readonly ?string $url = null,
        public readonly int $unreadCount = 0,
        public readonly ?string $notificationId = null,
        public readonly string $notificationType = 'workflow',
        public readonly ?string $actor = null,
        public readonly ?string $createdAt = null,
        public readonly array $meta = [],
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("users.{$this->userId}")];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notificationId,
            'type' => $this->notificationType,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'actor' => $this->actor,
            'meta' => $this->meta,
            'unread_count' => $this->unreadCount,
            'created_at' => $this->createdAt,
        ];
    }
}
