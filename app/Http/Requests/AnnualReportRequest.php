<?php

namespace App\Http\Requests;

use App\Models\AnnualReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnnualReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // dostęp pilnuje middleware trasy (module-access:reports)
    }

    public function rules(): array
    {
        $current = $this->route('annualReport');
        $statuses = array_keys(AnnualReport::STATUSES);
        $fileRules = ['nullable', 'file', 'mimes:pdf', 'max:10240']; // 10 MB

        return [
            'year' => [
                'required', 'integer', 'min:1990', 'max:'.(now()->year + 1),
                Rule::unique('annual_reports', 'year')->ignore($current?->id),
            ],

            'substantive_status' => ['required', Rule::in($statuses)],
            'substantive_reason' => ['nullable', 'required_if:substantive_status,custom', 'string', 'max:500'],
            'substantive_file' => $fileRules,

            'financial_status' => ['required', Rule::in($statuses)],
            'financial_reason' => ['nullable', 'required_if:financial_status,custom', 'string', 'max:500'],
            'financial_file' => $fileRules,

            // Dodatkowe pliki — dopuszczamy typowe formaty dokumentów.
            'additional_files' => ['nullable', 'array'],
            'additional_files.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,odt,ods,csv,zip,jpg,jpeg,png', 'max:10240'],
            'remove_files' => ['nullable', 'array'],
            'remove_files.*' => ['integer'],
            'remove_substantive' => ['sometimes', 'boolean'],
            'remove_financial' => ['sometimes', 'boolean'],

            'is_published' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'year' => 'rok',
            'substantive_status' => 'status sprawozdania merytorycznego',
            'substantive_reason' => 'powód (merytoryczne)',
            'substantive_file' => 'plik sprawozdania merytorycznego',
            'financial_status' => 'status sprawozdania finansowego',
            'financial_reason' => 'powód (finansowe)',
            'financial_file' => 'plik sprawozdania finansowego',
            'additional_files.*' => 'plik dodatkowy',
        ];
    }
}
