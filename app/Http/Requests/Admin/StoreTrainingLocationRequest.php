<?php

namespace App\Http\Requests\Admin;

use App\Models\TrainingLocation;
use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $location = $this->route('training_location');

        return $location
            ? $this->user()->can('update', TrainingLocation::class)
            : $this->user()->can('create', TrainingLocation::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ];
    }
}
