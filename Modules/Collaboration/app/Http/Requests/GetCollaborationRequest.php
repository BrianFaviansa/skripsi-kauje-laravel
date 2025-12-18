<?php

namespace Modules\Collaboration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetCollaborationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string'],
            'collaboration_field_id' => ['nullable', 'uuid'],
            'posted_by_id' => ['nullable', 'uuid'],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'string', 'in:title,created_at'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
