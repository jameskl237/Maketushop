<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssistantSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('useAssistant', $this->route('shop'));
    }

    public function rules(): array
    {
        return [
            'additional_instructions' => ['nullable', 'string', 'max:4000'],
            'ai_temperature' => ['nullable', 'numeric', 'between:0,2'],
            'tone' => ['nullable', 'string', 'max:120'],
            'language' => ['required', Rule::in(['fr', 'en'])],
            'delivery_policy' => ['nullable', 'string', 'max:2000'],
            'return_policy' => ['nullable', 'string', 'max:2000'],
            'welcome_message_enabled' => ['required', 'boolean'],
            'welcome_message' => ['nullable', 'required_if:welcome_message_enabled,true', 'string', 'max:1024'],
            'away_message_enabled' => ['required', 'boolean'],
            'away_message' => ['nullable', 'required_if:away_message_enabled,true', 'string', 'max:1024'],
            'human_transfer_keywords' => ['present', 'array', 'max:30'],
            'human_transfer_keywords.*' => ['string', 'max:60'],
            'business_address' => ['nullable', 'string', 'max:255'],
            'ai_share_location' => ['required', 'boolean'],
        ];
    }
}
