<?php

namespace App\Http\Requests\Vendor;

use App\Models\ShopBotTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAssistantCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('useAssistant', $this->route('shop'));
    }

    public function rules(): array
    {
        return [
            'template_id' => ['required', Rule::exists('shop_bot_templates', 'id')
                ->where('shop_bot_id', $this->route('shop')->bot?->id)
                ->where('status', ShopBotTemplate::STATUS_APPROVED)],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
            'parameters' => ['nullable', 'array'],
            'parameters.*' => ['required', 'string', 'max:200'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $template = ShopBotTemplate::find($this->input('template_id'));

            if ($template && count($this->input('parameters', [])) !== $template->variableCount()) {
                $validator->errors()->add('parameters', 'Renseignez chaque variable du modèle.');
            }
        }];
    }
}
