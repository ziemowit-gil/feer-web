<?php

namespace App\Http\Requests;

use App\Models\StrategyPlan;
use App\Models\StrategyPlanResource;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * Walidacja formularza planu działania (moduł „Strategia organizacji"),
 * wraz z zagnieżdżoną tablicą pozycji alokacji zasobów.
 */
class StrategyPlanRequest extends FormRequest
{
    /**
     * Moduł strategii komunikuje się przez fetch/JSON, a globalny handler
     * renderuje JSON tylko dla tras api/* — dlatego wymuszamy tu odpowiedź
     * 422 z błędami zamiast domyślnego przekierowania.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Popraw zaznaczone pola formularza.',
            'errors'  => $validator->errors(),
        ], 422));
    }

    public function rules(): array
    {
        return [
            'title'             => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string', 'max:5000'],
            'year'              => ['required', 'integer', 'between:2020,2100'],
            'month'             => ['required', 'integer', 'between:1,12'],
            'status'            => ['required', Rule::in(array_keys(StrategyPlan::STATUSES))],
            'action_type_id'    => ['required', 'exists:strategy_action_types,id'],
            'statute_ref_id'    => ['nullable', 'exists:strategy_statute_refs,id'],
            'funding_source_id' => ['nullable', 'exists:strategy_funding_sources,id'],
            'budget_planned'    => ['required', 'numeric', 'min:0', 'max:999999999'],

            'target_groups'     => ['required', 'array', 'min:1'],
            'target_groups.*'   => ['integer', 'exists:strategy_target_groups,id'],

            'resources'            => ['nullable', 'array', 'max:50'],
            'resources.*.type'     => ['required', Rule::in(array_keys(StrategyPlanResource::TYPES))],
            'resources.*.name'     => ['required', 'string', 'max:255'],
            'resources.*.user_id'  => ['nullable', 'exists:users,id'],
            'resources.*.quantity' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'resources.*.unit'     => ['nullable', 'string', 'max:50'],
            'resources.*.cost'     => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'resources.*.note'     => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title'             => 'nazwa działania',
            'description'       => 'opis',
            'year'              => 'rok',
            'month'             => 'miesiąc',
            'status'            => 'status',
            'action_type_id'    => 'typ działania',
            'statute_ref_id'    => 'powiązanie statutowe',
            'funding_source_id' => 'źródło finansowania',
            'budget_planned'    => 'budżet',
            'target_groups'     => 'grupy docelowe',
            'resources.*.type'  => 'typ zasobu',
            'resources.*.name'  => 'nazwa zasobu',
        ];
    }

    public function messages(): array
    {
        return [
            'target_groups.required' => 'Wybierz co najmniej jedną grupę docelową.',
            'target_groups.min'      => 'Wybierz co najmniej jedną grupę docelową.',
        ];
    }
}
