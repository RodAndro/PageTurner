# Advanced Dashboard Quick Start Guide

## Overview
This guide walks you through the newly implemented advanced dashboard features for admin monitoring and GDPR-compliant user data portability.

---

## For Administrators

### Accessing the Advanced Dashboard

**URL:** `/admin/dashboard/advanced`

**Requirements:**
- Admin role
- Active authentication
- Access control middleware bypass

**What You'll See:**
1. **System Health Status** - Overall health badge with last update time
2. **Import/Export Widget** - Tracks bulk operations and their success
3. **Backup Widget** - Latest backup status and verification
4. **Security Alerts** - Recent security events and critical alerts
5. **API Usage** - API request statistics and throttling info
6. **Task Health** - Scheduled task execution and failures

### Understanding Dashboard Widgets

#### Widget 1: Import/Export Status
```
Shows:
- Total imports today
- Total exports today
- Import success rate
- Recent operations list
- Alerts for failures
```

**Actions:**
- Click on recent operations to see details
- Monitor in-progress items
- Track failed operations

#### Widget 2: Backup Status
```
Shows:
- Latest backup name and date
- Backup health status
- Total backups count
- Verified vs unverified
- Storage usage
```

**Alerts:**
- Failed backups shown in red
- Corrupted backups flagged
- Health warnings for low verification rate

#### Widget 3: Security Alerts
```
Shows:
- Critical events (last 24 hours)
- Security alert count
- Recent alert details
- User involvement
```

**Actions:**
- Click alerts for more details
- Review user activities
- Access full audit logs

#### Widget 4: API Usage
```
Shows:
- Total API requests today
- Error rate percentage
- Throttled users count
- Blocked IPs list
- Top endpoints
```

**Alerts:**
- Throttling warnings
- Blocked IP notifications
- Error rate spikes

#### Widget 5: Task Health
```
Shows:
- Enabled tasks count
- Disabled tasks
- Failures in last 24 hours
- Critical task failures
```

**Alerts:**
- Critical failures in red
- Recent failures in yellow
- All operational in green

### Dashboard Color Scheme

| Color | Meaning | Status |
|-------|---------|--------|
| Green | Success | Healthy |
| Blue | Information | Active |
| Yellow | Warning | Issues |
| Red | Danger | Critical |
| Gray | Neutral | Inactive |

---

## For Users: Data Portability

### Accessing Your Data

**URL:** `/data-portability`

**Requirements:**
- Customer role
- Verified email
- Active login

### What You Can Export

#### 1. Personal Data
- **Format:** JSON
- **Size:** ~10 KB
- **Contents:**
  - Name and contact info
  - Profile information
  - Account details
  - Preferences and settings

**How to Export:**
1. Click "Export" button in Personal Data card
2. Click "Export" in confirmation modal
3. File downloads automatically
4. Check export history

#### 2. Order History
- **Formats:** CSV, Excel, PDF
- **Size:** Variable (2+ KB per order)
- **Contents:**
  - Order ID and date
  - Order status
  - Items purchased
  - Total amount
  - Delivery info

**How to Export:**
1. Click "Export" button in Order History card
2. Select file format
3. Optionally filter by date range
4. Click "Export"
5. Download starts automatically

**Format Details:**
- **CSV:** Spreadsheet format, good for Excel/Sheets
- **Excel:** Native XLSX format with formatting
- **PDF:** Print-friendly with all details

#### 3. Reading History
- **Formats:** JSON, CSV, Excel
- **Size:** Variable (1+ KB per book)
- **Contents:**
  - Books purchased
  - Purchase dates
  - Books reviewed
  - Your ratings
  - Reading preferences

**How to Export:**
1. Click "Export" button in Reading History card
2. Select file format
3. Choose optional includes:
   - Include Reviews & Ratings
   - Include Wishlist
4. Click "Export"

**What's Included:**
- Complete book purchase history
- Personal ratings on each book
- Review comments
- Purchase timeline
- Inferred reading preferences
- Reading statistics

### Export History

**What You Can Do:**
- Download previous exports
- View export details
- See when file was created
- Check when file was downloaded
- Delete old exports

**Table Columns:**
- **Type:** Data type exported
- **Format:** File format (JSON, CSV, Excel, PDF)
- **Status:** Pending, Processing, Completed, Failed
- **Records:** Number of data records
- **Created:** Date/time of export
- **Actions:** Download or Delete

### GDPR Rights Information

The system explains your data rights:

✅ **Right to Access** - Download all your data
✅ **Right to Portability** - Export in standard formats
✅ **Right to be Forgotten** - Delete exports
✅ **Machine-Readable** - Multiple format options

### File Size Estimates

| Data Type | Estimated Size |
|-----------|-----------------|
| Personal Data | 10 KB |
| 1 Order | 2 KB |
| All Orders (50) | 100 KB |
| Reading History | 1 KB per book |

### Storage Duration

- Active export files: Available for download
- Old files: Can be deleted manually
- Deleted files: Removed from storage
- Export logs: Retained for compliance (30+ days)

---

## Using Exported Data

### Personal Data (JSON)
```json
{
  "export_date": "2024-02-08T10:30:00Z",
  "user": {
    "id": 123,
    "email": "user@example.com",
    "first_name": "John",
    ...
  },
  "profile": {...},
  "preferences": {...},
  "data_summary": {
    "total_orders": 42,
    "total_reviews": 15
  }
}
```

**Use with:**
- JSON viewers
- Text editors
- Data portability services
- API integrations

### Order History (CSV/Excel)
```
Order ID | Date | Status | Total | Items | Books
1001 | 2024-01-15 | Delivered | $45.99 | 2 | Book A; Book B
```

**Use with:**
- Excel/Google Sheets
- Data analysis tools
- Accounting software
- Record keeping

### Reading History (JSON/CSV)
```json
{
  "books_purchased": [
    {
      "title": "Book Title",
      "author": "Author Name",
      "purchase_date": "2024-01-10",
      "price_paid": "19.99",
      "rating": 4.5
    }
  ]
}
```

**Use with:**
- Goodreads import
- Reading tracking apps
- Analysis tools
- Personal libraries

---

## Troubleshooting

### Export Not Starting
**Problem:** "Export failed" message
**Solutions:**
1. Check file storage permissions
2. Verify sufficient disk space
3. Try again in a few minutes
4. Contact support if persistent

### Download Not Working
**Problem:** File doesn't download
**Solutions:**
1. Check browser download settings
2. Try different file format
3. Clear browser cache
4. Use different browser

### File Corruption
**Problem:** Downloaded file won't open
**Solutions:**
1. Delete and re-export
2. Try different format
3. Check file wasn't truncated
4. Try on different computer

### Data Incomplete
**Problem:** Missing orders or books
**Solutions:**
1. Verify account has data
2. Check date filters are correct
3. Try broader date range
4. Contact support

---

## Best Practices

### For Admins
1. Check dashboard daily for alerts
2. Review task health status
3. Monitor backup verification rate
4. Track API usage patterns
5. Archive critical events regularly

### For Users
1. Download your data periodically
2. Keep exports as backups
3. Try different formats to find preferred one
4. Delete old exports to save space
5. Review export history for tracking

---

## Technical Details

### Supported Export Formats

| Format | Extension | Use Case | Size |
|--------|-----------|----------|------|
| JSON | .json | Data interchange | Medium |
| CSV | .csv | Spreadsheets | Small |
| Excel | .xlsx | Advanced analysis | Small |
| PDF | .pdf | Print & sharing | Large |

### File Naming
```
[type]_export_[user-id]_[date-time].[ext]

Example:
order_export_123_2024-02-08-103015.csv
```

### Storage Location
```
storage/app/exports/
├── personal_data_*.json
├── order_export_*.{csv|xlsx|pdf}
└── reading_history_*.{json|csv|xlsx}
```

---

## Privacy & Security

### Your Data is Protected by:
- ✅ User authentication required
- ✅ Role-based access control
- ✅ Ownership verification
- ✅ HTTPS encryption
- ✅ Audit trail logging
- ✅ Compliant with GDPR

### What Happens to Your Data:
- Exported on-demand only
- Stored temporarily with you
- Never shared with third parties
- Deleted upon your request
- Tracked in audit logs

---

## Support

### Need Help?

**Dashboard Issues:**
- Contact Admin Support
- Email: admin@example.com
- Access admin panel for logging

**Data Portability Issues:**
- Check this guide first
- Contact User Support
- Email: support@example.com
- Check FAQ section

**GDPR Questions:**
- Read Privacy Policy
- Contact Privacy Officer
- Email: privacy@example.com

---

## FAQ

**Q: How often can I export my data?**
A: Unlimited. Export anytime you need.

**Q: Will exporting delete my data?**
A: No. Exports create copies only.

**Q: How long are exports stored?**
A: Until you delete them manually.

**Q: Can I download the same export twice?**
A: Yes, if the file still exists.

**Q: What if an export fails?**
A: Try again. Check system status if repeated.

**Q: Are exports encrypted?**
A: Stored securely; HTTPS during download.

**Q: Can admins see my exports?**
A: Only file metadata in logs. Not contents.

**Q: Can I export others' data?**
A: No. Only your own data is accessible.

---

## Resources

- [GDPR Compliance Guide](https://pageturner.example.com/gdpr)
- [Privacy Policy](https://pageturner.example.com/privacy)
- [Terms of Service](https://pageturner.example.com/terms)
- [Contact Support](https://pageturner.example.com/support)

---

**Last Updated:** February 8, 2026
**Version:** 1.0
**Status:** Production Ready
