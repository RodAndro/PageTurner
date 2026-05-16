# API Rate Limiting & Security - Configuration Template

Add these values to your `.env` file:

```env
################################################################################
# API RATE LIMITING CONFIGURATION
################################################################################

# Enable/disable rate limiting
API_RATE_LIMIT_ENABLED=true

# Rate limiting driver: cache or redis
API_RATE_LIMIT_DRIVER=cache

# Cache driver for rate limiting (file, redis, database, array)
CACHE_DRIVER=redis

# Redis configuration (if using Redis)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0

################################################################################
# API RESPONSE TRANSFORMATION
################################################################################

# Enable automatic snake_case to camelCase transformation
API_TRANSFORM_RESPONSE=true

# Allow clients to disable transformation with ?_no_transform=true
API_ALLOW_DISABLE_TRANSFORM=true

################################################################################
# API FIELD FILTERING
################################################################################

# Enable field filtering (?fields=id,title,price)
API_FIELD_FILTERING=true

# Query parameter name for field filtering
API_FILTER_FIELDS_PARAMETER=fields

################################################################################
# PAGINATION CONFIGURATION
################################################################################

# Pagination driver: cursor or offset
API_PAGINATION_DRIVER=cursor

# Default items per page
API_PAGINATION_DEFAULT_PER_PAGE=20

# Maximum items per page
API_PAGINATION_MAX_PER_PAGE=100

# Cursor parameter name
API_PAGINATION_CURSOR_PARAMETER=cursor

# Per-page parameter name
API_PAGINATION_PER_PAGE_PARAMETER=per_page

# Order by parameter name
API_PAGINATION_ORDER_BY_PARAMETER=order_by

# Direction parameter name
API_PAGINATION_DIRECTION_PARAMETER=direction

################################################################################
# ETAG CACHING
################################################################################

# Enable ETag support for conditional caching
API_ETAG_ENABLED=true

# ETag algorithm: sha256 or md5
API_ETAG_ALGORITHM=sha256

# Cache-Control header value
API_ETAG_CACHE_CONTROL="public, max-age=3600"

################################################################################
# API VERSIONING
################################################################################

# Enable API versioning
API_VERSIONING_ENABLED=true

# Versioning driver: uri or header
API_VERSIONING_DRIVER=uri

# URI prefix for versioning (/api/v)
API_VERSIONING_URI_PREFIX="/api/v"

# Header for versioning (if using header driver)
API_VERSIONING_HEADER="X-API-Version"

# Default API version
API_DEFAULT_VERSION=1

# Current API version
API_VERSION="1.0.0"

################################################################################
# CORS CONFIGURATION
################################################################################

# Enable CORS headers
API_CORS_ENABLED=true

# Allowed origins (comma-separated)
API_CORS_ALLOWED_ORIGINS="http://localhost:3000,http://localhost:5173,http://localhost:8000"

# Allowed HTTP methods
API_CORS_ALLOWED_METHODS="GET,POST,PUT,PATCH,DELETE,OPTIONS"

# Allowed headers
API_CORS_ALLOWED_HEADERS="Content-Type,Authorization,X-API-Version,If-None-Match"

# Exposed headers (accessible to browser)
API_CORS_EXPOSED_HEADERS="X-RateLimit-Limit,X-RateLimit-Remaining,X-RateLimit-Reset,ETag"

# Max age for CORS preflight (seconds)
API_CORS_MAX_AGE=3600

################################################################################
# API AUTHENTICATION
################################################################################

# Authentication method: sanctum, jwt, basic, oauth2
API_AUTH_METHOD=sanctum

# Sanctum configuration
SANCTUM_STATEFUL_DOMAINS="localhost,localhost:3000,localhost:5173"
SANCTUM_TOKEN_EXPIRATION=525600  # 1 year in minutes

################################################################################
# RATE LIMIT WHITELIST
################################################################################

# IPs to whitelist from rate limiting (comma-separated)
API_RATE_LIMIT_WHITELIST_IPS="127.0.0.1,::1"

# User IDs to whitelist from rate limiting (comma-separated)
# Example: 1,2,3 (admin user IDs)
API_RATE_LIMIT_WHITELIST_USERS=""

################################################################################
# API LOGGING
################################################################################

# Enable API request/response logging
API_LOGGING_ENABLED=true

# Log all requests
API_LOG_REQUESTS=false

# Log all responses
API_LOG_RESPONSES=false

# Log slow queries
API_LOG_SLOW_QUERIES=true

# Slow query threshold (milliseconds)
API_LOG_SLOW_QUERY_THRESHOLD=500

################################################################################
# API DOCUMENTATION
################################################################################

# Enable API documentation
API_DOCUMENTATION_ENABLED=true

# API documentation URL
API_DOCS_URL="/api/docs"

# API documentation title
API_DOCS_TITLE="PageTurner API"

# API documentation description
API_DOCS_DESCRIPTION="RESTful API for the PageTurner online bookstore"

################################################################################
# RATE LIMIT TIER CONFIGURATIONS
# (These can be customized in config/api.php)
################################################################################

# Public tier: 30 requests/minute (unauthenticated visitors)
API_RATE_LIMIT_PUBLIC=30

# Standard tier: 60 requests/minute (authenticated users)
API_RATE_LIMIT_STANDARD=60

# Premium tier: 300 requests/minute (premium subscribers)
API_RATE_LIMIT_PREMIUM=300

# Admin tier: 1000 requests/minute (administrators)
API_RATE_LIMIT_ADMIN=1000

# Auth tier: 10 requests/minute (login, registration, etc.)
API_RATE_LIMIT_AUTH=10

################################################################################
# ADDITIONAL API SETTINGS
################################################################################

# API base URL
API_URL="http://localhost:8000"

# API prefix
API_PREFIX="api"

# Timezone for API responses
API_TIMEZONE="UTC"

# Default response format
API_DEFAULT_FORMAT="json"

# Pretty-print JSON responses
API_PRETTY_PRINT_JSON=true

# Include timestamps in all responses
API_INCLUDE_TIMESTAMPS=true

# Include API version in responses
API_INCLUDE_VERSION=true

################################################################################
# MONITORING & ALERTS
################################################################################

# Alert on high rate limit usage (percentage)
API_ALERT_RATE_LIMIT_THRESHOLD=80

# Alert on slow query
API_ALERT_SLOW_QUERY=true

# Alert email
API_ALERT_EMAIL="admin@example.com"

################################################################################
# END OF API CONFIGURATION
################################################################################
```

## Usage

1. Copy relevant variables from this file
2. Add to your `.env` file
3. Run `php artisan config:cache` to cache configuration
4. Test with `php artisan tinker` or API endpoints

## Environment-Specific Configuration

### Development `.env`

```env
API_RATE_LIMIT_ENABLED=true
API_TRANSFORM_RESPONSE=true
API_FIELD_FILTERING=true
API_ETAG_ENABLED=true
API_LOGGING_ENABLED=true
API_LOG_REQUESTS=true
API_LOG_RESPONSES=false
CACHE_DRIVER=array
```

### Production `.env`

```env
API_RATE_LIMIT_ENABLED=true
API_TRANSFORM_RESPONSE=true
API_FIELD_FILTERING=true
API_ETAG_ENABLED=true
API_LOGGING_ENABLED=true
API_LOG_REQUESTS=false
API_LOG_RESPONSES=false
CACHE_DRIVER=redis
```

### Testing `.env`

```env
API_RATE_LIMIT_ENABLED=false  # Disable for testing
API_LOGGING_ENABLED=false
CACHE_DRIVER=array
```

## Verification

After adding configuration, verify:

```bash
# Check configuration is loaded
php artisan config:show api

# Verify services are working
php artisan tinker
>>> app('App\Services\RateLimiterService')->getTiers()
>>> app('cache')->get('test') // Verify cache works
```
