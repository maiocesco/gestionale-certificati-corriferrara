<?php

namespace App\Http\Controllers;

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
}
