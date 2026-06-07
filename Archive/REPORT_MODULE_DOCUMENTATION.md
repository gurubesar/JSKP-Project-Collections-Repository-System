# Admin Report Module - Documentation

## Overview
The Admin Report Module provides a comprehensive reporting dashboard accessible only to administrators. It displays key statistics about the system and allows exporting reports to Excel format.

## Features
✅ **Admin-Only Access** - Protected by session authentication
✅ **Dashboard Statistics**
   - Total Students
   - Total Lecturers
   - Total Repositories
   
✅ **Dynamic Data Retrieval** - Real-time data from PostgreSQL database using PDO
✅ **Beautiful Dashboard Cards** - Professional card-based UI with color-coded statistics
✅ **Excel Export** - Download reports as formatted .xlsx files
✅ **Complete Error Handling** - Try-catch blocks with user-friendly error messages
✅ **Professional Formatting** - Bold headers, auto-sized columns, timestamp tracking

---

## Installation

### Step 1: Install PhpSpreadsheet
Navigate to your project root and run:
```bash
composer install
```

This will install `phpoffice/phpspreadsheet` based on the `composer.json` configuration.

### Step 2: Verify Installation
Confirm that the vendor directory was created:
```bash
ls -la vendor/
```

You should see a `phpoffice` folder inside vendor.

---

## File Structure

```
/admin/
├── report.php              # Main report page (dashboard)
├── export_report.php       # Excel export handler
├── admin_sidebar.php       # Updated with Reports link
└── [other admin files...]

/database/
├── db.php                  # Database connection (existing)
└── postgres_schema.sql     # PostgreSQL schema (existing)

composer.json              # New - dependency management
```

---

## Database Queries

### PostgreSQL Queries Used:

#### 1. Total Students
```sql
SELECT COUNT(*) as total FROM students
```

#### 2. Total Lecturers
```sql
SELECT COUNT(*) as total FROM lecturers
```

#### 3. Total Repositories
```sql
SELECT COUNT(*) as total FROM projects
```

**Note:** These queries are safe and optimized for the existing schema.

---

## Usage Guide

### Accessing the Report
1. Log in as an Admin
2. Click "Reports" in the left sidebar
3. View the dashboard with statistics

### Exporting to Excel
1. On the Report page, click the "Export to Excel" button
2. A file named `jskp_summary_report_YYYYMMDD.xlsx` will be downloaded
3. Open with Excel, LibreOffice, or Google Sheets

---

## Excel Export Features

### File Format
- **Format:** Microsoft Excel (.xlsx)
- **Filename:** `jskp_summary_report_YYYYMMDD.xlsx`
- **Example:** `jskp_summary_report_20260531.xlsx`

### Content Structure
```
JSKP Project Repository System Report
Generated: 2026-05-31 14:30:45

Metric                 Value
Total Students         [count]
Total Lecturers        [count]
Total Repositories     [count]
```

### Formatting Applied
- ✅ Title: 16pt, Bold, Dark Red Color (#800020)
- ✅ Headers: Bold, White text on Dark Red background
- ✅ Data rows: Centered content with alternating row colors
- ✅ Auto-sized columns for readability
- ✅ Borders on all cells
- ✅ A4 page size with proper margins
- ✅ Timestamp included on export

---

## Security Features

### Authentication
- Session-based admin verification
- Redirects unauthorized users to login
- 403 error for non-authenticated API calls

### Error Handling
- Database errors caught and logged
- User-friendly error messages displayed
- Stack traces logged server-side (not shown to users)
- PDO prepared statements prevent SQL injection

### Data Validation
- Input validation on JSON requests
- HTTP method verification (POST required for export)
- Database table existence checks (via existing helpers)

---

## API Endpoints

### POST /admin/export_report.php
**Description:** Generates and downloads an Excel report

**Request:**
```json
{
  "action": "export"
}
```

**Response:** Binary Excel file (.xlsx)

**HTTP Headers:**
```
Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
Content-Disposition: attachment; filename="jskp_summary_report_20260531.xlsx"
```

**Error Response (500):**
```json
{
  "error": "Export failed",
  "message": "Error description"
}
```

---

## Code Architecture

### report.php
- **Purpose:** Main dashboard page
- **Authentication:** Admin session check
- **Functionality:**
  - Retrieves statistics from database
  - Displays cards with formatted numbers
  - Renders summary table
  - Provides export button with JavaScript handler

### export_report.php
- **Purpose:** Excel file generation and download
- **Authentication:** Admin session check + POST method verification
- **Functionality:**
  - Validates request format
  - Queries database for current statistics
  - Uses PhpSpreadsheet to create formatted Excel file
  - Sets proper HTTP headers for download
  - Handles errors gracefully

---

## Error Handling Examples

### Database Connection Error
```php
try {
    // Database query
} catch (PDOException $e) {
    $errors[] = "Database Error: Unable to retrieve statistics.";
    error_log("Error: " . $e->getMessage());
}
```

### Missing PhpSpreadsheet
```php
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    throw new Exception('PhpSpreadsheet is not installed. Run: composer install');
}
```

### Invalid Export Request
```php
if (!isset($input['action']) || $input['action'] !== 'export') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}
```

---

## Troubleshooting

### Issue: "PhpSpreadsheet is not installed"
**Solution:** Run `composer install` in project root

### Issue: Export button not working
**Solution:** 
1. Check browser console for JavaScript errors (F12)
2. Verify admin is logged in (session check)
3. Ensure database connection is working

### Issue: Statistics showing as 0
**Solution:**
1. Verify database tables exist: `students`, `lecturers`, `projects`
2. Check database connection in `/database/db.php`
3. Ensure tables have data

### Issue: Database connection error
**Solution:**
1. Verify PostgreSQL is running
2. Check credentials in `.env` or environment variables
3. Review `/database/db.php` for connection settings

---

## Performance Considerations

- **Count queries** are optimized with `COUNT(*)` aggregation
- **No N+1 queries** - single query per statistic
- **Result caching** - statistics retrieved once per page load
- **Excel generation** - fast in-memory operation
- **File streaming** - direct output to browser, no disk storage

---

## Future Enhancements

Potential improvements for future versions:

1. **Date Range Filtering**
   - Select date range for statistics
   - Filter by department/lecturer/course

2. **Additional Metrics**
   - Submission statistics
   - Project status breakdown
   - User activity over time

3. **Chart Visualizations**
   - Pie charts for distribution
   - Line graphs for trends
   - Bar charts for comparisons

4. **Multiple Report Formats**
   - PDF export
   - CSV export
   - JSON API

5. **Scheduled Reports**
   - Automated daily/weekly reports
   - Email delivery
   - Report history/archive

6. **Advanced Filtering**
   - Filter by study year
   - Filter by course
   - Filter by submission status

---

## Database Schema Reference

### students table
```sql
CREATE TABLE students (
    student_id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL UNIQUE,
    matric_no VARCHAR(50) NOT NULL UNIQUE,
    course VARCHAR(150) NOT NULL,
    intake VARCHAR(50) NOT NULL
);
```

### lecturers table
```sql
CREATE TABLE lecturers (
    lecturer_id INTEGER PRIMARY KEY,
    user_id INTEGER NOT NULL UNIQUE,
    staff_id VARCHAR(50) NOT NULL UNIQUE,
    department VARCHAR(150) NOT NULL
);
```

### projects table
```sql
CREATE TABLE projects (
    project_id INTEGER PRIMARY KEY,
    title_encrypted TEXT NOT NULL,
    description_encrypted TEXT,
    category_encrypted TEXT,
    lecturer_id INTEGER,
    study_year INTEGER,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL
);
```

---

## License & Credits

Created for: FYP Submission Management System
Framework: Bootstrap 5.3.3
Dependencies: PhpOffice\PhpSpreadsheet 1.28+

---

## Support

For issues or questions:
1. Check troubleshooting section above
2. Review error logs in server logs
3. Verify database connection settings
4. Ensure all files are in correct locations

---

**Last Updated:** May 31, 2026
**Version:** 1.0
**Status:** Production Ready
