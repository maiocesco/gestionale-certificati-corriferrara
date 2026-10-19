<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Athlete = 'athlete';

    public function label(): string
    {
        return match ($this) {
            self::Admin => __('Amministratore'),
            self::Athlete => __('Atleta'),
        };
    }
}
