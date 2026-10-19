<?php

namespace App\Enums;

enum CadastralCodeType: string
{
    case Comune = 'comune';
    case ForeignState = 'stato_estero';

    public function label(): string
    {
        return match ($this) {
            self::Comune => __('Comune italiano'),
            self::ForeignState => __('Stato estero'),
        };
    }
}
