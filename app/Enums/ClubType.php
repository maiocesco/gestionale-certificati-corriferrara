<?php

namespace App\Enums;

enum ClubType: string
{
    case Corriferrara = 'corriferrara';
    case CorriferraraBike = 'corriferrara_bike';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Corriferrara => 'Corriferrara',
            self::CorriferraraBike => 'Corriferrara Bike',
            self::External => __('Altra società'),
        };
    }

    /**
     * Discipline ammesse per questo tipo di società (null = qualunque).
     *
     * @return Discipline[]|null
     */
    public function allowedDisciplines(): ?array
    {
        return match ($this) {
            self::Corriferrara => [Discipline::AtleticaLeggera, Discipline::Nuoto],
            self::CorriferraraBike => [Discipline::Ciclismo],
            self::External => null,
        };
    }
}
