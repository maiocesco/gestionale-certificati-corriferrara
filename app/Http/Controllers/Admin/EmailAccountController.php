<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmailAccountPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateEmailAccountRequest;
use App\Mail\AccountTestEmail;
use App\Models\EmailAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class EmailAccountController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', EmailAccount::class);

        $accounts = EmailAccount::all()->keyBy(fn (EmailAccount $account) => $account->purpose->value);

        return view('admin.email-accounts.index', [
            'purposes' => EmailAccountPurpose::cases(),
            'accounts' => $accounts,
        ]);
    }

    public function update(UpdateEmailAccountRequest $request, EmailAccountPurpose $purpose): RedirectResponse
    {
        $data = $request->validated();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        EmailAccount::updateOrCreate(['purpose' => $purpose->value], $data);

        return redirect()->route('admin.email-accounts.index')->with('status', 'email-account-updated');
    }

    public function sendTest(EmailAccountPurpose $purpose): RedirectResponse
    {
        $this->authorize('update', EmailAccount::class);

        try {
            Mail::to($this->currentAdminEmail())->send(new AccountTestEmail($purpose));

            return redirect()->route('admin.email-accounts.index')->with('status', 'email-account-test-sent');
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('admin.email-accounts.index')
                ->with('status', 'email-account-test-failed')
                ->with('testError', $e->getMessage());
        }
    }

    private function currentAdminEmail(): string
    {
        return auth()->user()->email;
    }
}
