<?php

namespace App\Http\Requests\Admin;

use App\Models\Shift;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(trim($this->input('code'))),
            ]);
        }
    }

    public function rules(): array
    {
        /** @var Shift $shift */
        $shift = $this->route('shift');
        $shiftId = $shift instanceof Shift ? $shift->id : $shift;

        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:30', Rule::unique('shifts', 'code')->ignore($shiftId)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'grace_period_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'check_in_open_minutes_before' => ['required', 'integer', 'min:0', 'max:480'],
            'check_in_close_minutes_after' => ['required', 'integer', 'min:0', 'max:480'],
            'check_out_open_minutes_before' => ['required', 'integer', 'min:0', 'max:480'],
            'break_minutes' => ['required', 'integer', 'min:0', 'max:480'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama shift wajib diisi.',
            'code.required' => 'Kode shift wajib diisi.',
            'code.unique' => 'Kode shift ini sudah digunakan.',
            'start_time.required' => 'Jam mulai shift wajib diisi.',
            'start_time.date_format' => 'Format jam mulai harus HH:MM (contoh: 09:00).',
            'end_time.required' => 'Jam selesai shift wajib diisi.',
            'end_time.date_format' => 'Format jam selesai harus HH:MM (contoh: 17:00).',
            'grace_period_minutes.required' => 'Toleransi keterlambatan (menit) wajib diisi.',
            'grace_period_minutes.min' => 'Toleransi keterlambatan minimal 0 menit.',
        ];
    }
}
