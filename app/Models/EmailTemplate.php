<?php

namespace App\Models;

use App\Enums\EmailTemplateKey;
use Database\Factories\EmailTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    /** @use HasFactory<EmailTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'subject',
        'body_markdown',
    ];

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    public function keyEnum(): EmailTemplateKey
    {
        return EmailTemplateKey::from($this->key);
    }
}
