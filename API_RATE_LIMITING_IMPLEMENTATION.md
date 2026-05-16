# API Rate Limiting & Security - Implementation Guide

## Quick Start Checklist

- [ ] Register middleware in HTTP Kernel
- [ ] Create API routes with rate limiting middleware
- [ ] Configure environment variables
- [ ] Test rate limiting behavior
- [ ] Test field filtering
- [ ] Test cursor pagination
- [ ] Test ETag caching
- [ ] Monitor and adjust limits

---

## Files Created

### Services
| File | Purpose |
|------|---------|
| `app/Services/RateLimiterService.php` | Core rate limiting logic |
| `app/Services/CursorPaginationService.php` | Cursor-based pagination |

### Middleware
| File | Purpose |
|------|---------|
| `app/Http/Middleware/RateLimitMiddleware.php` | Rate limit enforcement |
| `app/Http/Middleware/TransformResponse.php` | Snake case → camelCase |
| `app/Http/Middleware/FilterFields.php` | Field filtering (?fields=) |
| `app/Http/Middleware/ETagMiddleware.php` | ETag caching support |

### Controllers & Routes
| File | Purpose |
|------|---------|
| `app/Http/Controllers/Api/ApiController.php` | API endpoints |
| `routes/api.php` | API route definitions |
| `app/Http/Kernel.php` | Middleware registration |

### Configuration
| File | Purpose |
|------|---------|
| `config/api.php` | API configuration |
| `.env` | Environment variables |

### Traits
| File | Purpose |
|------|---------|
| `app/Http/Traits/ApiPaginationTrait.php` | Pagination helpers |

---

## Configuration Steps

### 1. Environment Variables

Add to `.env`:

```env
# API Rate Limiting
API_RATE_LIMIT_ENABLED=true
API_RATE_LIMIT_DRIVER=cache
CACHE_DRIVER=redis  # Use Redis for better performance

# Response Transformation
API_TRANSFORM_RESPONSE=true

# Field Filtering
API_FIELD_FILTERING=true

# ETag Support
API_ETAG_ENABLED=true

# Pagination
API_PAGINATION_DRIVER=cursor

# CORS
API_CORS_ENABLED=true
API_CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173

# Rate Limit Whitelist (optional)
API_RATE_LIMIT_WHITELIST_IPS=127.0.0.1,::1
API_RATE_LIMIT_WHITELIST_USERS=
```

### 2. Configure Cache

Ensure Redis is configured in `.env`:

```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

If Redis not available, use cache driver:

```env
CACHE_DRIVER=file  # or database
```

### 3. Register Middleware

The middleware are registered in `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    'rate.limit' => \App\Http\Middleware\RateLimitMiddleware::class,
    'transform.response' => \App\Http\Middleware\TransformResponse::class,
    'filter.fields' => \App\Http\Middleware\FilterFields::class,
    'etag' => \App\Http\Middleware\ETagMiddleware::class,
];
```

---

## Usage Examples

### In Controllers

```php
use App\Http\Traits\ApiPaginationTrait;

class BookController extends Controller
{
    use ApiPaginationTrait;

    public function index()
    {
        // Use cursor pagination automatically
        return $this->apiPaginatedResponse(Book::query(), 20);
    }

    public function show(Book $book)
    {
        return $this->apiResponse($book->toArray());
    }

    public function error()
    {
        return $this->apiErrorResponse('Book not found', 404);
    }
}
```

### Applying Middleware to Routes

```php
// routes/api.php

Route::middleware(['rate.limit', 'etag', 'transform.response', 'filter.fields'])
    ->get('/books', [ApiController::class, 'listBooks']);
```

---

## Testing

### Manual Testing with cURL

```bash
# Test rate limiting
for i in {1..35}; do
  curl -i http://localhost:8000/api/v1/books
done

# Test field filtering
curl "http://localhost:8000/api/v1/books?fields=id,title,price"

# Test cursor pagination
curl "http://localhost:8000/api/v1/books?per_page=10"

# Test ETag caching
curl -i "http://localhost:8000/api/v1/books/1"
# Note the ETag header, then:
curl -i -H 'If-None-Match: "value-from-etag"' "http://localhost:8000/api/v1/books/1"
```

### Testing with Postman

1. **Create collection**
2. **Add authorization** (Bearer token if needed)
3. **Set up pre-request scripts**:

```javascript
// Store cursor for pagination
pm.test("Check pagination", function() {
    let data = pm.response.json();
    if (data.pagination.next_cursor) {
        pm.globals.set("next_cursor", data.pagination.next_cursor);
    }
});
```

4. **Test rate limiting**: Rapid fire requests and monitor headers

### Automated Testing

```php
// tests/Feature/ApiRateLimitingTest.php

class ApiRateLimitingTest extends TestCase
{
    public function test_rate_limit_returns_429()
    {
        // Simulate 31 requests as public user
        for ($i = 0; $i < 30; $i++) {
            $this->getJson('/api/v1/books');
        }

        // Next request should be rate limited
        $response = $this->getJson('/api/v1/books');
        $response->assertStatus(429);
        $response->assertJsonStructure([
            'error', 'message', 'retry_after', 'reset_at'
        ]);
    }

    public function test_field_filtering()
    {
        $response = $this->getJson('/api/v1/books?fields=id,title');

        $data = $response->json('data.0');
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayNotHasKey('description', $data);
    }

    public function test_cursor_pagination()
    {
        $response = $this->getJson('/api/v1/books?per_page=10');

        $response->assertJsonStructure([
            'pagination' => ['per_page', 'total', 'has_more', 'next_cursor']
        ]);
    }

    public function test_etag_caching()
    {
        $response1 = $this->getJson('/api/v1/books/1');
        $etag = $response1->header('ETag');

        $response2 = $this->getJson(
            '/api/v1/books/1',
            ['If-None-Match' => $etag]
        );
        
        $response2->assertStatus(304);
    }
}
```

---

## Monitoring & Metrics

### Check Rate Limit Status

```bash
# Get rate limit status for current user/IP
curl -H "Authorization: Bearer token" \
  http://localhost:8000/api/v1/rate-limit-status
```

Response:

```json
{
  "success": true,
  "data": {
    "tier": "standard",
    "limit": 60,
    "used": 15,
    "remaining": 45,
    "percentage_used": 25,
    "reset_at": "2024-01-15T10:30:45Z"
  }
}
```

### Health Check

```bash
curl http://localhost:8000/api/v1/health
```

---

## Performance Tuning

### 1. Cache Configuration

For best performance, use Redis:

```bash
# Install Redis
# macOS
brew install redis

# Ubuntu/Debian
sudo apt-get install redis-server

# Start Redis
redis-server
```

### 2. Database Indexes

Add indexes for frequently queried fields:

```sql
CREATE INDEX books_created_at_idx ON books(created_at);
CREATE INDEX books_category_id_idx ON books(category_id);
CREATE INDEX books_price_idx ON books(price);
```

### 3. Pagination Performance

```php
// Use select() to limit columns
Book::select('id', 'title', 'price')
    ->orderBy('id')
    ->paginate();
```

### 4. Response Caching

For read-heavy endpoints, cache entire responses:

```php
Route::middleware(['cache:3600', 'rate.limit'])
    ->get('/books', function() {
        return Book::all();
    });
```

---

## Debugging

### Enable API Logging

```env
API_LOGGING_ENABLED=true
API_LOG_REQUESTS=true
API_LOG_RESPONSES=true
API_LOG_SLOW_QUERIES=true
```

### Check Laravel Logs

```bash
tail -f storage/logs/laravel.log
```

### Common Issues

#### Rate Limit Always Triggered
- Check cache is working: `php artisan cache:clear`
- Verify Redis connection
- Check whitelist configuration

#### Field Filtering Not Working
- Verify middleware order in routes
- Check parameter name: `?fields=id,title`
- Verify field names exist

#### ETag Not Returning 304
- Ensure exact ETag match in `If-None-Match`
- Check response hasn't changed
- Verify ETag middleware is enabled

---

## Rate Limit Tier Assignment Logic

```
Request comes in
    ↓
Is it an auth endpoint? → Use 'auth' tier (10 req/min)
    ↓ NO
Is user authenticated?
    ↓ YES
Is user admin? → Use 'admin' tier (1000 req/min)
    ↓ NO
Is user premium? → Use 'premium' tier (300 req/min)
    ↓ NO
Use 'standard' tier (60 req/min)
    ↓ NO (not authenticated)
Use 'public' tier (30 req/min)
```

---

## Updating Rate Limits

To adjust limits, edit `config/api.php`:

```php
'tiers' => [
    'public' => ['requests_per_minute' => 50],  // Changed from 30
    'standard' => ['requests_per_minute' => 100], // Changed from 60
    // ... etc
]
```

Or via environment variables:

```env
# Won't work directly, modify config/api.php instead
```

---

## API Versioning Strategy

Current implementation uses URI versioning:

```
/api/v1/books     ← Version 1
/api/v2/books     ← Version 2 (future)
```

To add new version:

1. Create new routes: `routes/api-v2.php`
2. Include in main routes file
3. Route to new controllers

---

## Security Considerations

1. **Never expose rate limit calculations** - prevents circumvention
2. **Use Redis** for distributed rate limiting (multiple servers)
3. **Whitelist trusted IPs** if needed
4. **Implement authentication** for premium tier
5. **Log suspicious activity** - many 429 responses
6. **Implement WAF** (Web Application Firewall) if available

---

## Next Steps

1. [ ] Test all endpoints
2. [ ] Configure rate limits for your use case
3. [ ] Set up monitoring/alerting
4. [ ] Document custom API endpoints
5. [ ] Implement API key system for third parties
6. [ ] Set up API gateway if high-traffic expected
7. [ ] Consider API analytics/usage tracking

---

## API Endpoints Reference

| Method | Endpoint | Rate Limit | Auth |
|--------|----------|-----------|------|
| GET | `/api/v1/books` | public (30) | No |
| GET | `/api/v1/books/{id}` | public (30) | No |
| GET | `/api/v1/books/{id}/reviews` | public (30) | No |
| GET | `/api/v1/categories` | public (30) | No |
| GET | `/api/v1/categories/{id}/books` | public (30) | No |
| GET | `/api/v1/rate-limit-status` | user tier | Yes |
| GET | `/api/v1/health` | none | No |

---

## Support & Resources

- Full documentation: See `API_RATE_LIMITING.md`
- Config reference: See `config/api.php`
- Code examples: See `app/Http/Controllers/Api/ApiController.php`
