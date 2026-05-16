# Import/Export Module Documentation

## Overview

The PageTurner Import/Export Module provides comprehensive functionality for:
- **Book Import**: Bulk upload books via Excel/CSV with validation and duplicate handling
- **Order Export**: Export orders with filtering and custom column selection
- **User Import/Export**: Manage user accounts with PII redaction for GDPR compliance

---

## 4.1.1 Book Import/Export Module

### Import Features

#### File Format Support
- **Supported Formats**: XLSX, CSV
- **Template Available**: Download template from admin dashboard

#### Required Headers
```
ISBN | Title | Author | Price | Stock | Category | Description
```

#### Data Validation Rules

| Field | Validation |
|-------|-----------|
| **ISBN** | Must be unique, valid ISBN-10 or ISBN-13 format |
| **Title** | Required, max 255 characters |
| **Author** | Optional (defaults to 'Unknown') |
| **Price** | Numeric, positive, max 9,999.99 |
| **Stock** | Non-negative integer |
| **Category** | Must exist in categories table |
| **Description** | Optional |

#### Duplicate Detection
- **Skip Mode**: Duplicates (by ISBN) are skipped with error logged
- **Update Mode**: Existing books are updated with new data

#### Processing
- **Small Files** (≤10,000 rows): Immediate processing
- **Large Files** (>10,000 rows): Queue-based background processing
- **Batch Size**: 1,000 records per batch for DB optimization
- **Chunk Size**: 1,000 rows per memory chunk

#### Error Handling
- Skip-on-failure approach with detailed error reports
- Each failure includes row number, field, and error message
- Download failure report from status dashboard

#### Access
```
GET /admin/import-export/books/import
POST /admin/import-export/books/import
GET /admin/import-export/books/template
```

---

### Export Features

#### Export Formats
- XLSX (Excel)
- CSV
- PDF (planned)

#### Filter Options
1. **Category**: Filter by single category
2. **Price Range**: Min and max price
3. **Stock Status**: 
   - In Stock (> 0)
   - Out of Stock (= 0)
   - Low Stock (< 10 and > 0)
4. **Date Range**: Created date between dates

#### Custom Column Selection
Available columns:
- ID
- ISBN
- Title
- Author
- Category
- Price
- Stock Quantity
- Description
- Created Date
- Updated Date

#### Processing
- **Small Exports** (≤10,000 records): Immediate
- **Large Exports** (>10,000 records): Queued with progress tracking
- **Chunked Processing**: Via FromQuery concern for memory efficiency

#### Access
```
POST /admin/import-export/books/export (form at orders-export view)
```

---

## 4.1.2 Order Export Module

### Features

#### Export Filters
- **Order Status**: pending, processing, completed, cancelled
- **Date Range**: From date and to date
- **Customer**: Filter by customer email or ID

#### Financial Reporting
- Revenue summaries with order totals
- Tax calculations (estimated at 10%)
- Subtotal reporting

#### Custom Columns
- Order ID
- Order Number
- Customer Name
- Customer Email
- Total Amount
- Status
- Items Count
- Order Date

#### Scheduled Exports
- Email notifications on export completion
- Automatic daily sales report generation (future implementation)

#### Access
```
GET /admin/import-export/orders/export
POST /admin/import-export/orders/export
```

#### Customer Self-Service
- Customers can export their order history via profile
- PDF invoice generation capability (future)

---

## 4.1.3 User Import/Export (Admin Only)

### Import Features

#### File Format
- **Supported Formats**: XLSX, CSV
- **Required Header**: email, first_name

#### Validation Rules

| Field | Validation |
|-------|-----------|
| **email** | Required, unique, valid format |
| **first_name** | Required |
| **last_name** | Optional (defaults to 'User') |
| **middle_name** | Optional |
| **suffix** | Optional (Jr., Sr., III, etc.) |
| **password** | Optional (auto-generated if not provided) |
| **role** | Optional - 'admin', 'customer', 'visitor' (defaults to 'customer') |

#### Role Assignment
- Assign roles during bulk import
- Supported roles: admin, customer, visitor
- Default role: customer

#### Processing
- Batch processing with duplicate email detection
- All users created with email_verified_at set to now()
- Password auto-generation if not provided

#### Access
```
GET /admin/import-export/users/import
POST /admin/import-export/users/import
```

### Export Features

#### PII Redaction (GDPR Compliance)
When **PII Redaction** is enabled:
- **Name**: First letter + *** (e.g., "J*** D***")
- **Email**: First 2 chars + *** (e.g., "jo***@***")

#### Export Columns
- User ID
- Name (with optional redaction)
- Email (with optional redaction)
- Role
- Email Verified Status
- 2FA Status
- Registration Date

#### Filters
- **Role**: Filter by user role
- **Date Range**: Users created between dates
- **Verification Status**: Only verified users

#### Access
```
POST /admin/import-export/users/export
GET  /admin/import-export/users/export
```

---

## Database Tables

### import_logs Table
```sql
id                  BIGINT PRIMARY KEY
user_id             BIGINT FOREIGN KEY (users)
module_type         VARCHAR (books, users, etc.)
file_name           VARCHAR (original filename)
file_path           VARCHAR (storage path)
status              VARCHAR (pending, processing, completed, failed)
total_rows          INT (rows in file)
successful_rows     INT (successfully processed)
failed_rows         INT (failed records)
error_details       JSON (detailed errors)
failure_report      JSON (array of failed rows)
started_at          TIMESTAMP
completed_at        TIMESTAMP
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

### export_logs Table
```sql
id                  BIGINT PRIMARY KEY
user_id             BIGINT FOREIGN KEY (users)
module_type         VARCHAR (books, orders, users, etc.)
export_format       VARCHAR (csv, xlsx, pdf)
file_name           VARCHAR (export filename)
file_path           VARCHAR (storage path)
status              VARCHAR (pending, processing, completed, failed)
total_records       INT (records exported)
filters             JSON (applied filters)
selected_columns    JSON (column selection)
started_at          TIMESTAMP
completed_at        TIMESTAMP
error_message       LONGTEXT
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

---

## Technical Implementation

### Packages & Dependencies
- **Laravel Excel (maatwebsite/excel)**: v3.1.68
- **PhpOffice/PhpSpreadsheet**: v1.30.3
- **Laravel Queue**: For background processing

### Classes Structure

#### Imports
- `App\Imports\BooksImport`
  - Implements: ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading, SkipsOnFailure
  - ISBN validation (ISBN-10/ISBN-13)
  - Duplicate handling (skip/update)
  - Category validation
  
- `App\Imports\UsersImport`
  - Email validation
  - Role assignment
  - Password hashing

#### Exports
- `App\Exports\BooksExport`
  - Implements: FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithChunkReading
  - Filtered export support
  - Custom column selection
  
- `App\Exports\OrdersExport`
  - Financial reporting capability
  - Status and date filtering
  - Customer filtering
  
- `App\Exports\UsersExport`
  - PII redaction support
  - Role and verification filtering

#### Jobs
- `App\Jobs\ProcessBookImport`: Queue-based book import
- `App\Jobs\ProcessUserImport`: Queue-based user import
- `App\Jobs\ProcessExport`: Unified export processing job

#### Controller
- `App\Http\Controllers\ImportExportController`
  - Book import/template download
  - Order export with filters
  - User import/export
  - Status dashboard
  - File download handler

### Routes
```php
/admin/import-export/books/import          [GET/POST] - Book import form & process
/admin/import-export/books/template        [GET]      - Download template
/admin/import-export/orders/export         [GET/POST] - Order export
/admin/import-export/users/import          [GET/POST] - User import
/admin/import-export/users/export          [POST]     - User export
/admin/import-export/status                [GET]      - View import/export logs
/admin/import-export/download/{id}         [GET]      - Download exported file
```

### Views
- `resources/views/admin/import-export/books-import.blade.php`
- `resources/views/admin/import-export/orders-export.blade.php`
- `resources/views/admin/import-export/users-import.blade.php`
- `resources/views/admin/import-export/status.blade.php`

---

## Usage Examples

### 1. Bulk Import Books

**Steps:**
1. Download template: `/admin/import-export/books/template`
2. Fill in book data with valid ISBNs and existing categories
3. Upload file: `/admin/import-export/books/import`
4. Choose duplicate handling (skip or update)
5. View status: `/admin/import-export/status`

**Error Handling:**
- Validation errors logged with row numbers
- Download failure report for investigation
- Failed rows can be corrected and re-imported

### 2. Export Orders with Filters

**Steps:**
1. Navigate to: `/admin/import-export/orders/export`
2. Select export format (XLSX/CSV)
3. Apply filters (status, date range, customer)
4. Select columns to include
5. Click Export
6. Download from status dashboard

### 3. Import Users with Roles

**Steps:**
1. Create CSV/XLSX file with user data
2. Navigate to: `/admin/import-export/users/import`
3. Assign roles during import
4. Upload file
5. View results in status dashboard

---

## Queue Configuration

For background processing to work:

```bash
# .env
QUEUE_CONNECTION=database
# or
QUEUE_CONNECTION=redis
```

### Start Queue Worker
```bash
php artisan queue:work --tries=1 --timeout=0
```

### For Development
```bash
# Start all services including queue
npm run dev
```

---

## Performance Optimization

### Batch Processing
- Database inserts in batches of 1,000
- Memory chunks of 1,000 rows
- Prevents memory overflow on large files

### Chunked Exports
- FromQuery concern for memory efficiency
- No loading all records into memory
- Suitable for exports with 100k+ records

### Caching
- Template caching (minimal since templates are simple)
- Log queries optimized with indexes on user_id, module_type, status

---

## Security Considerations

### File Upload
- Accepted formats: CSV, XLSX only
- Files stored in `storage/imports`
- Access restricted to authenticated admins

### PII Redaction
- GDPR compliance for user exports
- Name and email redaction available
- Opt-in via checkbox during export

### Role-Based Access
- Admin only: All import/export functions
- Middleware: `access_control:admin`

### Validation
- Database constraints ensure referential integrity
- Input validation at application level
- Failed imports prevent partial data corruption

---

## Future Enhancements

1. **PDF Export**: Add PDF format for orders/invoices
2. **Scheduled Exports**: Automatic daily/weekly exports
3. **Email Notifications**: Alert on import/export completion
4. **API Import**: REST API for third-party integrations
5. **Webhook Support**: Post-import actions
6. **Advanced Filtering**: UI builder for complex filters
7. **Data Transformation**: Custom field mapping
8. **Audit Trail**: Track all imports/exports by user

---

## Troubleshooting

### Import Fails with "Category not found"
- Verify category name matches exactly
- Create missing categories before import

### Large Import Hangs
- Check queue worker is running
- Verify database has sufficient disk space
- Check `storage/logs/laravel.log` for errors

### Export File Not Found
- Verify export log shows "completed" status
- Check `storage/exports/` directory exists
- Ensure sufficient disk space

### Memory Issues
- Large files use chunked processing (automatic for >10k rows)
- Verify server has adequate memory allocation
- Use queue worker for background processing

---

## Support

For issues or questions:
1. Check `storage/logs/laravel.log`
2. Review import/export logs in admin dashboard
3. Verify file format and data validation rules
4. Check database and file storage permissions
