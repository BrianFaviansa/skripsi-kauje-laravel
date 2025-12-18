<?php

namespace Modules\News\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadNewsImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File wajib diupload',
            'file.file' => 'File tidak valid',
            'file.mimes' => 'File harus berupa JPG, JPEG, PNG, GIF, atau WEBP',
            'file.max' => 'Ukuran file maksimal 5MB',
        ];
    }
}
