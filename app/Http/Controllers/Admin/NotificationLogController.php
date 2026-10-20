<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationStatus;
use App\Http\Controllers\Controller;
use App\Models\CertificateNotification;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', CertificateNotification::class);

        $notifications = CertificateNotification::query()
            ->with('certificate.user')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.notification-log.index', [
            'notifications' => $notifications,
            'statuses' => NotificationStatus::cases(),
            'selectedStatus' => $request->string('status')->toString(),
        ]);
    }
}
