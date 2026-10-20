<?php

use App\Enums\EmailTemplateKey;
use App\Mail\TemplatePreviewEmail;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();

    foreach (EmailTemplateKey::cases() as $key) {
        EmailTemplate::factory()->create([
            'key' => $key->value,
            'subject' => $key->defaultSubject(),
            'body_markdown' => $key->defaultBody(),
        ]);
    }
});

it('impedisce a un non amministratore di accedere ai testi email', function () {
    $athlete = User::factory()->create();

    $this->actingAs($athlete)->get(route('admin.email-templates.index'))->assertForbidden();
});

it('mostra l\'elenco dei testi email', function () {
    $this->actingAs($this->admin)->get(route('admin.email-templates.index'))->assertOk();
});

it('mostra la pagina di modifica di un testo email', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.email-templates.edit', EmailTemplateKey::Welcome->value))
        ->assertOk()
        ->assertSee('password_provvisoria');
});

it('salva un testo email modificato', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.email-templates.update', EmailTemplateKey::Welcome->value), [
        'subject' => 'Nuovo oggetto',
        'body_markdown' => 'Ciao {{nome}}, ecco la tua password provvisoria: {{password_provvisoria}}.',
    ]);

    $response->assertRedirect(route('admin.email-templates.edit', EmailTemplateKey::Welcome->value));

    $template = EmailTemplate::where('key', EmailTemplateKey::Welcome->value)->first();
    expect($template->subject)->toBe('Nuovo oggetto');
    expect($template->body_markdown)->toContain('{{password_provvisoria}}');
});

it('blocca il salvataggio se manca un segnaposto obbligatorio', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.email-templates.update', EmailTemplateKey::Welcome->value), [
        'subject' => 'Nuovo oggetto',
        'body_markdown' => 'Ciao {{nome}}, senza la password.',
    ]);

    $response->assertSessionHasErrors('body_markdown');

    $template = EmailTemplate::where('key', EmailTemplateKey::Welcome->value)->first();
    expect($template->subject)->not->toBe('Nuovo oggetto');
});

it('mostra un\'anteprima con dati di esempio senza salvare', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.email-templates.preview', EmailTemplateKey::Welcome->value), [
        'subject' => 'Oggetto non salvato',
        'body_markdown' => 'Ciao {{nome}}, password: {{password_provvisoria}}.',
    ]);

    $response->assertOk()->assertSee('Mario');

    $template = EmailTemplate::where('key', EmailTemplateKey::Welcome->value)->first();
    expect($template->subject)->not->toBe('Oggetto non salvato');
});

it('invia un\'email di prova con dati di esempio', function () {
    Mail::fake();

    $response = $this->actingAs($this->admin)->post(route('admin.email-templates.test', EmailTemplateKey::Welcome->value));

    $response->assertRedirect(route('admin.email-templates.edit', EmailTemplateKey::Welcome->value));
    Mail::assertSent(TemplatePreviewEmail::class);
});

it('non permette di inviare una prova per la firma comune, che non è un\'email a se stante', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.email-templates.test', EmailTemplateKey::Signature->value))
        ->assertNotFound();
});

it('ripristina il testo di default di un\'email modificata', function () {
    $template = EmailTemplate::where('key', EmailTemplateKey::Welcome->value)->first();
    $template->update(['subject' => 'Modificato', 'body_markdown' => 'Testo modificato {{password_provvisoria}}']);

    $response = $this->actingAs($this->admin)->put(route('admin.email-templates.reset', EmailTemplateKey::Welcome->value));

    $response->assertRedirect(route('admin.email-templates.edit', EmailTemplateKey::Welcome->value));

    $template->refresh();
    expect($template->subject)->toBe(EmailTemplateKey::Welcome->defaultSubject());
    expect($template->body_markdown)->toBe(EmailTemplateKey::Welcome->defaultBody());
});
