<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CadastralCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CadastralCodeController extends Controller
{
    /**
     * Ricerca comuni italiani e stati esteri per il campo "luogo di
     * nascita" dell'anagrafica, ad uso dell'autocompletamento.
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\User::class);

        $term = $request->string('q')->trim()->toString();

        if (strlen($term) < 2) {
            return response()->json([]);
        }

        $results = CadastralCode::query()
            ->search($term)
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'province', 'type']);

        return response()->json($results->map(fn (CadastralCode $code) => [
            'id' => $code->id,
            'label' => $code->displayName(),
        ]));
    }
}
