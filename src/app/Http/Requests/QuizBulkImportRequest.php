<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuizBulkImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create quizzes');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:json,csv,zip',
                'max:51200' // 50MB max file size for ZIP with images
            ],
            'format' => [
                'required',
                'string',
                'in:json,csv,zip'
            ],
            'overwrite' => [
                'sometimes',
                'boolean'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to import.',
            'file.mimes' => 'The file must be a JSON or CSV file.',
            'file.max' => 'The file size must not exceed 10MB.',
            'format.required' => 'Please specify the file format (json or csv).',
            'format.in' => 'The format must be either json or csv.',
        ];
    }
}
