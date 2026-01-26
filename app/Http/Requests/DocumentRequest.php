<?php

namespace App\Http\Requests;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ticket_id' => ['required', 'exists:tickets,id'],
            'type' => [
                'required',
                Rule::in(Document::TYPES),
            ],
            'file' => [
                'required',
                'file',
                'max:' . Document::MAX_FILE_SIZE,
                'mimes:pdf,jpg,jpeg,png',
            ],
        ];
    }

    public function messages(): array
    {
        $maxSizeMB = Document::MAX_FILE_SIZE / 1024;

        return [
            'ticket_id.required' => 'Ticket ID is required.',
            'ticket_id.exists' => 'Ticket does not exist.',
            'type.required' => 'Document type is required.',
            'type.in' => 'Invalid document type. Must be one of: ' . implode(', ', Document::TYPES),
            'file.required' => 'Please upload a file.',
            'file.file' => 'The uploaded item must be a file.',
            'file.max' => "File size cannot exceed {$maxSizeMB}MB.",
            'file.mimes' => 'File must be a PDF, JPG, JPEG, or PNG.',
        ];
    }
}
