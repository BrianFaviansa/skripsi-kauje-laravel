<?php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string'],
            'faculty_id' => ['nullable', 'uuid'],
            'major_id' => ['nullable', 'uuid'],
            'province_id' => ['nullable', 'uuid'],
            'city_id' => ['nullable', 'uuid'],
            'enrollment_year' => ['nullable', 'integer'],
            'graduation_year' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'string', 'in:name,enrollment_year,graduation_year,created_at'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
