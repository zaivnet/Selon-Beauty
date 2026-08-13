<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobTitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $jobTitleId = $this->route('job_title')?->id ?? $this->route('job_title');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('job_titles', 'name')->ignore($jobTitleId)],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama jabatan wajib diisi.',
            'name.unique' => 'Nama jabatan ini sudah terdaftar.',
        ];
    }
}
