<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExternalClub;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExternalClubController extends Controller
{
    /**
     * Ricerca società esterne esistenti, per il campo "altra società"
     * nel form dei tesseramenti.
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $term = $request->string('q')->trim()->toString();

        if (strlen($term) < 2) {
            return response()->json([]);
        }

        $results = ExternalClub::query()
            ->where('name', 'like', "%{$term}%")
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name']);

        return response()->json($results->map(fn (ExternalClub $club) => [
            'id' => $club->id,
            'label' => $club->name,
        ]));
    }
}
