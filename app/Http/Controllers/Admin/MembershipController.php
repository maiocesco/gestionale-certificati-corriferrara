<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClubType;
use App\Enums\MembershipEntity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMembershipRequest;
use App\Http\Requests\Admin\UpdateMembershipRequest;
use App\Models\ExternalClub;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class MembershipController extends Controller
{
    public function store(StoreMembershipRequest $request, User $user): RedirectResponse
    {
        $user->memberships()->create($this->prepareData($request));

        return redirect()->route('admin.users.edit', $user)->with('status', 'membership-created');
    }

    public function update(UpdateMembershipRequest $request, User $user, Membership $membership): RedirectResponse
    {
        $membership->update($this->prepareData($request));

        return redirect()->route('admin.users.edit', $user)->with('status', 'membership-updated');
    }

    public function destroy(User $user, Membership $membership): RedirectResponse
    {
        $this->authorize('update', $user);

        $membership->delete();

        return redirect()->route('admin.users.edit', $user)->with('status', 'membership-deleted');
    }

    private function prepareData(StoreMembershipRequest|UpdateMembershipRequest $request): array
    {
        $data = $request->validated();

        if ($data['club_type'] === ClubType::External->value && empty($data['external_club_id']) && ! empty($data['external_club_name'])) {
            $data['external_club_id'] = ExternalClub::firstOrCreate(['name' => $data['external_club_name']])->id;
        }

        if ($data['club_type'] !== ClubType::External->value) {
            $data['external_club_id'] = null;
        }

        $data['is_competitive'] = $data['entity'] === MembershipEntity::Fidal->value
            ? true
            : ($data['is_competitive'] ?? false);

        unset($data['external_club_name']);

        return $data;
    }
}
