<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'sometimes|string|unique:products,slug',
            'sku' => 'sometimes|string|unique:products,sku',
            'description' => 'sometimes|string',
            'base_price' => 'required|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'low_stock_threshold' => 'sometimes|integer|min:0',
            'initial_stock' => 'sometimes|integer|min:0',
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'variants' => 'sometimes|array',
            'variants.*.name' => 'required|string',
            'variants.*.sku' => 'sometimes|string',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.attributes' => 'sometimes|array',
            'variants.*.initial_stock' => 'sometimes|integer|min:0',
        ];
    }
}
