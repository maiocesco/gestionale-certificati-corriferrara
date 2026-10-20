<?php

namespace App\Models;

use App\Enums\EmailAccountPurpose;
use App\Enums\SmtpEncryption;
use Database\Factories\EmailAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailAccount extends Model
{
    /** @use HasFactory<EmailAccountFactory> */
    use HasFactory;

    protected $fillable = [
        'purpose',
        'from_address',
        'from_name',
        'host',
        'port',
        'encryption',
        'username',
        'password',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => EmailAccountPurpose::class,
            'encryption' => SmtpEncryption::class,
            'port' => 'integer',
            'password' => 'encrypted',
        ];
    }

    public function isConfigured(): bool
    {
        return filled($this->host) && filled($this->from_address);
    }
}
