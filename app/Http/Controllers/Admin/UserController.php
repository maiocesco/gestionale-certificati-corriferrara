<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClubType;
use App\Enums\Discipline;
use App\Enums\MembershipEntity;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\ExternalClub;
use App\Models\TrainingLocation;
use App\Models\User;
use App\Services\UserAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $search = $request->string('q')->trim()->toString();
        $filters = $request->only(['club_type', 'external_club_id', 'entity', 'discipline', 'card_number', 'training_location_id']);

        $hasMembershipFilters = collect($filters)
            ->except('training_location_id')
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->isNotEmpty();

        $users = User::query()
            ->with('trainingLocation')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('fiscal_code', 'like', "%{$search}%");
                });
            })
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
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'filters' => $filters,
            'entities' => MembershipEntity::cases(),
            'disciplines' => Discipline::cases(),
            'clubTypes' => ClubType::cases(),
            'externalClubs' => ExternalClub::orderBy('name')->get(),
            'trainingLocations' => TrainingLocation::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', [
            'roles' => UserRole::cases(),
            'trainingLocations' => TrainingLocation::orderBy('name')->get(),
        ]);
    }

    public function store(StoreUserRequest $request, UserAccountService $accountService): RedirectResponse
    {
        $accountService->createUser($request->validated());

        return redirect()->route('admin.users.index')->with('status', 'user-created');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $user->load('memberships.externalClub');

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => UserRole::cases(),
            'trainingLocations' => TrainingLocation::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return redirect()->route('admin.users.index')->with('status', 'user-updated');
    }

    public function resetPassword(User $user, UserAccountService $accountService): RedirectResponse
    {
        $this->authorize('resetPassword', $user);

        $accountService->resetPassword($user);

        return redirect()->back()->with('status', 'password-reset');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $this->authorize('toggleActive', $user);

        $user->update(['is_active' => ! $user->is_active]);

        return redirect()->back()->with('status', $user->is_active ? 'user-activated' : 'user-deactivated');
    }
}
