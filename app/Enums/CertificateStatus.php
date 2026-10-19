<?php

namespace App\Enums;

enum CertificateStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('In attesa di verifica'),
            self::Approved => __('Approvato'),
            self::Rejected => __('Rifiutato'),
        };
    }
}
