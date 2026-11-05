<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivateAssistantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('useAssistant', $this->route('shop'));
    }

    public function rules(): array
    {
        return [
            'tone' => ['nullable', 'string', 'max:120'],
            'language' => ['required', Rule::in(['fr', 'en'])],
            'delivery_policy' => ['nullable', 'string', 'max:2000'],
            'return_policy' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
