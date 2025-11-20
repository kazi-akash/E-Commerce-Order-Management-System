<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        private ProductRepository $productRepository,
        private InventoryService $inventoryService,
        private ImageService $imageService
    ) {}

    public function createProduct(array $data, int $vendorId): Product
    {
        return DB::transaction(function () use ($data, $vendorId) {
            // Handle image uploads
            $imagePaths = [];
            if (isset($data['images']) && is_array($data['images'])) {
                $imagePaths = $this->imageService->storeProductImages($data['images']);
            }

            $product = $this->productRepository->create([
                'vendor_id' => $vendorId,
                'category_id' => $data['category_id'] ?? null,
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'sku' => $data['sku'] ?? $this->generateSku(),
                'description' => $data['description'] ?? null,
                'base_price' => $data['base_price'],
                'has_variants' => isset($data['variants']) && count($data['variants']) > 0,
                'is_active' => $data['is_active'] ?? true,
                'low_stock_threshold' => $data['low_stock_threshold'] ?? 10,
                'images' => $imagePaths,
                'meta_data' => $data['meta_data'] ?? [],
            ]);

            // Create inventory if no variants
            if (!$product->has_variants && isset($data['initial_stock'])) {
                $this->inventoryService->addStock($product, $data['initial_stock'], 'Initial stock');
            }

            // Create variants if provided
            if (isset($data['variants'])) {
                foreach ($data['variants'] as $variantData) {
                    $variant = $product->variants()->create([
                        'sku' => $variantData['sku'] ?? $this->generateSku(),
                        'name' => $variantData['name'],
                        'attributes' => $variantData['attributes'] ?? [],
                        'price' => $variantData['price'],
                        'is_active' => $variantData['is_active'] ?? true,
                    ]);

                    if (isset($variantData['initial_stock'])) {
                        $this->inventoryService->addStock($variant, $variantData['initial_stock'], 'Initial stock');
                    }
                }
            }

            return $product->fresh(['variants', 'inventory']);
        });
    }

    public function updateProduct(Product $product, array $data): Product
    {
        $updateData = [
            'category_id' => $data['category_id'] ?? $product->category_id,
            'name' => $data['name'] ?? $product->name,
            'slug' => $data['slug'] ?? $product->slug,
            'description' => $data['description'] ?? $product->description,
            'base_price' => $data['base_price'] ?? $product->base_price,
            'is_active' => $data['is_active'] ?? $product->is_active,
            'low_stock_threshold' => $data['low_stock_threshold'] ?? $product->low_stock_threshold,
            'meta_data' => $data['meta_data'] ?? $product->meta_data,
        ];

        // Handle new image uploads
        if (isset($data['images']) && is_array($data['images'])) {
            $newImagePaths = $this->imageService->storeProductImages($data['images']);
            
            // Optionally delete old images if replace_images is true
            if (isset($data['replace_images']) && $data['replace_images']) {
                $this->imageService->deleteImages($product->images ?? []);
                $updateData['images'] = $newImagePaths;
            } else {
                // Append new images to existing ones
                $updateData['images'] = array_merge($product->images ?? [], $newImagePaths);
            }
        }

        $this->productRepository->update($product, $updateData);

        return $product->fresh();
    }

    public function deleteProduct(Product $product): bool
    {
        return $this->productRepository->delete($product);
    }

    private function generateSku(): string
    {
        return 'SKU-' . strtoupper(Str::random(8));
    }
}
