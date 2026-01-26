<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $ticketId = $this->route('ticket')?->id;

        return [
            'number' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('tickets', 'number')->ignore($ticketId),
            ],
            'date' => ['required', 'date'],
            'contract_id' => ['required', 'exists:contracts,id'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'status' => ['sometimes', Rule::in(['complete', 'incomplete'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'number.unique' => 'This ticket number is already taken.',
            'date.required' => 'Ticket date is required.',
            'date.date' => 'Ticket date must be a valid date.',
            'contract_id.required' => 'Please select a contract.',
            'contract_id.exists' => 'Selected contract does not exist.',
            'vendor_id.required' => 'Please select a vendor.',
            'vendor_id.exists' => 'Selected vendor does not exist.',
            'status.in' => 'Status must be either complete or incomplete.',
            'notes.max' => 'Notes cannot exceed 2000 characters.',
        ];
    }
}
