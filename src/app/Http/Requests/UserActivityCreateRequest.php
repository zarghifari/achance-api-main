<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserActivityCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'nullable|integer',
            'activity_type' => 'required|string|in:lesson,epub,quiz,task',
            'activity_id' => 'required|integer',
            'last_seen_url' => 'nullable|string|max:255',
            'last_seen_at' => 'nullable|date',
            'metadata' => 'nullable|array',
            'duration_seconds' => 'nullable|integer|min:0',
            'progress_percentage' => 'nullable|numeric|min:0|max:100',
            'action' => 'required|string|in:start,progress,complete,download,view',
            'started_at' => 'nullable|date',
            'completed_at' => 'nullable|date|after_or_equal:started_at',
            'device_type' => 'nullable|string|max:50',
            'user_agent' => 'nullable|string|max:500',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors()
        ], 400));
    }
}
