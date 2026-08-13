<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['nullable', 'exists:employees,id'],
            'work_date' => ['nullable', 'date'],
            'schedule_type' => ['required', 'in:work,off,holiday'],
            'shift_id' => ['required_if:schedule_type,work', 'nullable', 'exists:shifts,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.exists' => 'Karyawan yang dipilih tidak valid.',
            'work_date.date' => 'Format tanggal tidak valid.',
            'schedule_type.required' => 'Jenis jadwal wajib dipilih.',
            'shift_id.required_if' => 'Shift wajib dipilih untuk jenis jadwal Kerja.',
            'shift_id.exists' => 'Shift yang dipilih tidak valid.',
        ];
    }
}
