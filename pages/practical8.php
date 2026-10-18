<?php
/**
 * ============================================================================
 * StudentHub Portal - Practical 8: MySQL Schema Design, ER Model & Connectivity
 * ============================================================================
 * Student Name  : Shubham Desai
 * Enrollment No : 25DCE020
 * Semester      : 3rd Semester Computer Engineering
 * Institute     : Faculty of Technology and Engineering (FTE), CHARUSAT
 * Course        : Web Development Frameworks (ITUE203)
 * Subject       : Practical 8 - MySQL Schema Design, ER Model and Database Connectivity
 * ============================================================================
 * Problem Definition (from Syllabus):
 * Design the database for StudentHub with students, events and registrations.
 * Create PHP database connection using MySQL statements / PDO Prepared Statements.
 * ============================================================================
 */

// Include the database connection file
require_once __DIR__ . '/db.php';

// Flash message variables for user feedback
$successMsg = "";
$errorMsg   = "";

// Check database connection
$dbInfo = testDBConnection();
$pdo    = $dbInfo['connected'] ? getPDOConnection(true) : null;

// Handle Form Submissions (Simple Beginner-Friendly POST handling)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';

    // Action 1: Auto-initialize Database & Tables with 1 click
    if ($action === 'init_database') {
        $initResult = autoInitializeDatabase();
        if ($initResult['success']) {
            $successMsg = $initResult['message'];
            // Refresh connection
            $dbInfo = testDBConnection();
            $pdo = $dbInfo['connected'] ? getPDOConnection(true) : null;
        } else {
            $errorMsg = $initResult['message'];
        }
    }

    // Action 2: Add New Student using Prepared Statement
    if ($action === 'add_student' && $pdo) {
        $roll_no   = trim($_POST['roll_no'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $mobile    = trim($_POST['mobile'] ?? '');
        $course    = trim($_POST['course'] ?? '');
        $semester  = trim($_POST['semester'] ?? '3rd Semester');

        if (empty($roll_no) || empty($full_name) || empty($email)) {
            $errorMsg = "Please fill in all required fields (Roll No, Name, and Email).";
        } else {
            try {
                // Prepared statement to prevent SQL Injection
                $sql = "INSERT INTO students (roll_no, full_name, email, mobile, course, semester) 
                        VALUES (:roll_no, :full_name, :email, :mobile, :course, :semester)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':roll_no'   => $roll_no,
                    ':full_name' => $full_name,
                    ':email'     => $email,
                    ':mobile'    => $mobile,
                    ':course'    => $course,
                    ':semester'  => $semester
                ]);
                $successMsg = "Student added successfully via PDO Prepared Statement!";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $errorMsg = "A student with this Roll No or Email already exists!";
                } else {
                    $errorMsg = "Database Error: " . $e->getMessage();
                }
            }
        }
    }

    // Action 3: Register Student for Event using Prepared Statement
    if ($action === 'register_event' && $pdo) {
        $student_id = (int)($_POST['student_id'] ?? 0);
        $event_id   = (int)($_POST['event_id'] ?? 0);
        $notes      = trim($_POST['notes'] ?? 'General Registration');

        if ($student_id <= 0 || $event_id <= 0) {
            $errorMsg = "Please select both a valid Student and an Event.";
        } else {
            try {
                // Check if already registered (Composite Unique constraint test)
                $checkStmt = $pdo->prepare("SELECT * FROM registrations WHERE student_id = :sid AND event_id = :eid");
                $checkStmt->execute([':sid' => $student_id, ':eid' => $event_id]);

                if ($checkStmt->rowCount() > 0) {
                    $errorMsg = "This student is already registered for this event! (Unique constraint enforced).";
                } else {
                    // Safe Insert with Prepared Statement
                    $insertSql = "INSERT INTO registrations (student_id, event_id, attendance_status, notes) 
                                  VALUES (:sid, :eid, 'Registered', :notes)";
                    $insertStmt = $pdo->prepare($insertSql);
                    $insertStmt->execute([
                        ':sid'   => $student_id,
                        ':eid'   => $event_id,
                        ':notes' => $notes
                    ]);
                    $successMsg = "Student enrolled in event successfully! (Registration ID: #" . $pdo->lastInsertId() . ")";
                }
            } catch (PDOException $e) {
                $errorMsg = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch Records for Display (Simple SELECT Queries)
$studentsList      = [];
$eventsList        = [];
$registrationsList = [];

if ($pdo) {
    try {
        // 1. Fetch Students
        $studentsList = $pdo->query("SELECT * FROM students ORDER BY student_id ASC")->fetchAll();

        // 2. Fetch Events
        $eventsList = $pdo->query("SELECT * FROM events ORDER BY event_date ASC")->fetchAll();

        // 3. Fetch Registrations using simple INNER JOIN query
        $joinSql = "
            SELECT 
                r.registration_id,
                r.registration_date,
                r.attendance_status,
                r.notes,
                s.roll_no,
                s.full_name AS student_name,
                s.course,
                e.title AS event_title,
                e.category AS event_category,
                e.event_date,
                e.venue
            FROM registrations r
            INNER JOIN students s ON r.student_id = s.student_id
            INNER JOIN events e ON r.event_id = e.event_id
            ORDER BY r.registration_id DESC
        ";
        $registrationsList = $pdo->query($joinSql)->fetchAll();

    } catch (PDOException $e) {
        $errorMsg = "Failed to load database records: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 8: MySQL Schema, ER Model & Database Connectivity - StudentHub</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
    <style>
        /* Simple, clean helper styles for Practical 8 */
        .status-badge-online {
            display: inline-block;
            background-color: #27ae60;
            color: #ffffff;
            font-weight: bold;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .status-badge-offline {
            display: inline-block;
            background-color: #e74c3c;
            color: #ffffff;
            font-weight: bold;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .er-box {
            background-color: var(--card-bg);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            flex: 1;
            min-width: 250px;
        }
        .er-box h4 {
            color: var(--accent-color);
            border-bottom: 2px solid var(--accent-color);
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .er-box ul {
            list-style: none;
            padding-left: 0;
            font-size: 0.9rem;
        }
        .er-box li {
            padding: 4px 0;
            border-bottom: 1px dashed var(--border-color);
            display: flex;
            justify-content: space-between;
        }
        .key-pk {
            color: #d35400;
            font-weight: bold;
            font-size: 0.75rem;
            background: #fdebd0;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .key-fk {
            color: #2980b9;
            font-weight: bold;
            font-size: 0.75rem;
            background: #ebf5fb;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .code-box {
            background-color: #2c3e50;
            color: #ecf0f1;
            padding: 12px;
            border-radius: 6px;
            font-family: Consolas, "Courier New", monospace;
            font-size: 0.85rem;
            overflow-x: auto;
            margin-top: 10px;
        }
        .q-card {
            background-color: var(--card-bg);
            border-left: 4px solid var(--accent-color);
            padding: 12px 16px;
            margin-bottom: 15px;
            border-radius: 0 6px 6px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .q-card h4 {
            color: var(--text-color);
            margin-bottom: 6px;
        }
        .q-card p {
            font-size: 0.92rem;
            color: var(--text-color);
            line-height: 1.5;
        }
        .step-num {
            display: inline-block;
            width: 26px;
            height: 26px;
            line-height: 26px;
            background-color: var(--accent-color);
            color: white;
            text-align: center;
            border-radius: 50%;
            font-weight: bold;
            margin-right: 8px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

    <!-- Header & Navigation -->
    <header>
        <h1><a href="index.html">StudentHub</a></h1>
        <button id="hamburger-btn" class="hamburger-btn" aria-label="Menu">☰</button>
        <nav>
            <ul id="nav-list">
                <li><a href="index.html">Home</a></li>
                <li><a href="about.html">About</a></li>
                <li><a href="dashboard.html">Dashboard</a></li>
                <li><a href="course.html">Courses</a></li>
                <li><a href="attendance.html">Attendance</a></li>
                <li><a href="assignment.html">Assignments</a></li>
                <li><a href="result.html">Results</a></li>
                <li><a href="event.html">Events</a></li>
                <li><a href="faq.html">FAQ</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="login.html">Login</a></li>
                <li><a href="register.php">Register</a></li>
                <li><a href="view_records.php">Stored Records</a></li>
                <li><a href="practical8.php" class="active">Practical 8</a></li>
            </ul>
        </nav>
        <button id="themeBtn" class="btn">Dark Mode</button>
    </header>

    <!-- Main Content -->
    <main id="main-content" class="container">

        <!-- Breadcrumbs -->
        <nav class="breadcrumbs">
            <a href="index.html">Home</a> &gt; <span>Practical 8: MySQL Schema, ER Model & Database Connectivity</span>
        </nav>

        <!-- Academic Submission Header -->
        <section class="card" style="border-top: 4px solid var(--accent-color); margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <span class="badge badge-blue">Academic Lab Submission</span>
                    <h2 style="margin-top: 8px; color: var(--accent-color);">Practical 8: MySQL Schema Design, ER Model and Database Connectivity</h2>
                    <p style="margin-top: 4px; font-size: 0.95rem;">
                        <strong>Problem Definition:</strong> Design the database for StudentHub with <code>students</code>, <code>events</code>, and <code>registrations</code>. Create PHP database connection using MySQL statements / PDO Prepared Statements.
                    </p>
                </div>
                <div>
                    <?php if ($dbInfo['connected']): ?>
                        <span class="status-badge-online">🟢 MySQL: Connected</span>
                    <?php else: ?>
                        <span class="status-badge-offline">🔴 MySQL: Offline</span>
                    <?php endif; ?>
                </div>
            </div>

            <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid var(--border-color); display: flex; gap: 20px; flex-wrap: wrap; font-size: 0.88rem;">
                <div><strong>👨‍🎓 Student:</strong> Shubham Desai (25DCE020)</div>
                <div><strong>🏛️ Branch:</strong> 3rd Semester &bull; Computer Engineering</div>
                <div><strong>📖 Course:</strong> Web Development Frameworks (ITUE203)</div>
                <div><strong>🎯 CO Mapping:</strong> CO1, CO5</div>
            </div>
        </section>

        <!-- Flash Messages -->
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;">
                <strong>Success!</strong> <?php echo htmlspecialchars($successMsg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <strong>Notice:</strong> <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>

        <!-- SECTION 1: Database Connection Status & Settings -->
        <section class="card" style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <h3>🔌 1. Database Connection Status (PHP PDO)</h3>
                <form method="POST" onsubmit="return confirm('Do you want to initialize or reload tables and seed data in studenthub_db?');">
                    <input type="hidden" name="action" value="init_database">
                    <button type="submit" class="btn" style="background-color: #27ae60;">⚡ Initialize / Reset Database & Tables</button>
                </form>
            </div>

            <div class="grid" style="margin-top: 15px;">
                <div class="card" style="padding: 10px; text-align: center;">
                    <div style="font-weight: bold; color: var(--accent-color);">Database Host</div>
                    <div><?php echo htmlspecialchars(DB_HOST); ?>:<?php echo htmlspecialchars(DB_PORT); ?></div>
                </div>
                <div class="card" style="padding: 10px; text-align: center;">
                    <div style="font-weight: bold; color: var(--accent-color);">Database Name</div>
                    <div><?php echo htmlspecialchars(DB_NAME); ?></div>
                </div>
                <div class="card" style="padding: 10px; text-align: center;">
                    <div style="font-weight: bold; color: var(--accent-color);">DB Username</div>
                    <div><?php echo htmlspecialchars(DB_USER); ?></div>
                </div>
                <div class="card" style="padding: 10px; text-align: center;">
                    <div style="font-weight: bold; color: var(--accent-color);">Connection Status</div>
                    <div>
                        <?php echo $dbInfo['connected'] ? '✅ Online (PDO)' : '❌ Offline (Start XAMPP)'; ?>
                    </div>
                </div>
            </div>

            <?php if (!$dbInfo['connected']): ?>
                <div class="alert alert-info" style="margin-top: 15px;">
                    <strong>ℹ️ MySQL Offline Notice:</strong> <?php echo htmlspecialchars($dbInfo['message']); ?><br>
                    To test the database connection:
                    <ol style="margin-left: 20px; margin-top: 5px;">
                        <li>Open the <strong>XAMPP Control Panel</strong>.</li>
                        <li>Click <strong>Start</strong> next to <strong>Apache</strong> and <strong>MySQL</strong>.</li>
                        <li>Click the <strong>Initialize / Reset Database & Tables</strong> button above or refresh this page.</li>
                    </ol>
                </div>
            <?php endif; ?>

            <div style="margin-top: 15px;">
                <p><strong>Beginner-Friendly Database Connection Code (<code>db.php</code>):</strong></p>
                <div class="code-box">// db.php - Simple PDO Connection for Practical 8
$host     = "localhost";
$dbname   = "studenthub_db";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Connection successful!
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}</div>
            </div>
        </section>

        <!-- SECTION 2: Entity-Relationship (ER) Model & Schema Design -->
        <section class="card" style="margin-bottom: 20px;">
            <h3>📐 2. Entity-Relationship (ER) Model & Schema Design</h3>
            <p style="margin-top: 5px; font-size: 0.95rem;">
                As required by the practical syllabus, the database is normalized into <strong>3rd Normal Form (3NF)</strong>. The Many-to-Many relationship between <code>students</code> and <code>events</code> is resolved using the junction/associative table <code>registrations</code>.
            </p>

            <!-- Visual ER Cards -->
            <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 15px;">
                
                <!-- Table 1: students -->
                <div class="er-box">
                    <h4>📋 Table: students [1]</h4>
                    <ul>
                        <li><span>student_id (INT)</span> <span class="key-pk">PK (Auto)</span></li>
                        <li><span>roll_no (VARCHAR 20)</span> <span>UNIQUE</span></li>
                        <li><span>full_name (VARCHAR 100)</span> <span>NOT NULL</span></li>
                        <li><span>email (VARCHAR 100)</span> <span>UNIQUE</span></li>
                        <li><span>mobile (VARCHAR 15)</span> <span>NOT NULL</span></li>
                        <li><span>course (VARCHAR 50)</span> <span>NOT NULL</span></li>
                        <li><span>semester (VARCHAR 20)</span> <span>DEFAULT</span></li>
                    </ul>
                </div>

                <!-- Table 2: registrations (Associative) -->
                <div class="er-box" style="border-color: #8e44ad;">
                    <h4 style="color: #8e44ad; border-color: #8e44ad;">🔗 Table: registrations [M]</h4>
                    <ul>
                        <li><span>registration_id (INT)</span> <span class="key-pk">PK (Auto)</span></li>
                        <li><span>student_id (INT)</span> <span class="key-fk">FK -> students</span></li>
                        <li><span>event_id (INT)</span> <span class="key-fk">FK -> events</span></li>
                        <li><span>registration_date</span> <span>TIMESTAMP</span></li>
                        <li><span>attendance_status</span> <span>VARCHAR</span></li>
                        <li><span>notes</span> <span>VARCHAR</span></li>
                        <li style="font-size: 0.8rem; color: #8e44ad; font-weight: bold;"><span>UNIQUE (student_id, event_id)</span></li>
                    </ul>
                </div>

                <!-- Table 3: events -->
                <div class="er-box">
                    <h4>🎪 Table: events [1]</h4>
                    <ul>
                        <li><span>event_id (INT)</span> <span class="key-pk">PK (Auto)</span></li>
                        <li><span>title (VARCHAR 150)</span> <span>NOT NULL</span></li>
                        <li><span>category (VARCHAR 50)</span> <span>NOT NULL</span></li>
                        <li><span>event_date (DATE)</span> <span>NOT NULL</span></li>
                        <li><span>event_time (VARCHAR 30)</span> <span>NOT NULL</span></li>
                        <li><span>venue (VARCHAR 100)</span> <span>NOT NULL</span></li>
                        <li><span>fee (DECIMAL 8,2)</span> <span>DEFAULT 0</span></li>
                    </ul>
                </div>

            </div>

            <div style="background: rgba(41, 128, 185, 0.08); padding: 12px; border-radius: 6px; font-size: 0.9rem;">
                <strong>Relationship Explanation:</strong>
                One student can register for multiple events, and one event can have multiple students (Many-to-Many). The <code>registrations</code> table connects them using foreign keys with <code>ON DELETE CASCADE</code> so no orphan records are left behind.
            </div>
        </section>

        <!-- SECTION 3: Simple Forms using PDO Prepared Statements -->
        <section class="card" style="margin-bottom: 20px;">
            <h3>🛡️ 3. Try It Out: PDO Prepared Statements (Live Forms)</h3>
            <p style="margin-top: 5px; font-size: 0.95rem;">
                Prepared statements compile the SQL query first on the database server, and bind user values separately as safe data parameters. This completely stops <strong>SQL Injection (SQLi)</strong> attacks.
            </p>

            <div class="grid" style="margin-top: 15px;">
                
                <!-- Form 1: Event Registration -->
                <div class="card" style="border: 1px solid var(--border-color); padding: 15px;">
                    <h4 style="color: var(--accent-color); margin-bottom: 10px;">Enrolling Student in Event (INSERT Demo)</h4>
                    <form method="POST" action="practical8.php">
                        <input type="hidden" name="action" value="register_event">

                        <div class="form-group">
                            <label for="student_id">Select Student:</label>
                            <select id="student_id" name="student_id" required>
                                <option value="">-- Choose Student --</option>
                                <?php foreach ($studentsList as $s): ?>
                                    <option value="<?php echo (int)$s['student_id']; ?>">
                                        <?php echo htmlspecialchars($s['roll_no'] . ' - ' . $s['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="event_id">Select Event:</label>
                            <select id="event_id" name="event_id" required>
                                <option value="">-- Choose Event --</option>
                                <?php foreach ($eventsList as $ev): ?>
                                    <option value="<?php echo (int)$ev['event_id']; ?>">
                                        <?php echo htmlspecialchars($ev['title'] . ' (' . $ev['category'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="notes">Notes / Team Name:</label>
                            <input type="text" id="notes" name="notes" placeholder="e.g. Solo / Team Hub" value="Practical 8 Live Demo">
                        </div>

                        <button type="submit" class="btn" <?php echo !$pdo ? 'disabled' : ''; ?>>Enroll Student</button>
                    </form>
                </div>

                <!-- Form 2: Add New Student -->
                <div class="card" style="border: 1px solid var(--border-color); padding: 15px;">
                    <h4 style="color: var(--accent-color); margin-bottom: 10px;">Add New Student (INSERT Demo)</h4>
                    <form method="POST" action="practical8.php">
                        <input type="hidden" name="action" value="add_student">

                        <div class="form-group">
                            <label for="roll_no">Roll Number:</label>
                            <input type="text" id="roll_no" name="roll_no" placeholder="e.g. 25DCE025" required>
                        </div>

                        <div class="form-group">
                            <label for="full_name">Full Name:</label>
                            <input type="text" id="full_name" name="full_name" placeholder="e.g. Ankit Sharma" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address:</label>
                            <input type="email" id="email" name="email" placeholder="e.g. ankit@studenthub.edu" required>
                        </div>

                        <div class="form-group">
                            <label for="course">Course / Branch:</label>
                            <input type="text" id="course" name="course" value="Computer Engineering">
                        </div>

                        <button type="submit" class="btn btn-secondary" <?php echo !$pdo ? 'disabled' : ''; ?>>Add Student</button>
                    </form>
                </div>

            </div>
        </section>

        <!-- SECTION 4: Live Data Tables (SELECT Queries & JOINs) -->
        <section class="card" style="margin-bottom: 20px;">
            <h3>📊 4. Live Data Records from MySQL</h3>
            <p style="margin-top: 5px; font-size: 0.95rem;">
                Records fetched from MySQL using PDO. Notice how the Event Registrations table joins data from both the <code>students</code> and <code>events</code> tables.
            </p>

            <!-- Table 1: Joined Registrations -->
            <h4 style="margin-top: 15px; color: var(--accent-color);">🔗 Table: registrations (Joined with students & events)</h4>
            <div class="table-responsive" style="margin-top: 10px;">
                <table>
                    <thead>
                        <tr>
                            <th>Reg ID</th>
                            <th>Student Roll & Name</th>
                            <th>Event Title</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($registrationsList)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 15px;">
                                    <?php echo $dbInfo['connected'] ? 'No registrations found.' : 'Database offline. Please start MySQL in XAMPP to view records.'; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($registrationsList as $reg): ?>
                                <tr>
                                    <td><strong>#<?php echo (int)$reg['registration_id']; ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($reg['student_name']); ?></strong><br>
                                        <small style="color: var(--text-muted);"><?php echo htmlspecialchars($reg['roll_no']); ?> &bull; <?php echo htmlspecialchars($reg['course']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($reg['event_title']); ?></td>
                                    <td><span class="badge badge-blue"><?php echo htmlspecialchars($reg['event_category']); ?></span></td>
                                    <td><?php echo htmlspecialchars($reg['event_date']); ?></td>
                                    <td><span class="badge badge-green"><?php echo htmlspecialchars($reg['attendance_status']); ?></span></td>
                                    <td><small><?php echo htmlspecialchars($reg['notes']); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Table 2: Students List -->
            <h4 style="margin-top: 20px; color: var(--accent-color);">👥 Table: students (Total: <?php echo count($studentsList); ?>)</h4>
            <div class="table-responsive" style="margin-top: 10px;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Roll No</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Course</th>
                            <th>Semester</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($studentsList)): ?>
                            <tr><td colspan="7" style="text-align:center; padding: 15px;">No students found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($studentsList as $stu): ?>
                                <tr>
                                    <td>#<?php echo (int)$stu['student_id']; ?></td>
                                    <td><code><?php echo htmlspecialchars($stu['roll_no']); ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($stu['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($stu['email']); ?></td>
                                    <td><?php echo htmlspecialchars($stu['mobile']); ?></td>
                                    <td><?php echo htmlspecialchars($stu['course']); ?></td>
                                    <td><?php echo htmlspecialchars($stu['semester']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Table 3: Events List -->
            <h4 style="margin-top: 20px; color: var(--accent-color);">🎪 Table: events (Total: <?php echo count($eventsList); ?>)</h4>
            <div class="table-responsive" style="margin-top: 10px;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Event Title</th>
                            <th>Category</th>
                            <th>Date & Time</th>
                            <th>Venue</th>
                            <th>Fee</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($eventsList)): ?>
                            <tr><td colspan="6" style="text-align:center; padding: 15px;">No events found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($eventsList as $ev): ?>
                                <tr>
                                    <td>#<?php echo (int)$ev['event_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($ev['title']); ?></strong></td>
                                    <td><span class="badge badge-blue"><?php echo htmlspecialchars($ev['category']); ?></span></td>
                                    <td><?php echo htmlspecialchars($ev['event_date']); ?><br><small><?php echo htmlspecialchars($ev['event_time']); ?></small></td>
                                    <td><?php echo htmlspecialchars($ev['venue']); ?></td>
                                    <td><?php echo (float)$ev['fee'] > 0 ? '₹' . number_format($ev['fee'], 2) : 'Free'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- SECTION 5: Key Analysis & Viva Voce Q&A -->
        <section class="card" style="margin-bottom: 20px;">
            <h3>🎓 5. Key Practical Analysis & Viva Voce Questions</h3>
            <p style="margin-top: 5px; font-size: 0.95rem;">
                Standard answers to the key questions specified in the lab manual:
            </p>

            <div class="q-card" style="margin-top: 15px;">
                <h4>Q1: Is the schema normalized properly?</h4>
                <p>
                    <strong>Yes, the schema satisfies 3rd Normal Form (3NF):</strong><br>
                    &bull; <strong>1NF:</strong> Every column stores single atomic values (no lists or repeating groups), and each table has a Primary Key.<br>
                    &bull; <strong>2NF:</strong> Satisfies 1NF and contains no partial dependencies. The Many-to-Many relationship between students and events is factored out into the <code>registrations</code> table.<br>
                    &bull; <strong>3NF:</strong> Satisfies 2NF and contains no transitive dependencies. Non-key attributes (e.g., student name or event venue) depend only on the Primary Key of their respective tables.
                </p>
            </div>

            <div class="q-card">
                <h4>Q2: Are primary keys and foreign keys correctly defined?</h4>
                <p>
                    <strong>Yes:</strong><br>
                    &bull; Each table defines an <code>INT AUTO_INCREMENT PRIMARY KEY</code> (<code>student_id</code>, <code>event_id</code>, <code>registration_id</code>).<br>
                    &bull; The associative <code>registrations</code> table has Foreign Keys referencing <code>students(student_id)</code> and <code>events(event_id)</code> with <code>ON DELETE CASCADE</code>.<br>
                    &bull; A composite unique key <code>UNIQUE (student_id, event_id)</code> prevents a student from registering for the exact same event more than once.
                </p>
            </div>

            <div class="q-card">
                <h4>Q3: Is the database connection handled securely?</h4>
                <p>
                    <strong>Yes:</strong><br>
                    &bull; Connection is created using <strong>PHP Data Objects (PDO)</strong>.<br>
                    &bull; Handled within a structured <code>try-catch</code> block with <code>PDO::ERRMODE_EXCEPTION</code>.<br>
                    &bull; <code>utf8mb4</code> character set is enforced to prevent encoding-based exploits.<br>
                    &bull; All user inputs are bound safely via <strong>Prepared Statements</strong> (<code>$stmt->prepare()</code> and <code>$stmt->execute()</code>), which stops SQL Injection.
                </p>
            </div>

            <div class="q-card">
                <h4>Q4: What is the advantage of PDO over MySQLi?</h4>
                <p>
                    <strong>Answer:</strong> PDO supports 12 different database drivers (MySQL, PostgreSQL, SQLite, etc.) and allows named parameters (like <code>:email</code>), whereas MySQLi works only with MySQL. PDO provides a clean, unified object-oriented interface.
                </p>
            </div>
        </section>

        <!-- SECTION 6: How to Set Up in XAMPP (Step-by-Step Guide) -->
        <section class="card" style="margin-bottom: 20px;">
            <h3>🛠️ 6. Quick Setup Guide for Beginners (XAMPP & phpMyAdmin)</h3>
            <p style="margin-top: 5px; font-size: 0.95rem;">
                Follow these 4 simple steps to run Practical 8 on your local machine:
            </p>

            <div style="margin-top: 15px; display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; align-items: flex-start;">
                    <span class="step-num">1</span>
                    <div>
                        <strong>Start XAMPP:</strong> Open the XAMPP Control Panel and click <strong>Start</strong> next to <strong>Apache</strong> and <strong>MySQL</strong>.
                    </div>
                </div>
                <div style="display: flex; align-items: flex-start;">
                    <span class="step-num">2</span>
                    <div>
                        <strong>Open phpMyAdmin:</strong> In your browser, open <a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a>.
                    </div>
                </div>
                <div style="display: flex; align-items: flex-start;">
                    <span class="step-num">3</span>
                    <div>
                        <strong>Create / Import Database:</strong> Either click the <strong>⚡ Initialize / Reset Database & Tables</strong> button at the top of this page, or in phpMyAdmin create <code>studenthub_db</code> and import <code>studenthub_db.sql</code>.
                    </div>
                </div>
                <div style="display: flex; align-items: flex-start;">
                    <span class="step-num">4</span>
                    <div>
                        <strong>View Practical 8:</strong> Refresh this page to see the green connection status badge and live database records!
                    </div>
                </div>
            </div>

            <div style="margin-top: 20px; text-align: center; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                <a href="studenthub_db.sql" download="studenthub_db.sql" class="btn">📥 Download studenthub_db.sql</a>
                <a href="db.php" target="_blank" class="btn btn-secondary">📄 View db.php Code</a>
                <a href="dashboard.html" class="btn btn-secondary">Go to Dashboard</a>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 StudentHub Portal | Practical 8 Submission | Shubham Desai (25DCE020)</p>
        <p><a href="about.html">About Us</a> | <a href="contact.php">Contact</a> | <a href="practical8.php">Practical 8</a> | <a href="faq.html">FAQ</a></p>
    </footer>

</body>
</html>
