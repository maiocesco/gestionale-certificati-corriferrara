<?php

use App\Enums\UserRole;
use App\Mail\PasswordResetByAdminEmail;
use App\Mail\WelcomeEmail;
use App\Models\CadastralCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->ferrara = CadastralCode::factory()->create([
        'name' => 'FERRARA',
        'province' => 'FE',
        'belfiore_code' => 'D548',
    ]);
});

function validUserPayload(int $cadastralCodeId, array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'email' => 'nuovo.atleta@example.com',
        'birth_date' => '1985-03-15',
        'birth_cadastral_code_id' => $cadastralCodeId,
        'fiscal_code' => 'RSSMRA85C15D548K',
        'role' => UserRole::Athlete->value,
    ], $overrides);
}

it('impedisce a un non amministratore di accedere all\'area admin', function () {
    $athlete = User::factory()->create();

    $this->actingAs($athlete)->get(route('admin.users.index'))->assertForbidden();
});

it('mostra l\'elenco utenti all\'amministratore', function () {
    $this->actingAs($this->admin)->get(route('admin.users.index'))->assertOk();
});

it('crea un nuovo utente e invia l\'email di benvenuto', function () {
    Mail::fake();

    $response = $this->actingAs($this->admin)->post(route('admin.users.store'), validUserPayload($this->ferrara->id));

    $response->assertRedirect(route('admin.users.index'));

    $user = User::where('email', 'nuovo.atleta@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->must_change_password)->toBeTrue();
    expect($user->fiscal_code)->toBe('RSSMRA85C15D548K');
    expect($user->birth_cadastral_code_id)->toBe($this->ferrara->id);

    Mail::assertQueued(WelcomeEmail::class, fn ($mail) => $mail->user->is($user));
});

it('rifiuta un codice fiscale non coerente con i dati anagrafici', function () {
    Mail::fake();

    $response = $this->actingAs($this->admin)->post(
        route('admin.users.store'),
        validUserPayload($this->ferrara->id, ['fiscal_code' => 'RSSMRA85C15D548X'])
    );

    $response->assertSessionHasErrors('fiscal_code');
    expect(User::where('email', 'nuovo.atleta@example.com')->exists())->toBeFalse();
});

it('rifiuta email o codice fiscale duplicati', function () {
    $existing = User::factory()->create(['email' => 'esiste@example.com', 'fiscal_code' => 'RSSMRA85C15D548K']);

    $response = $this->actingAs($this->admin)->post(
        route('admin.users.store'),
        validUserPayload($this->ferrara->id, ['email' => 'esiste@example.com'])
    );
    $response->assertSessionHasErrors('email');

    $response = $this->actingAs($this->admin)->post(
        route('admin.users.store'),
        validUserPayload($this->ferrara->id, ['fiscal_code' => 'RSSMRA85C15D548K'])
    );
    $response->assertSessionHasErrors('fiscal_code');
});

it('permette all\'amministratore di modificare l\'anagrafica di un utente', function () {
    $athlete = User::factory()->create();

    $response = $this->actingAs($this->admin)->put(
        route('admin.users.update', $athlete),
        validUserPayload($this->ferrara->id, ['email' => $athlete->email])
    );

    $response->assertRedirect(route('admin.users.index'));
    expect($athlete->refresh()->first_name)->toBe('Mario');
    expect($athlete->fiscal_code)->toBe('RSSMRA85C15D548K');
});

it('impedisce a un atleta di modificare utenti', function () {
    $athlete = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($athlete)->get(route('admin.users.edit', $other))->assertForbidden();
    $this->actingAs($athlete)->put(route('admin.users.update', $other), [])->assertForbidden();
});

it('reimposta la password di un utente e invia l\'email', function () {
    Mail::fake();

    $athlete = User::factory()->create(['password' => bcrypt('vecchia-Password1!'), 'must_change_password' => false]);

    $response = $this->actingAs($this->admin)->put(route('admin.users.reset-password', $athlete));

    $response->assertRedirect();
    $athlete->refresh();
    expect($athlete->must_change_password)->toBeTrue();
    expect(Hash::check('vecchia-Password1!', $athlete->password))->toBeFalse();

    Mail::assertQueued(PasswordResetByAdminEmail::class, fn ($mail) => $mail->user->is($athlete));
});

it('permette di disattivare e riattivare un utente', function () {
    $athlete = User::factory()->create(['is_active' => true]);

    $this->actingAs($this->admin)->put(route('admin.users.toggle-active', $athlete));
    expect($athlete->refresh()->is_active)->toBeFalse();

    $this->actingAs($this->admin)->put(route('admin.users.toggle-active', $athlete));
    expect($athlete->refresh()->is_active)->toBeTrue();
});

it('impedisce all\'amministratore di disattivare se stesso', function () {
    $this->actingAs($this->admin)->put(route('admin.users.toggle-active', $this->admin))->assertForbidden();
    expect($this->admin->refresh()->is_active)->toBeTrue();
});

it('filtra l\'elenco utenti per nome, email o codice fiscale', function () {
    User::factory()->create(['first_name' => 'Mario', 'last_name' => 'Rossi']);
    User::factory()->create(['first_name' => 'Luigi', 'last_name' => 'Verdi']);

    $response = $this->actingAs($this->admin)->get(route('admin.users.index', ['q' => 'Mario']));

    $response->assertOk();
    $response->assertSee('Mario Rossi');
    $response->assertDontSee('Luigi Verdi');
});
