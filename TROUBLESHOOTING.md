# Troubleshooting "Page Expired" Error

## Quick Fixes to Try

### 1. Clear Cache and Sessions
```bash
php artisan config:clear
php artisan cache:clear
php artisan session:clear
php artisan route:clear
php artisan view:clear
```

### 2. Check Session Configuration

Make sure your `.env` has:
```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
```

### 3. Verify Database Sessions Table Exists
```bash
php artisan migrate
```

The sessions table should have been created by the default Laravel migration.

### 4. Test Without 2FA First

Temporarily disable 2FA to verify basic login works:

**Option A: Via Database**
```sql
UPDATE users SET two_factor_enabled = false WHERE email = 'your@email.com';
```

**Option B: Via Tinker**
```bash
php artisan tinker
```
Then:
```php
$user = User::where('email', 'your@email.com')->first();
$user->two_factor_enabled = false;
$user->save();
```

### 5. Enable Detailed Error Logging

In your `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

Check `storage/logs/laravel.log` for detailed errors.

---

## Common Causes

1. **Session not persisting** - Make sure sessions table exists and is writable
2. **CSRF token mismatch** - Clear browser cookies/cache
3. **Multiple tabs** - Close other tabs and try in a fresh browser window
4. **Browser blocking cookies** - Check browser settings
5. **App key not set** - Run `php artisan key:generate`

---

## Testing Steps

1. **Clear everything:**
   ```bash
   php artisan optimize:clear
   ```

2. **Close all browser tabs** for your app

3. **Open in incognito/private window**

4. **Try login again**

If it still fails, check `storage/logs/laravel.log` for the exact error.

---

## Still Having Issues?

Run this diagnostic:

```bash
php artisan tinker
```

Then check:
```php
// Check if sessions table exists
DB::table('sessions')->count();

// Check your app key is set
config('app.key');

// Check session config
config('session.driver');
```

All should return values without errors.
