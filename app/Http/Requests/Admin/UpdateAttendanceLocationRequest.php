<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:1', 'max:10000'],
            'max_accuracy_meters' => ['required', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lokasi wajib diisi.',
            'latitude.required' => 'Latitude lokasi wajib diisi.',
            'latitude.between' => 'Latitude harus bernilai antara -90 sampai 90.',
            'longitude.required' => 'Longitude lokasi wajib diisi.',
            'longitude.between' => 'Longitude harus bernilai antara -180 sampai 180.',
            'radius_meters.required' => 'Radius absensi (meter) wajib diisi.',
            'radius_meters.min' => 'Radius minimal 1 meter.',
            'max_accuracy_meters.required' => 'Maksimal akurasi GPS (meter) wajib diisi.',
            'max_accuracy_meters.min' => 'Maksimal akurasi GPS minimal 1 meter.',
        ];
    }
}
