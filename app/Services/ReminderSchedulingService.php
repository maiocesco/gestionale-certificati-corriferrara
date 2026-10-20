<?php

namespace App\Services;

use App\Enums\CertificateStatus;
use App\Enums\EmailTemplateKey;
use App\Enums\NotificationStatus;
use App\Jobs\SendCertificateNotification;
use App\Models\Certificate;
use App\Models\CertificateNotification;
use App\Services\Coverage\CoverageResult;
use Illuminate\Support\Collection;

/**
 * Determina, per ogni certificato approvato, quale promemoria o avviso di
 * scadenza manca ancora e lo accoda. Unica fonte di verità per questa
 * logica: non duplicarla altrove (vedi CertificateCoverageService per la
 * stessa regola sulla copertura).
 *
 * Ogni certificato riceve al più un invio per esecuzione: la tappa più
 * urgente ancora da inviare. Dopo un'interruzione dello scheduler questo
 * evita di "recuperare" in blocco più avvisi superati sullo stesso
 * certificato (es. passare da 32 a 2 giorni senza i promemoria a 30/10)
 * — arriva solo l'avviso ancora rilevante.
 */
class ReminderSchedulingService
{
    /**
     * Soglie in giorni-alla-scadenza, dalla più urgente alla meno urgente.
     * La tappa "0" rappresenta la scadenza stessa: la scelta tra diffida e
     * sospensione parziale si fa al momento dell'invio, in base alla
     * copertura residua.
     *
     * @var array<int, list<EmailTemplateKey>>
     */
    private const CHECKPOINTS = [
        0 => [EmailTemplateKey::ExpiryNotice, EmailTemplateKey::PartialSuspension],
        3 => [EmailTemplateKey::Reminder3],
        10 => [EmailTemplateKey::Reminder10],
        30 => [EmailTemplateKey::Reminder30],
    ];

    public function __construct(
        private readonly CertificateCoverageService $coverageService,
    ) {}

    /**
     * Accoda tutti gli avvisi dovuti. Restituisce quanti ne ha accodati.
     */
    public function sendDueNotifications(): int
    {
        return Certificate::where('status', CertificateStatus::Approved->value)
            ->with('user')
            ->get()
            ->filter(fn (Certificate $certificate) => $this->notifyIfDue($certificate))
            ->count();
    }

    private function notifyIfDue(Certificate $certificate): bool
    {
        if (! $certificate->user->is_active) {
            return false;
        }

        if ($this->coverageService->isSuperseded($certificate)) {
            return false;
        }

        $days = (int) now()->startOfDay()->diffInDays($certificate->expires_at, false);

        $checkpoint = $this->dueCheckpoint($certificate, $days);
        if ($checkpoint === null) {
            return false;
        }

        [$threshold, $candidateKeys] = $checkpoint;

        [$key, $extraPlaceholders] = $threshold === 0
            ? $this->resolveExpiryNotice($certificate)
            : [$candidateKeys[0], []];

        $this->queue($certificate, $key, $extraPlaceholders);

        return true;
    }

    /**
     * @return array{0: int, 1: list<EmailTemplateKey>}|null
     */
    private function dueCheckpoint(Certificate $certificate, int $days): ?array
    {
        foreach (self::CHECKPOINTS as $threshold => $keys) {
            if ($days > $threshold) {
                continue;
            }

            $alreadyHandled = CertificateNotification::where('certificate_id', $certificate->id)
                ->whereIn('type', array_map(fn (EmailTemplateKey $key) => $key->value, $keys))
                ->whereIn('status', [NotificationStatus::Queued->value, NotificationStatus::Sent->value])
                ->exists();

            if (! $alreadyHandled) {
                return [$threshold, $keys];
            }
        }

        return null;
    }

    /**
     * @return array{0: EmailTemplateKey, 1: array<string, string>}
     */
    private function resolveExpiryNotice(Certificate $certificate): array
    {
        $remaining = $this->coverageService->remainingCoverageWithout($certificate->user, $certificate);
        $covered = $remaining->filter(fn (CoverageResult $result) => $result->isCovered());

        if ($covered->isEmpty()) {
            return [EmailTemplateKey::ExpiryNotice, []];
        }

        return [EmailTemplateKey::PartialSuspension, [
            'attivita_consentite' => $this->describeAllowedActivities($covered),
        ]];
    }

    /**
     * @param  Collection<int, CoverageResult>  $covered
     */
    private function describeAllowedActivities(Collection $covered): string
    {
        return $covered
            ->map(fn (CoverageResult $result) => $result->requirement->isAny()
                ? __('attività non agonistica in tutte le discipline')
                : __('attività agonistica di :discipline', [
                    'discipline' => mb_strtolower($result->requirement->discipline->label()),
                ]))
            ->implode('; ');
    }

    private function queue(Certificate $certificate, EmailTemplateKey $key, array $extraPlaceholders): void
    {
        $notification = CertificateNotification::create([
            'certificate_id' => $certificate->id,
            'type' => $key->value,
            'status' => NotificationStatus::Queued->value,
        ]);

        SendCertificateNotification::dispatch($notification->id, $extraPlaceholders);
    }
}
