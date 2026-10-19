<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Concerns\ValidatesMembershipRules;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreMembershipRequest extends FormRequest
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

        return $this->membershipRules($user);
    }
}
