<?php

namespace App\Models;

use App\Enums\MembershipEntity;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'is_active',
        'must_change_password',
        'birth_date',
        'birth_cadastral_code_id',
        'fiscal_code',
        'training_location_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'birth_date' => 'date',
        ];
    }

    public function birthCadastralCode(): BelongsTo
    {
        return $this->belongsTo(CadastralCode::class, 'birth_cadastral_code_id');
    }

    public function trainingLocation(): BelongsTo
    {
        return $this->belongsTo(TrainingLocation::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isAthlete(): bool
    {
        return $this->role === UserRole::Athlete;
    }

    public function hasFidalMembership(): bool
    {
        return $this->memberships->contains(fn (Membership $membership) => $membership->entity === MembershipEntity::Fidal);
    }
}
