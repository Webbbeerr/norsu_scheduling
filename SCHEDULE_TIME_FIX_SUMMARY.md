# Schedule Time Display Issue - Fix Summary

## Problem Identified

**Issue**: Schedules were displaying PM times instead of AM times in the admin dashboard.

**Example from screenshot**:
- ITS 101 Section C showed "05:00 PM - 06:00 PM" 
- ITS 101 Section B showed "02:00 PM - 03:00 PM"
- ITS 101 Section D showed "03:30 PM - 04:30 PM"

**Actual times in database**:
- ITS 101 Section C: 09:00:00 - 10:00:00 (9 AM - 10 AM)
- ITS 101 Section B: 06:00:00 - 07:00:00 (6 AM - 7 AM)
- ITS 101 Section D: 07:30:00 - 08:30:00 (7:30 AM - 8:30 AM)

## Root Cause

**Timezone Mismatch Between PHP and Twig**

1. **PHP Default Timezone**: UTC (Coordinated Universal Time)
2. **Twig Date Filter Timezone**: Asia/Manila (UTC+8)
3. **Database Storage**: Times stored as-is without timezone conversion

### What Was Happening:

1. When creating a schedule with start time "06:00" (6 AM):
   - PHP (in UTC) created a DateTime object
   - Time was stored in database as "06:00:00"

2. When displaying the schedule:
   - Twig read the DateTime object (assumed to be UTC)
   - Applied timezone conversion: 06:00 UTC + 8 hours = 14:00 Manila Time (2:00 PM)
   - Displayed as "02:00 PM"

This created an **8-hour time shift** on all displayed schedules.

## Solution Implemented

### 1. Set PHP Default Timezone in Kernel (`src/Kernel.php`)

Added timezone initialization in the Kernel constructor:

```php
public function __construct(string $environment, bool $debug)
{
    // Set default timezone to match Twig configuration
    date_default_timezone_set('Asia/Manila');
    
    parent::__construct($environment, $debug);
}
```

This ensures:
- All DateTime objects are created in Asia/Manila timezone
- Time parsing and storage use the correct timezone
- Consistency across the entire application

### 2. Removed Twig Timezone Override (`config/packages/twig.yaml`)

**Before**:
```yaml
twig:
    file_name_pattern: '*.twig'
    date:
        timezone: 'Asia/Manila'
```

**After**:
```yaml
twig:
    file_name_pattern: '*.twig'
    # Timezone is now set globally in Kernel.php to 'Asia/Manila'
    # Removed twig.date.timezone to prevent double timezone conversion
```

This prevents Twig from applying an additional timezone conversion since PHP now handles timezone correctly.

## Files Modified

1. `src/Kernel.php` - Added timezone initialization
2. `config/packages/twig.yaml` - Removed Twig-specific timezone setting

## Verification Steps

1. **Clear Symfony cache**:
   ```bash
   php bin/console cache:clear
   ```

2. **Check current timezone**:
   ```bash
   php -r "echo date_default_timezone_get();"
   # Should output: Asia/Manila
   ```

3. **Test schedule display**:
   - Navigate to Admin Dashboard → Schedules
   - Verify times display correctly:
     - 06:00:00 in DB should show as "06:00 AM" (not "02:00 PM")
     - 09:00:00 in DB should show as "09:00 AM" (not "05:00 PM")
     - 14:00:00 in DB should show as "02:00 PM" (correct)

## Expected Results After Fix

All schedules should now display with correct times:

| Subject | Section | Day Pattern | Database Time | Display Time (Before Fix) | Display Time (After Fix) |
|---------|---------|-------------|---------------|---------------------------|--------------------------|
| ITS 101 | C | MTWTHF | 09:00-10:00 | 05:00 PM - 06:00 PM ❌ | 09:00 AM - 10:00 AM ✅ |
| ITS 101 | B | MWF | 06:00-07:00 | 02:00 PM - 03:00 PM ❌ | 06:00 AM - 07:00 AM ✅ |
| ITS 101 | D | MWF | 07:30-08:30 | 03:30 PM - 04:30 PM ❌ | 07:30 AM - 08:30 AM ✅ |
| ITS 200 | B | MWF | 08:30-09:30 | 04:30 PM - 05:30 PM ❌ | 08:30 AM - 09:30 AM ✅ |
| ITS 100 | C | MWF | 05:00-06:00 | 01:00 PM - 02:00 PM ❌ | 05:00 AM - 06:00 AM ✅ |
| ITS 100 | A | MWF | 06:00-07:00 | 02:00 PM - 03:00 PM ❌ | 06:00 AM - 07:00 AM ✅ |

## Impact on Future Schedules

- **New schedules created after this fix**: Will store and display correctly in Asia/Manila timezone
- **Existing schedules**: Will now display correctly without needing database migration
- **Time input forms**: Will continue to work as expected with 24-hour time format

## Additional Notes

### Why This Happened

The issue occurred because:
1. Symfony's default PHP timezone is UTC
2. Twig was configured to display times in Asia/Manila
3. This created an implicit timezone conversion that wasn't intended

### Prevention

To prevent similar issues in the future:
- Always set application timezone explicitly in the Kernel
- Keep PHP and Twig timezone settings consistent
- Use timezone-aware DateTime objects when working with times
- Test time display when deploying to servers with different timezone settings

## Testing Checklist

- [x] Verified database times are stored correctly
- [x] Identified timezone mismatch between PHP (UTC) and Twig (Asia/Manila)
- [x] Updated Kernel.php to set Asia/Manila timezone
- [x] Removed Twig timezone override
- [x] Cleared Symfony cache
- [ ] **User should verify**: Schedule times now display correctly in admin dashboard
- [ ] **User should verify**: Creating new schedules works properly
- [ ] **User should verify**: Editing existing schedules preserves correct times

## Related Configuration Files

- `src/Kernel.php` - Application kernel with timezone initialization
- `config/packages/twig.yaml` - Twig configuration
- `config/services.yaml` - Contains app.timezone parameter (kept for reference)

## No Database Changes Required

✅ **No database migration needed** - Existing data is correct, only the display was wrong due to timezone conversion.
