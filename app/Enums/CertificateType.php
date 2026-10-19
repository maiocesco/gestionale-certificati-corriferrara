<?php

namespace App\Enums;

enum CertificateType: string
{
    case Competitive = 'competitive';
    case NonCompetitive = 'non_competitive';

    public function label(): string
    {
        return match ($this) {
            self::Competitive => __('Agonistico'),
            self::NonCompetitive => __('Non agonistico'),
        };
    }
}
