<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $contractId = $this->route('contract')?->id;

        $rules = [
            'number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('contracts', 'number')->ignore($contractId),
            ],
            'date' => ['required', 'date'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999999999999.99'],
            'cooperation_type' => ['required', Rule::in(['progress', 'routine'])],
            'is_active' => ['boolean'],
        ];

        // Add conditional rules for progress type
        if ($this->input('cooperation_type') === 'progress') {
            $rules['term_count'] = ['required', 'integer', 'min:1', 'max:12'];
            $rules['term_percentages'] = ['required', 'array', 'min:1'];
            $rules['term_percentages.*'] = ['required', 'numeric', 'min:0', 'max:100'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'number.required' => 'Contract number is required.',
            'number.unique' => 'This contract number is already taken.',
            'date.required' => 'Contract date is required.',
            'date.date' => 'Contract date must be a valid date.',
            'vendor_id.required' => 'Please select a vendor.',
            'vendor_id.exists' => 'Selected vendor does not exist.',
            'amount.required' => 'Contract amount is required.',
            'amount.numeric' => 'Contract amount must be a number.',
            'amount.min' => 'Contract amount must be at least 0.',
            'cooperation_type.required' => 'Cooperation type is required.',
            'cooperation_type.in' => 'Cooperation type must be either progress or routine.',
            'term_count.required' => 'Number of terms is required for progress contracts.',
            'term_count.min' => 'At least 1 term is required.',
            'term_count.max' => 'Maximum 12 terms allowed.',
            'term_percentages.required' => 'Term percentages are required for progress contracts.',
            'term_percentages.*.numeric' => 'Each term percentage must be a number.',
            'term_percentages.*.min' => 'Term percentage must be at least 0.',
            'term_percentages.*.max' => 'Term percentage cannot exceed 100.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('cooperation_type') === 'progress') {
                $percentages = $this->input('term_percentages', []);
                $total = array_sum($percentages);

                if (abs($total - 100) > 0.01) {
                    $validator->errors()->add(
                        'term_percentages',
                        "Total percentage must equal 100%. Current total: {$total}%"
                    );
                }
            }
        });
    }
}
