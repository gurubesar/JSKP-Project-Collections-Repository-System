-- SQLite schema for FYP Submission Management System
-- Sensitive columns store encrypted data (AES-256-GCM)

CREATE TABLE IF NOT EXISTS users (
  user_id INTEGER PRIMARY KEY AUTOINCREMENT,
  name_encrypted TEXT NOT NULL,
  email_hash TEXT NOT NULL UNIQUE,
  email_encrypted TEXT NOT NULL,
  password_hash TEXT NOT NULL,
  role TEXT CHECK (role IN ('admin', 'lecturer', 'student')),
  created_by INTEGER REFERENCES users(user_id),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS students (
  student_id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL UNIQUE REFERENCES users(user_id) ON DELETE CASCADE,
  matric_no TEXT NOT NULL UNIQUE,
  course TEXT NOT NULL,
  intake TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS lecturers (
  lecturer_id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL UNIQUE REFERENCES users(user_id) ON DELETE CASCADE,
  staff_id TEXT NOT NULL UNIQUE,
  department TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS projects (
  project_id INTEGER PRIMARY KEY AUTOINCREMENT,
  title_encrypted TEXT NOT NULL,
  description_encrypted TEXT,
  category_encrypted TEXT,
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
  file_name_encrypted TEXT NOT NULL,
  file_path_encrypted TEXT NOT NULL,
  uploaded_by INTEGER REFERENCES users(user_id),
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (project_id, file_name_encrypted)
);

CREATE TABLE IF NOT EXISTS file_versions (
  version_id INTEGER PRIMARY KEY AUTOINCREMENT,
  file_id INTEGER NOT NULL REFERENCES files(file_id),
  version_number INTEGER NOT NULL,
  file_path_encrypted TEXT NOT NULL,
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (file_id, version_number)
);

CREATE TABLE IF NOT EXISTS comments (
  comment_id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER NOT NULL REFERENCES projects(project_id),
  user_id INTEGER REFERENCES users(user_id),
  content_encrypted TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS comment_visibility (
  comment_id INTEGER NOT NULL REFERENCES comments(comment_id),
  user_id INTEGER NOT NULL REFERENCES users(user_id),
  hidden_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (comment_id, user_id)
);

CREATE TABLE IF NOT EXISTS submissions (
  submission_id INTEGER PRIMARY KEY AUTOINCREMENT,
  project_id INTEGER NOT NULL REFERENCES projects(project_id),
  submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  status TEXT CHECK (status IN ('pending','approved','rejected'))
);

CREATE TABLE IF NOT EXISTS notifications (
  notification_id INTEGER PRIMARY KEY AUTOINCREMENT,
  recipient_user_id INTEGER NOT NULL REFERENCES users(user_id),
  sender_user_id INTEGER REFERENCES users(user_id),
  project_id INTEGER REFERENCES projects(project_id),
  notification_type TEXT NOT NULL,
  message TEXT NOT NULL,
  is_read INTEGER NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
