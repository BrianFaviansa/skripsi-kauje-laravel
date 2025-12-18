<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nim' => ['required', 'string', 'min:5', 'unique:users,nim'],
            'name' => ['required', 'string', 'min:3'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:5'],
            'phone_number' => ['required', 'string', 'min:10', 'unique:users,phone_number'],
            'enrollment_year' => ['required', 'integer'],
            'graduation_year' => ['required', 'integer'],
            'role_id' => ['nullable', 'uuid', 'exists:roles,id'],
            'province_id' => ['required', 'uuid', 'exists:provinces,id'],
            'city_id' => ['required', 'uuid', 'exists:cities,id'],
            'faculty_id' => ['required', 'uuid', 'exists:faculties,id'],
            'major_id' => ['required', 'uuid', 'exists:majors,id'],
            'verification_file_url' => ['required', 'string', 'min:1'],
            'place_date_of_birth' => ['nullable', 'string'],
            'instance' => ['nullable', 'string'],
            'position' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nim.required' => 'NIM wajib diisi',
            'nim.min' => 'NIM minimal 5 karakter',
            'nim.unique' => 'NIM sudah terdaftar',
            'name.required' => 'Nama wajib diisi',
            'name.min' => 'Nama minimal 3 karakter',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 5 karakter',
            'phone_number.required' => 'Nomor telepon wajib diisi',
            'phone_number.min' => 'Nomor telepon minimal 10 karakter',
            'phone_number.unique' => 'Nomor telepon sudah terdaftar',
            'enrollment_year.required' => 'Tahun masuk wajib diisi',
            'enrollment_year.integer' => 'Tahun masuk harus berupa angka',
            'graduation_year.required' => 'Tahun lulus wajib diisi',
            'graduation_year.integer' => 'Tahun lulus harus berupa angka',
            'province_id.required' => 'Provinsi wajib dipilih',
            'province_id.exists' => 'Provinsi tidak ditemukan',
            'city_id.required' => 'Kota wajib dipilih',
            'city_id.exists' => 'Kota tidak ditemukan',
            'faculty_id.required' => 'Fakultas wajib dipilih',
            'faculty_id.exists' => 'Fakultas tidak ditemukan',
            'major_id.required' => 'Jurusan wajib dipilih',
            'major_id.exists' => 'Jurusan tidak ditemukan',
            'verification_file_url.required' => 'File verifikasi wajib diupload',
        ];
    }
}
