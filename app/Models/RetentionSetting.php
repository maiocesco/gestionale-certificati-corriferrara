<?php

namespace App\Models;

use App\Enums\RetentionCategory;
use Illuminate\Database\Eloquent\Model;

class RetentionSetting extends Model
{
    protected $fillable = [
        'category',
        'years',
    ];

    protected function casts(): array
    {
        return [
            'category' => RetentionCategory::class,
            'years' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'category';
    }
}
