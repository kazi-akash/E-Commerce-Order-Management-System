<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\InventoryService;
use App\Services\ImageService;
use App\Repositories\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductService $productService;

    protected function setUp(): void
    {
        parent::setUp();

        $productRepository = new ProductRepository();
        $inventoryService = new InventoryService();
        $imageService = $this->createMock(ImageService::class);

        // Mock image service to avoid actual file operations
        $imageService->method('storeProductImages')->willReturn([]);
        $imageService->method('deleteImages')->willReturn(null);

        $this->productService = new ProductService(
            $productRepository,
            $inventoryService,
            $imageService
        );
    }

    public function test_create_product_generates_slug_if_not_provided()
    {
        $vendor = User::factory()->vendor()->create();

        $data = [
            'name' => 'Test Product',
            'base_price' => 99.99,
        ];

        $product = $this->productService->createProduct($data, $vendor->id);

        $this->assertEquals('test-product', $product->slug);
    }

    public function test_create_product_uses_provided_slug()
    {
        $vendor = User::factory()->vendor()->create();

        $data = [
            'name' => 'Test Product',
            'slug' => 'custom-slug',
            'base_price' => 99.99,
        ];

        $product = $this->productService->createProduct($data, $vendor->id);

        $this->assertEquals('custom-slug', $product->slug);
    }

    public function test_create_product_generates_sku_if_not_provided()
    {
        $vendor = User::factory()->vendor()->create();

        $data = [
            'name' => 'Test Product',
            'base_price' => 99.99,
        ];

        $product = $this->productService->createProduct($data, $vendor->id);

        $this->assertNotNull($product->sku);
        $this->assertStringStartsWith('SKU-', $product->sku);
    }

    public function test_create_product_sets_default_values()
    {
        $vendor = User::factory()->vendor()->create();

        $data = [
            'name' => 'Test Product',
            'base_price' => 99.99,
        ];

        $product = $this->productService->createProduct($data, $vendor->id);

        $this->assertTrue($product->is_active);
        $this->assertEquals(10, $product->low_stock_threshold);
        $this->assertFalse($product->has_variants);
    }

    public function test_create_product_creates_inventory_when_initial_stock_provided()
    {
        $vendor = User::factory()->vendor()->create();

        $data = [
            'name' => 'Test Product',
            'base_price' => 99.99,
            'initial_stock' => 50,
        ];

        $product = $this->productService->createProduct($data, $vendor->id);

        $this->assertDatabaseHas('inventories', [
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
            'available_quantity' => 50,
        ]);
    }

    public function test_create_product_with_variants()
    {
        $vendor = User::factory()->vendor()->create();

        $data = [
            'name' => 'T-Shirt',
            'base_price' => 29.99,
            'variants' => [
                [
                    'name' => 'Small - Red',
                    'price' => 29.99,
                    'attributes' => ['size' => 'S', 'color' => 'Red'],
                    'initial_stock' => 20,
                ],
                [
                    'name' => 'Large - Blue',
                    'price' => 29.99,
                    'attributes' => ['size' => 'L', 'color' => 'Blue'],
                    'initial_stock' => 15,
                ],
            ],
        ];

        $product = $this->productService->createProduct($data, $vendor->id);

        $this->assertTrue($product->has_variants);
        $this->assertCount(2, $product->variants);

        $variant = $product->variants->first();
        $this->assertEquals('Small - Red', $variant->name);
        $this->assertEquals(['size' => 'S', 'color' => 'Red'], $variant->attributes);
    }

    public function test_create_product_creates_inventory_for_variants()
    {
        $vendor = User::factory()->vendor()->create();

        $data = [
            'name' => 'T-Shirt',
            'base_price' => 29.99,
            'variants' => [
                [
                    'name' => 'Small',
                    'price' => 29.99,
                    'initial_stock' => 20,
                ],
            ],
        ];

        $product = $this->productService->createProduct($data, $vendor->id);
        $variant = $product->variants->first();

        $this->assertDatabaseHas('inventories', [
            'inventoriable_type' => get_class($variant),
            'inventoriable_id' => $variant->id,
            'available_quantity' => 20,
        ]);
    }

    public function test_create_product_does_not_create_inventory_for_product_with_variants()
    {
        $vendor = User::factory()->vendor()->create();

        $data = [
            'name' => 'T-Shirt',
            'base_price' => 29.99,
            'initial_stock' => 50, // Should be ignored
            'variants' => [
                [
                    'name' => 'Small',
                    'price' => 29.99,
                    'initial_stock' => 20,
                ],
            ],
        ];

        $product = $this->productService->createProduct($data, $vendor->id);

        $this->assertDatabaseMissing('inventories', [
            'inventoriable_type' => Product::class,
            'inventoriable_id' => $product->id,
        ]);
    }

    public function test_update_product_updates_fields()
    {
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        $data = [
            'name' => 'Updated Name',
            'base_price' => 149.99,
            'is_active' => false,
        ];

        $updatedProduct = $this->productService->updateProduct($product, $data);

        $this->assertEquals('Updated Name', $updatedProduct->name);
        $this->assertEquals(149.99, $updatedProduct->base_price);
        $this->assertFalse($updatedProduct->is_active);
    }

    public function test_update_product_keeps_existing_values_if_not_provided()
    {
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Original Name',
            'base_price' => 99.99,
        ]);

        $data = [
            'description' => 'New description',
        ];

        $updatedProduct = $this->productService->updateProduct($product, $data);

        $this->assertEquals('Original Name', $updatedProduct->name);
        $this->assertEquals(99.99, $updatedProduct->base_price);
        $this->assertEquals('New description', $updatedProduct->description);
    }

    public function test_delete_product_soft_deletes()
    {
        $vendor = User::factory()->vendor()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        $result = $this->productService->deleteProduct($product);

        $this->assertTrue($result);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_create_product_is_transactional()
    {
        $vendor = User::factory()->vendor()->create();

        $data = [
            'name' => 'Test Product',
            'base_price' => 99.99,
            'variants' => [
                [
                    'name' => 'Variant 1',
                    'price' => 'invalid', // This should cause an error
                ],
            ],
        ];

        try {
            $this->productService->createProduct($data, $vendor->id);
        } catch (\Exception $e) {
            // Expected exception
        }

        // No product should be created
        $this->assertEquals(0, Product::count());
    }

    public function test_generated_sku_is_unique()
    {
        $vendor = User::factory()->vendor()->create();

        $product1 = $this->productService->createProduct([
            'name' => 'Product 1',
            'base_price' => 99.99,
        ], $vendor->id);

        $product2 = $this->productService->createProduct([
            'name' => 'Product 2',
            'base_price' => 99.99,
        ], $vendor->id);

        $this->assertNotEquals($product1->sku, $product2->sku);
    }
}
