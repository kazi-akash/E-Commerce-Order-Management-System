<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    private string $disk = 'public';
    private string $productPath = 'products';

    /**
     * Store multiple product images
     *
     * @param array $images Array of UploadedFile instances
     * @return array Array of stored image paths
     */
    public function storeProductImages(array $images): array
    {
        $storedPaths = [];

        foreach ($images as $image) {
            if ($image instanceof UploadedFile) {
                $storedPaths[] = $this->storeImage($image);
            }
        }

        return $storedPaths;
    }

    /**
     * Store a single image
     *
     * @param UploadedFile $image
     * @return string Stored image path
     */
    public function storeImage(UploadedFile $image): string
    {
        $filename = Str::uuid() . '.' . $image->getClientOriginalExtension();
        $path = $image->storeAs($this->productPath, $filename, $this->disk);

        return $path;
    }

    /**
     * Delete product images
     *
     * @param array $imagePaths
     * @return void
     */
    public function deleteImages(array $imagePaths): void
    {
        foreach ($imagePaths as $path) {
            if (Storage::disk($this->disk)->exists($path)) {
                Storage::disk($this->disk)->delete($path);
            }
        }
    }

    /**
     * Get full URL for an image path
     *
     * @param string $path
     * @return string
     */
    public function getImageUrl(string $path): string
    {
        return Storage::disk($this->disk)->url($path);
    }

    /**
     * Get full URLs for multiple image paths
     *
     * @param array $paths
     * @return array
     */
    public function getImageUrls(array $paths): array
    {
        return array_map(fn($path) => $this->getImageUrl($path), $paths);
    }
}
