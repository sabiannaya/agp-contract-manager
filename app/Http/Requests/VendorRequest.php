<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vendorId = $this->route('vendor')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vendors', 'code')->ignore($vendorId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'join_date' => ['required', 'date'],
            'contact_person' => ['required', 'string', 'max:255'],
            'tax_id' => ['required', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Vendor code is required.',
            'code.unique' => 'This vendor code is already taken.',
            'name.required' => 'Vendor name is required.',
            'address.required' => 'Address is required.',
            'join_date.required' => 'Join date is required.',
            'join_date.date' => 'Join date must be a valid date.',
            'contact_person.required' => 'Contact person is required.',
            'tax_id.required' => 'Tax ID (NPWP) is required.',
        ];
    }
}
