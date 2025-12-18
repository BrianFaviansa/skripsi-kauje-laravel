<?php

namespace Modules\Job\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string'],
            'job_type' => ['nullable', 'string', 'in:FULL_TIME,PART_TIME,CONTRACT,INTERNSHIP,FREELANCE'],
            'province_id' => ['nullable', 'uuid'],
            'city_id' => ['nullable', 'uuid'],
            'job_field_id' => ['nullable', 'uuid'],
            'company' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort_by' => ['nullable', 'string', 'in:title,open_from,open_until,created_at'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
