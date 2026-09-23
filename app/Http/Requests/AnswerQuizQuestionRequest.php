<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnswerQuizQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Nullable so the client can explicitly submit "no answer" once
            // its local countdown runs out, without needing a placeholder
            // option; the server independently re-checks the timeout too.
            'chosen_option' => ['present', 'nullable', 'string', 'in:A,B,C,D'],
        ];
    }
}
