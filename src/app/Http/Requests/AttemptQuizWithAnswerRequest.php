<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Models\QuizAnswer;

class AttemptQuizWithAnswerRequest extends FormRequest
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
            'quiz_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'score' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|string|in:in_progress,completed,failed',
            'started_at' => 'nullable|date|before_or_equal:now',
            'completed_at' => 'nullable|date|after_or_equal:started_at',
            'attempt_answers' => 'nullable|array|min:1',
            'attempt_answers.*.selected_answer_id' => 'required_with:attempt_answers|integer|exists:quiz_answers,id',
            'attempt_answers.*.answer_text' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'attempt_answers.min' => 'At least one answer is required when providing answers.',
            'attempt_answers.*.selected_answer_id.required_with' => 'Answer selection is required when providing answers.',
            'attempt_answers.*.selected_answer_id.exists' => 'Selected answer does not exist.',
            'attempt_answers.*.answer_text.max' => 'Answer text cannot exceed 1000 characters.',
            'started_at.before_or_equal' => 'Start time cannot be in the future.',
            'completed_at.after_or_equal' => 'Completion time must be after start time.',
            'score.min' => 'Score cannot be negative.',
            'score.max' => 'Score cannot exceed 100.',
        ];
    }

    /**
     * Configure the validator instance
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateNoDuplicateQuestions($validator);
            $this->validateAnswersBelongToQuiz($validator);
        });
    }

    /**
     * Validate no duplicate questions in answers
     */
    protected function validateNoDuplicateQuestions($validator)
    {
        $attemptAnswers = $this->input('attempt_answers', []);
        $questionIds = [];
        
        foreach ($attemptAnswers as $index => $answer) {
            $answerId = $answer['selected_answer_id'] ?? null;
            
            if ($answerId) {
                $quizAnswer = QuizAnswer::with('quizQuestions')->find($answerId);
                
                if ($quizAnswer) {
                    $questionId = $quizAnswer->quiz_question_id;
                    
                    if (in_array($questionId, $questionIds)) {
                        $validator->errors()->add(
                            "attempt_answers.{$index}.selected_answer_id", 
                            'Duplicate answer for the same question is not allowed.'
                        );
                    } else {
                        $questionIds[] = $questionId;
                    }
                }
            }
        }
    }

    /**
     * Validate that all answers belong to the quiz
     */
    protected function validateAnswersBelongToQuiz($validator)
    {
        $quizId = $this->route('quiz_id');
        $attemptAnswers = $this->input('attempt_answers', []);
        
        if (!$quizId) {
            return;
        }
        
        foreach ($attemptAnswers as $index => $answer) {
            $answerId = $answer['selected_answer_id'] ?? null;
            
            if ($answerId) {
                $quizAnswer = QuizAnswer::with('quizQuestions')->find($answerId);
                
                if ($quizAnswer && $quizAnswer->quizQuestions->quiz_id != $quizId) {
                    $validator->errors()->add(
                        "attempt_answers.{$index}.selected_answer_id", 
                        'This answer does not belong to the specified quiz.'
                    );
                }
            }
        }
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation()
    {
        // Remove any user_id and quiz_id from input as they should be set by the controller
        $this->merge([
            'user_id' => null,
            'quiz_id' => null,
        ]);
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors()
        ], 422));
    }
}