<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
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
        return [
            'employee_code' => ['required', 'string', 'max:50', 'unique:employees,employee_code'],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:190', 'unique:employees,email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'unique:employees,phone', 'unique:users,phone'],
            'job_title_id' => ['nullable', 'exists:job_titles,id'],
            'join_date' => ['nullable', 'date'],
            'status' => ['required', 'in:active,inactive'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'create_user_account' => ['nullable', 'boolean'],
            'account_password' => ['nullable', 'required_if:create_user_account,1', 'string', 'min:6'],
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
            'account_password.required_if' => 'Password wajib diisi jika membuat akun login karyawan.',
            'account_password.min' => 'Password minimal 6 karakter.',
        ];
    }
}
