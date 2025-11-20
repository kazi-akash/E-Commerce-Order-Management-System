# Testing Documentation

## Overview

This project includes comprehensive test coverage for the E-Commerce Order Management API. Tests are organized into Feature tests (end-to-end API testing) and Unit tests (isolated component testing).

## Test Statistics

### Feature Tests (4 files, ~50 tests)
- **AuthenticationTest** - 10 tests covering registration, login, logout, token management
- **ProductTest** - 15 tests covering product CRUD and authorization
- **OrderTest** - 18 tests covering order lifecycle and permissions
- **InventoryTest** - 12 tests covering stock management

### Unit Tests (6 files, ~60 tests)
- **AuthServiceTest** - 15 tests for authentication logic
- **InventoryServiceTest** - 12 tests for inventory operations
- **OrderServiceTest** - 12 tests for order creation and management
- **ProductServiceTest** - 12 tests for product operations
- **ProcessOrderActionTest** - 8 tests for order processing
- **CancelOrderActionTest** - 10 tests for order cancellation

**Total: ~110 tests**

## Quick Start

```bash
# Run all tests
php artisan test

# Run with output
php artisan test --verbose

# Run specific suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run specific file
php artisan test tests/Feature/AuthenticationTest.php
```

## Test Coverage by Feature

### Authentication
**Feature Tests:**
- User registration with validation
- Login with valid/invalid credentials
- Token refresh mechanism
- Logout functionality
- Profile retrieval

**Unit Tests:**
- Token generation and verification
- Password hashing
- Refresh token management
- Token revocation
- JWT payload validation

### Products
**Feature Tests:**
- List products with filters
- Get single product
- Create product with/without variants
- Update product details
- Delete product
- Vendor-specific access control

**Unit Tests:**
- Slug generation
- SKU generation
- Inventory creation
- Variant handling
- Transaction rollback

### Orders
**Feature Tests:**
- List orders with filters
- Get order details
- Create order with items
- Confirm order (admin/vendor)
- Cancel order
- Update order status
- Customer/admin access control

**Unit Tests:**
- Order number generation
- Total calculation
- Order item creation
- Status history tracking
- Transaction handling

### Inventory
**Feature Tests:**
- Add stock to products/variants
- Deduct stock
- Insufficient stock handling
- Inventory logs creation
- Role-based permissions

**Unit Tests:**
- Stock addition logic
- Stock deduction with validation
- Stock restoration
- Inventory log creation
- Transaction safety

### Actions
**Unit Tests:**
- Order processing workflow
- Inventory deduction on confirmation
- Order cancellation workflow
- Inventory restoration on cancel
- Event dispatching
- Transaction rollback on errors

## Test Patterns

### Authentication Pattern
```php
// Create user with specific role
$admin = User::factory()->admin()->create();
$vendor = User::factory()->vendor()->create();
$customer = User::factory()->customer()->create();

// Act as user
$response = $this->actingAs($vendor)
    ->getJson('/api/v1/products');
```

### Database Assertions
```php
// Check record exists
$this->assertDatabaseHas('products', [
    'name' => 'Test Product',
    'vendor_id' => $vendor->id,
]);

// Check record doesn't exist
$this->assertDatabaseMissing('products', [
    'name' => 'Deleted Product',
]);

// Check soft delete
$this->assertSoftDeleted('products', ['id' => $product->id]);
```

### API Response Assertions
```php
$response->assertStatus(200);
$response->assertJson(['message' => 'Success']);
$response->assertJsonStructure([
    'data' => ['id', 'name', 'price']
]);
$response->assertJsonValidationErrors(['email']);
```

### Event Testing
```php
Event::fake();

// Perform action
$this->orderService->confirmOrder($order);

// Assert event was dispatched
Event::assertDispatched(OrderConfirmed::class);
```

## Test Data Factories

### User Factory
```php
User::factory()->create();
User::factory()->admin()->create();
User::factory()->vendor()->create();
User::factory()->customer()->create();
```

### Product Factory
```php
Product::factory()->create();
Product::factory()->inactive()->create();
Product::factory()->withVariants()->create();
```

### Order Factory
```php
Order::factory()->create();
Order::factory()->pending()->create();
Order::factory()->processing()->create();
Order::factory()->shipped()->create();
Order::factory()->delivered()->create();
Order::factory()->cancelled()->create();
```

## Common Test Scenarios

### Testing Authorization
```php
public function test_vendor_cannot_update_other_vendor_product()
{
    $vendor = User::factory()->vendor()->create();
    $otherVendor = User::factory()->vendor()->create();
    $product = Product::factory()->create(['vendor_id' => $otherVendor->id]);

    $response = $this->actingAs($vendor)
        ->putJson('/api/v1/products/' . $product->id, [
            'name' => 'Hacked Name',
        ]);

    $response->assertStatus(403);
}
```

### Testing Validation
```php
public function test_product_creation_requires_name_and_price()
{
    $vendor = User::factory()->vendor()->create();

    $response = $this->actingAs($vendor)
        ->postJson('/api/v1/products', [
            'description' => 'Missing required fields',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'base_price']);
}
```

### Testing Transactions
```php
public function test_create_order_is_transactional()
{
    $data = [
        'items' => [
            ['product_id' => 1, 'quantity' => 1],
            ['product_id' => 99999, 'quantity' => 1], // Invalid
        ],
    ];

    try {
        $this->orderService->createOrder($data, $customer->id);
    } catch (\Exception $e) {
        // Expected
    }

    // No order should be created
    $this->assertEquals(0, Order::count());
}
```

## Running Tests in CI/CD

### GitHub Actions Example
```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: test_db
        ports:
          - 3306:3306
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.1
          extensions: mbstring, pdo_mysql
      
      - name: Install Dependencies
        run: composer install --no-interaction
      
      - name: Copy Environment
        run: cp .env.example .env
      
      - name: Generate Key
        run: php artisan key:generate
      
      - name: Run Tests
        run: php artisan test
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: test_db
          DB_USERNAME: root
          DB_PASSWORD: password
```

## Test Maintenance

### Adding New Tests
1. Create test file in appropriate directory
2. Use `RefreshDatabase` trait
3. Follow naming convention: `test_description_of_behavior`
4. Keep tests focused and independent
5. Use factories for test data

### Updating Tests
When modifying code:
1. Run affected tests first
2. Update test expectations if behavior changed
3. Add new tests for new functionality
4. Ensure all tests pass before committing

### Debugging Failed Tests
```bash
# Run with verbose output
php artisan test --verbose

# Run specific failing test
php artisan test --filter test_name

# Stop on first failure
php artisan test --stop-on-failure

# Show detailed error output
php artisan test --testdox
```

## Best Practices

1. **One assertion per test** - Focus on testing one thing
2. **Descriptive names** - Test name should explain what it tests
3. **Arrange-Act-Assert** - Clear test structure
4. **Use factories** - Don't create data manually
5. **Test edge cases** - Not just happy paths
6. **Keep tests fast** - Use in-memory database
7. **Independent tests** - No dependencies between tests
8. **Mock external services** - Don't make real API calls

## Coverage Goals

- **Critical paths**: 100% coverage (authentication, orders, payments)
- **Business logic**: 90% coverage (services, actions)
- **Controllers**: 80% coverage (feature tests)
- **Overall project**: 80% minimum

## Troubleshooting

### Tests Failing Locally
```bash
# Clear cache
php artisan config:clear
php artisan cache:clear

# Regenerate autoload
composer dump-autoload

# Check database connection
php artisan migrate:fresh --env=testing
```

### Slow Tests
- Use SQLite in-memory database
- Reduce factory data creation
- Mock external services
- Use `--parallel` flag (Laravel 8+)

### Random Failures
- Check for test interdependencies
- Ensure database is refreshed
- Look for timing issues
- Check for shared state

## Resources

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Test-Driven Development](https://martinfowler.com/bliki/TestDrivenDevelopment.html)
- [Testing Best Practices](https://github.com/goldbergyoni/javascript-testing-best-practices)
