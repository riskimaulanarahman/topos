<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $proof = config('subscriptions.proof_upload');
        $planCodes = array_keys(config('subscriptions.plans', []));

        $uniqueLength = (int) config('subscriptions.unique_code.length', 3);

        return [
            'plan_code' => ['required', Rule::in($planCodes)],
            'payment_account_id' => [
                'required',
                Rule::exists('payment_accounts', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'transferred_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'payer_name' => ['required', 'string', 'max:120'],
            'payment_channel' => ['nullable', 'string', 'max:120'],
            'unique_code' => ['required', 'digits:' . $uniqueLength],
            'customer_note' => ['nullable', 'string', 'max:500'],
            'proof' => [
                'required',
                'file',
                'max:' . (int) ($proof['max_size_kb'] ?? 4096),
                'mimes:' . implode(',', $proof['mimes'] ?? ['jpg', 'jpeg', 'png', 'pdf']),
            ],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);
        $data['unique_code'] = (int) $data['unique_code'];

        return $data;
    }
}
