<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CrmDataChanged implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $resource,
        public string $action,
        public ?int $id = null,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('crm-updates'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'crm.data.changed';
    }
}
