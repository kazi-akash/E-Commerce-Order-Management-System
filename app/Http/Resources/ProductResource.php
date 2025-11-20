<?php

namespace App\Http\Resources;

use App\Services\ImageService;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        $imageService = app(ImageService::class);
        
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => $this->description,
            'base_price' => $this->base_price,
            'has_variants' => $this->has_variants,
            'is_active' => $this->is_active,
            'low_stock_threshold' => $this->low_stock_threshold,
            'images' => $this->images ? $imageService->getImageUrls($this->images) : [],
            'vendor' => $this->whenLoaded('vendor', fn() => [
                'id' => $this->vendor->id,
                'name' => $this->vendor->name,
            ]),
            'category' => $this->whenLoaded('category'),
            'variants' => $this->whenLoaded('variants'),
            'inventory' => $this->whenLoaded('inventory'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
