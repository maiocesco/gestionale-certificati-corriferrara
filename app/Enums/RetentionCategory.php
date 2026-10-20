<?php

namespace App\Enums;

/**
 * Categorie per cui è configurabile il periodo di conservazione dei
 * certificati scaduti. Rispecchia MembershipEntity più il caso "nessun
 * tesseramento": non riusa direttamente MembershipEntity perché quest'ultimo
 * rappresenta un tesseramento reale, non l'assenza di uno.
 */
enum RetentionCategory: string
{
    case Fidal = 'fidal';
    case Uisp = 'uisp';
    case Csi = 'csi';
    case None = 'none';

    public static function fromMembershipEntity(MembershipEntity $entity): self
    {
        return match ($entity) {
            MembershipEntity::Fidal => self::Fidal,
            MembershipEntity::Uisp => self::Uisp,
            MembershipEntity::Csi => self::Csi,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Fidal => 'FIDAL',
            self::Uisp => 'UISP',
            self::Csi => 'CSI',
            self::None => __('Nessun tesseramento'),
        };
    }

    /**
     * Anni di conservazione di default (da specifica), usati per
     * precaricare le impostazioni al primo avvio.
     */
    public function defaultYears(): int
    {
        return match ($this) {
            self::Fidal => 3,
            self::Csi => 5,
            self::Uisp, self::None => 10,
        };
    }
}
