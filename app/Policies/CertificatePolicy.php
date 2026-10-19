<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Certificate $certificate): bool
    {
        return $user->isAdmin() || $user->is($certificate->user);
    }

    public function viewFile(User $user, Certificate $certificate): bool
    {
        return $this->view($user, $certificate);
    }

    public function review(User $user, Certificate $certificate): bool
    {
        return $user->isAdmin();
    }
}
