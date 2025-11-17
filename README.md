# Laravel Sanctum API with Role-Based Authentication

This project is configured for API development with Laravel Sanctum authentication and role-based access control.

## Features

- ✅ Laravel Sanctum API authentication
- ✅ Role-based access control (User, Admin, Vendor)
- ✅ Token-based authentication
- ✅ Protected API routes
- ✅ CORS configuration
- ✅ Postman collection included

## Quick Setup

1. **Install dependencies:**
```bash
composer install
```

2. **Environment setup:**
```bash
copy .env.example .env
php artisan key:generate
```

3. **Configure database in `.env`:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

4. **Run migrations:**
```bash
php artisan migrate
```

5. **Start the server:**
```bash
php artisan serve
```

## API Documentation

See [API_DOCUMENTATION.md](API_DOCUMENTATION.md) for complete API documentation.

## Roles

- **user**: Regular user with basic access
- **admin**: Administrator with full access  
- **vendor**: Vendor with management access

## Testing

Import `postman_collection.json` into Postman for easy API testing.

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── AuthController.php    # Authentication endpoints
│   └── Middleware/
│       └── CheckRole.php              # Role-based middleware
routes/
└── api.php                            # API routes with role protection
```

## Available Endpoints

### Public
- `POST /api/register` - Register new user
- `POST /api/login` - Login user

### Protected (requires authentication)
- `GET /api/me` - Get current user
- `POST /api/logout` - Logout user
- `GET /api/admin/*` - Admin only routes
- `GET /api/vendor/*` - Vendor only routes
- `GET /api/user/*` - User routes
- `GET /api/management/*` - Admin & Vendor routes

## Quick Test

```bash
# Register a user
curl -X POST http://localhost:8000/api/register -H "Content-Type: application/json" -d "{\"name\":\"Test User\",\"email\":\"test@example.com\",\"password\":\"password123\",\"password_confirmation\":\"password123\",\"role\":\"user\"}"

# Login
curl -X POST http://localhost:8000/api/login -H "Content-Type: application/json" -d "{\"email\":\"test@example.com\",\"password\":\"password123\"}"
```

---

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>
