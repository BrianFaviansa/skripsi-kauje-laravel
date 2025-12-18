<?php

namespace Modules\Forum\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Komentar wajib diisi',
            'content.min' => 'Komentar minimal 1 karakter',
        ];
    }
}
