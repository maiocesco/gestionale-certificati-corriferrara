<?php

namespace App\Enums;

enum Discipline: string
{
    case AtleticaLeggera = 'atletica_leggera';
    case Ciclismo = 'ciclismo';
    case Nuoto = 'nuoto';

    public function label(): string
    {
        return match ($this) {
            self::AtleticaLeggera => __('Atletica leggera'),
            self::Ciclismo => __('Ciclismo'),
            self::Nuoto => __('Nuoto'),
        };
    }
}
