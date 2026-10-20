<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Mostra il profilo dell'utente autenticato.
     *
     * L'anagrafica è gestita esclusivamente dall'amministratore: l'atleta
     * la visualizza qui in sola lettura e può modificare solo la password.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $user->load(['memberships.externalClub', 'trainingLocation']);

        return view('profile.edit', [
            'user' => $user,
        ]);
    }

    /**
     * Attiva/disattiva il riepilogo giornaliero via email: preferenza
     * personale di ogni amministratore, disattivata di default.
     */
    public function updateDigestPreference(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $request->user()->update([
            'daily_digest_enabled' => $request->boolean('daily_digest_enabled'),
        ]);

        return redirect()->route('profile.edit')->with('status', 'digest-preference-updated');
    }
}
