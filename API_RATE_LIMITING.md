# 🔐 API Rate Limiting & Security Guide

## Overview

This guide explains the comprehensive API rate limiting and security system for the PageTurner application. The system provides:

- **Tiered rate limiting** based on user role and subscription
- **Per-second burst protection** for DDoS prevention
- **Response transformation** (snake_case to camelCase)
- **Field filtering** for optimized responses
- **Cursor-based pagination** for performance
- **ETag support** for client-side caching

---

## Table of Contents

1. [Rate Limiting](#rate-limiting)
2. [Implementation Details](#implementation-details)
3. [API Usage](#api-usage)
4. [Response Transformation](#response-transformation)
5. [Field Filtering](#field-filtering)
6. [Cursor Pagination](#cursor-pagination)
7. [ETag Caching](#etag-caching)
8. [Configuration](#configuration)
9. [Examples](#examples)
10. [Troubleshooting](#troubleshooting)

---

## Rate Limiting

### Tiered System

The system implements a 5-tier rate limiting strategy:

| Tier | Requests/Minute | Scope | Users |
|------|-----------------|-------|-------|
| **public** | 30 | General browsing, book search | Visitors (not authenticated) |
| **standard** | 60 | Authenticated API access | Regular customers |
| **premium** | 300 | High-volume API access | Premium/VIP customers |
| **admin** | 1000 | Administrative operations | Administrators |
| **auth** | 10 | Login, registration, password reset | All users (strict) |

### How It Works

1. **Tier Determination**
   - System identifies request type (authentication, admin, etc.)
   - Checks user's authentication status and role
   - Determines subscription tier (premium vs standard)
   - Assigns appropriate rate limit tier

2. **Identifier**
   - Authenticated users: Limited by user ID
   - Guests: Limited by IP address
   - Ensures fair resource allocation

3. **Burst Protection**
   - Per-second granularity (1/60th of minute limit)
   - Prevents sudden traffic spikes
   - Smooths traffic load

---

## Implementation Details

### Architecture

```
Request
   ↓
RateLimitMiddleware (checks tier & counts requests)
   ↓
Cache/Redis (stores request counts)
   ↓
Response + Headers (includes rate limit info)
```

### Components

#### 1. **RateLimiterService**
- Manages rate limit tiers and logic
- Stores counts in Cache or Redis
- Provides rate limit status

#### 2. **RateLimitMiddleware**
- Intercepts all API requests
- Determines tier for current request
- Checks against rate limits
- Returns 429 if limit exceeded
- Adds response headers

#### 3. **Request Identification**
- For authenticated users: `user_id`
- For guests: `client_ip`
- Ensures per-user/per-IP limits

---

## API Usage

### Basic Request

```bash
# Get books (public tier - 30 req/min)
GET /api/v1/books
```

### Authenticated Request

```bash
# Premium user gets 300 requests/minute
GET /api/v1/books \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Response Headers

```
HTTP/1.1 200 OK
X-RateLimit-Limit: 30
X-RateLimit-Remaining: 29
X-RateLimit-Reset: 1705267800
ETag: "a3f5d8e1c"
Cache-Control: public, max-age=3600
```

### Rate Limit Exceeded

```bash
HTTP/1.1 429 Too Many Requests

{
  "error": "Too Many Requests",
  "message": "Rate limit exceeded",
  "retry_after": 45,
  "reset_at": "2024-01-15T10:30:45.000000Z",
  "tier": "public"
}
```

---

## Response Transformation

### Snake Case to Camel Case

The API automatically transforms database field names to camelCase for better JavaScript compatibility.

#### Database (snake_case):
```json
{
  "id": 1,
  "book_title": "Laravel Guide",
  "category_id": 5,
  "created_at": "2024-01-15T10:00:00Z",
  "is_available": true
}
```

#### API Response (camelCase):
```json
{
  "id": 1,
  "bookTitle": "Laravel Guide",
  "categoryId": 5,
  "createdAt": "2024-01-15T10:00:00Z",
  "isAvailable": true
}
```

### Disable Transformation

```bash
# Get response in original snake_case format
GET /api/v1/books?_no_transform=true
```

---

## Field Filtering

### Overview

Clients can specify which fields to return, reducing response payload size.

### Usage

```bash
# Get only id, title, and price
GET /api/v1/books?fields=id,title,price
```

### Response

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Laravel Guide",
      "price": 29.99
    },
    {
      "id": 2,
      "title": "React Essentials",
      "price": 34.99
    }
  ]
}
```

### Nested Fields

```bash
# Include nested fields
GET /api/v1/books?fields=id,title,category.name
```

### Benefits

- **Reduced bandwidth**: Only send needed data
- **Faster responses**: Smaller JSON payloads
- **Client optimization**: Clients get exactly what they need
- **Better mobile experience**: Lower data usage

---

## Cursor Pagination

### Overview

Cursor-based pagination is more efficient than offset-based pagination for large datasets.

### How It Works

1. Server returns items + next cursor
2. Client sends cursor to get next page
3. No offset calculation needed
4. Efficient database queries

### Usage

```bash
# Get first 20 books
GET /api/v1/books?per_page=20

# Response includes next_cursor
{
  "data": [...],
  "pagination": {
    "per_page": 20,
    "total": 5000,
    "has_more": true,
    "next_cursor": "eyJ2YWx1ZSI6MjB9"
  }
}

# Get next page using cursor
GET /api/v1/books?per_page=20&cursor=eyJ2YWx1ZSI6MjB9
```

### Parameters

| Parameter | Default | Max | Description |
|-----------|---------|-----|-------------|
| `per_page` | 20 | 100 | Items per page |
| `cursor` | null | - | Pagination cursor |
| `order_by` | id | - | Field to order by |
| `direction` | asc | - | asc or desc |

### Benefits

- **Consistent results**: No issues with data changes during pagination
- **Efficient**: Uses database indexes effectively
- **Scalable**: Works well with millions of records
- **No counting**: Doesn't require expensive COUNT queries

### Example Flow

```bash
# Page 1
GET /api/v1/books?per_page=20
→ Returns items 1-20 + next_cursor=A

# Page 2
GET /api/v1/books?per_page=20&cursor=A
→ Returns items 21-40 + next_cursor=B

# Page 3
GET /api/v1/books?per_page=20&cursor=B
→ Returns items 41-60 + next_cursor=C
```

---

## ETag Caching

### Overview

ETags enable efficient client-side caching and reduce bandwidth.

### How It Works

1. Server computes SHA-256 hash of response body
2. Includes `ETag` header with hash
3. Client can send `If-None-Match` header with saved ETag
4. Server returns 304 if unchanged, avoiding resend

### Usage

#### First Request
```bash
GET /api/v1/books/1

# Response
HTTP/1.1 200 OK
ETag: "abc123def456"
Cache-Control: public, max-age=3600

{
  "id": 1,
  "title": "Laravel Guide",
  ...
}
```

#### Subsequent Request (Cache Valid)
```bash
GET /api/v1/books/1
If-None-Match: "abc123def456"

# Response
HTTP/1.1 304 Not Modified
ETag: "abc123def456"
```

### Benefits

- **Bandwidth savings**: No response body for unchanged resources
- **Faster**: 304 responses are smaller
- **User experience**: Reduced data usage
- **Caching**: Automatic browser caching integration

### Cache Headers

```
ETag: "sha256_hash_of_content"
Cache-Control: public, max-age=3600
```

- **public**: Cacheable by any HTTP cache
- **max-age=3600**: Cache for 1 hour

---

## Configuration

### Environment Variables

```env
# API Rate Limiting
API_RATE_LIMIT_ENABLED=true
API_RATE_LIMIT_DRIVER=cache  # cache or redis

# Response Transformation
API_TRANSFORM_RESPONSE=true

# Field Filtering
API_FIELD_FILTERING=true

# ETag Support
API_ETAG_ENABLED=true

# Pagination
API_PAGINATION_DRIVER=cursor
API_PAGINATION_PER_PAGE_MAX=100

# Rate Limit Whitelist (comma-separated)
API_RATE_LIMIT_WHITELIST_IPS=127.0.0.1,::1
API_RATE_LIMIT_WHITELIST_USERS=1,2,3

# CORS Configuration
API_CORS_ENABLED=true
API_CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173
```

### Config File

See `config/api.php` for all available options.

---

## Examples

### JavaScript (Fetch API)

```javascript
// Function to handle cursor pagination
async function fetchBooks(cursor = null) {
  const params = new URLSearchParams({
    per_page: 20,
    fields: 'id,title,price,categoryId',
    order_by: 'created_at',
    direction: 'desc'
  });

  if (cursor) {
    params.append('cursor', cursor);
  }

  const response = await fetch(`/api/v1/books?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'If-None-Match': lastETag // For caching
    }
  });

  if (response.status === 304) {
    console.log('Using cached data');
    return lastData;
  }

  const data = await response.json();
  const nextCursor = data.pagination.next_cursor;
  
  // Store ETag for next request
  lastETag = response.headers.get('ETag');
  lastData = data;

  // Check rate limit status
  console.log('Rate limit:', {
    limit: response.headers.get('X-RateLimit-Limit'),
    remaining: response.headers.get('X-RateLimit-Remaining')
  });

  return data;
}
```

### Python (Requests)

```python
import requests

# Rate limit aware pagination
def fetch_books(cursor=None, etag=None):
    headers = {}
    
    if etag:
        headers['If-None-Match'] = etag

    params = {
        'per_page': 20,
        'fields': 'id,title,price,categoryId',
        'order_by': 'created_at',
        'direction': 'desc'
    }
    
    if cursor:
        params['cursor'] = cursor

    response = requests.get(
        'https://api.example.com/api/v1/books',
        headers=headers,
        params=params
    )

    # Handle 304 Not Modified
    if response.status_code == 304:
        print("Data unchanged, using cache")
        return None

    # Check rate limit
    print(f"Rate limit: {response.headers.get('X-RateLimit-Remaining')} remaining")

    data = response.json()
    next_cursor = data['pagination'].get('next_cursor')

    return data, next_cursor
```

### cURL

```bash
# Basic request with field filtering
curl -G "https://api.example.com/api/v1/books" \
  -d "per_page=20" \
  -d "fields=id,title,price" \
  -H "Authorization: Bearer token_here"

# With cursor pagination
curl -G "https://api.example.com/api/v1/books" \
  -d "cursor=eyJ2YWx1ZSI6MjB9" \
  -H "Authorization: Bearer token_here"

# With ETag caching
curl -G "https://api.example.com/api/v1/books/1" \
  -H 'If-None-Match: "abc123"' \
  -H "Authorization: Bearer token_here"
```

---

## Troubleshooting

### Rate Limit Exceeded

**Problem**: Getting 429 Too Many Requests

**Solution**:
1. Check `X-RateLimit-Remaining` header before making requests
2. Wait for `Retry-After` seconds
3. Upgrade user tier (standard → premium)
4. Consider batching requests

### Slow Performance

**Problem**: Queries are slow

**Solution**:
1. Use field filtering: `?fields=id,title`
2. Reduce per_page: `?per_page=10`
3. Use cursor pagination instead of offset
4. Add database indexes on frequently sorted fields

### ETag Not Working

**Problem**: 304 responses not received

**Solution**:
1. Verify ETag header is in response
2. Send exact same `If-None-Match` value
3. Check Cache-Control directives
4. Clear browser cache if testing locally

### Field Filtering Not Applied

**Problem**: All fields returned despite ?fields parameter

**Solution**:
1. Verify `API_FIELD_FILTERING` is enabled
2. Check field names are spelled correctly
3. Use camelCase names (after transformation)
4. Verify middleware is loaded

### Cursor Pagination Returns Empty

**Problem**: No results with cursor parameter

**Solution**:
1. Verify cursor format is valid base64
2. Check if direction parameter is correct
3. Verify order_by field exists in database
4. Try without cursor first

---

## Response Headers Reference

### Rate Limiting Headers

```
X-RateLimit-Limit: 30              # Maximum requests
X-RateLimit-Remaining: 25          # Requests remaining
X-RateLimit-Reset: 1705267800      # Unix timestamp of reset
Retry-After: 45                     # Seconds to wait (on 429 only)
```

### Caching Headers

```
ETag: "abc123def456"                # Response fingerprint
Cache-Control: public, max-age=3600 # Cache policy
Last-Modified: Mon, 15 Jan 2024 10:00:00 GMT  # Last change time
```

### CORS Headers

```
Access-Control-Allow-Origin: http://localhost:3000
Access-Control-Allow-Methods: GET, POST, PUT, DELETE
Access-Control-Allow-Headers: Content-Type, Authorization
Access-Control-Expose-Headers: X-RateLimit-Limit, ETag
```

---

## Best Practices

1. **Always check rate limit headers** before making next request
2. **Implement exponential backoff** for retries
3. **Use field filtering** to reduce bandwidth
4. **Cache 304 responses** locally
5. **Monitor rate limits** and upgrade tier if needed
6. **Batch operations** when possible
7. **Use cursor pagination** for large datasets
8. **Cache aggressively** with ETags

---

## Support

For API issues or questions:
- Check rate limit status: `GET /api/v1/rate-limit-status`
- Health check: `GET /api/v1/health`
- API documentation: `/api/docs`

