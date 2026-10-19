<?php

namespace App\Models;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Enums\Discipline;
use Database\Factories\CertificateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    /** @use HasFactory<CertificateFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'uploaded_by',
        'type',
        'discipline',
        'expires_at',
        'file_path',
        'file_original_name',
        'file_mime',
        'file_size',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => CertificateType::class,
            'discipline' => Discipline::class,
            'status' => CertificateStatus::class,
            'expires_at' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Un certificato è considerato scaduto a partire dal giorno stesso
     * della scadenza (incluso): la specifica fa coincidere l'invio
     * dell'avviso di scadenza con la verifica della copertura "dopo la
     * scadenza di quel certificato" proprio in quel giorno.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->status === CertificateStatus::Approved && ! $this->isExpired();
    }

    /**
     * "Ambito" del certificato: tipo e, se agonistico, disciplina.
     * Due certificati con lo stesso ambito coprono lo stesso requisito.
     */
    public function sameScopeAs(self $other): bool
    {
        return $this->type === $other->type && $this->discipline === $other->discipline;
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('status', CertificateStatus::Approved->value)
            ->whereDate('expires_at', '>', now()->toDateString());
    }

    public function scopeExpiringOn(Builder $query, \DateTimeInterface $date): Builder
    {
        return $query->where('status', CertificateStatus::Approved->value)
            ->whereDate('expires_at', $date->format('Y-m-d'));
    }

    /**
     * Descrizione testuale del tipo, con la disciplina se agonistico
     * (es. "agonistico per il nuoto", "non agonistico").
     */
    public function typeDescription(): string
    {
        if ($this->type === CertificateType::Competitive) {
            return __('agonistico per :discipline', ['discipline' => mb_strtolower($this->discipline->label())]);
        }

        return __('non agonistico');
    }
}
