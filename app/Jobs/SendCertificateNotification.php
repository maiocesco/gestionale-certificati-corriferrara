<?php

namespace App\Jobs;

use App\Enums\NotificationStatus;
use App\Mail\CertificateNotificationEmail;
use App\Models\CertificateNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Invia un singolo promemoria/avviso di scadenza e aggiorna la riga
 * CertificateNotification già creata (stato "in coda") con l'esito. I
 * tentativi ripetuti in caso di errore SMTP sono gestiti dalla coda
 * stessa ($tries/$backoff); solo dopo l'ultimo tentativo fallito lo stato
 * passa a "fallito", visibile agli amministratori nel log invii.
 */
class SendCertificateNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300];

    public function __construct(
        public int $notificationId,
        public array $extraPlaceholders = [],
    ) {}

    public function handle(): void
    {
        $notification = CertificateNotification::with('certificate.user')->findOrFail($this->notificationId);

        Mail::to($notification->certificate->user->email)->send(
            new CertificateNotificationEmail($notification->certificate, $notification->type, $this->extraPlaceholders)
        );

        $notification->update(['status' => NotificationStatus::Sent->value]);
    }

    public function failed(?Throwable $exception): void
    {
        CertificateNotification::where('id', $this->notificationId)->update([
            'status' => NotificationStatus::Failed->value,
            'error_message' => $exception?->getMessage(),
        ]);
    }
}
