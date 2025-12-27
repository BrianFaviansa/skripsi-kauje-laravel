<?php

namespace Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'nim' => ['sometimes', 'string', 'min:5', 'unique:users,nim,' . $userId],
            'name' => ['sometimes', 'string', 'min:3'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $userId],
            'password' => ['sometimes', 'string', 'min:5'],
            'phone_number' => ['sometimes', 'string', 'min:10', 'unique:users,phone_number,' . $userId],
            'enrollment_year' => ['sometimes', 'integer'],
            'graduation_year' => ['sometimes', 'integer'],
            'role_id' => ['sometimes', 'uuid', 'exists:roles,id'],
            'province_id' => ['sometimes', 'uuid', 'exists:provinces,id'],
            'city_id' => ['sometimes', 'uuid', 'exists:cities,id'],
            'faculty_id' => ['sometimes', 'uuid', 'exists:faculties,id'],
            'major_id' => ['sometimes', 'uuid', 'exists:majors,id'],
            'verification_file_url' => ['sometimes', 'string', 'min:1'],
            'instance' => ['nullable', 'string'],
            'position' => ['nullable', 'string'],
            'verification_status' => ['sometimes', 'string', 'in:PENDING,VERIFIED,REJECTED'],
        ];
    }

    public function messages(): array
    {
        return [
            'nim.min' => 'NIM minimal 5 karakter',
            'nim.unique' => 'NIM sudah terdaftar',
            'name.min' => 'Nama minimal 3 karakter',
            'email.email' => 'Email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'password.min' => 'Password minimal 5 karakter',
            'phone_number.min' => 'Nomor telepon minimal 10 karakter',
            'phone_number.unique' => 'Nomor telepon sudah terdaftar',
            'enrollment_year.integer' => 'Tahun masuk harus berupa angka',
            'graduation_year.integer' => 'Tahun lulus harus berupa angka',
            'role_id.exists' => 'Role tidak ditemukan',
            'province_id.exists' => 'Provinsi tidak ditemukan',
            'city_id.exists' => 'Kota tidak ditemukan',
            'faculty_id.exists' => 'Fakultas tidak ditemukan',
            'major_id.exists' => 'Jurusan tidak ditemukan',
            'verification_status.in' => 'Status verifikasi tidak valid',
        ];
    }
}
