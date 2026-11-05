<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssistantClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('useAssistant', $this->route('shop'));
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:120'],
            'tags' => ['present', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
            'opted_out' => ['required', 'boolean'],
        ];
    }
}
