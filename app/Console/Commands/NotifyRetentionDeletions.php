<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Mail\RetentionNoticeEmail;
use App\Models\User;
use App\Services\RetentionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class NotifyRetentionDeletions extends Command
{
    protected $signature = 'certificates:retention-notify';

    protected $description = 'Individua i certificati appena entrati nell\'elenco "da eliminare" e avvisa gli amministratori';

    public function handle(RetentionService $retentionService): int
    {
        $newlyEligible = $retentionService->eligibleForDeletion()
            ->whereNull('retention_notified_at');

        if ($newlyEligible->isEmpty()) {
            $this->info('Nessun nuovo certificato oltre il periodo di conservazione.');

            return self::SUCCESS;
        }

        foreach ($newlyEligible as $certificate) {
            $certificate->update(['retention_notified_at' => now()]);
        }

        $admins = User::where('role', UserRole::Admin->value)->where('is_active', true)->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->send(new RetentionNoticeEmail($admin, $newlyEligible->count()));
        }

        $this->info("{$newlyEligible->count()} nuovi certificati da eliminare, notificati a {$admins->count()} amministratori.");

        return self::SUCCESS;
    }
}
