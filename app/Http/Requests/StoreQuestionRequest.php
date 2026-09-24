<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a new question. Authorization is handled by the `admin`
 * route middleware.
 */
class StoreQuestionRequest extends FormRequest
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
            'question_text' => ['required', 'array'],
            'question_text.en' => ['required', 'string'],
            'question_text.hr' => ['nullable', 'string'],
            'question_text.nl' => ['nullable', 'string'],
            'question_text.se' => ['nullable', 'string'],
            'option_a' => ['required', 'array'],
            'option_a.en' => ['required', 'string', 'max:255'],
            'option_a.hr' => ['nullable', 'string', 'max:255'],
            'option_a.nl' => ['nullable', 'string', 'max:255'],
            'option_a.se' => ['nullable', 'string', 'max:255'],
            'option_b' => ['required', 'array'],
            'option_b.en' => ['required', 'string', 'max:255'],
            'option_b.hr' => ['nullable', 'string', 'max:255'],
            'option_b.nl' => ['nullable', 'string', 'max:255'],
            'option_b.se' => ['nullable', 'string', 'max:255'],
            'option_c' => ['required', 'array'],
            'option_c.en' => ['required', 'string', 'max:255'],
            'option_c.hr' => ['nullable', 'string', 'max:255'],
            'option_c.nl' => ['nullable', 'string', 'max:255'],
            'option_c.se' => ['nullable', 'string', 'max:255'],
            'option_d' => ['required', 'array'],
            'option_d.en' => ['required', 'string', 'max:255'],
            'option_d.hr' => ['nullable', 'string', 'max:255'],
            'option_d.nl' => ['nullable', 'string', 'max:255'],
            'option_d.se' => ['nullable', 'string', 'max:255'],
            'correct_option' => ['required', 'string', 'in:A,B,C,D'],
            'category' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:255'],
            'difficulty' => ['required', 'string', 'in:easy,medium,hard'],
            'time_limit_seconds' => ['sometimes', 'integer', 'min:5', 'max:120'],
        ];
    }
}
