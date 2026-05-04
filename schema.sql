-- SQLite schema for FYP Submission Management System

CREATE TABLE IF NOT EXISTS users (
  user_id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  password TEXT NOT NULL,
  role TEXT CHECK (role IN ('admin', 'lecturer', 'student')),
  created_by INTEGER REFERENCES users(user_id),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS projects (
  project_id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL,
  description TEXT,
  lecturer_id INTEGER REFERENCES users(user_id),
  study_year INTEGER CHECK (study_year BETWEEN 1 AND 5),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS project_members (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER NOT NULL REFERENCES projects(project_id),
  user_id INTEGER NOT NULL REFERENCES users(user_id),
  role TEXT NOT NULL,
  UNIQUE (project_id, user_id)
);

CREATE TABLE IF NOT EXISTS files (
  file_id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER NOT NULL REFERENCES projects(project_id),
  file_name TEXT NOT NULL,
  file_path TEXT NOT NULL,
  uploaded_by INTEGER REFERENCES users(user_id),
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (project_id, file_name)
);

CREATE TABLE IF NOT EXISTS file_versions (
  version_id INTEGER PRIMARY KEY AUTOINCREMENT,
  file_id INTEGER NOT NULL REFERENCES files(file_id),
  version_number INTEGER NOT NULL,
  file_path TEXT NOT NULL,
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (file_id, version_number)
);

CREATE TABLE IF NOT EXISTS comments (
  comment_id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER NOT NULL REFERENCES projects(project_id),
  user_id INTEGER REFERENCES users(user_id),
  content TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS submissions (
  submission_id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER NOT NULL REFERENCES projects(project_id),
  submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  status TEXT CHECK (status IN ('pending','approved','rejected'))
);


-- Sample data
INSERT OR IGNORE INTO users (user_id, name, email, password, role, created_by) VALUES
  (1, 'Admin', 'admin@example.com', 'password', 'admin', NULL),
  (2, 'Lecturer', 'lect@example.com', 'password', 'lecturer', 1),
  (3, 'Student', 'student@example.com', 'password', 'student', 1);
  