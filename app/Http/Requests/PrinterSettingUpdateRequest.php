<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrinterSettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'paper_size' => ['sometimes', 'required', 'in:58,80'],
            'title_font_size' => ['sometimes', 'required', 'integer', 'between:1,8'],
            'show_logo' => ['sometimes', 'required', 'boolean'],
            'show_footer' => ['sometimes', 'required', 'boolean'],
            'footer_text' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
        ];
    }
}
