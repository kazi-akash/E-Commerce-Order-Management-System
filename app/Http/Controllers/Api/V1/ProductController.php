<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService,
        private ProductRepository $productRepository
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['vendor_id', 'category_id', 'is_active', 'search']);
        
        // Vendors can only see their own products
        if (auth()->user()->isVendor()) {
            $filters['vendor_id'] = auth()->id();
        }

        $products = $this->productRepository->paginate(
            $request->get('per_page', 15),
            $filters
        );

        return response()->json($products);
    }

    public function show($id)
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Vendors can only view their own products
        if (auth()->user()->isVendor() && $product->vendor_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['data' => $product]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
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
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vendorId = auth()->user()->isVendor() ? auth()->id() : $request->vendor_id;

        $product = $this->productService->createProduct($request->all(), $vendorId);

        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Vendors can only update their own products
        if (auth()->user()->isVendor() && $product->vendor_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:products,slug,' . $id,
            'description' => 'sometimes|string',
            'base_price' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'low_stock_threshold' => 'sometimes|integer|min:0',
            'images' => 'sometimes|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'replace_images' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = $this->productService->updateProduct($product, $request->all());

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product,
        ]);
    }

    public function destroy($id)
    {
        $product = $this->productRepository->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Vendors can only delete their own products
        if (auth()->user()->isVendor() && $product->vendor_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $this->productService->deleteProduct($product);

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
