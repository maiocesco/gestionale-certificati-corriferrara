<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesMembershipRules;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMembershipRequest extends FormRequest
{
    use ValidatesMembershipRules;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');
        /** @var Membership $membership */
        $membership = $this->route('membership');

        return $this->membershipRules($user, $membership->id);
    }
}
