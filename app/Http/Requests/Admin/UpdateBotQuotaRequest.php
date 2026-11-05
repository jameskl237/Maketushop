<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** L'accès est déjà limité aux administrateurs par le groupe de routes. */
class UpdateBotQuotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['monthly_credit_quota' => ['nullable', 'integer', 'min:0', 'max:10000000']];
    }
}
