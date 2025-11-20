<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'sometimes|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.variant_attributes' => 'sometimes|array',
            'tax' => 'sometimes|numeric|min:0',
            'shipping_fee' => 'sometimes|numeric|min:0',
            'discount' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'shipping_address' => 'sometimes|string',
            'billing_address' => 'sometimes|string',
            'customer_email' => 'sometimes|email',
            'customer_phone' => 'sometimes|string',
            'notes' => 'sometimes|string',
        ];
    }
}
