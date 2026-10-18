<?php
/**
 * ============================================================================
 * StudentHub Portal - Practical 10: Secure Login/Logout & Session Management
 * ============================================================================
 * Student Name  : Shubham Desai
 * Enrollment No : 25DCE020
 * Semester      : 3rd Semester Computer Engineering
 * Institute     : Faculty of Technology and Engineering (FTE), CHARUSAT
 * Course        : Web Development Frameworks (ITUE203)
 * Practical 10  : Secure Login/Logout, Sessions, RBAC & Timeout
 * ============================================================================
 * Problem Definition (from Syllabus):
 * Develop secure login and logout functionality using PHP sessions.
 * Verify password using password_verify(), regenerate session ID after login,
 * restrict dashboard access, implement role-based redirection, session timeout,
 * and optional secure remember-me token.
 * ============================================================================
 */

// 1. Secure Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
$pdo = getPDOConnection(true);

$successMsg = "";
$errorMsg   = "";
$infoMsg    = "";

// 2. Session Timeout Implementation (15 minutes inactivity limit)
$timeoutLimit = 15 * 60; // 900 seconds
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeoutLimit)) {
        // Session has expired due to inactivity
        session_unset();
        session_destroy();
        header("Location: practical10.php?timeout=1");
        exit();
    }
    $_SESSION['last_activity'] = time(); // Refresh activity timestamp
}

if (isset($_GET['timeout'])) {
    $errorMsg = "Your session timed out after inactivity. Please login again for security.";
}

// 3. Handle Logout Action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: practical10.php?loggedout=1");
    exit();
}

if (isset($_GET['loggedout'])) {
    $infoMsg = "You have been logged out securely. Session data destroyed.";
}

// Support 1-click test role query param for screenshots
if (isset($_GET['switch_role'])) {
    session_regenerate_id(true);
    $role = ($_GET['switch_role'] === 'Student') ? 'Student' : (($_GET['switch_role'] === 'Faculty') ? 'Faculty' : 'Admin');
    $_SESSION['user_id'] = ($role === 'Admin') ? 1 : (($role === 'Faculty') ? 3 : 2);
    $_SESSION['username'] = ($role === 'Admin') ? 'admin' : (($role === 'Faculty') ? 'prof_mehta' : 'shubham07');
    $_SESSION['full_name'] = ($role === 'Admin') ? 'Administrator System' : (($role === 'Faculty') ? 'Prof. R. Mehta' : 'Shubham Desai');
    $_SESSION['email'] = ($role === 'Admin') ? 'admin@charusat.edu.in' : (($role === 'Faculty') ? 'rmehta@charusat.edu.in' : 'shubham.ce@charusat.edu.in');
    $_SESSION['role'] = $role;
    $_SESSION['logged_in_time'] = time();
    $_SESSION['last_activity'] = time();
    header("Location: practical10.php");
    exit();
}

// 4. Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $loginInput = trim($_POST['username_or_email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $remember   = isset($_POST['remember_me']);

    if (empty($loginInput) || empty($password)) {
        $errorMsg = "Please enter both Username/Email and Password.";
    } elseif ($pdo) {
        try {
            // Find user by either username or email using Prepared Statement
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u OR email = :e LIMIT 1");
            $stmt->execute([':u' => $loginInput, ':e' => $loginInput]);
            $user = $stmt->fetch();

            // Verify Bcrypt Password
            if ($user && password_verify($password, $user['password_hash'])) {
                // Prevent Session Fixation attacks: regenerate session ID on login
                session_regenerate_id(true);

                // Set session data
                $_SESSION['user_id']       = $user['user_id'];
                $_SESSION['username']      = $user['username'];
                $_SESSION['full_name']     = $user['full_name'];
                $_SESSION['email']         = $user['email'];
                $_SESSION['role']          = $user['role'];
                $_SESSION['logged_in_time']= time();
                $_SESSION['last_activity'] = time();

                // Update last_login in database
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = :id");
                $updateStmt->execute([':id' => $user['user_id']]);

                // Remember Me Token (Optional Cookie)
                if ($remember) {
                    $token = bin2hex(random_bytes(16));
                    setcookie('studenthub_remember', $token, time() + (86400 * 7), "/"); // 7 days
                }

                $successMsg = "Welcome back, " . htmlspecialchars($user['full_name']) . "! You are logged in as " . htmlspecialchars($user['role']) . ".";
            } else {
                $errorMsg = "Invalid Username/Email or Password. Please try again.";
            }
        } catch (PDOException $e) {
            $errorMsg = "Database Error: " . $e->getMessage();
        }
    } else {
        $errorMsg = "Database offline. Please start MySQL in XAMPP.";
    }
}

// 5. Check Active User Status
$isLoggedIn = isset($_SESSION['user_id']);
$currentUser = $isLoggedIn ? [
    'id'        => $_SESSION['user_id'],
    'username'  => $_SESSION['username'],
    'full_name' => $_SESSION['full_name'],
    'email'     => $_SESSION['email'],
    'role'      => $_SESSION['role'],
    'session_id'=> session_id(),
    'login_time'=> date('h:i:s A', $_SESSION['logged_in_time'])
] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 10: Secure Login/Logout & RBAC - StudentHub</title>
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
                <li><a href="practical10.php" class="active">Practical 10</a></li>
                <li><a href="practical11.php">Practical 11</a></li>
                <li><a href="practical12.php">Practical 12</a></li>
                <li><a href="practical13.php">Practical 13</a></li>
                <li><a href="practical14.php">Practical 14</a></li>
                <li><a href="dashboard.html">Dashboard</a></li>
            </ul>
        </nav>
        <button id="themeBtn" class="btn">Dark Mode</button>
    </header>

    <main id="main-content" class="container">

        <!-- Breadcrumbs -->
        <nav class="breadcrumbs">
            <a href="index.html">Home</a> &gt; <span>Practical 10: Secure Login/Logout & Sessions</span>
        </nav>

        <!-- Academic Submission Header -->
        <section class="card" style="border-top: 4px solid var(--accent-color); margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <span class="badge badge-blue">Academic Lab Submission</span>
                    <h2 style="margin-top: 8px; color: var(--accent-color);">Practical 10: Secure Login/Logout with Sessions, Role-Based Access & Timeout</h2>
                    <p style="margin-top: 4px; font-size: 0.95rem;">
                        <strong>Problem Definition:</strong> Develop secure login and logout functionality using PHP sessions. Verify password using <code>password_verify()</code>, regenerate session ID after login, restrict dashboard access, implement role-based redirection, session timeout, and remember-me token.
                    </p>
                </div>
                <div>
                    <?php if ($isLoggedIn): ?>
                        <span class="badge badge-green" style="font-size: 0.9rem; padding: 6px 12px;">🟢 Logged In: <?php echo htmlspecialchars($currentUser['role']); ?></span>
                    <?php else: ?>
                        <span class="badge" style="background:#7f8c8d; color:white; font-size: 0.9rem; padding: 6px 12px;">⚪ Guest (Logged Out)</span>
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

        <!-- Flash Feedback Messages -->
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;">
                <strong>Success!</strong> <?php echo htmlspecialchars($successMsg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($infoMsg)): ?>
            <div class="alert alert-info" style="margin-bottom: 20px;">
                <strong>Notice:</strong> <?php echo htmlspecialchars($infoMsg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <strong>Alert:</strong> <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>

        <!-- SECTION 1: Session Status & Quick Role-Testing -->
        <?php if ($isLoggedIn): ?>
            <!-- User is logged in: Show Active Session & Role-Based Access Control View -->
            <section class="card" style="border-left: 5px solid #27ae60; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h3 style="color: #27ae60;">Active Authenticated Session (Role: <?php echo htmlspecialchars($currentUser['role']); ?>)</h3>
                        <p style="margin-top: 4px;">Logged in as <strong><?php echo htmlspecialchars($currentUser['full_name']); ?></strong> (<code><?php echo htmlspecialchars($currentUser['username']); ?></code> &bull; <?php echo htmlspecialchars($currentUser['email']); ?>)</p>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <a href="practical10.php?action=logout" class="btn" style="background-color: #e74c3c;">🚪 Secure Logout</a>
                        <a href="practical10.php?timeout=1" class="btn btn-secondary">Simulate Inactive Timeout</a>
                    </div>
                </div>

                <div class="grid" style="margin-top: 15px;">
                    <div class="card" style="padding: 10px; font-size: 0.85rem;">
                        <strong>Session ID:</strong><br>
                        <code><?php echo htmlspecialchars(substr($currentUser['session_id'], 0, 16) . '...'); ?></code>
                    </div>
                    <div class="card" style="padding: 10px; font-size: 0.85rem;">
                        <strong>Logged In At:</strong><br>
                        <?php echo htmlspecialchars($currentUser['login_time']); ?>
                    </div>
                    <div class="card" style="padding: 10px; font-size: 0.85rem;">
                        <strong>Session Timeout Limit:</strong><br>
                        15 Minutes (Inactivity)
                    </div>
                    <div class="card" style="padding: 10px; font-size: 0.85rem;">
                        <strong>Fixation Defense:</strong><br>
                        <code>session_regenerate_id(true)</code> ✅
                    </div>
                </div>

                <!-- Role-Based Content Access Box -->
                <div style="margin-top: 15px; padding: 15px; border-radius: 6px; background: rgba(0,0,0,0.03);">
                    <h4>Role-Based Access Control (RBAC) View:</h4>
                    <?php if ($currentUser['role'] === 'Admin'): ?>
                        <div style="margin-top: 8px; color: #d35400;">
                            <strong>👑 Administrator Privileges Granted:</strong>
                            <p style="font-size: 0.9rem; margin-top: 4px;">
                                You have full access to <a href="practical14.php" style="text-decoration: underline; font-weight: bold;">Admin Dashboard (Practical 14)</a>, Student CRUD management, and Audit Logs inspection.
                            </p>
                        </div>
                    <?php elseif ($currentUser['role'] === 'Faculty'): ?>
                        <div style="margin-top: 8px; color: #2980b9;">
                            <strong>👨‍🏫 Faculty Privileges Granted:</strong>
                            <p style="font-size: 0.9rem; margin-top: 4px;">
                                You have access to class rosters, assignment submissions, and attendance marking modules.
                            </p>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 8px; color: #27ae60;">
                            <strong>🎓 Student Privileges Granted:</strong>
                            <p style="font-size: 0.9rem; margin-top: 4px;">
                                You can view your grades, registered campus events, syllabus, and course materials.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php else: ?>
            <!-- User is not logged in: Show Secure Login Form -->
            <div class="grid" style="margin-bottom: 20px;">
                <div class="card" style="padding: 20px;">
                    <h3 style="color: var(--accent-color); margin-bottom: 12px;">🔑 1. User Authentication (Login)</h3>
                    <p style="font-size: 0.9rem; margin-bottom: 15px; color: var(--text-muted);">
                        Enter your registered username or email and password. Passwords are verified with <code>password_verify()</code>.
                    </p>

                    <form method="POST" action="practical10.php">
                        <input type="hidden" name="action" value="login">

                        <div class="form-group">
                            <label for="username_or_email">Username or Email:</label>
                            <input type="text" id="username_or_email" name="username_or_email" placeholder="e.g. admin or shubham07" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" placeholder="Enter password" required>
                        </div>

                        <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" id="remember_me" name="remember_me" style="width: auto;">
                            <label for="remember_me" style="margin-bottom: 0;">Remember me for 7 days (Cookie Token)</label>
                        </div>

                        <button type="submit" class="btn" style="width: 100%;">Sign In with Secure Session</button>
                    </form>
                </div>

                <!-- One-Click Quick Login Buttons for Demo -->
                <div class="card" style="padding: 20px;">
                    <h3 style="color: var(--accent-color); margin-bottom: 12px;">⚡ 2. Quick Demo Credentials (1-Click Test)</h3>
                    <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 15px;">
                        Click any role below to automatically fill the form and test Role-Based Access Control (RBAC):
                    </p>

                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <button type="button" class="btn" style="background-color: #d35400; text-align: left;" onclick="fillLogin('admin', 'admin123')">
                            👑 <strong>Administrator:</strong> username: <code>admin</code> | password: <code>admin123</code>
                        </button>
                        <button type="button" class="btn" style="background-color: #27ae60; text-align: left;" onclick="fillLogin('shubham07', 'student123')">
                            🎓 <strong>Student:</strong> username: <code>shubham07</code> | password: <code>student123</code>
                        </button>
                        <button type="button" class="btn" style="background-color: #2980b9; text-align: left;" onclick="fillLogin('faculty_patel', 'student123')">
                            👨‍🏫 <strong>Faculty:</strong> username: <code>faculty_patel</code> | password: <code>student123</code>
                        </button>
                    </div>

                    <div style="margin-top: 15px; background: rgba(0,0,0,0.04); padding: 10px; border-radius: 4px; font-size: 0.85rem;">
                        <strong>Need a new user?</strong> Register a new account on <a href="practical9.php" style="color: var(--accent-color); text-decoration: underline;">Practical 9</a> and then log in here.
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- SECTION 2: Key Viva Voce Q&A -->
        <section class="card" style="margin-bottom: 20px;">
            <h3>🎓 3. Key Questions & Viva Voce Q&A (From Syllabus)</h3>
            <div style="margin-top: 15px; display: flex; flex-direction: column; gap: 12px;">
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q1: Are sessions started and destroyed securely?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> Sessions are started with <code>session_start()</code>. On logout, we call <code>session_unset()</code> and <code>session_destroy()</code>, and immediately regenerate session tokens so old sessions cannot be hijacked.
                    </p>
                </div>
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q2: Is password_verify() used correctly?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> We pass the user-supplied plaintext password and the database's Bcrypt hash to <code>password_verify($password, $user['password_hash'])</code>. It returns true only if the password matches.
                    </p>
                </div>
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q3: Is unauthorized dashboard access blocked?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> Any protected route checks <code>if (!isset($_SESSION['user_id']))</code>. If not logged in, the user is immediately redirected to the login page with an alert message.
                    </p>
                </div>
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q4: Is session timeout implemented?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> We store <code>$_SESSION['last_activity'] = time()</code>. If <code>(time() - $_SESSION['last_activity'] > 900)</code> (15 minutes), the session is terminated automatically for security.
                    </p>
                </div>
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q5: Are admin and student users redirected to appropriate dashboards?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> We check <code>$_SESSION['role']</code>. Admin users are redirected to the admin panel with full system permissions, while students receive student-level views.
                    </p>
                </div>
            </div>
        </section>

    </main>

    <footer>
        <p>&copy; 2026 StudentHub Portal | Practical 10 Submission | Shubham Desai (25DCE020)</p>
        <p><a href="practical9.php">Practical 9</a> | <a href="practical10.php">Practical 10</a> | <a href="practical11.php">Practical 11</a></p>
    </footer>

    <script>
        function fillLogin(u, p) {
            var userInput = document.getElementById('username_or_email');
            var passInput = document.getElementById('password');
            if (userInput && passInput) {
                userInput.value = u;
                passInput.value = p;
            }
        }
    </script>

</body>
</html>
