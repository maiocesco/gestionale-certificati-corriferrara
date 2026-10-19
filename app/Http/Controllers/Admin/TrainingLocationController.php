<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTrainingLocationRequest;
use App\Models\TrainingLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TrainingLocationController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', TrainingLocation::class);

        $locations = TrainingLocation::withCount('users')->orderBy('name')->get();

        return view('admin.training-locations.index', ['locations' => $locations]);
    }

    public function create(): View
    {
        $this->authorize('create', TrainingLocation::class);

        return view('admin.training-locations.create');
    }

    public function store(StoreTrainingLocationRequest $request): RedirectResponse
    {
        TrainingLocation::create($request->validated());

        return redirect()->route('admin.training-locations.index')->with('status', 'location-created');
    }

    public function edit(TrainingLocation $trainingLocation): View
    {
        $this->authorize('update', TrainingLocation::class);

        return view('admin.training-locations.edit', ['location' => $trainingLocation]);
    }

    public function update(StoreTrainingLocationRequest $request, TrainingLocation $trainingLocation): RedirectResponse
    {
        $trainingLocation->update($request->validated());

        return redirect()->route('admin.training-locations.index')->with('status', 'location-updated');
    }

    /**
     * Mostra l'avviso di conferma prima dell'eliminazione, indicando
     * quanti atleti resterebbero senza sede.
     */
    public function confirmDelete(TrainingLocation $trainingLocation): View
    {
        $this->authorize('delete', TrainingLocation::class);

        $athletesCount = $trainingLocation->users()->count();

        return view('admin.training-locations.confirm-delete', [
            'location' => $trainingLocation,
            'athletesCount' => $athletesCount,
        ]);
    }

    public function destroy(TrainingLocation $trainingLocation): RedirectResponse
    {
        $this->authorize('delete', TrainingLocation::class);

        $trainingLocation->users()->update(['training_location_id' => null]);
        $trainingLocation->delete();

        return redirect()->route('admin.training-locations.index')->with('status', 'location-deleted');
    }
}
