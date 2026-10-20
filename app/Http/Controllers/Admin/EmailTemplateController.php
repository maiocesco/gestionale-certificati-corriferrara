<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmailTemplateKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateEmailTemplateRequest;
use App\Mail\TemplatePreviewEmail;
use App\Models\EmailTemplate;
use App\Services\Mail\EmailTemplateRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class EmailTemplateController extends Controller
{
    public function __construct(
        private readonly EmailTemplateRenderer $renderer,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', EmailTemplate::class);

        $templates = EmailTemplate::all()->keyBy('key');

        return view('admin.email-templates.index', [
            'keys' => EmailTemplateKey::cases(),
            'templates' => $templates,
        ]);
    }

    public function edit(EmailTemplate $emailTemplate): View
    {
        $this->authorize('update', EmailTemplate::class);

        return view('admin.email-templates.edit', [
            'emailTemplate' => $emailTemplate,
            'subjectValue' => old('subject', $emailTemplate->subject),
            'bodyValue' => old('body_markdown', $emailTemplate->body_markdown),
            'preview' => null,
        ]);
    }

    public function update(UpdateEmailTemplateRequest $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $emailTemplate->update($request->validated());

        return redirect()->route('admin.email-templates.edit', $emailTemplate)->with('status', 'email-template-updated');
    }

    public function preview(Request $request, EmailTemplate $emailTemplate): View
    {
        $this->authorize('update', EmailTemplate::class);

        $subject = $request->input('subject');
        $body = (string) $request->input('body_markdown', $emailTemplate->body_markdown);

        return view('admin.email-templates.edit', [
            'emailTemplate' => $emailTemplate,
            'subjectValue' => $subject,
            'bodyValue' => $body,
            'preview' => $this->renderer->renderTemplate($subject, $body, EmailTemplateRenderer::samplePlaceholders()),
        ]);
    }

    public function sendTest(EmailTemplate $emailTemplate): RedirectResponse
    {
        $this->authorize('update', EmailTemplate::class);

        $key = $emailTemplate->keyEnum();

        if ($key->mailerPurpose() === null) {
            abort(404);
        }

        try {
            Mail::to(auth()->user()->email)->send(new TemplatePreviewEmail($key));

            $status = 'email-template-test-sent';
        } catch (Throwable $e) {
            report($e);

            $status = 'email-template-test-failed';
        }

        return redirect()->route('admin.email-templates.edit', $emailTemplate)->with('status', $status);
    }

    public function reset(EmailTemplate $emailTemplate): RedirectResponse
    {
        $this->authorize('update', EmailTemplate::class);

        $key = $emailTemplate->keyEnum();

        $emailTemplate->update([
            'subject' => $key->defaultSubject(),
            'body_markdown' => $key->defaultBody(),
        ]);

        return redirect()->route('admin.email-templates.edit', $emailTemplate)->with('status', 'email-template-reset');
    }
}
