<?php

namespace App\Http\Requests\Mahasiswa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class NonSkripsiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->role === 'mahasiswa';
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['required', 'string'],
            'final_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'link_publikasi' => ['nullable', 'url', 'max:500'],
            'report_file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ];
    }
}
