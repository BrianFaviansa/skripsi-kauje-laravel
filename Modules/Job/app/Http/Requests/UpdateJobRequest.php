<?php

namespace Modules\Job\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobRequest extends FormRequest
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
            'company' => ['sometimes', 'string', 'min:2'],
            'job_type' => ['sometimes', 'string', 'in:FULL_TIME,PART_TIME,CONTRACT,INTERNSHIP,FREELANCE'],
            'open_from' => ['sometimes', 'date'],
            'open_until' => ['sometimes', 'date'],
            'registration_link' => ['nullable', 'url'],
            'image_url' => ['nullable', 'string'],
            'province_id' => ['sometimes', 'uuid', 'exists:provinces,id'],
            'city_id' => ['sometimes', 'uuid', 'exists:cities,id'],
            'job_field_id' => ['sometimes', 'uuid', 'exists:job_fields,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.min' => 'Judul minimal 3 karakter',
            'content.min' => 'Konten minimal 10 karakter',
            'company.min' => 'Nama perusahaan minimal 2 karakter',
            'job_type.in' => 'Tipe pekerjaan tidak valid',
            'registration_link.url' => 'Link pendaftaran harus berupa URL yang valid',
            'province_id.exists' => 'Provinsi tidak ditemukan',
            'city_id.exists' => 'Kota tidak ditemukan',
            'job_field_id.exists' => 'Bidang pekerjaan tidak ditemukan',
        ];
    }
}
