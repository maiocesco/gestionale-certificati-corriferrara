<?php

namespace App\Models;

use App\Enums\EmailTemplateKey;
use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificateNotification extends Model
{
    protected $fillable = [
        'certificate_id',
        'type',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'type' => EmailTemplateKey::class,
            'status' => NotificationStatus::class,
        ];
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }
}
