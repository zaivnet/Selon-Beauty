<?php

namespace App\Http\Requests\Admin;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email') && $this->email !== null) {
            $this->merge([
                'email' => strtolower(trim((string) $this->email)),
            ]);
        }
    }

    public function rules(): array
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');
        $employeeId = $employee instanceof Employee ? $employee->id : $employee;
        $userId = $employee instanceof Employee ? $employee->user?->id : null;

        return [
            'employee_code' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_code')->ignore($employeeId)],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => [
                'nullable',
                'email',
                'max:190',
                Rule::unique('employees', 'email')->ignore($employeeId),
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('employees', 'phone')->ignore($employeeId),
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'job_title_id' => ['nullable', 'exists:job_titles,id'],
            'join_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive'],
            'attendance_enabled' => ['sometimes', 'boolean'],
            'attendance_participation_reason' => ['nullable', 'string', 'min:5', 'max:1000'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'role' => ['nullable', 'string', 'in:superadmin,owner,admin,employee'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_code.required' => 'Kode karyawan wajib diisi.',
            'employee_code.unique' => 'Kode karyawan ini sudah digunakan.',
            'full_name.required' => 'Nama lengkap karyawan wajib diisi.',
            'email.unique' => 'Email tersebut sudah digunakan oleh akun lain.',
            'phone.unique' => 'Nomor HP ini sudah terdaftar.',
            'profile_photo.image' => 'File foto profil harus berupa gambar.',
            'profile_photo.mimes' => 'Format foto profil harus jpeg, png, atau webp.',
            'profile_photo.max' => 'Ukuran foto profil maksimal 2MB.',
        ];
    }
}
