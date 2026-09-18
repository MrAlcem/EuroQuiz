<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a batch submission of quiz answers.
 *
 * Authorization is handled by the `auth:sanctum` route middleware; any
 * authenticated user may submit a quiz.
 */
class SubmitQuizAnswersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1', 'max:10'],
            'answers.*.question_id' => ['required', 'integer', 'distinct', 'exists:questions,id'],
            'answers.*.chosen_option' => ['required', 'string', 'in:A,B,C,D'],
        ];
    }
}
