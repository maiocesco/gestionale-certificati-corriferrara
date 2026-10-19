<?php

namespace App\Services;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Models\Certificate;
use App\Models\Membership;
use App\Models\User;
use App\Services\Coverage\CoverageRequirement;
use App\Services\Coverage\CoverageResult;
use Illuminate\Support\Collection;

/**
 * Unica fonte di verità per le regole di copertura dei certificati medici.
 *
 * Regole (vedi docs/SPECIFICHE.md):
 * - un agonistico vale per una sola disciplina;
 * - per ogni disciplina con almeno un tesseramento agonistico serve un
 *   agonistico valido per quella disciplina;
 * - in ogni caso serve almeno un certificato valido di qualunque tipo
 *   (un agonistico di qualunque disciplina copre anche questo requisito).
 *
 * Questa classe è l'unico posto che implementa questa logica: non va
 * duplicata in controller, viste o query.
 */
class CertificateCoverageService
{
    /**
     * @return Collection<int, CoverageRequirement>
     */
    public function requirementsFor(User $user): Collection
    {
        $competitiveDisciplines = $user->memberships()->get()
            ->filter(fn (Membership $membership) => $membership->is_competitive)
            ->pluck('discipline')
            ->unique();

        $requirements = $competitiveDisciplines
            ->map(fn ($discipline) => CoverageRequirement::forCompetitiveDiscipline($discipline))
            ->values();

        return $requirements->push(CoverageRequirement::any());
    }

    /**
     * @return Collection<int, CoverageResult>
     */
    public function coverageFor(User $user): Collection
    {
        return $this->coverageAgainst($user, $this->validCertificates($user));
    }

    public function isCompliant(User $user): bool
    {
        return $this->coverageFor($user)->every(fn (CoverageResult $result) => $result->isCovered());
    }

    /**
     * Requisiti ancora scoperti per l'atleta (usato per la dashboard
     * "atleti non in regola").
     *
     * @return Collection<int, CoverageResult>
     */
    public function gapsFor(User $user): Collection
    {
        return $this->coverageFor($user)->reject(fn (CoverageResult $result) => $result->isCovered());
    }

    /**
     * Descrizione testuale di un requisito scoperto, distinguendo "mai
     * caricato" da "scaduto" quando possibile (es. "agonistico nuoto
     * scaduto", "nessun certificato valido").
     */
    public function gapDescription(User $user, CoverageRequirement $requirement): string
    {
        if ($requirement->isAny()) {
            return $user->certificates()->get()->isEmpty()
                ? __('Nessun certificato caricato')
                : __('Nessun certificato valido');
        }

        $hadCertificateForDiscipline = $user->certificates()->get()->contains(
            fn (Certificate $certificate) => $certificate->type === CertificateType::Competitive
                && $certificate->discipline === $requirement->discipline
        );

        return $hadCertificateForDiscipline
            ? __('Agonistico :discipline scaduto', ['discipline' => $requirement->discipline->label()])
            : __('Agonistico :discipline mancante', ['discipline' => $requirement->discipline->label()]);
    }

    /**
     * Copertura residua se il certificato indicato smettesse di essere
     * valido (usato per distinguere diffida/sospensione parziale in
     * Fase 6: nessuna copertura residua -> diffida; resta qualcosa ->
     * sospensione parziale).
     *
     * @return Collection<int, CoverageResult>
     */
    public function remainingCoverageWithout(User $user, Certificate $expiringCertificate): Collection
    {
        $remaining = $this->validCertificates($user)->reject(
            fn (Certificate $certificate) => $certificate->is($expiringCertificate)
        );

        return $this->coverageAgainst($user, $remaining);
    }

    /**
     * Un certificato approvato è "superato" quando esiste un altro
     * certificato approvato dello stesso ambito (tipo e, se agonistico,
     * disciplina) con scadenza più lontana (o più recente a parità di
     * scadenza). Resta nello storico ma non è più quello di riferimento.
     */
    public function isSuperseded(Certificate $certificate): bool
    {
        if ($certificate->status !== CertificateStatus::Approved) {
            return false;
        }

        return Certificate::where('user_id', $certificate->user_id)->get()
            ->filter(fn (Certificate $other) => ! $other->is($certificate)
                && $other->status === CertificateStatus::Approved
                && $other->sameScopeAs($certificate))
            ->contains(function (Certificate $other) use ($certificate) {
                if (! $other->expires_at->equalTo($certificate->expires_at)) {
                    return $other->expires_at->greaterThan($certificate->expires_at);
                }

                return $other->created_at->greaterThan($certificate->created_at);
            });
    }

    /**
     * @return Collection<int, Certificate>
     */
    private function validCertificates(User $user): Collection
    {
        return $user->certificates()->get()->filter(fn (Certificate $certificate) => $certificate->isValid());
    }

    /**
     * @param  Collection<int, Certificate>  $availableCertificates
     * @return Collection<int, CoverageResult>
     */
    private function coverageAgainst(User $user, Collection $availableCertificates): Collection
    {
        return $this->requirementsFor($user)->map(
            fn (CoverageRequirement $requirement) => new CoverageResult(
                $requirement,
                $this->bestCertificateFor($requirement, $availableCertificates),
            )
        );
    }

    private function bestCertificateFor(CoverageRequirement $requirement, Collection $availableCertificates): ?Certificate
    {
        return $availableCertificates
            ->filter(function (Certificate $certificate) use ($requirement) {
                if ($requirement->isAny()) {
                    return true;
                }

                return $certificate->type === CertificateType::Competitive
                    && $certificate->discipline === $requirement->discipline;
            })
            ->sortByDesc(fn (Certificate $certificate) => $certificate->expires_at)
            ->first();
    }
}
