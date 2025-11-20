<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:products,slug,' . $this->route('product'),
            'description' => 'sometimes|string',
            'base_price' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'low_stock_threshold' => 'sometimes|integer|min:0',
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'replace_images' => 'sometimes|boolean',
        ];
    }
}
