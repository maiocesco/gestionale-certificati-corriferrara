<?php

namespace App\Enums;

enum Discipline: string
{
    case AtleticaLeggera = 'atletica_leggera';
    case Ciclismo = 'ciclismo';
    case Nuoto = 'nuoto';

    /**
     * Agonistico per uno sport diverso da quelli tesserabili presso la
     * società (es. basket): non essendo una disciplina tesserabile, non è
     * una delle discipline selezionabili per un tesseramento, ma come
     * certificato copre comunque il requisito generico (attività non
     * agonistica), esattamente come un certificato non agonistico.
     */
    case AltriSport = 'altri_sport';

    public function label(): string
    {
        return match ($this) {
            self::AtleticaLeggera => __('Atletica leggera'),
            self::Ciclismo => __('Ciclismo'),
            self::Nuoto => __('Nuoto'),
            self::AltriSport => __('Altri sport'),
        };
    }

    /**
     * Discipline selezionabili per un tesseramento (esclude "Altri
     * sport", che non è una disciplina tesserabile presso la società).
     *
     * @return list<self>
     */
    public static function membershipCases(): array
    {
        return array_values(array_filter(self::cases(), fn (self $discipline) => $discipline !== self::AltriSport));
    }
}
