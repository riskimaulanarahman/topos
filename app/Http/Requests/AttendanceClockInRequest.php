<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceClockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Allow employee guard only
        return auth('employee')->check();
    }

    public function rules(): array
    {
        return [
            'lat' => ['nullable','numeric','between:-90,90'],
            'lng' => ['nullable','numeric','between:-180,180'],
            'photo_base64' => ['nullable','string'],
            'notes' => ['nullable','string'],
        ];
    }
}

