<?php

namespace App\Http\Requests\Vendor;

use App\Models\ShopBotTemplate;
use App\Services\Botisimo\TemplateCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAssistantTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('useAssistant', $this->route('shop'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => TemplateCatalog::slug((string) $this->input('name'))]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:512', 'regex:/^[a-z0-9_]+$/',
                Rule::unique('shop_bot_templates')->where('shop_bot_id', $this->route('shop')->bot?->id)->where('language', $this->input('language')),
            ],
            // AUTHENTICATION suit un format imposé par Meta, sans corps libre.
            'category' => ['required', Rule::in(['MARKETING', 'UTILITY'])],
            'language' => ['required', Rule::in(['fr', 'en'])],
            'body' => ['required', 'string', 'max:1024'],
            'examples' => ['nullable', 'array', 'max:20'],
            'examples.*' => ['required', 'string', 'max:200'],
            'footer' => ['nullable', 'string', 'max:60'],
            'buttons' => ['nullable', 'array', 'max:3'],
            'buttons.*' => ['nullable', 'string', 'max:25'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $variables = ShopBotTemplate::variablesIn((string) $this->input('body'));

            if ($variables !== range(1, max(1, count($variables))) && $variables !== []) {
                $validator->errors()->add('body', 'Les variables doivent se suivre : {{1}}, {{2}}, {{3}}…');
            }

            if (count($this->input('examples', [])) !== count($variables)) {
                $validator->errors()->add('examples', 'Donnez un exemple pour chaque variable.');
            }
        }];
    }
}
