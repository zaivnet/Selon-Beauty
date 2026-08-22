<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'work_date' => ['required', 'date'],
            'schedule_type' => ['required', 'in:work,off,holiday'],
            'shift_id' => ['required_if:schedule_type,work', 'nullable', 'exists:shifts,id'],
            'work_outlet_id' => ['nullable', 'integer', 'exists:outlets,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Karyawan wajib dipilih.',
            'work_date.required' => 'Tanggal kerja wajib diisi.',
            'schedule_type.required' => 'Jenis jadwal wajib dipilih.',
            'shift_id.required_if' => 'Shift wajib dipilih untuk jenis jadwal Kerja.',
            'shift_id.exists' => 'Shift yang dipilih tidak valid.',
            'work_outlet_id.exists' => 'Outlet Kerja yang dipilih tidak valid.',
        ];
    }
}
