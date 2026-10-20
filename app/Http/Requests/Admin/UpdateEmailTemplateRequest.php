<?php

namespace App\Http\Requests\Admin;

use App\Models\EmailTemplate;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', EmailTemplate::class);
    }

    public function rules(): array
    {
        /** @var EmailTemplate $emailTemplate */
        $emailTemplate = $this->route('emailTemplate');
        $hasSubject = $emailTemplate->keyEnum()->hasSubject();

        return [
            'subject' => [$hasSubject ? 'required' : 'nullable', 'string', 'max:255'],
            'body_markdown' => ['required', 'string'],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            /** @var EmailTemplate $emailTemplate */
            $emailTemplate = $this->route('emailTemplate');
            $body = (string) $this->input('body_markdown');

            foreach ($emailTemplate->keyEnum()->requiredPlaceholders() as $placeholder) {
                if (! preg_match('/\{\{\s*'.$placeholder.'\s*\}\}/', $body)) {
                    $validator->errors()->add(
                        'body_markdown',
                        __('Il testo deve contenere il segnaposto obbligatorio {{:placeholder}}.', ['placeholder' => $placeholder])
                    );
                }
            }
        });
    }
}
