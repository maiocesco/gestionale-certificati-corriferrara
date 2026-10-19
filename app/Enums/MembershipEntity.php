<?php

namespace App\Enums;

enum MembershipEntity: string
{
    case Fidal = 'fidal';
    case Uisp = 'uisp';
    case Csi = 'csi';

    public function label(): string
    {
        return match ($this) {
            self::Fidal => 'FIDAL',
            self::Uisp => 'UISP',
            self::Csi => 'CSI',
        };
    }
}
