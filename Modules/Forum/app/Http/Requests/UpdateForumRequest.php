<?php

namespace Modules\Forum\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateForumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'min:3'],
            'content' => ['sometimes', 'string', 'min:10'],
            'image_url' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.min' => 'Judul minimal 3 karakter',
            'content.min' => 'Konten minimal 10 karakter',
        ];
    }
}
