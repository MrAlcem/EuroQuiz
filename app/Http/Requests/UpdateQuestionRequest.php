<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates an edit to an existing question. Authorization is handled
 * by the `admin` route middleware.
 */
class UpdateQuestionRequest extends FormRequest
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
            'question_text' => ['sometimes', 'required', 'string'],
            'option_a' => ['sometimes', 'required', 'string', 'max:255'],
            'option_b' => ['sometimes', 'required', 'string', 'max:255'],
            'option_c' => ['sometimes', 'required', 'string', 'max:255'],
            'option_d' => ['sometimes', 'required', 'string', 'max:255'],
            'correct_option' => ['sometimes', 'required', 'string', 'in:A,B,C,D'],
            'category' => ['sometimes', 'required', 'string', 'max:255'],
            'country' => ['sometimes', 'required', 'string', 'max:255'],

            'difficulty' => ['sometimes', 'required', 'string', 'in:easy,medium,hard'],
        ];
    }
}
