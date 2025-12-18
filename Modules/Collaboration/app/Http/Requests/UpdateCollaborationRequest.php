<?php

namespace Modules\Collaboration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCollaborationRequest extends FormRequest
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
            'collaboration_field_id' => ['nullable', 'uuid', 'exists:collaboration_fields,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.min' => 'Judul minimal 3 karakter',
            'content.min' => 'Konten minimal 10 karakter',
            'collaboration_field_id.exists' => 'Bidang kolaborasi tidak ditemukan',
        ];
    }
}
