<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** L'accès est déjà limité aux administrateurs par le groupe de routes. */
class RechargeCreditsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:500'],
            'operator' => ['required', Rule::in(['mtn', 'orange', 'moov', 'wave'])],
            'phone_number' => ['required', 'string', 'regex:/^\+?[0-9 ]{8,20}$/'],
        ];
    }
}
