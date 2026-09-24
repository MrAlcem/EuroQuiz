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
            'question_text' => ['sometimes', 'required', 'array'],
            'question_text.en' => ['required_with:question_text', 'string'],
            'question_text.hr' => ['nullable', 'string'],
            'question_text.nl' => ['nullable', 'string'],
            'question_text.se' => ['nullable', 'string'],
            'option_a' => ['sometimes', 'required', 'array'],
            'option_a.en' => ['required_with:option_a', 'string', 'max:255'],
            'option_a.hr' => ['nullable', 'string', 'max:255'],
            'option_a.nl' => ['nullable', 'string', 'max:255'],
            'option_a.se' => ['nullable', 'string', 'max:255'],
            'option_b' => ['sometimes', 'required', 'array'],
            'option_b.en' => ['required_with:option_b', 'string', 'max:255'],
            'option_b.hr' => ['nullable', 'string', 'max:255'],
            'option_b.nl' => ['nullable', 'string', 'max:255'],
            'option_b.se' => ['nullable', 'string', 'max:255'],
            'option_c' => ['sometimes', 'required', 'array'],
            'option_c.en' => ['required_with:option_c', 'string', 'max:255'],
            'option_c.hr' => ['nullable', 'string', 'max:255'],
            'option_c.nl' => ['nullable', 'string', 'max:255'],
            'option_c.se' => ['nullable', 'string', 'max:255'],
            'option_d' => ['sometimes', 'required', 'array'],
            'option_d.en' => ['required_with:option_d', 'string', 'max:255'],
            'option_d.hr' => ['nullable', 'string', 'max:255'],
            'option_d.nl' => ['nullable', 'string', 'max:255'],
            'option_d.se' => ['nullable', 'string', 'max:255'],
            'correct_option' => ['sometimes', 'required', 'string', 'in:A,B,C,D'],
            'category' => ['sometimes', 'required', 'string', 'max:255'],
            'country' => ['sometimes', 'required', 'string', 'max:255'],

            'difficulty' => ['sometimes', 'required', 'string', 'in:easy,medium,hard'],
            'time_limit_seconds' => ['sometimes', 'integer', 'min:5', 'max:120'],
        ];
    }
}
