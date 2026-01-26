<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleGroupId = $this->route('role')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('role_groups', 'name')->ignore($roleGroupId),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'privileges' => ['sometimes', 'array'],
            'privileges.*.page_id' => ['required_with:privileges', 'integer', 'exists:pages,id'],
            'privileges.*.create' => ['boolean'],
            'privileges.*.read' => ['boolean'],
            'privileges.*.update' => ['boolean'],
            'privileges.*.delete' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Role group name is required.',
            'name.unique' => 'This role group name is already taken.',
            'name.max' => 'Role group name cannot exceed 100 characters.',
            'description.max' => 'Description cannot exceed 500 characters.',
            'privileges.array' => 'Privileges must be an array.',
            'privileges.*.page_id.exists' => 'One or more selected pages do not exist.',
        ];
    }
}
