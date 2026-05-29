<?php
/**
 * Database Initialization Script
 * Run this once to initialize the PostgreSQL database with schema and encrypted sample data.
 */

require __DIR__ . '/db.php';

try {
    initializeDatabase($db);

    // Insert encrypted sample users
    $sampleUsers = [
        ['name' => 'Admin',          'email' => 'admin@example.com',  'password' => 'password', 'role' => 'admin',    'created_by' => null],
        ['name' => 'Lecturer',       'email' => 'lect@example.com',   'password' => 'password', 'role' => 'lecturer', 'created_by' => 1],
        ['name' => 'Student',        'email' => 'student@example.com','password' => 'password', 'role' => 'student',  'created_by' => 1],
    ];

    $insertUser = $db->prepare(
        'INSERT INTO users (user_id, name_encrypted, email_hash, email_encrypted, password_hash, role, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?)
         ON CONFLICT (user_id) DO NOTHING'
    );

    $uid = 1;
    foreach ($sampleUsers as $u) {
        $insertUser->execute([
            $uid,
            encryptData($u['name']),
            hashEmail($u['email']),
            encryptData($u['email']),
            password_hash($u['password'], PASSWORD_BCRYPT),
            $u['role'],
            $u['created_by']
        ]);
        $uid++;
    }

    $insertLecturer = $db->prepare(
        'INSERT INTO lecturers (user_id, staff_id, department)
         VALUES (?, ?, ?)
         ON CONFLICT (user_id) DO NOTHING'
    );
    $insertLecturer->execute([2, 'UTM-L001', 'Faculty of Computing']);

    $insertStudent = $db->prepare(
        'INSERT INTO students (user_id, matric_no, course, intake)
         VALUES (?, ?, ?, ?)
         ON CONFLICT (user_id) DO NOTHING'
    );
    $insertStudent->execute([3, 'A23CS0001', 'Software Engineering', '2023/2024']);

    // Insert encrypted sample projects
    $insertProject = $db->prepare(
        'INSERT INTO projects (project_id, title_encrypted, description_encrypted, category_encrypted, lecturer_id, study_year)
         VALUES (?, ?, ?, ?, ?, ?)
         ON CONFLICT (project_id) DO NOTHING'
    );
    $insertProject->execute([101, encryptData('JSKP System A'), encryptData('Project A description'), encryptData('Web Application'), 2, 3]);
    $insertProject->execute([102, encryptData('JSKP System B'), encryptData('Project B description'), encryptData('Research Project'), 2, 3]);

    // Insert sample project members
    $insertMember = $db->prepare(
        'INSERT INTO project_members (id, project_id, user_id, role)
         VALUES (?, ?, ?, ?)
         ON CONFLICT (project_id, user_id) DO NOTHING'
    );
    $insertMember->execute([1, 101, 3, 'leader']);
    $insertMember->execute([2, 102, 3, 'member']);

    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
        $db->exec("SELECT setval(pg_get_serial_sequence('users', 'user_id'), COALESCE((SELECT MAX(user_id) FROM users), 1))");
        $db->exec("SELECT setval(pg_get_serial_sequence('students', 'student_id'), COALESCE((SELECT MAX(student_id) FROM students), 1))");
        $db->exec("SELECT setval(pg_get_serial_sequence('lecturers', 'lecturer_id'), COALESCE((SELECT MAX(lecturer_id) FROM lecturers), 1))");
        $db->exec("SELECT setval(pg_get_serial_sequence('projects', 'project_id'), COALESCE((SELECT MAX(project_id) FROM projects), 1))");
        $db->exec("SELECT setval(pg_get_serial_sequence('project_members', 'id'), COALESCE((SELECT MAX(id) FROM project_members), 1))");
    }
    
    echo "Database initialized successfully.\n";
    echo "Sample users created (encrypted):\n";
    echo "  - admin@example.com / password (Admin)\n";
    echo "  - lect@example.com / password (Lecturer)\n";
    echo "  - student@example.com / password (Student)\n";
    
} catch (PDOException $e) {
    echo "Error initializing database: " . $e->getMessage() . "\n";
    exit(1);
}
