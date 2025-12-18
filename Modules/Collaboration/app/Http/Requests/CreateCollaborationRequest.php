<?php

namespace Modules\Collaboration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCollaborationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3'],
            'content' => ['required', 'string', 'min:10'],
            'image_url' => ['nullable', 'string'],
            'collaboration_field_id' => ['nullable', 'uuid', 'exists:collaboration_fields,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul wajib diisi',
            'title.min' => 'Judul minimal 3 karakter',
            'content.required' => 'Konten wajib diisi',
            'content.min' => 'Konten minimal 10 karakter',
            'collaboration_field_id.exists' => 'Bidang kolaborasi tidak ditemukan',
        ];
    }
}
