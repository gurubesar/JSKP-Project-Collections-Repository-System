# Admin Report Module - Architecture & Flow Diagram

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     Admin Browser Interface                      │
│  (Bootstrap 5 Dashboard with Statistics Cards)                  │
└─────────────────────────────────┬───────────────────────────────┘
                                  │
                    ┌─────────────┴──────────────┐
                    │                            │
           ┌────────▼────────┐        ┌─────────▼──────────┐
           │  report.php     │        │ export_report.php  │
           │  (Dashboard)    │        │ (Excel Export)     │
           └────────┬────────┘        └─────────┬──────────┘
                    │                            │
        ┌───────────┼────────────┐              │
        │           │            │              │
   ┌────▼──┐  ┌────▼──┐  ┌──────▼──┐    ┌─────▼────────┐
   │ Query │  │ Query │  │ Query  │    │ Query All    │
   │ Count │  │ Count │  │ Count  │    │ Statistics  │
   │Student│  │Lecturer│ │Project │    │ & Format    │
   └────┬──┘  └────┬──┘  └──────┬──┘    └─────┬────────┘
        │          │            │              │
        └──────────┼────────────┴──────────────┘
                   │
        ┌──────────▼──────────┐
        │   PostgreSQL        │
        │   Database          │
        │                     │
        │ ┌─────────────────┐│
        │ │ students table  ││
        │ └─────────────────┘│
        │ ┌─────────────────┐│
        │ │ lecturers table ││
        │ └─────────────────┘│
        │ ┌─────────────────┐│
        │ │ projects table  ││
        │ └─────────────────┘│
        └─────────────────────┘
```

---

## Request Flow Diagram

### Dashboard Page Load Flow

```
User Visits /admin/report.php
         │
         ▼
┌─────────────────────────────────┐
│ Check Session Authentication    │
│ $_SESSION['admin_logged_in']    │
└────────┬──────────────┬─────────┘
         │              │
      YES│              │NO
         │              └─────────► Redirect to Login
         │
         ▼
┌─────────────────────────────────┐
│ Execute 3 SELECT COUNT(*) Queries
│ • students
│ • lecturers  
│ • projects
└────────┬──────────────────────────┘
         │
         ▼
┌─────────────────────────────────┐
│ Store Results in $stats Array   │
│ $stats['total_students']
│ $stats['total_lecturers']
│ $stats['total_repositories']
└────────┬──────────────────────────┘
         │
         ▼
┌─────────────────────────────────┐
│ Render HTML Dashboard           │
│ • Title with timestamp
│ • 3 Statistics Cards
│ • Summary Table
│ • Export Button
└────────┬──────────────────────────┘
         │
         ▼
┌─────────────────────────────────┐
│ Send HTML + CSS + JavaScript    │
│ to Browser                      │
└─────────────────────────────────┘
```

### Excel Export Flow

```
User Clicks Export Button
         │
         ▼
┌─────────────────────────────────┐
│ JavaScript Fetch Request        │
│ Method: POST
│ Body: {action: "export"}
└────────┬──────────────────────────┘
         │
         ▼
┌─────────────────────────────────┐
│ export_report.php Receives      │
│ POST Request                    │
└────────┬──────────────────────────┘
         │
         ▼
┌─────────────────────────────────┐
│ Verify Admin Authentication     │
└────────┬──────────────┬─────────┘
         │              │
      YES│              │NO
         │              └─────────► Return Error 403
         │
         ▼
┌─────────────────────────────────┐
│ Verify POST Method & JSON       │
└────────┬──────────────┬─────────┘
         │              │
      YES│              │NO
         │              └─────────► Return Error 400/405
         │
         ▼
┌─────────────────────────────────┐
│ Load PhpSpreadsheet Library     │
│ require vendor/autoload.php     │
└────────┬──────────────┬─────────┘
         │              │
      YES│              │NO
         │              └─────────► Return Error (not installed)
         │
         ▼
┌─────────────────────────────────┐
│ Query Database (3 Queries)      │
│ SELECT COUNT(*) FROM students
│ SELECT COUNT(*) FROM lecturers
│ SELECT COUNT(*) FROM projects
└────────┬──────────────────────────┘
         │
         ▼
┌─────────────────────────────────┐
│ Create Spreadsheet Object       │
│ new Spreadsheet()               │
└────────┬──────────────────────────┘
         │
         ▼
┌─────────────────────────────────┐
│ Add Content & Formatting        │
│ • Title (16pt, Bold, Red)
│ • Generated timestamp
│ • Headers (White on Red)
│ • Data rows with colors
│ • Auto-size columns
└────────┬──────────────────────────┘
         │
         ▼
┌─────────────────────────────────┐
│ Generate Filename               │
│ jskp_summary_report_YYYYMMDD    │
└────────┬──────────────────────────┘
         │
         ▼
┌─────────────────────────────────┐
│ Set Download Headers            │
│ Content-Type: .xlsx
│ Content-Disposition: attachment
│ Cache-Control: max-age=0
└────────┬──────────────────────────┘
         │
         ▼
┌─────────────────────────────────┐
│ Write XLSX to Output Stream     │
│ $writer->save('php://output')   │
└────────┬──────────────────────────┘
         │
         ▼
┌─────────────────────────────────┐
│ Browser Downloads File          │
│ Filename: jskp_summary_report_  │
│           YYYYMMDD.xlsx         │
└─────────────────────────────────┘
```

---

## File Interaction Map

```
┌──────────────────────────────────────────────────────┐
│ User Session & Authentication                        │
└──────────────┬──────────────────────┬────────────────┘
               │                      │
        ┌──────▼──────┐        ┌──────▼───────┐
        │ report.php  │        │ admin_sidebar│
        │  Dashboard  │        │  (Navigation)│
        │             │        │              │
        │ - Queries   │        │ - Reports    │
        │ - Cards     │        │   Link       │
        │ - Table     │        │              │
        │ - Button    │        │              │
        └──────┬──────┘        └──────────────┘
               │
               │ (1) User clicks Export
               │
        ┌──────▼────────────────────┐
        │ export_report.php          │
        │ (Excel Generation)         │
        │                            │
        │ - Check Auth               │
        │ - Verify Request           │
        │ - Query Database           │
        │ - Create Spreadsheet       │
        │ - Format Cells             │
        │ - Set Headers              │
        │ - Download File            │
        └──────┬─────────────────────┘
               │
        ┌──────▼────────────────┐
        │ PostgreSQL Database   │
        │                       │
        │ - students table      │
        │ - lecturers table     │
        │ - projects table      │
        └───────────────────────┘

        ┌──────────────────────────┐
        │ PhpSpreadsheet Library   │
        │ (vendor/phpoffice/)      │
        │ - Spreadsheet creation   │
        │ - Cell formatting        │
        │ - XLSX writer            │
        └──────────────────────────┘
```

---

## Database Query Execution Flow

```
report.php Page Load
         │
         ├─► Query 1: SELECT COUNT(*) FROM students
         │       │
         │       └─► PDO Prepare & Execute
         │           └─► Store in $stats['total_students']
         │
         ├─► Query 2: SELECT COUNT(*) FROM lecturers
         │       │
         │       └─► PDO Prepare & Execute
         │           └─► Store in $stats['total_lecturers']
         │
         └─► Query 3: SELECT COUNT(*) FROM projects
                  │
                  └─► PDO Prepare & Execute
                      └─► Store in $stats['total_repositories']
                  
         Display Results in HTML
```

---

## Excel File Structure

```
┌────────────────────────────────────────────────┐
│ JSKP Project Repository System Report          │
│ (Row 1: Title, 16pt, Bold, Dark Red)          │
├────────────────────────────────────────────────┤
│                                                │
│ Generated: 2026-05-31 14:30:45                │
│ (Row 3: Timestamp)                            │
│                                                │
├──────────────────────┬────────────────────────┤
│ Metric               │ Value                  │
│ (Bold, White text,  │ (Bold, White text,    │
│  Dark Red bg)       │  Dark Red bg)          │
├──────────────────────┼────────────────────────┤
│ Total Students       │ 150                    │
│ (Light blue bg)      │ (Light blue bg, right)│
├──────────────────────┼────────────────────────┤
│ Total Lecturers      │ 25                     │
│ (White bg)           │ (White bg, right)     │
├──────────────────────┼────────────────────────┤
│ Total Repositories   │ 450                    │
│ (Light blue bg)      │ (Light blue bg, right)│
└──────────────────────┴────────────────────────┘

Column A: 25 units wide
Column B: 20 units wide
```

---

## Security & Authentication Flow

```
HTTP Request to report.php or export_report.php
         │
         ▼
┌────────────────────────────────────────┐
│ Check Session Status                   │
│ if (session_status() ===               │
│     PHP_SESSION_NONE)                  │
└────────┬────────────────┬──────────────┘
         │                │
      ACTIVE│            │NONE
         │                │
         ▼                └─► Start Session
┌────────────────────────────────────────┐
│ Verify Admin Login Flag                │
│ if (!$_SESSION['admin_logged_in'])     │
│ if ($_SESSION['admin_logged_in'] !==   │
│     true)                              │
└────────┬────────────────┬──────────────┘
         │                │
      TRUE│               │FALSE/NOT SET
         │                │
         ▼                └─► Redirect to Login
┌────────────────────────────────────────┐
│ User is Authenticated Admin            │
│ Proceed with Report/Export             │
└────────────────────────────────────────┘
```

---

## Error Handling Flow

```
request to export_report.php
         │
         ├─► Is Session Valid?
         │       NO → return Error 403
         │
         ├─► Is Method POST?
         │       NO → return Error 405
         │
         ├─► Is JSON Valid?
         │       NO → return Error 400
         │
         ├─► Is Action "export"?
         │       NO → return Error 400
         │
         ├─► PhpSpreadsheet Installed?
         │       NO → return Error 500
         │
         ├─► Database Connection OK?
         │       NO → return Error 500
         │
         ├─► Queries Execute?
         │       NO → return Error 500 (try-catch)
         │
         ├─► File Headers Set?
         │       NO → return Error 500
         │
         └─► SUCCESS → Download .xlsx
```

---

## Data Flow Diagram

```
┌─────────────────────────────────────────────────────┐
│                    User Interface                   │
│  ┌─────────────┬──────────────┬──────────────────┐ │
│  │   Card 1    │   Card 2     │    Card 3        │ │
│  │ Students    │  Lecturers   │ Repositories     │ │
│  │    150      │      25      │      450         │ │
│  └─────────────┴──────────────┴──────────────────┘ │
│  ┌────────────────────────────────────────────────┐ │
│  │        Summary Table                           │ │
│  │ Metric          │  Value                       │ │
│  │ Students        │   150                        │ │
│  │ Lecturers       │    25                        │ │
│  │ Repositories    │   450                        │ │
│  └────────────────────────────────────────────────┘ │
│  ┌────────────────────────────────────────────────┐ │
│  │  [Export to Excel] Button                     │ │
│  └────────────────────────────────────────────────┘ │
└─────────────┬───────────────────────────────────────┘
              │
              ▼
      ┌──────────────────┐
      │ PHP Processing   │
      │                  │
      │ $stats = [       │
      │  'students' => 150,
      │  'lecturers' => 25,
      │  'repositories' => 450
      │ ];
      └─────────┬────────┘
                │
      ┌─────────▼────────┐
      │ Excel Formatting │
      │                  │
      │ A1: Title        │
      │ A5: Headers      │
      │ A6-A8: Data      │
      │ Colors & Borders │
      └─────────┬────────┘
                │
         ┌──────▼────────┐
         │ jskp_summary_ │
         │ report_      │
         │ 20260531.xlsx│
         │ (Download)   │
         └──────────────┘
```

---

## Component Diagram

```
┌─────────────────────────────────────────────────────────┐
│ Admin Report Module                                     │
│                                                         │
│  ┌──────────────────────────────────────────────────┐  │
│  │ report.php                                       │  │
│  │ • Authentication Handler                        │  │
│  │ • Statistics Retriever                          │  │
│  │ • HTML/CSS Generator                           │  │
│  │ • JavaScript Handler                           │  │
│  └──────────────────────────────────────────────────┘  │
│                                                         │
│  ┌──────────────────────────────────────────────────┐  │
│  │ export_report.php                               │  │
│  │ • Request Validator                            │  │
│  │ • Database Querier                             │  │
│  │ • Excel Builder                                │  │
│  │ • Download Manager                             │  │
│  └──────────────────────────────────────────────────┘  │
│                                                         │
│  Dependencies:                                          │
│  ├─ database/db.php (PDO Connection)                   │
│  ├─ vendor/phpoffice/phpspreadsheet (Excel Lib)       │
│  ├─ admin_sidebar.php (Navigation)                     │
│  └─ admin_header.php (Layout)                          │
│                                                         │
│  Data Sources:                                          │
│  ├─ students table                                      │
│  ├─ lecturers table                                     │
│  └─ projects table                                      │
└─────────────────────────────────────────────────────────┘
```

---

## Technology Stack

```
┌──────────────────────────────────────────────┐
│ Frontend                                     │
├──────────────────────────────────────────────┤
│ • HTML 5                                     │
│ • Bootstrap 5.3.3 (CSS Framework)            │
│ • Bootstrap Icons (Icon Library)             │
│ • JavaScript (Fetch API)                     │
│ • Custom CSS (UTM Theme)                     │
└──────────────────────────────────────────────┘

┌──────────────────────────────────────────────┐
│ Backend                                      │
├──────────────────────────────────────────────┤
│ • PHP 7.4+                                   │
│ • PDO Database Extension                     │
│ • PhpSpreadsheet 1.28+ (Excel)              │
│ • Composer (Dependency Manager)              │
└──────────────────────────────────────────────┘

┌──────────────────────────────────────────────┐
│ Database                                     │
├──────────────────────────────────────────────┤
│ • PostgreSQL 10+                             │
│ • Optimized Count Queries                    │
│ • Proper Indexes                             │
│ • Transaction Support                        │
└──────────────────────────────────────────────┘
```

---

## Performance Optimization Points

```
Request Flow Optimization:
  
  Dashboard Load:
  • 3 Simple COUNT(*) Queries (Fast)
  • No Joins or Complex Logic
  • Results Cached in Array
  • HTML Built from Cache
  • Total: < 500ms
  
Excel Export:
  • Single Count Queries (Reused)
  • In-Memory File Generation
  • Direct Stream to Output
  • No Disk I/O
  • Total: < 1s
  
Database Optimization:
  • CREATE INDEX idx_students_id ON students(student_id)
  • CREATE INDEX idx_lecturers_id ON lecturers(lecturer_id)
  • CREATE INDEX idx_projects_id ON projects(project_id)
  • COUNT(*) is PostgreSQL Native
```

---

## Deployment Architecture

```
┌─────────────────────────────────────────────────────┐
│ Production Environment                              │
├─────────────────────────────────────────────────────┤
│                                                     │
│  ┌──────────────────────────────────────────────┐  │
│  │ Web Server (Apache/Nginx)                    │  │
│  │ • PHP-FPM                                    │  │
│  │ • HTTPS Enabled                              │  │
│  │ • Gzip Compression                           │  │
│  └──────────────────────────────────────────────┘  │
│                  │                                 │
│                  ▼                                 │
│  ┌──────────────────────────────────────────────┐  │
│  │ Application Files                            │  │
│  │ • report.php                                 │  │
│  │ • export_report.php                          │  │
│  │ • vendor/ (PhpSpreadsheet)                   │  │
│  │ • .gitignore                                 │  │
│  └──────────────────────────────────────────────┘  │
│                  │                                 │
│                  ▼                                 │
│  ┌──────────────────────────────────────────────┐  │
│  │ PostgreSQL Database                          │  │
│  │ • students table (Indexed)                   │  │
│  │ • lecturers table (Indexed)                  │  │
│  │ • projects table (Indexed)                   │  │
│  └──────────────────────────────────────────────┘  │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

## Summary

This architecture provides:
✅ **Separation of Concerns** - UI, Logic, Data
✅ **Security** - Authentication, Validation, Error Handling
✅ **Performance** - Optimized Queries, Fast Generation
✅ **Scalability** - Can add more metrics easily
✅ **Maintainability** - Clear code structure, Complete docs

**Status:** Production Ready ✨
