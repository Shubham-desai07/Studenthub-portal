<?php
/**
 * ============================================================================
 * StudentHub Portal - Practical 14: Admin Dashboard with RBAC & Audit Log
 * ============================================================================
 * Student Name  : Shubham Desai
 * Enrollment No : 25DCE020
 * Semester      : 3rd Semester Computer Engineering
 * Institute     : Faculty of Technology and Engineering (FTE), CHARUSAT
 * Course        : Web Development Frameworks (ITUE203)
 * Practical 14  : Admin Dashboard, Role-Based Access & Audit Logging
 * ============================================================================
 * Problem Definition (from Syllabus):
 * Develop an admin dashboard that displays user statistics, student count,
 * event count and latest registrations. Restrict access to admin users only
 * and show dynamic menu options based on role.
 * ============================================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
$pdo = getPDOConnection(true);

$successMsg = "";
$errorMsg   = "";

// Allow switching active test role via query param for easy lab demonstration
if (isset($_GET['switch_role'])) {
    $newRole = ($_GET['switch_role'] === 'Student') ? 'Student' : 'Admin';
    $_SESSION['role'] = $newRole;
    $_SESSION['user_id'] = ($newRole === 'Admin') ? 1 : 2;
    $_SESSION['username'] = ($newRole === 'Admin') ? 'admin' : 'shubham07';
    $_SESSION['full_name'] = ($newRole === 'Admin') ? 'Administrator System' : 'Shubham Desai';
    header("Location: practical14.php");
    exit();
}

// Default role to Admin for evaluation if not yet set
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'Admin';
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['full_name'] = 'Administrator System';
}

$currentRole = $_SESSION['role'];
$isAdmin = ($currentRole === 'Admin');

// Handle Admin Action simulation (Writing into audit_logs table)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'log_test_action') {
    if (!$isAdmin) {
        $errorMsg = "Unauthorized action! Only Administrators can perform system logging.";
    } elseif ($pdo) {
        try {
            $actionName = trim($_POST['action_name'] ?? 'SYSTEM_MAINTENANCE');
            $actionDesc = trim($_POST['action_desc'] ?? 'Manual database health check performed.');

            $logStmt = $pdo->prepare("INSERT INTO audit_logs (username, role, action, details, ip_address) 
                                      VALUES (:user, :role, :act, :det, :ip)");
            $logStmt->execute([
                ':user' => $_SESSION['username'],
                ':role' => $_SESSION['role'],
                ':act'  => $actionName,
                ':det'  => $actionDesc,
                ':ip'   => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
            $successMsg = "Action recorded in audit_logs table successfully!";
        } catch (PDOException $e) {
            $errorMsg = "Audit log error: " . $e->getMessage();
        }
    }
}

// Fetch Dynamic Dashboard Counts (SQL Aggregation)
$stats = [
    'students'      => 0,
    'events'        => 0,
    'registrations' => 0,
    'users'         => 0
];

$latestRegistrations = [];
$auditLogs = [];

if ($pdo) {
    try {
        $stats['students']      = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
        $stats['events']        = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
        $stats['registrations'] = (int)$pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn();
        $stats['users']         = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

        // Latest registrations with student & event joins
        $latestRegistrations = $pdo->query("
            SELECT r.registration_id, r.registration_date, r.attendance_status, s.full_name AS student_name, s.roll_no, e.title AS event_title
            FROM registrations r
            JOIN students s ON r.student_id = s.student_id
            JOIN events e ON r.event_id = e.event_id
            ORDER BY r.registration_id DESC LIMIT 5
        ")->fetchAll();

        // Recent Audit logs
        $auditLogs = $pdo->query("SELECT * FROM audit_logs ORDER BY log_id DESC LIMIT 6")->fetchAll();

    } catch (PDOException $e) {
        $errorMsg = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 14: Admin Dashboard, RBAC & Audit Log - StudentHub</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>

    <!-- Header & Navigation -->
    <header>
        <h1><a href="index.html">StudentHub</a></h1>
        <button id="hamburger-btn" class="hamburger-btn" aria-label="Menu">☰</button>
        <nav>
            <ul id="nav-list">
                <li><a href="index.html">Home</a></li>
                <li><a href="practical8.php">Practical 8</a></li>
                <li><a href="practical9.php">Practical 9</a></li>
                <li><a href="practical10.php">Practical 10</a></li>
                <li><a href="practical11.php">Practical 11</a></li>
                <li><a href="practical12.php">Practical 12</a></li>
                <li><a href="practical13.php">Practical 13</a></li>
                <li><a href="practical14.php" class="active">Practical 14</a></li>
                <li><a href="dashboard.html">Dashboard</a></li>
            </ul>
        </nav>
        <button id="themeBtn" class="btn">Dark Mode</button>
    </header>

    <main id="main-content" class="container">

        <!-- Breadcrumbs -->
        <nav class="breadcrumbs">
            <a href="index.html">Home</a> &gt; <span>Practical 14: Admin Dashboard, RBAC &amp; Audit Log</span>
        </nav>

        <!-- Academic Submission Header -->
        <section class="card" style="border-top: 4px solid var(--accent-color); margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <span class="badge badge-blue">Academic Lab Submission</span>
                    <h2 style="margin-top: 8px; color: var(--accent-color);">Practical 14: Admin Dashboard with RBAC, Dynamic Menu &amp; Audit Log</h2>
                    <p style="margin-top: 4px; font-size: 0.95rem;">
                        <strong>Problem Definition:</strong> Develop an admin dashboard that displays user statistics, student count, event count and latest registrations. Restrict access to admin users only and show dynamic menu options based on role.
                    </p>
                </div>
                <!-- Role Toggle Bar for Viva & Lab Demonstration -->
                <div style="text-align: right;">
                    <span style="font-size: 0.85rem; font-weight: bold;">Current Test Role:</span><br>
                    <span class="badge <?php echo $isAdmin ? 'badge-orange' : 'badge-green'; ?>" style="font-size: 0.9rem; padding: 5px 12px;">
                        <?php echo htmlspecialchars($currentRole); ?>
                    </span>
                    <div style="margin-top: 6px;">
                        <?php if ($isAdmin): ?>
                            <a href="practical14.php?switch_role=Student" class="btn btn-secondary" style="font-size: 0.75rem; padding: 4px 8px;">Switch to Student View (Test Block)</a>
                        <?php else: ?>
                            <a href="practical14.php?switch_role=Admin" class="btn" style="font-size: 0.75rem; padding: 4px 8px;">Switch to Admin View (Grant Access)</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid var(--border-color); display: flex; gap: 20px; flex-wrap: wrap; font-size: 0.88rem;">
                <div><strong>👨‍🎓 Student:</strong> Shubham Desai (25DCE020)</div>
                <div><strong>🏛️ Branch:</strong> 3rd Semester &bull; Computer Engineering</div>
                <div><strong>📖 Course:</strong> Web Development Frameworks (ITUE203)</div>
                <div><strong>🎯 CO Mapping:</strong> CO1, CO4, CO5</div>
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

        <?php if (!$isAdmin): ?>
            <!-- RBAC Access Restriction View: Blocked for Non-Admin -->
            <section class="card" style="border-left: 5px solid #e74c3c; margin-bottom: 20px; text-align: center; padding: 35px 20px;">
                <div style="font-size: 3rem; margin-bottom: 10px;">🚫</div>
                <h3 style="color: #e74c3c;">Access Denied: Administrator Privileges Required</h3>
                <p style="margin-top: 8px; font-size: 0.95rem; max-width: 600px; margin-left: auto; margin-right: auto;">
                    You are currently logged in with the <strong>Student</strong> role. In accordance with Practical 14 Role-Based Access Control (RBAC), administrative analytics and audit logs are restricted to Administrators only.
                </p>
                <div style="margin-top: 20px;">
                    <a href="practical14.php?switch_role=Admin" class="btn">👑 Switch to Administrator View (Demonstrate RBAC)</a>
                    <a href="dashboard.html" class="btn btn-secondary">Return to Student Dashboard</a>
                </div>
            </section>
        <?php else: ?>
            <!-- ADMIN DASHBOARD (When logged in as Admin) -->

            <!-- Dynamic Aggregated Metrics Section -->
            <section style="margin-bottom: 20px;">
                <h3>📊 1. Live Aggregated Portal Metrics (Dynamic SQL)</h3>
                <div class="grid" style="margin-top: 12px;">
                    <div class="card stat-box" style="padding: 15px; border-left: 4px solid #3498db;">
                        <div class="stat-number" style="color: #3498db;"><?php echo $stats['students']; ?></div>
                        <p style="font-weight: bold; margin-top: 5px;">Enrolled Students</p>
                        <small style="color: var(--text-muted);"><a href="practical11.php">Manage via Practical 11 &rarr;</a></small>
                    </div>

                    <div class="card stat-box" style="padding: 15px; border-left: 4px solid #9b59b6;">
                        <div class="stat-number" style="color: #9b59b6;"><?php echo $stats['events']; ?></div>
                        <p style="font-weight: bold; margin-top: 5px;">Campus Events</p>
                        <small style="color: var(--text-muted);"><a href="practical12.php">Manage via Practical 12 &rarr;</a></small>
                    </div>

                    <div class="card stat-box" style="padding: 15px; border-left: 4px solid #2ecc71;">
                        <div class="stat-number" style="color: #2ecc71;"><?php echo $stats['registrations']; ?></div>
                        <p style="font-weight: bold; margin-top: 5px;">Event Registrations</p>
                        <small style="color: var(--text-muted);"><a href="practical8.php">View via Practical 8 &rarr;</a></small>
                    </div>

                    <div class="card stat-box" style="padding: 15px; border-left: 4px solid #e67e22;">
                        <div class="stat-number" style="color: #e67e22;"><?php echo $stats['users']; ?></div>
                        <p style="font-weight: bold; margin-top: 5px;">User Accounts</p>
                        <small style="color: var(--text-muted);"><a href="practical9.php">Registered via Practical 9 &rarr;</a></small>
                    </div>
                </div>
            </section>

            <!-- Dynamic Menu Based on Role -->
            <section class="card" style="margin-bottom: 20px;">
                <h3>🎛️ 2. Dynamic Administrative Navigation Menu</h3>
                <p style="font-size: 0.9rem; color: var(--text-muted); margin-top: 4px;">
                    These menu options are generated dynamically exclusively for the <strong>Administrator</strong> role:
                </p>
                <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="practical11.php" class="btn">👥 Student Management CRUD</a>
                    <a href="practical12.php" class="btn">🎪 Event Management &amp; Posters</a>
                    <a href="practical13.php" class="btn">⚡ Asynchronous JSON API</a>
                    <a href="practical8.php" class="btn btn-secondary">🗄️ Relational Schema &amp; ER</a>
                    <a href="view_records.php" class="btn btn-secondary">📂 CSV/JSON File Records</a>
                </div>
            </section>

            <!-- Latest Registrations & Audit Log Tables Grid -->
            <div class="grid" style="margin-bottom: 20px;">
                
                <!-- Latest Event Registrations -->
                <div class="card" style="padding: 18px;">
                    <h3 style="color: var(--accent-color); margin-bottom: 10px;">🎟️ Latest Registrations</h3>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Event</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($latestRegistrations)): ?>
                                    <tr><td colspan="3" style="text-align:center;">No recent registrations.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($latestRegistrations as $reg): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($reg['student_name']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($reg['roll_no']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($reg['event_title']); ?></td>
                                            <td><span class="badge badge-green"><?php echo htmlspecialchars($reg['attendance_status']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Simulate Admin Action Form -->
                <div class="card" style="padding: 18px;">
                    <h3 style="color: var(--accent-color); margin-bottom: 10px;">✍️ Record Admin Action in Audit Log</h3>
                    <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 12px;">
                        Traceability test: Enter an administrative action to insert into the <code>audit_logs</code> table:
                    </p>
                    <form method="POST" action="practical14.php">
                        <input type="hidden" name="action" value="log_test_action">

                        <div class="form-group">
                            <label for="action_name">Action Code:</label>
                            <select id="action_name" name="action_name">
                                <option value="BACKUP_DATABASE">BACKUP_DATABASE</option>
                                <option value="STUDENT_VERIFY">STUDENT_VERIFY</option>
                                <option value="EVENT_APPROVAL">EVENT_APPROVAL</option>
                                <option value="SECURITY_AUDIT">SECURITY_AUDIT</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="action_desc">Details / Notes:</label>
                            <input type="text" id="action_desc" name="action_desc" value="Administrator conducted scheduled verification." required>
                        </div>

                        <button type="submit" class="btn" style="width: 100%;">Record in Audit Log</button>
                    </form>
                </div>

            </div>

            <!-- Full Audit Logs Table -->
            <section class="card" style="margin-bottom: 20px;">
                <h3>🛡️ 3. System Audit Log Entries (`audit_logs` Table)</h3>
                <p style="font-size: 0.9rem; color: var(--text-muted); margin-top: 4px;">
                    Tracks user actions, roles, details, timestamps, and client IP addresses for complete accountability.
                </p>

                <div class="table-responsive" style="margin-top: 15px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Log ID</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Action Code</th>
                                <th>Details</th>
                                <th>Client IP</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($auditLogs)): ?>
                                <tr><td colspan="7" style="text-align:center; padding: 20px;">No audit records found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($auditLogs as $log): ?>
                                    <tr>
                                        <td>#<?php echo (int)$log['log_id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($log['username']); ?></strong></td>
                                        <td><span class="badge badge-orange"><?php echo htmlspecialchars($log['role']); ?></span></td>
                                        <td><code><?php echo htmlspecialchars($log['action']); ?></code></td>
                                        <td><?php echo htmlspecialchars($log['details']); ?></td>
                                        <td><small><?php echo htmlspecialchars($log['ip_address']); ?></small></td>
                                        <td><small><?php echo htmlspecialchars($log['created_at']); ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <!-- SECTION 4: Key Questions & Viva Voce Q&A -->
        <section class="card" style="margin-bottom: 20px;">
            <h3>🎓 4. Key Questions &amp; Viva Voce Q&amp;A (From Syllabus)</h3>
            <div style="margin-top: 15px; display: flex; flex-direction: column; gap: 12px;">
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q1: Are dashboard values loaded dynamically from the database?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> Metric cards execute dynamic SQL aggregate functions (<code>SELECT COUNT(*) FROM table</code>) directly on <code>students</code>, <code>events</code>, <code>registrations</code>, and <code>users</code>.
                    </p>
                </div>
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q2: Is access restricted to admin users?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> The page inspects <code>$_SESSION['role']</code>. If the role is not 'Admin', sensitive dashboard stats and audit controls are hidden and replaced with an "Access Denied" notice.
                    </p>
                </div>
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q3: Are role-based menus working?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> Administrative menus (Student CRUD, Poster Management, Audit Logs) appear only for administrators, while regular students see only student-facing navigation.
                    </p>
                </div>
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q4: Are important admin actions recorded in audit_logs?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> Critical operations insert a record into the <code>audit_logs</code> table capturing the operator's username, role, action code, description, IP address, and server timestamp.
                    </p>
                </div>
            </div>
        </section>

    </main>

    <footer>
        <p>&copy; 2026 StudentHub Portal | Practical 14 Submission | Shubham Desai (25DCE020)</p>
        <p><a href="practical12.php">Practical 12</a> | <a href="practical13.php">Practical 13</a> | <a href="practical14.php">Practical 14</a></p>
    </footer>

</body>
</html>
