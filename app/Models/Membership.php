<?php

namespace App\Models;

use App\Enums\ClubType;
use App\Enums\Discipline;
use App\Enums\MembershipEntity;
use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Membership extends Model
{
    /** @use HasFactory<MembershipFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'entity',
        'discipline',
        'club_type',
        'external_club_id',
        'card_number',
        'is_competitive',
    ];

    protected function casts(): array
    {
        return [
            'entity' => MembershipEntity::class,
            'discipline' => Discipline::class,
            'club_type' => ClubType::class,
            'is_competitive' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function externalClub(): BelongsTo
    {
        return $this->belongsTo(ExternalClub::class);
    }

    public function clubName(): string
    {
        return $this->club_type === ClubType::External
            ? ($this->externalClub?->name ?? __('Altra società'))
            : $this->club_type->label();
    }
}
