<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait FiltersAthletes
{
    /**
     * Filtri combinabili per società, ente, numero di tessera, disciplina
     * e sede, usati sia dall'elenco utenti sia dagli elenchi della
     * dashboard amministratore.
     */
    protected function applyAthleteFilters(Builder $query, Request $request): Builder
    {
        $filters = $request->only(['club_type', 'external_club_id', 'entity', 'discipline', 'card_number']);

        $hasMembershipFilters = collect($filters)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->isNotEmpty();

        return $query
            ->when($request->filled('training_location_id'), function ($query) use ($request) {
                $query->where('training_location_id', $request->input('training_location_id'));
            })
            ->when($hasMembershipFilters, function ($query) use ($request) {
                $query->whereHas('memberships', function ($query) use ($request) {
                    $query
                        ->when($request->filled('entity'), fn ($q) => $q->where('entity', $request->input('entity')))
                        ->when($request->filled('discipline'), fn ($q) => $q->where('discipline', $request->input('discipline')))
                        ->when($request->filled('club_type'), fn ($q) => $q->where('club_type', $request->input('club_type')))
                        ->when($request->filled('external_club_id'), fn ($q) => $q->where('external_club_id', $request->input('external_club_id')))
                        ->when($request->filled('card_number'), fn ($q) => $q->where('card_number', 'like', '%'.$request->input('card_number').'%'));
                });
            });
    }

    /**
     * @return array<string, mixed>
     */
    protected function athleteFilterOptions(): array
    {
        return [
            'entities' => \App\Enums\MembershipEntity::cases(),
            'disciplines' => \App\Enums\Discipline::cases(),
            'clubTypes' => \App\Enums\ClubType::cases(),
            'externalClubs' => \App\Models\ExternalClub::orderBy('name')->get(),
            'trainingLocations' => \App\Models\TrainingLocation::orderBy('name')->get(),
        ];
    }
}
