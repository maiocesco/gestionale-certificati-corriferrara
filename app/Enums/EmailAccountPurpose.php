<?php

namespace App\Enums;

enum EmailAccountPurpose: string
{
    case Pec = 'pec';
    case Classic = 'classic';

    public function label(): string
    {
        return match ($this) {
            self::Pec => __('PEC della società'),
            self::Classic => __('Email classica'),
        };
    }
}
