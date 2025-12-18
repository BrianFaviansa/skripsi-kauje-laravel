<?php

namespace Modules\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:3'],
            'description' => ['sometimes', 'string', 'min:10'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'category' => ['sometimes', 'string', 'in:PRODUK,JASA'],
            'image_url' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.min' => 'Nama produk minimal 3 karakter',
            'description.min' => 'Deskripsi minimal 10 karakter',
            'price.numeric' => 'Harga harus berupa angka',
            'price.min' => 'Harga tidak boleh negatif',
            'category.in' => 'Kategori tidak valid',
        ];
    }
}
