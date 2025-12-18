<?php

namespace Modules\Forum\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['sometimes', 'string', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.min' => 'Komentar minimal 1 karakter',
        ];
    }
}
