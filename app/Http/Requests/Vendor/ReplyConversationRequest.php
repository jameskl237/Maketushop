<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class ReplyConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('useAssistant', $this->route('shop'));
    }

    public function rules(): array
    {
        return ['message' => ['required', 'string', 'max:4096']];
    }
}
