# Report Module - Quick Troubleshooting Guide

## Installation Issues

### ❌ Issue: "composer: command not found"
**Symptoms:** Terminal says composer is not installed

**Solutions:**
1. **Install Composer Globally:**
   ```bash
   curl -sS https://getcomposer.org/installer | php
   sudo mv composer.phar /usr/local/bin/composer
   ```

2. **Use Docker (if available):**
   ```bash
   docker run --rm -v "$(pwd):/app" composer install
   ```

---

### ❌ Issue: "PhpSpreadsheet is not installed"
**Symptoms:** Export button shows error "PhpSpreadsheet is not installed"

**Solutions:**
1. **Run Composer Install:**
   ```bash
   cd /workspaces/FYP-Submission-Management-System
   composer install
   ```

2. **Verify Installation:**
   ```bash
   ls -la vendor/phpoffice/
   ```

3. **Check vendor/autoload.php exists:**
   ```bash
   ls -la vendor/autoload.php
   ```

---

## Access & Authentication Issues

### ❌ Issue: Report page redirects to login
**Symptoms:** Click Reports, gets redirected to login page

**Solutions:**
1. **Ensure you're logged in as admin:**
   - Log out completely
   - Log in again with admin credentials
   - Check Session username displays at top-right

2. **Check SESSION variables:**
   ```php
   // Add to report.php temporarily for debugging:
   echo '<pre>';
   var_dump($_SESSION);
   echo '</pre>';
   ```

3. **Clear browser cache:**
   - Press Ctrl+Shift+Del (or Cmd+Shift+Del on Mac)
   - Clear cookies and cache
   - Try again

---

### ❌ Issue: "403 Unauthorized" when exporting
**Symptoms:** Export button clicked but nothing happens

**Solutions:**
1. **Check browser console:**
   - Press F12 to open Developer Tools
   - Check Console tab for errors
   - Look for network errors (red entries)

2. **Verify admin session:**
   ```bash
   # Check if session is being maintained:
   # Add this to report.php:
   echo "Session ID: " . session_id();
   echo "Admin Logged In: " . ($_SESSION['admin_logged_in'] ?? 'false');
   ```

---

## Database Issues

### ❌ Issue: Statistics show as 0
**Symptoms:** All numbers display 0

**Solutions:**
1. **Check database connection:**
   ```bash
   # Test PostgreSQL connection
   psql -h localhost -U postgres -d fyp_submission_system -c "SELECT COUNT(*) FROM students;"
   ```

2. **Verify tables exist:**
   ```sql
   SELECT table_name FROM information_schema.tables WHERE table_schema='public';
   ```

3. **Check if tables have data:**
   ```sql
   SELECT COUNT(*) FROM students;
   SELECT COUNT(*) FROM lecturers;
   SELECT COUNT(*) FROM projects;
   ```

4. **Check database credentials in db.php:**
   - Username: correct?
   - Password: correct?
   - Database name: correct?
   - Host: correct?

---

### ❌ Issue: "Database Error: Unable to retrieve statistics"
**Symptoms:** Error message shown on report page

**Solutions:**
1. **Check database connection:**
   ```bash
   psql -U postgres -h localhost
   ```

2. **Verify PostgreSQL is running:**
   ```bash
   # Linux/Mac
   pg_isready -h localhost
   
   # Expected output: accepting connections
   ```

3. **Check database logs:**
   ```bash
   tail -f /var/log/postgresql/postgresql.log
   ```

4. **Test connection in PHP:**
   ```php
   try {
       $db = new PDO('pgsql:host=localhost;dbname=fyp_submission_system', 'postgres', 'password');
       echo "Connected!";
   } catch (Exception $e) {
       echo "Error: " . $e->getMessage();
   }
   ```

---

## Excel Export Issues

### ❌ Issue: Export button doesn't respond
**Symptoms:** Click Export, nothing happens (no download, no error)

**Solutions:**
1. **Check browser console (F12):**
   - Look for JavaScript errors
   - Check Network tab for failed requests
   - Look for CORS issues

2. **Verify fetch request:**
   ```javascript
   // Open browser console and test:
   fetch('export_report.php', {
       method: 'POST',
       headers: {'Content-Type': 'application/json'},
       body: JSON.stringify({action: 'export'})
   })
   .then(r => console.log(r.status))
   .catch(e => console.error(e));
   ```

3. **Check PHP error logs:**
   ```bash
   tail -f /var/log/php-fpm/error.log
   # or
   tail -f /var/log/apache2/error.log
   ```

---

### ❌ Issue: Downloaded file is corrupted or empty
**Symptoms:** File downloads but won't open or is empty

**Solutions:**
1. **Check file size:**
   - File should be 8-15 KB
   - If < 1 KB, likely corrupted

2. **Verify Excel headers are sent:**
   ```bash
   # Check HTTP headers:
   curl -I http://localhost/admin/export_report.php
   ```

3. **Remove any output before export:**
   - Ensure no PHP output before headers
   - Check for BOM (Byte Order Mark) in files
   - Ensure no whitespace before `<?php`

4. **Test export directly:**
   ```bash
   curl -X POST http://localhost/admin/export_report.php \
        -H "Content-Type: application/json" \
        -d '{"action":"export"}' \
        -o test.xlsx
   ```

---

### ❌ Issue: Excel file opens but data is malformed
**Symptoms:** File opens but content looks wrong

**Solutions:**
1. **Check formatting code:**
   - Verify cell formatting in export_report.php
   - Check for encoding issues
   - Ensure data is properly typed

2. **Try opening with different program:**
   - Excel
   - LibreOffice Calc
   - Google Sheets
   - One of these should work

3. **Regenerate file:**
   - Clear browser cache
   - Try exporting again

---

## Page Display Issues

### ❌ Issue: Cards not displaying correctly
**Symptoms:** Dashboard cards misaligned or broken

**Solutions:**
1. **Clear browser cache:**
   - Ctrl+Shift+Del (Windows) or Cmd+Shift+Del (Mac)
   - Clear all cached files
   - Reload page

2. **Check CSS loads:**
   - Open browser Developer Tools (F12)
   - Check Network tab
   - Verify Bootstrap CSS loads (200 status)
   - Verify utm-theme.css loads

3. **Check for JavaScript errors:**
   - Open Console tab in Developer Tools
   - Any red error messages?
   - Fix syntax if needed

---

### ❌ Issue: Statistics numbers not formatted with commas
**Symptoms:** "1000" instead of "1,000"

**Solutions:**
- This is normal for initial load
- Numbers are formatted with `number_format()` function
- Check if function call is present in report.php

---

## File Structure Verification

### Verify all files exist:
```bash
# Check if all required files are present
ls -la /workspaces/FYP-Submission-Management-System/admin/report.php
ls -la /workspaces/FYP-Submission-Management-System/admin/export_report.php
ls -la /workspaces/FYP-Submission-Management-System/composer.json
ls -la /workspaces/FYP-Submission-Management-System/vendor/autoload.php
```

### Expected output:
```
-rw-r--r-- ... report.php
-rw-r--r-- ... export_report.php
-rw-r--r-- ... composer.json
-rw-r--r-- ... autoload.php
```

---

## Performance Issues

### ❌ Issue: Report page loads slowly
**Symptoms:** Takes > 2 seconds to load

**Solutions:**
1. **Check database query performance:**
   ```sql
   EXPLAIN ANALYZE SELECT COUNT(*) FROM students;
   ```

2. **Add indexes if needed:**
   ```sql
   CREATE INDEX idx_students_id ON students(student_id);
   CREATE INDEX idx_lecturers_id ON lecturers(lecturer_id);
   CREATE INDEX idx_projects_id ON projects(project_id);
   ```

3. **Check database load:**
   ```bash
   psql -U postgres -c "SELECT * FROM pg_stat_activity;"
   ```

---

## Debug Mode

### Enable detailed error reporting:
Add to top of report.php (temporarily):
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Log all database queries:
```php
$db->setAttribute(PDO::ATTR_STATEMENT_CLASS, ['PDOStatementExtended', [true]]);
```

### Monitor file access:
```bash
tail -f /var/log/apache2/access.log | grep report.php
```

---

## Testing Checklist

- [ ] Composer installed and dependencies loaded
- [ ] Can access report.php when logged in as admin
- [ ] Statistics display with correct numbers
- [ ] Export button is clickable
- [ ] Excel file downloads successfully
- [ ] Excel file opens in at least one program
- [ ] Excel file contains all required data
- [ ] File is named with current date
- [ ] Non-admins cannot access report page

---

## Getting Help

If issues persist:

1. **Check error logs:**
   ```bash
   grep -r "Report\|export" /var/log/apache2/
   grep -r "PhpSpreadsheet\|composer" /var/log/php-fpm/
   ```

2. **Gather diagnostic info:**
   - PHP version: `php -v`
   - Composer version: `composer -V`
   - PostgreSQL version: `psql --version`
   - Web server: Apache/Nginx

3. **Create test file:**
   - Create a simple PHP file to test components
   - Test database connection
   - Test PhpSpreadsheet installation
   - Test file permissions

---

## Common Quick Fixes

| Problem | Solution |
|---------|----------|
| "Command not found: composer" | Install Composer or use `php composer.phar` |
| "PhpSpreadsheet not installed" | Run `composer install` |
| Page shows 0 statistics | Check database connection and tables |
| Export button doesn't work | Check browser console (F12), verify admin login |
| File won't open | Try different Excel program (LibreOffice, Google Sheets) |
| Page loads slowly | Check database indexes, clear cache |

---

**Still stuck?** Check the complete documentation in `REPORT_MODULE_DOCUMENTATION.md` and `REPORT_MODULE_SETUP.md`
