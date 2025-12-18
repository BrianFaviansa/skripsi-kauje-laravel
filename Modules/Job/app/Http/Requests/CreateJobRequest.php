<?php

namespace Modules\Job\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateJobRequest extends FormRequest
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
            'company' => ['required', 'string', 'min:2'],
            'job_type' => ['required', 'string', 'in:FULL_TIME,PART_TIME,CONTRACT,INTERNSHIP,FREELANCE'],
            'open_from' => ['required', 'date'],
            'open_until' => ['required', 'date', 'after:open_from'],
            'registration_link' => ['nullable', 'url'],
            'image_url' => ['nullable', 'string'],
            'province_id' => ['required', 'uuid', 'exists:provinces,id'],
            'city_id' => ['required', 'uuid', 'exists:cities,id'],
            'job_field_id' => ['required', 'uuid', 'exists:job_fields,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul wajib diisi',
            'title.min' => 'Judul minimal 3 karakter',
            'content.required' => 'Konten wajib diisi',
            'content.min' => 'Konten minimal 10 karakter',
            'company.required' => 'Nama perusahaan wajib diisi',
            'company.min' => 'Nama perusahaan minimal 2 karakter',
            'job_type.required' => 'Tipe pekerjaan wajib dipilih',
            'job_type.in' => 'Tipe pekerjaan tidak valid',
            'open_from.required' => 'Tanggal mulai wajib diisi',
            'open_until.required' => 'Tanggal berakhir wajib diisi',
            'open_until.after' => 'Tanggal berakhir harus setelah tanggal mulai',
            'registration_link.url' => 'Link pendaftaran harus berupa URL yang valid',
            'province_id.required' => 'Provinsi wajib dipilih',
            'province_id.exists' => 'Provinsi tidak ditemukan',
            'city_id.required' => 'Kota wajib dipilih',
            'city_id.exists' => 'Kota tidak ditemukan',
            'job_field_id.required' => 'Bidang pekerjaan wajib dipilih',
            'job_field_id.exists' => 'Bidang pekerjaan tidak ditemukan',
        ];
    }
}
