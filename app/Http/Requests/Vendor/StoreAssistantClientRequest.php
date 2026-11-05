<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssistantClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('useAssistant', $this->route('shop'));
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^\+?[0-9 ]{8,20}$/'],
            'name' => ['nullable', 'string', 'max:120'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
        ];
    }
}
