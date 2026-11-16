<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGoogleRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return is_array($this->session()->get('registration.google_identity'));
    }

    public function rules(): array
    {
        return [
            'nim' => ['required', 'string', 'max:30', Rule::unique('users', 'nim')],
            'study_program_id' => ['required', 'integer', Rule::exists('study_programs', 'id')->where('is_active', true)],
        ];
    }
}
