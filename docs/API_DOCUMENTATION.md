# PageTurner API Documentation

## Overview

The PageTurner API provides RESTful endpoints for managing books, orders, users, and system operations. This documentation covers rate limiting, data endpoints, and usage guidelines for developers integrating with the platform.

## Authentication

### API Key Authentication
```http
GET /api/books
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json
```

### Session Authentication
```http
GET /api/books
Cookie: laravel_session=your_session_token
Content-Type: application/json
```

## Rate Limiting

### Rate Limit Structure

The API implements tiered rate limiting based on user roles and endpoint types:

#### Anonymous Users
- **Per Second**: 2 requests
- **Per Minute**: 10 requests  
- **Per Hour**: 100 requests
- **Per Day**: 1,000 requests

#### Customer Users
- **Per Second**: 5 requests
- **Per Minute**: 100 requests
- **Per Hour**: 1,000 requests
- **Per Day**: 10,000 requests

#### Premium Users
- **Per Second**: 10 requests
- **Per Minute**: 500 requests
- **Per Hour**: 5,000 requests
- **Per Day**: 50,000 requests

#### Admin Users
- **Per Second**: 20 requests
- **Per Minute**: 1,000 requests
- **Per Hour**: 10,000 requests
- **Per Day**: 100,000 requests

### Rate Limit Headers

All API responses include rate limiting headers:

```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 2024-01-01T12:00:00Z
Retry-After: 60
```

### Rate Limit Exceeded Response

When rate limits are exceeded, the API returns a 429 status code:

```json
{
    "message": "Too many requests",
    "error": "rate_limit_exceeded",
    "limit": 100,
    "period": "per_minute",
    "retry_after": 1704067200
}
```

### Burst Protection

The API implements burst protection to prevent rapid-fire requests:
- Maximum 10 requests per second for all users
- Progressive penalties for repeated violations
- Automatic recovery after penalty period

## Data Endpoints

### Books API

#### Get All Books
```http
GET /api/books?page=1&limit=20&category=fiction&sort=title&order=asc
```

**Parameters:**
- `page` (integer, optional): Page number for pagination (default: 1)
- `limit` (integer, optional): Number of books per page (max: 100, default: 20)
- `category` (string, optional): Filter by category name
- `sort` (string, optional): Sort field (title, author, price, created_at)
- `order` (string, optional): Sort order (asc, desc)
- `search` (string, optional): Search term for title/author
- `min_price` (float, optional): Minimum price filter
- `max_price` (float, optional): Maximum price filter

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "title": "The Great Gatsby",
            "author": "F. Scott Fitzgerald",
            "isbn": "9780743273565",
            "price": 12.99,
            "description": "A classic American novel",
            "category": {
                "id": 1,
                "name": "Fiction"
            },
            "cover_image": "https://example.com/covers/1.jpg",
            "stock_quantity": 50,
            "created_at": "2024-01-01T12:00:00Z",
            "updated_at": "2024-01-01T12:00:00Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 20,
        "total": 100,
        "has_more": true
    }
}
```

#### Get Single Book
```http
GET /api/books/{id}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "title": "The Great Gatsby",
        "author": "F. Scott Fitzgerald",
        "isbn": "9780743273565",
        "price": 12.99,
        "description": "A classic American novel",
        "category": {
            "id": 1,
            "name": "Fiction"
        },
        "cover_image": "https://example.com/covers/1.jpg",
        "stock_quantity": 50,
        "created_at": "2024-01-01T12:00:00Z",
        "updated_at": "2024-01-01T12:00:00Z",
        "reviews": [
            {
                "id": 1,
                "user_id": 123,
                "rating": 5,
                "comment": "Excellent book!",
                "created_at": "2024-01-02T10:00:00Z"
            }
        ]
    }
}
```

#### Search Books
```http
GET /api/books/search?q=gatsby&category=fiction&min_price=10&max_price=20
```

**Parameters:**
- `q` (string, required): Search query
- `category` (string, optional): Category filter
- `min_price` (float, optional): Minimum price
- `max_price` (float, optional): Maximum price
- `sort` (string, optional): Sort results by relevance, price, title, author
- `limit` (integer, optional): Results limit (max: 50)

### Orders API

#### Get User Orders
```http
GET /api/orders?status=completed&date_from=2024-01-01&date_to=2024-01-31
```

**Parameters:**
- `status` (string, optional): Order status (pending, processing, shipped, delivered, cancelled)
- `date_from` (date, optional): Filter orders from date
- `date_to` (date, optional): Filter orders to date
- `page` (integer, optional): Page number
- `limit` (integer, optional): Orders per page (max: 50)

**Response:**
```json
{
    "data": [
        {
            "id": 1001,
            "user_id": 123,
            "status": "delivered",
            "total_amount": 25.98,
            "currency": "USD",
            "created_at": "2024-01-15T10:30:00Z",
            "shipped_at": "2024-01-16T14:20:00Z",
            "delivered_at": "2024-01-18T16:45:00Z",
            "items": [
                {
                    "id": 2001,
                    "book_id": 1,
                    "quantity": 2,
                    "price": 12.99,
                    "total": 25.98,
                    "book": {
                        "id": 1,
                        "title": "The Great Gatsby",
                        "author": "F. Scott Fitzgerald"
                    }
                }
            ],
            "shipping_address": {
                "street": "123 Main St",
                "city": "Anytown",
                "state": "CA",
                "zip": "12345",
                "country": "USA"
            }
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 20,
        "total": 45,
        "has_more": true
    }
}
```

#### Create Order
```http
POST /api/orders
Content-Type: application/json
```

**Request Body:**
```json
{
    "items": [
        {
            "book_id": 1,
            "quantity": 2
        },
        {
            "book_id": 2,
            "quantity": 1
        }
    ],
    "shipping_address": {
        "street": "123 Main St",
        "city": "Anytown",
        "state": "CA",
        "zip": "12345",
        "country": "USA"
    },
    "payment_method": "credit_card"
}
```

**Response:**
```json
{
    "data": {
        "id": 1002,
        "user_id": 123,
        "status": "pending",
        "total_amount": 45.97,
        "currency": "USD",
        "created_at": "2024-01-20T10:00:00Z",
        "items": [...],
        "payment_url": "https://payment.example.com/pay/1002"
    }
}
```

### Users API

#### Get User Profile
```http
GET /api/users/profile
```

**Response:**
```json
{
    "data": {
        "id": 123,
        "first_name": "John",
        "last_name": "Doe",
        "email": "john.doe@example.com",
        "role": "customer",
        "created_at": "2024-01-01T12:00:00Z",
        "last_login_at": "2024-01-20T09:15:00Z",
        "preferences": {
            "newsletter": true,
            "notifications": true,
            "theme": "light"
        }
    }
}
```

#### Update User Profile
```http
PUT /api/users/profile
Content-Type: application/json
```

**Request Body:**
```json
{
    "first_name": "John",
    "last_name": "Doe",
    "preferences": {
        "newsletter": false,
        "notifications": true,
        "theme": "dark"
    }
}
```

### Import/Export API

#### Export Books
```http
POST /api/books/export
Content-Type: application/json
```

**Request Body:**
```json
{
    "format": "xlsx",
    "filters": {
        "category": "fiction",
        "date_from": "2024-01-01",
        "date_to": "2024-01-31",
        "min_price": 10,
        "max_price": 50
    },
    "fields": ["title", "author", "isbn", "price", "category"],
    "queue": true
}
```

**Response:**
```json
{
    "data": {
        "export_id": "exp_123456",
        "status": "queued",
        "estimated_completion": "2024-01-20T10:05:00Z",
        "download_url": null
    }
}
```

#### Get Export Status
```http
GET /api/exports/{export_id}
```

**Response:**
```json
{
    "data": {
        "id": "exp_123456",
        "status": "completed",
        "progress": 100,
        "total_records": 5000,
        "processed_records": 5000,
        "file_size": 2048576,
        "download_url": "https://example.com/downloads/exp_123456.xlsx",
        "expires_at": "2024-01-27T10:00:00Z",
        "created_at": "2024-01-20T10:00:00Z",
        "completed_at": "2024-01-20T10:02:30Z"
    }
}
```

#### Import Books
```http
POST /api/books/import
Content-Type: multipart/form-data
```

**Request Body:**
```
file: [CSV/XLSX file]
format: "xlsx"
chunk_size: 1000
validate_only: false
update_existing: false
```

**Response:**
```json
{
    "data": {
        "import_id": "imp_789012",
        "status": "processing",
        "total_rows": 10000,
        "estimated_completion": "2024-01-20T10:10:00Z"
    }
}
```

#### Get Import Status
```http
GET /api/imports/{import_id}
```

**Response:**
```json
{
    "data": {
        "id": "imp_789012",
        "status": "completed",
        "progress": 100,
        "total_rows": 10000,
        "processed_rows": 9850,
        "failed_rows": 150,
        "success_rate": 98.5,
        "errors": [
            {
                "row": 125,
                "error": "Invalid ISBN format",
                "data": {
                    "title": "Invalid Book",
                    "isbn": "12345"
                }
            }
        ],
        "created_at": "2024-01-20T10:00:00Z",
        "completed_at": "2024-01-20T10:05:15Z"
    }
}
```

### Admin API

#### Get System Status
```http
GET /api/admin/status
```

**Response:**
```json
{
    "data": {
        "system_health": "healthy",
        "uptime": 99.9,
        "active_users": 1250,
        "total_orders": 45678,
        "total_books": 12345,
        "database_status": "healthy",
        "cache_status": "healthy",
        "queue_status": "healthy",
        "last_backup": "2024-01-20T02:00:00Z",
        "disk_usage": {
            "total_gb": 500,
            "used_gb": 250,
            "free_gb": 250,
            "usage_percentage": 50
        },
        "memory_usage": {
            "total_mb": 2048,
            "used_mb": 1024,
            "free_mb": 1024,
            "usage_percentage": 50
        }
    }
}
```

#### Get Audit Logs
```http
GET /api/admin/audit-logs?user_id=123&action=created&date_from=2024-01-01&limit=50
```

**Parameters:**
- `user_id` (integer, optional): Filter by user ID
- `entity_type` (string, optional): Entity type (book, order, user)
- `action` (string, optional): Action (created, updated, deleted)
- `date_from` (date, optional): Filter from date
- `date_to` (date, optional): Filter to date
- `limit` (integer, optional): Results limit (max: 1000)

**Response:**
```json
{
    "data": [
        {
            "id": 5001,
            "user_id": 123,
            "entity_type": "book",
            "entity_id": 1,
            "action": "updated",
            "old_values": {
                "price": 12.99
            },
            "new_values": {
                "price": 14.99
            },
            "ip_address": "192.168.1.100",
            "user_agent": "Mozilla/5.0...",
            "created_at": "2024-01-20T10:00:00Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 10,
        "per_page": 50,
        "total": 500,
        "has_more": true
    }
}
```

## Error Handling

### Error Response Format

All API errors follow this consistent format:

```json
{
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "The given data was invalid.",
        "details": {
            "title": ["The title field is required."],
            "price": ["The price must be a number."]
        }
    },
    "meta": {
        "timestamp": "2024-01-20T10:00:00Z",
        "request_id": "req_123456"
    }
}
```

### Common Error Codes

- `VALIDATION_ERROR` (400): Request validation failed
- `UNAUTHORIZED` (401): Authentication required or invalid
- `FORBIDDEN` (403): Insufficient permissions
- `NOT_FOUND` (404): Resource not found
- `RATE_LIMIT_EXCEEDED` (429): Rate limit exceeded
- `SERVER_ERROR` (500): Internal server error
- `SERVICE_UNAVAILABLE` (503): Service temporarily unavailable

## SDKs and Libraries

### JavaScript SDK
```javascript
import { PageTurnerAPI } from '@pageturner/javascript-sdk';

const api = new PageTurnerAPI({
    apiKey: 'your-api-key',
    baseUrl: 'https://api.pageturner.com'
});

// Get books
const books = await api.books.list({
    category: 'fiction',
    limit: 20
});

// Create order
const order = await api.orders.create({
    items: [
        { book_id: 1, quantity: 2 }
    ]
});
```

### Python SDK
```python
from pageturner_sdk import PageTurnerAPI

api = PageTurnerAPI(
    api_key='your-api-key',
    base_url='https://api.pageturner.com'
)

# Get books
books = api.books.list(
    category='fiction',
    limit=20
)

# Create order
order = api.orders.create(
    items=[
        {'book_id': 1, 'quantity': 2}
    ]
)
```

## Best Practices

### Performance Optimization
- Use pagination for large datasets
- Implement caching for frequently accessed data
- Use compression for large payloads
- Batch operations when possible

### Security
- Always use HTTPS
- Never expose API keys in client-side code
- Implement proper error handling
- Validate all input data

### Rate Limiting
- Implement exponential backoff for retries
- Cache responses when possible
- Use appropriate limits for your use case
- Monitor rate limit headers

### Error Handling
- Always check HTTP status codes
- Implement retry logic for temporary failures
- Log errors for debugging
- Provide meaningful error messages to users

## Support

### Documentation
- Full API documentation: https://docs.pageturner.com/api
- SDK documentation: https://docs.pageturner.com/sdks
- Rate limiting guide: https://docs.pageturner.com/rate-limiting

### Contact
- API support: api-support@pageturner.com
- Status page: https://status.pageturner.com
- Developer forum: https://community.pageturner.com

### Changelog
- v1.0.0: Initial API release
- v1.1.0: Added bulk operations
- v1.2.0: Enhanced rate limiting
- v1.3.0: Added export/import endpoints

This API documentation provides comprehensive coverage of all available endpoints, rate limiting policies, and integration guidelines for developers working with the PageTurner platform.
