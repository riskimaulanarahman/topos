<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() || auth('employee')->check();
    }

    public function rules(): array
    {
        return [
            'date_from' => ['required','date'],
            'date_to' => ['required','date','after_or_equal:date_from'],
            'employee_id' => ['nullable','exists:employees,id'],
            'format' => ['nullable','in:json,csv']
        ];
    }
}

