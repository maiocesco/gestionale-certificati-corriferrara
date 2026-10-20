<?php

namespace App\Services;

use App\Enums\CertificateStatus;
use App\Enums\RetentionCategory;
use App\Models\Certificate;
use App\Models\RetentionSetting;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Unica fonte di verità per la politica di conservazione dei certificati
 * (vedi docs/SPECIFICHE.md): non duplicarla in controller o comandi.
 *
 * Regole:
 * - il periodo decorre dalla data di scadenza del certificato;
 * - se l'atleta ha più tesseramenti, vale il periodo più lungo tra quelli
 *   applicabili; senza tesseramenti si applica il periodo per "nessun
 *   tesseramento" (di default il più lungo previsto);
 * - i certificati validi o in attesa di verifica non sono mai eliminabili,
 *   qualunque sia la loro età.
 */
class RetentionService
{
    public function retentionYearsFor(User $user): int
    {
        $categories = $user->memberships()->get()
            ->pluck('entity')
            ->unique()
            ->map(fn ($entity) => RetentionCategory::fromMembershipEntity($entity));

        if ($categories->isEmpty()) {
            $categories = collect([RetentionCategory::None]);
        }

        return $categories->map(fn (RetentionCategory $category) => $this->yearsFor($category))->max();
    }

    public function isEligibleForDeletion(Certificate $certificate): bool
    {
        if ($certificate->isRetentionDeleted()) {
            return false;
        }

        if ($certificate->status === CertificateStatus::Pending || $certificate->isValid()) {
            return false;
        }

        $years = $this->retentionYearsFor($certificate->user);

        return $certificate->expires_at->copy()->addYears($years)->isPast();
    }

    /**
     * @return Collection<int, Certificate>
     */
    public function eligibleForDeletion(): Collection
    {
        return Certificate::whereNull('deleted_at')
            ->where('status', '!=', CertificateStatus::Pending->value)
            ->with('user.memberships')
            ->get()
            ->filter(fn (Certificate $certificate) => $this->isEligibleForDeletion($certificate));
    }

    /**
     * Elimina il file e riduce il certificato al record minimo richiesto
     * dalla specifica (atleta, tipo, scadenza, data ed esecutore
     * dell'eliminazione), a fini di tracciabilità.
     */
    public function delete(Certificate $certificate, User $admin, CertificateFileStorage $fileStorage): void
    {
        if ($certificate->file_path) {
            $fileStorage->delete($certificate->file_path);
        }

        $certificate->update([
            'file_path' => null,
            'file_original_name' => null,
            'file_mime' => null,
            'file_size' => null,
            'rejection_reason' => null,
            'deleted_at' => now(),
            'deleted_by' => $admin->id,
        ]);
    }

    private function yearsFor(RetentionCategory $category): int
    {
        return RetentionSetting::where('category', $category->value)->value('years') ?? $category->defaultYears();
    }
}
