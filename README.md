# E-Commerce Order Management System

A robust RESTful API for managing e-commerce operations including products, orders, inventory, and user management. Built with Laravel 10 and featuring JWT authentication, role-based access control, and comprehensive testing.

Author

Kazi Sajedul Islam
- Email: quaziakash@gmail.com
- GitHub: https://github.com/kazi-akash

Table of Contents

- Features
- Tech Stack
- System Requirements
- Installation
- Environment Configuration
- Database Setup
- API Documentation
- Authentication
- Testing
- Project Structure
- API Endpoints
- Troubleshooting
- License

Features

#Core Functionality
- User Management
  - Multi-role system (Admin, Vendor, Customer)
  - JWT-based authentication with refresh tokens
  - Secure password hashing
  - User profile management

- Product Management
  - CRUD operations for products
  - Product variants (size, color, etc.)
  - Category organization
  - Image upload support
  - SKU and slug generation
  - Vendor-specific product management

- Order Management
  - Complete order lifecycle (pending → processing → shipped → delivered)
  - Order cancellation with inventory restoration
  - Order status history tracking
  - Multiple items per order
  - Tax and shipping calculations
  - Order confirmation workflow

- Inventory Management
  - Real-time stock tracking
  - Available and reserved quantity management
  - Stock addition and deduction
  - Low stock alerts
  - Inventory logs for audit trail
  - Support for both products and variants

#Security Features
- JWT authentication with access and refresh tokens
- Role-based access control (RBAC)
- Rate limiting on API endpoints
- Input validation and sanitization
- SQL injection prevention
- XSS protection

#Additional Features
- Comprehensive API documentation (OpenAPI/Swagger)
- Postman collection for easy testing
- Event-driven architecture
- Database transactions for data integrity
- Soft deletes for data recovery
- Pagination support
- Advanced filtering and search

Tech Stack

- Framework: Laravel 10.x
- PHP: 8.1+
- Database: MySQL 8.0
- Authentication: JWT (firebase/php-jwt)
- API Documentation: OpenAPI 3.0, Swagger
- Testing: PHPUnit
- Cache: Redis (optional)
- Queue: Redis/Database (optional)

System Requirements

- PHP >= 8.1
- Composer
- MySQL >= 8.0 or MariaDB >= 10.3
- Node.js >= 16.x (for asset compilation)
- NPM or Yarn

#PHP Extensions Required
- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML

Installation

#Step 1: Clone the Repository

git clone https://github.com/kazi-akash/ecommerce-order-management.git
cd ecommerce-order-management

#Step 2: Install PHP Dependencies

composer install

#Step 3: Install Node Dependencies

npm install

#Step 4: Environment Setup

# Copy the example environment file
cp .env.example .env

# Generate application key
php artisan key:generate

#Step 5: Configure Environment Variables

Edit the `.env` file with your database credentials and other settings (see [Environment Configuration](#environment-configuration) section).

#Step 6: Database Setup

# Create database (if not exists)
mysql -u root -p -e "CREATE DATABASE ecom_order_management;"

# Run migrations
php artisan migrate

# Seed the database with sample data
php artisan db:seed

#Step 7: Storage Link

# Create symbolic link for file storage
php artisan storage:link

#Step 8: Start Development Server

# Start Laravel development server
php artisan serve

# In another terminal, compile assets (optional)
npm run dev

The API will be available at `http://localhost:8000`

Environment Configuration

#Essential Variables
 
# Application
APP_NAME="E-Commerce API"
APP_ENV=local
APP_KEY=base64:your-generated-key
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecom_order_management
DB_USERNAME=root
DB_PASSWORD=your_password

# JWT Configuration
JWT_SECRET="${APP_KEY}"
JWT_ACCESS_TOKEN_EXPIRY=3600        # 1 hour in seconds
JWT_REFRESH_TOKEN_EXPIRY=604800     # 7 days in seconds

#Rate Limiting Configuration
 
# Rate limits per minute
RATE_LIMIT_API=60          # Standard API endpoints
RATE_LIMIT_AUTH=5          # Authentication endpoints
RATE_LIMIT_ORDERS=10       # Order creation endpoint
RATE_LIMIT_ADMIN=120       # Admin endpoints

#Optional Services
 
# Mail Configuration (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# Redis (for caching and queues)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue Configuration
QUEUE_CONNECTION=sync      # Use 'redis' or 'database' for production

#File Storage
 
FILESYSTEM_DISK=local      # Use 's3' for AWS S3 storage

# AWS S3 Configuration (if using S3)
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_bucket_name

Database Setup

#Create Database
 
# MySQL
mysql -u root -p
CREATE DATABASE ecom_order_management;
exit;

#Run Migrations
 
# Run all migrations
php artisan migrate

# Fresh migration (drops all tables and recreates)
php artisan migrate:fresh

# Rollback last migration
php artisan migrate:rollback

#Seed Database
 
# Seed with sample data
php artisan db:seed

# Seed specific seeder
php artisan db:seed --class=UserSeeder

# Fresh migration with seeding
php artisan migrate:fresh --seed

#Sample Users Created by Seeder

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@example.com | password |
| Vendor | vendor1@example.com | password |
| Vendor | vendor2@example.com | password |
| Vendor | vendor3@example.com | password |
| Customer | john@example.com | password |
| Customer | sarah@example.com | password |

API Documentation

#Available Documentation

1. OpenAPI/Swagger Specification
   - Location: `docs/openapi.yaml`
   - View in Swagger UI: Import the file to [Swagger Editor](https://editor.swagger.io/)

2. Postman Collection
   - Location: `docs/postman_collection.json`
   - Import into Postman for easy API testing
   - Includes automatic token management

3. Authentication Guide
   - Location: `docs/AUTHENTICATION.md`
   - Detailed guide on JWT authentication flow

4. Testing Documentation
   - Location: `docs/TESTING.md`
   - Comprehensive testing guide

#Quick API Reference

Base URL: `http://localhost:8000/api/v1`

Authentication: Bearer Token (JWT)

Content-Type: `application/json`

Authentication

This API uses JWT (JSON Web Tokens) for authentication with a dual-token approach:
- Access Token: Short-lived (1 hour), used for API requests
- Refresh Token: Long-lived (7 days), used to get new access tokens

#Authentication Flow

##1. Register a New User
 
POST /api/v1/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "customer"
}

Response:
 
{
  "message": "Registration successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "customer"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "expires_in": 3600
  }
}

##2. Login
 
POST /api/v1/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}

##3. Use Access Token

Include the access token in the Authorization header:
 
GET /api/v1/products
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...

##4. Refresh Token

When the access token expires:
 
POST /api/v1/refresh
Content-Type: application/json

{
  "refresh_token": "your_refresh_token"
}

##5. Logout
 
POST /api/v1/logout
Content-Type: application/json

{
  "refresh_token": "your_refresh_token"
}

#User Roles and Permissions

| Role | Permissions |
|------|-------------|
| Admin | Full access to all resources |
| Vendor | Manage own products, view/confirm orders, manage own inventory |
| Customer | View products, create/view own orders, cancel own orders |

Testing

#Run All Tests
 
php artisan test

#Run Specific Test Suites
 
# Feature tests only
php artisan test --testsuite=Feature

# Unit tests only
php artisan test --testsuite=Unit

#Run Specific Test File
 
php artisan test tests/Feature/AuthenticationTest.php

#Run with Coverage
 
php artisan test --coverage

#Test Statistics

- Total Tests: ~110
- Feature Tests: 50+ (API endpoint testing)
- Unit Tests: 60+ (Service and action testing)
- Coverage: 80%+

#Test Categories

1. Authentication Tests - Registration, login, token management
2. Product Tests - CRUD operations, variants, authorization
3. Order Tests - Order lifecycle, status updates, permissions
4. Inventory Tests - Stock management, logs, validation
5. Service Tests - Business logic, calculations, transactions
6. Action Tests - Order processing, cancellation workflows

For detailed testing documentation, see `docs/TESTING.md`

Project Structure

ecom-order-management/
├── app/
│   ├── Actions/              # Business action classes
│   │   ├── CancelOrderAction.php
│   │   └── ProcessOrderAction.php
│   ├── Events/               # Event classes
│   │   ├── LowStockDetected.php
│   │   ├── OrderCancelled.php
│   │   └── OrderConfirmed.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/V1/       # API controllers
│   │   │       ├── AuthController.php
│   │   │       ├── ProductController.php
│   │   │       ├── OrderController.php
│   │   │       └── InventoryController.php
│   │   └── Middleware/       # Custom middleware
│   │       ├── JwtAuth.php
│   │       └── RoleMiddleware.php
│   ├── Jobs/                 # Queue jobs
│   │   ├── CheckLowStockJob.php
│   │   └── SendOrderEmailJob.php
│   ├── Listeners/            # Event listeners
│   ├── Models/               # Eloquent models
│   │   ├── User.php
│   │   ├── Product.php
│   │   ├── ProductVariant.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   ├── Inventory.php
│   │   └── ...
│   ├── Repositories/         # Repository pattern
│   │   ├── ProductRepository.php
│   │   └── OrderRepository.php
│   └── Services/             # Business logic services
│       ├── AuthService.php
│       ├── ProductService.php
│       ├── OrderService.php
│       └── InventoryService.php
├── config/                   # Configuration files
├── database/
│   ├── factories/            # Model factories for testing
│   ├── migrations/           # Database migrations
│   └── seeders/              # Database seeders
├── docs/                     # API documentation
│   ├── openapi.yaml
│   ├── postman_collection.json
│   ├── AUTHENTICATION.md
│   └── TESTING.md
├── routes/
│   └── api.php               # API routes
├── tests/
│   ├── Feature/              # Feature tests
│   └── Unit/                 # Unit tests
└── storage/                  # File storage

API Endpoints

#Authentication

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/api/v1/register` | Register new user | No |
| POST | `/api/v1/login` | Login user | No |
| POST | `/api/v1/refresh` | Refresh access token | No |
| POST | `/api/v1/logout` | Logout user | No |
| GET | `/api/v1/me` | Get current user | Yes |

#Products

| Method | Endpoint | Description | Auth | Roles |
|--------|----------|-------------|------|-------|
| GET | `/api/v1/products` | List products | Yes | All |
| GET | `/api/v1/products/{id}` | Get product | Yes | All |
| POST | `/api/v1/products` | Create product | Yes | Admin, Vendor |
| PUT | `/api/v1/products/{id}` | Update product | Yes | Admin, Vendor |
| DELETE | `/api/v1/products/{id}` | Delete product | Yes | Admin, Vendor |

#Orders

| Method | Endpoint | Description | Auth | Roles |
|--------|----------|-------------|------|-------|
| GET | `/api/v1/orders` | List orders | Yes | All |
| GET | `/api/v1/orders/{id}` | Get order | Yes | All |
| POST | `/api/v1/orders` | Create order | Yes | All |
| POST | `/api/v1/orders/{id}/confirm` | Confirm order | Yes | Admin, Vendor |
| POST | `/api/v1/orders/{id}/cancel` | Cancel order | Yes | All |
| PATCH | `/api/v1/orders/{id}/status` | Update status | Yes | Admin, Vendor |

#Inventory

| Method | Endpoint | Description | Auth | Roles |
|--------|----------|-------------|------|-------|
| POST | `/api/v1/inventory/add` | Add stock | Yes | Admin, Vendor |
| POST | `/api/v1/inventory/deduct` | Deduct stock | Yes | Admin, Vendor |

Troubleshooting

#Common Issues

##1. Database Connection Error
 
# Check database credentials in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecom_order_management
DB_USERNAME=root
DB_PASSWORD=your_password

# Test connection
php artisan migrate:status

##2. JWT Token Issues
 
# Regenerate application key
php artisan key:generate

# Clear config cache
php artisan config:clear

##3. Permission Denied Errors
 
# Fix storage permissions (Linux/Mac)
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Windows
# Run as administrator or check folder permissions

##4. Composer Dependencies
 
# Update dependencies
composer update

# Clear composer cache
composer clear-cache
composer install

##5. Migration Errors
 
# Drop all tables and remigrate
php artisan migrate:fresh

# With seeding
php artisan migrate:fresh --seed

#Debug Mode

Enable debug mode in `.env` for detailed error messages:
 
APP_DEBUG=true
LOG_LEVEL=debug

Note: Disable debug mode in production!

📝 Development Guidelines

#Code Style

This project follows PSR-12 coding standards. Format code using:

 
# Install PHP CS Fixer
composer require --dev friendsofphp/php-cs-fixer

# Format code
./vendor/bin/php-cs-fixer fix

#Git Workflow
 
# Create feature branch
git checkout -b feature/your-feature-name

# Make changes and commit
git add .
git commit -m "Add: your feature description"

# Push to remote
git push origin feature/your-feature-name

#Adding New Features

1. Create migration: `php artisan make:migration create_table_name`
2. Create model: `php artisan make:model ModelName`
3. Create controller: `php artisan make:controller Api/V1/ControllerName`
4. Create service: Create in `app/Services/`
5. Add routes in `routes/api.php`
6. Write tests in `tests/Feature/` and `tests/Unit/`
7. Update API documentation

Deployment

#Production Checklist

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure production database
- [ ] Set up proper JWT secrets
- [ ] Configure mail service
- [ ] Set up queue workers
- [ ] Configure Redis for caching
- [ ] Set up SSL certificate
- [ ] Configure CORS properly
- [ ] Set up backup strategy
- [ ] Configure monitoring and logging

#Optimization Commands
 
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev

License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

Contact

Kazi Sajedul Islam
- Email: quaziakash@gmail.com
- GitHub: [@kazi-akash](https://github.com/kazi-akash)

Acknowledgments

- Laravel Framework
- JWT Authentication Library
- All contributors and testers
