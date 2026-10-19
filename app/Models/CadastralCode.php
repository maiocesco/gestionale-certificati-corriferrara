<?php

namespace App\Models;

use App\Enums\CadastralCodeType;
use Database\Factories\CadastralCodeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CadastralCode extends Model
{
    /** @use HasFactory<CadastralCodeFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'province',
        'name',
        'belfiore_code',
        'valid_from',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'type' => CadastralCodeType::class,
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where('name', 'like', '%'.$term.'%');
    }

    public function displayName(): string
    {
        return $this->type === CadastralCodeType::ForeignState
            ? $this->name
            : "{$this->name} ({$this->province})";
    }
}
