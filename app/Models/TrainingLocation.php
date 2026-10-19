<?php

namespace App\Models;

use Database\Factories\TrainingLocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingLocation extends Model
{
    /** @use HasFactory<TrainingLocationFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'city', 'address'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function displayName(): string
    {
        return "{$this->name} ({$this->city})";
    }
}
