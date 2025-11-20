# Authentication Guide

## Overview

This API uses JWT (JSON Web Tokens) for authentication. The system implements a dual-token approach with access tokens and refresh tokens for enhanced security.

## Authentication Flow

### 1. Registration

Create a new user account to get started.

**Endpoint:** `POST /api/v1/register`

**Request:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "customer",
  "phone": "+1234567890",
  "address": "123 Main St, City, Country"
}
```

**Response:**
```json
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
```

### 2. Login

Authenticate with existing credentials.

**Endpoint:** `POST /api/v1/login`

**Request:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "message": "Login successful",
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
```

### 3. Using Access Tokens

Include the access token in the Authorization header for all protected endpoints.

**Header:**
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Example Request:**
```bash
curl -X GET http://localhost/api/v1/products \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

### 4. Refreshing Tokens

When the access token expires, use the refresh token to get a new one.

**Endpoint:** `POST /api/v1/refresh`

**Request:**
```json
{
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Response:**
```json
{
  "message": "Token refreshed",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "expires_in": 3600
  }
}
```

### 5. Logout

Invalidate the refresh token when logging out.

**Endpoint:** `POST /api/v1/logout`

**Request:**
```json
{
  "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**Response:**
```json
{
  "message": "Logout successful"
}
```

## User Roles

The API supports three user roles with different permissions:

### Customer
- View and manage their own orders
- Browse products
- Create orders

### Vendor
- Manage their own products
- View and manage orders for their products
- Manage inventory for their products
- Confirm orders

### Admin
- Full access to all resources
- Manage all products, orders, and inventory
- Manage users
- Update order statuses

## Role-Based Access

Some endpoints require specific roles:

| Endpoint | Roles Required |
|----------|---------------|
| `POST /orders/{id}/confirm` | admin, vendor |
| `PATCH /orders/{id}/status` | admin, vendor |
| `POST /inventory/add` | admin, vendor |
| `POST /inventory/deduct` | admin, vendor |

## Rate Limiting

The API implements rate limiting to prevent abuse:

- **Authentication endpoints** (`/register`, `/login`, `/refresh`, `/logout`): Stricter limits
- **Standard API endpoints**: Standard rate limits
- **Order creation** (`POST /orders`): Special rate limit

Rate limit headers are included in responses:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
```

## Error Responses

### 401 Unauthorized
Returned when authentication fails or token is invalid/expired.

```json
{
  "message": "Invalid credentials"
}
```

### 403 Forbidden
Returned when user doesn't have permission to access the resource.

```json
{
  "message": "Forbidden"
}
```

### 422 Validation Error
Returned when request data is invalid.

```json
{
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

## Security Best Practices

1. **Store tokens securely**: Never store tokens in localStorage. Use httpOnly cookies or secure storage mechanisms.

2. **Token expiration**: Access tokens expire after 1 hour. Implement automatic token refresh in your client.

3. **HTTPS only**: Always use HTTPS in production to prevent token interception.

4. **Logout on sensitive actions**: Implement logout functionality and clear tokens when users log out.

5. **Handle token expiration**: Implement proper error handling for expired tokens and automatic refresh logic.

## Example Implementation (JavaScript)

```javascript
class ApiClient {
  constructor(baseUrl) {
    this.baseUrl = baseUrl;
    this.accessToken = null;
    this.refreshToken = null;
  }

  async login(email, password) {
    const response = await fetch(`${this.baseUrl}/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });

    const data = await response.json();
    
    if (response.ok) {
      this.accessToken = data.data.access_token;
      this.refreshToken = data.data.refresh_token;
    }
    
    return data;
  }

  async refreshAccessToken() {
    const response = await fetch(`${this.baseUrl}/refresh`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ refresh_token: this.refreshToken })
    });

    const data = await response.json();
    
    if (response.ok) {
      this.accessToken = data.data.access_token;
      this.refreshToken = data.data.refresh_token;
    }
    
    return data;
  }

  async request(endpoint, options = {}) {
    const headers = {
      'Content-Type': 'application/json',
      ...options.headers
    };

    if (this.accessToken) {
      headers['Authorization'] = `Bearer ${this.accessToken}`;
    }

    let response = await fetch(`${this.baseUrl}${endpoint}`, {
      ...options,
      headers
    });

    // If token expired, try to refresh
    if (response.status === 401 && this.refreshToken) {
      await this.refreshAccessToken();
      
      // Retry the request with new token
      headers['Authorization'] = `Bearer ${this.accessToken}`;
      response = await fetch(`${this.baseUrl}${endpoint}`, {
        ...options,
        headers
      });
    }

    return response.json();
  }

  async getProducts() {
    return this.request('/products');
  }

  async createOrder(orderData) {
    return this.request('/orders', {
      method: 'POST',
      body: JSON.stringify(orderData)
    });
  }
}

// Usage
const api = new ApiClient('http://localhost/api/v1');
await api.login('john@example.com', 'password123');
const products = await api.getProducts();
```

## Testing Authentication

### Using cURL

```bash
# Login
curl -X POST http://localhost/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"password123"}'

# Use the token
curl -X GET http://localhost/api/v1/products \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"

# Refresh token
curl -X POST http://localhost/api/v1/refresh \
  -H "Content-Type: application/json" \
  -d '{"refresh_token":"YOUR_REFRESH_TOKEN"}'
```

### Using Postman

1. Import the provided Postman collection
2. The collection includes automatic token management
3. Login or Register will automatically save tokens to collection variables
4. All subsequent requests will use the saved access token
5. Refresh endpoint will update the tokens automatically

## Troubleshooting

### Token Expired
**Problem:** Getting 401 errors on authenticated requests

**Solution:** Use the refresh token endpoint to get a new access token

### Invalid Credentials
**Problem:** Login fails with "Invalid credentials"

**Solution:** Verify email and password are correct. Check if user exists in database.

### Forbidden Access
**Problem:** Getting 403 errors

**Solution:** Check if your user role has permission for the endpoint. Some endpoints require admin or vendor roles.

### Rate Limit Exceeded
**Problem:** Getting rate limit errors

**Solution:** Wait for the rate limit window to reset. Implement exponential backoff in your client.
