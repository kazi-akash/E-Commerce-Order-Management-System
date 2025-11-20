# Testing Guide

This project includes comprehensive test coverage for all API endpoints, services, and actions.

## Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Suite
```bash
# Feature tests only
php artisan test --testsuite=Feature

# Unit tests only
php artisan test --testsuite=Unit
```

### Run Specific Test File
```bash
php artisan test tests/Feature/AuthenticationTest.php
php artisan test tests/Unit/AuthServiceTest.php
```

### Run Specific Test Method
```bash
php artisan test --filter test_user_can_login
```

### Run with Coverage (requires Xdebug)
```bash
php artisan test --coverage
```

## Test Structure

### Feature Tests
Feature tests verify that the API endpoints work correctly end-to-end. They test:
- HTTP request/response handling
- Authentication and authorization
- Validation rules
- Database interactions
- Role-based access control

**Location:** `tests/Feature/`

**Files:**
- `AuthenticationTest.php` - Registration, login, logout, token refresh
- `ProductTest.php` - Product CRUD operations
- `OrderTest.php` - Order management
- `InventoryTest.php` - Inventory operations

### Unit Tests
Unit tests verify individual components work correctly in isolation. They test:
- Service layer logic
- Action classes
- Business rules
- Data transformations

**Location:** `tests/Unit/`

**Files:**
- `AuthServiceTest.php` - Authentication service logic
- `InventoryServiceTest.php` - Inventory management logic
- `OrderServiceTest.php` - Order creation and management
- `ProductServiceTest.php` - Product creation and updates
- `ProcessOrderActionTest.php` - Order processing action
- `CancelOrderActionTest.php` - Order cancellation action

## Test Database

Tests use a separate database to avoid affecting your development data. Configure it in `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

Or use a dedicated test database:

```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="ecom_test"/>
```

## Writing Tests

### Feature Test Example
```php
public function test_user_can_create_product()
{
    $vendor = User::factory()->vendor()->create();

    $response = $this->actingAs($vendor)
        ->postJson('/api/v1/products', [
            'name' => 'Test Product',
            'base_price' => 99.99,
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['message', 'data']);

    $this->assertDatabaseHas('products', [
        'name' => 'Test Product',
    ]);
}
```

### Unit Test Example
```php
public function test_add_stock_increments_inventory()
{
    $product = Product::factory()->create();
    
    Inventory::create([
        'inventoriable_type' => Product::class,
        'inventoriable_id' => $product->id,
        'available_quantity' => 10,
        'reserved_quantity' => 0,
    ]);

    $this->inventoryService->addStock($product, 25);

    $this->assertDatabaseHas('inventories', [
        'inventoriable_id' => $product->id,
        'available_quantity' => 35,
    ]);
}
```

## Test Helpers

### Authentication
```php
// Act as a specific user
$this->actingAs($user);

// Create users with specific roles
$admin = User::factory()->admin()->create();
$vendor = User::factory()->vendor()->create();
$customer = User::factory()->customer()->create();
```

### Assertions
```php
// HTTP assertions
$response->assertStatus(200);
$response->assertJson(['key' => 'value']);
$response->assertJsonStructure(['data' => ['id', 'name']]);

// Database assertions
$this->assertDatabaseHas('table', ['column' => 'value']);
$this->assertDatabaseMissing('table', ['column' => 'value']);
$this->assertSoftDeleted('table', ['id' => 1]);

// Event assertions
Event::fake();
Event::assertDispatched(OrderConfirmed::class);
```

## Factories

Factories are used to create test data quickly:

```php
// Create a user
$user = User::factory()->create();

// Create with specific attributes
$user = User::factory()->create([
    'email' => 'test@example.com',
    'role' => 'admin',
]);

// Create multiple
$users = User::factory()->count(5)->create();

// Create without persisting
$user = User::factory()->make();
```

## Common Issues

### Database Not Refreshing
Make sure you're using the `RefreshDatabase` trait:
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase;
}
```

### Authentication Issues
Ensure you're using `actingAs()` for protected endpoints:
```php
$user = User::factory()->create();
$response = $this->actingAs($user)->getJson('/api/v1/products');
```

### Factory Not Found
Run the factory generation command:
```bash
php artisan make:factory ProductFactory --model=Product
```

## Continuous Integration

Tests should run automatically in CI/CD pipelines. Example GitHub Actions workflow:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.1
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: php artisan test
```

## Test Coverage Goals

- Feature tests: Cover all API endpoints
- Unit tests: Cover all service methods and actions
- Minimum coverage: 80%
- Critical paths: 100% coverage

## Best Practices

1. **Test one thing at a time** - Each test should verify a single behavior
2. **Use descriptive names** - Test names should explain what they test
3. **Arrange, Act, Assert** - Structure tests clearly
4. **Keep tests independent** - Tests should not depend on each other
5. **Use factories** - Don't manually create test data
6. **Clean up** - Use `RefreshDatabase` to reset state
7. **Mock external services** - Don't make real API calls or send emails

## Need Help?

- Laravel Testing Docs: https://laravel.com/docs/testing
- PHPUnit Docs: https://phpunit.de/documentation.html
- Test-Driven Development: https://martinfowler.com/bliki/TestDrivenDevelopment.html
