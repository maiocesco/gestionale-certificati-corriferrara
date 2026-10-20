<?php

namespace App\Enums;

enum NotificationStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => __('In coda'),
            self::Sent => __('Inviato'),
            self::Failed => __('Fallito'),
        };
    }
}
