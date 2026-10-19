<?php

namespace App\Services\Coverage;

use App\Enums\Discipline;

/**
 * Un requisito di copertura certificati per un atleta:
 * - un agonistico per una specifica disciplina (tesseramento agonistico in quella disciplina), oppure
 * - un qualunque certificato valido (attività non agonistica, Runner's School, allenamenti).
 */
final readonly class CoverageRequirement
{
    private function __construct(
        public string $key,
        public ?Discipline $discipline,
    ) {}

    public static function forCompetitiveDiscipline(Discipline $discipline): self
    {
        return new self('competitive:'.$discipline->value, $discipline);
    }

    public static function any(): self
    {
        return new self('any', null);
    }

    public function isAny(): bool
    {
        return $this->discipline === null;
    }

    public function label(): string
    {
        return $this->isAny()
            ? __('Un certificato valido (attività non agonistica)')
            : __('Agonistico :discipline', ['discipline' => $this->discipline->label()]);
    }
}
