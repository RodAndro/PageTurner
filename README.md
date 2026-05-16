# README

## PageTurner Bookstore

PageTurner is a Laravel-based online bookstore system built for catalog browsing, book management, orders, imports/exports, audit logging, backup maintenance, API rate limiting, large dataset performance testing, and AI-powered book recommendations.

The system is designed as a full web software tools project with customer-facing features, admin tools, background jobs, validation commands, and documentation for laboratory activities.

## Main Features

### Customer Features

- Browse available books
- View book details, descriptions, prices, ratings, and categories
- Add books to cart
- Checkout and place orders
- View purchased books
- Manage user profile and authentication
- Use AI-powered book recommendations/chatbot

### Admin Features

- Manage books
- Manage categories
- View and manage customer orders
- Import books and users from files
- Export books, users, and orders
- Monitor import/export status
- View audit logs
- Access backup maintenance dashboard
- Review system reports and scheduled task outputs

### Import and Export

The system supports spreadsheet/CSV-based import and export workflows using Laravel Excel patterns and queued jobs.

Important files:

- `app/Imports/BooksImport.php`
- `app/Imports/UsersImport.php`
- `app/Exports/BooksExport.php`
- `app/Exports/UsersExport.php`
- `app/Exports/OrdersExport.php`
- `app/Jobs/ProcessBookImport.php`
- `app/Jobs/ProcessUserImport.php`
- `app/Jobs/ProcessExport.php`
- `config/excel.php`

### API and Rate Limiting

PageTurner includes API endpoints with response transformation, field filtering, ETag support, and tiered rate limiting.

Important files:

- `routes/api.php`
- `app/Http/Controllers/Api/ApiController.php`
- `app/Http/Middleware/TransformResponse.php`
- `app/Http/Middleware/FilterFields.php`
- `app/Http/Middleware/ETagMiddleware.php`
- `app/Http/Middleware/TieredRateLimitMiddleware.php`
- `app/Services/RateLimiterService.php`
- `config/rate-limits.php`

### Backup and Maintenance

The system includes backup configuration, backup monitoring, cleanup commands, and maintenance dashboards.

Important files:

- `config/backup.php`
- `routes/backup-maintenance.php`
- `app/Http/Controllers/BackupMaintenanceController.php`
- `app/Console/Commands/CleanupBackupsCommand.php`
- `app/Console/Commands/RunScheduledTasks.php`
- `database/seeders/BackupMonitoringSeeder.php`

### Audit Logging

Audit logging tracks important model and user activity for accountability and security.

Important files:

- `config/audit.php`
- `app/Models/AuditLog.php`
- `app/Observers/AuditObserver.php`
- `app/Services/AuditLogService.php`
- `app/Services/AuditTamperDetectionService.php`
- `app/Console/Commands/ArchiveAuditLogsCommand.php`

### Large Catalog Performance

PageTurner includes tools for validating million-record catalog performance, query speed, cache behavior, load handling, and large exports.

Important files:

- `database/factories/BookFactory.php`
- `database/seeders/MassBookSeeder.php`
- `app/Console/Commands/BenchmarkBookQueries.php`
- `app/Console/Commands/LabActivity7Validation.php`
- `app/Repositories/BookRepository.php`
- `app/Services/BookCacheService.php`
- `app/Jobs/LargeBookExport.php`

### AI Integration

The AI feature helps customers discover books through natural language recommendations. The project uses Gemini API and Ollama, with fallback behavior when providers are unavailable.

Important files:

- `config/ai.php`
- `app/Services/AIServiceManager.php`
- `app/Services/GeminiAIService.php`
- `app/Services/BookRecommendationService.php`
- `app/Jobs/ProcessAITask.php`
- `app/Models/AIUsageLog.php`
- `app/Models/AIConversation.php`
- `resources/views/components/api-chatbot.blade.php`
- `resources/views/components/floating-chatbot.blade.php`
- `resources/views/components/ai-recommendations.blade.php`

## Technology Stack

- Laravel 12
- PHP 8.2+
- Blade templates
- Laravel Breeze authentication
- Laravel queues
- Laravel Excel
- Spatie Laravel Backup
- Laravel auditing package
- SQLite/MySQL-compatible database layer
- Redis/cache-ready configuration
- Gemini API
- Ollama local AI models

## Installation

Install dependencies:

```bash
composer install
npm install
```

Create environment file:

```bash
copy .env.example .env
php artisan key:generate
```

Run migrations and seeders:

```bash
php artisan migrate
php artisan db:seed
```

Build frontend assets:

```bash
npm run build
```

Start the application:

```bash
php artisan serve
```

Optional development mode:

```bash
npm run dev
```

## Environment Configuration

Common `.env` settings:

```env
APP_NAME=PageTurner
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=sqlite

CACHE_STORE=database
QUEUE_CONNECTION=database

GEMINI_API_KEY=your_gemini_api_key_here
AI_DEFAULT_PROVIDER=gemini
AI_FALLBACK_ENABLED=true
AI_FALLBACK_CHAIN=gemini,ollama

OLLAMA_ENABLED=true
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_MODEL=llama3.2
```

For Ollama:

```bash
ollama pull llama3.2
ollama run llama3.2
```

## Useful Commands

Run all tests:

```bash
php artisan test
```

Run import/export workflow checks:

```bash
php test_full_export_workflow.php
php test_export.php
php test_orders_export.php
```

Show scheduled tasks:

```bash
php artisan schedule:list
```

Run queue worker:

```bash
php artisan queue:work
```

Run Activity 7 validation:

```bash
php -d memory_limit=512M artisan lab7:validate --repair-isbns --seed-records=1000000 --iterations=100 --search-iterations=50 --export-limit=50000
```

Run query benchmark:

```bash
php artisan benchmark:queries 100
```

Run AI tests:

```bash
php artisan test tests/Feature/AiRecommendationsTest.php
php artisan test tests/Feature/FloatingChatbotTest.php
```

## Documentation

General documentation:

- `docs/TECHNICAL_DOCUMENTATION.md`
- `docs/API_DOCUMENTATION.md`
- `docs/USER_GUIDE.md`
- `COMPLETE_TESTING_GUIDE.md`
- `QUICK_TEST_REFERENCE.md`

Activity documentation:

- `ACTIVITY_6_README.md`
- `ACTIVITY_7_README.md`
- `ACTIVITY_8_README.md`

Feature documentation:

- `IMPORT_EXPORT_DOCUMENTATION.md`
- `BACKUP_MAINTENANCE_DOCUMENTATION.md`
- `AUDIT_LOG_IMPLEMENTATION.md`
- `API_RATE_LIMITING.md`
- `AI_CHATBOT_IMPLEMENTATION.md`
- `AI_RECOMMENDATIONS_SETUP.md`

## Project Structure

| Path | Description |
| --- | --- |
| `app/Http/Controllers` | Web, API, admin, backup, import/export controllers |
| `app/Http/Middleware` | Access control, rate limiting, response transformation, ETag handling |
| `app/Models` | Eloquent models for books, orders, audit logs, AI logs, imports, exports |
| `app/Services` | Business logic for AI, cache, audit, exports, rate limits, recommendations |
| `app/Jobs` | Queue jobs for imports, exports, AI tasks, and cache warming |
| `app/Console/Commands` | Custom Artisan commands and benchmarks |
| `database/migrations` | Database schema definitions |
| `database/seeders` | Seed data for categories, logs, scheduled tasks, limits, monitoring |
| `resources/views` | Blade views and reusable UI components |
| `routes` | Web, API, auth, backup, and import/export routes |
| `config` | Application, cache, queue, database, backup, Excel, audit, AI configuration |
| `tests` | Feature and unit tests |

## Testing and Validation

The system includes tests for:

- Authentication
- Profile management
- Import/export workflows
- Rate limiting
- Backup scheduling
- Audit compliance
- AI recommendations
- Floating chatbot
- Book factory validity
- Cache service behavior
- Export performance
- Million-book challenge validation

Run specific examples:

```bash
php artisan test tests/Feature/ImportExportTest.php
php artisan test tests/Feature/RateLimitingTest.php
php artisan test tests/Feature/AuditComplianceTest.php
php artisan test tests/Feature/AiRecommendationsTest.php
php artisan test tests/Unit/BookFactoryTest.php
php artisan test tests/Unit/BookCacheServiceTest.php
```

## Submission Notes

For laboratory submission, include:

- Source code repository
- Database migrations and seeders
- Configuration files
- Activity README files
- Technical documentation
- API documentation
- User guide
- Screenshots or terminal logs from validation commands
- Screenshots of working UI features
- Queue worker screenshot when demonstrating queued jobs
- AI fallback and cost tracking screenshots for Activity 8

## Authors and Course

Project: PageTurner Bookstore  
Course: ITSD Web Software Tools / Fundamentals of Laravel  
Section: BSIT 3C
