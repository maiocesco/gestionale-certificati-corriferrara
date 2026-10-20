<?php

namespace App\Services\Import;

use App\Enums\CertificateType;
use App\Enums\Discipline;
use Carbon\CarbonImmutable;

/**
 * Un singolo certificato da importare: un tipo+disciplina (un'eventuale
 * riga del CSV con più tipologie elencate viene già scomposta a monte in
 * più ImportedCertificateRow, una per tipologia).
 */
final readonly class ImportedCertificateRow
{
    /**
     * @param  list<string>  $driveLinks  Uno o più link allo stesso certificato (pagine diverse dello stesso scan).
     */
    public function __construct(
        public int $csvLineNumber,
        public CertificateType $type,
        public ?Discipline $discipline,
        public CarbonImmutable $expiresAt,
        public array $driveLinks,
        public CarbonImmutable $submittedAt,
    ) {}
}
