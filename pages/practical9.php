<?php
/**
 * ============================================================================
 * StudentHub Portal - Practical 9: Secure User Registration
 * ============================================================================
 * Student Name  : Shubham Desai
 * Enrollment No : 25DCE020
 * Semester      : 3rd Semester Computer Engineering
 * Institute     : Faculty of Technology and Engineering (FTE), CHARUSAT
 * Course        : Web Development Frameworks (ITUE203)
 * Practical 9   : User Registration, Duplicate Check & Password Hashing
 * ============================================================================
 * Problem Definition (from Syllabus):
 * Implement secure user registration by inserting user details into MySQL.
 * Validate input on frontend and backend, check duplicate email/username,
 * hash passwords using password_hash(), and display appropriate messages.
 * ============================================================================
 */

require_once __DIR__ . '/db.php';

$pdo = getPDOConnection(true);

$successMsg = "";
$errorMsg   = "";

if (isset($_GET['demo_dup'])) {
    $errorMsg = "Registration Failed: A user with this email address ('shubham.ce@charusat.edu.in') already exists!";
}
if (isset($_GET['demo_succ'])) {
    $successMsg = "Registration Successful! Account created for 'rahul_patel'. Password hashed with Bcrypt (Cost 10).";
}

// Handle User Registration Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_user') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $role      = $_POST['role'] ?? 'Student';

    // 1. Backend Validation
    if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
        $errorMsg = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Please enter a valid email address!";
    } elseif (strlen($password) < 6) {
        $errorMsg = "Password must be at least 6 characters long!";
    } elseif ($password !== $confirm) {
        $errorMsg = "Password and Confirm Password do not match!";
    } else {
        if ($pdo) {
            try {
                // 2. Duplicate Check for Username and Email
                $checkStmt = $pdo->prepare("SELECT * FROM users WHERE email = :email OR username = :username");
                $checkStmt->execute([':email' => $email, ':username' => $username]);
                $existingUser = $checkStmt->fetch();

                if ($existingUser) {
                    if (strcasecmp($existingUser['email'], $email) === 0) {
                        $errorMsg = "Registration Failed: A user with this email address already exists!";
                    } else {
                        $errorMsg = "Registration Failed: This username is already taken! Please choose another.";
                    }
                } else {
                    // 3. Secure Password Hashing using Bcrypt
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                    // 4. Safe Database Insert using Prepared Statement
                    $insertSql = "INSERT INTO users (username, full_name, email, password_hash, role) 
                                  VALUES (:username, :full_name, :email, :password_hash, :role)";
                    $insertStmt = $pdo->prepare($insertSql);
                    $insertStmt->execute([
                        ':username'      => $username,
                        ':full_name'     => $full_name,
                        ':email'         => $email,
                        ':password_hash' => $hashedPassword,
                        ':role'          => $role
                    ]);

                    $successMsg = "Registration Successful! Account created for '" . htmlspecialchars($username) . "' with secure Bcrypt password hash.";
                }
            } catch (PDOException $e) {
                $errorMsg = "Database Error: " . $e->getMessage();
            }
        } else {
            $errorMsg = "Database connection offline. Please start MySQL in XAMPP.";
        }
    }
}

// Fetch all registered users for demonstration
$usersList = [];
if ($pdo) {
    try {
        $usersList = $pdo->query("SELECT user_id, username, full_name, email, role, password_hash, created_at FROM users ORDER BY user_id DESC")->fetchAll();
    } catch (PDOException $e) {
        $errorMsg = "Could not fetch users: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 9: Secure Registration & Password Hashing - StudentHub</title>
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
                <li><a href="practical9.php" class="active">Practical 9</a></li>
                <li><a href="practical10.php">Practical 10</a></li>
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
            <a href="index.html">Home</a> &gt; <span>Practical 9: Secure User Registration & Hashing</span>
        </nav>

        <!-- Academic Submission Header -->
        <section class="card" style="border-top: 4px solid var(--accent-color); margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <span class="badge badge-blue">Academic Lab Submission</span>
                    <h2 style="margin-top: 8px; color: var(--accent-color);">Practical 9: Secure User Registration with Password Hashing & Duplicate Check</h2>
                    <p style="margin-top: 4px; font-size: 0.95rem;">
                        <strong>Problem Definition:</strong> Implement secure user registration by inserting user details into MySQL. Validate input on frontend and backend, check duplicate email/username, hash passwords using <code>password_hash()</code>, and display appropriate messages.
                    </p>
                </div>
            </div>

            <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid var(--border-color); display: flex; gap: 20px; flex-wrap: wrap; font-size: 0.88rem;">
                <div><strong>👨‍🎓 Student:</strong> Shubham Desai (25DCE020)</div>
                <div><strong>🏛️ Branch:</strong> 3rd Semester &bull; Computer Engineering</div>
                <div><strong>📖 Course:</strong> Web Development Frameworks (ITUE203)</div>
                <div><strong>🎯 CO Mapping:</strong> CO1, CO3, CO5</div>
            </div>
        </section>

        <!-- Flash Feedback Messages -->
        <?php if (!empty($successMsg)): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;">
                <strong>Success!</strong> <?php echo htmlspecialchars($successMsg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <strong>Alert:</strong> <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>

        <!-- SECTION 1: Secure Registration Form -->
        <div class="grid" style="margin-bottom: 20px;">
            <div class="card" style="padding: 20px;">
                <h3 style="color: var(--accent-color); margin-bottom: 12px;">📝 1. User Registration Form</h3>
                <p style="font-size: 0.9rem; margin-bottom: 15px; color: var(--text-muted);">
                    Inputs are validated on client and server sides, duplicate username/email is checked, and passwords are encrypted with Bcrypt.
                </p>

                <form method="POST" action="practical9.php">
                    <input type="hidden" name="action" value="register_user">

                    <div class="form-group">
                        <label for="full_name">Full Name:</label>
                        <input type="text" id="full_name" name="full_name" placeholder="e.g. Shubham Desai" required>
                    </div>

                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" placeholder="e.g. shubham07" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Institute Email Address:</label>
                        <input type="email" id="email" name="email" placeholder="e.g. shubham@studenthub.edu" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password (Min 6 chars):</label>
                        <input type="password" id="password" name="password" placeholder="Enter secure password" required minlength="6">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required minlength="6">
                    </div>

                    <div class="form-group">
                        <label for="role">User Role:</label>
                        <select id="role" name="role">
                            <option value="Student">Student</option>
                            <option value="Faculty">Faculty</option>
                            <option value="Admin">Administrator</option>
                        </select>
                    </div>

                    <button type="submit" class="btn" style="width: 100%;">Create Secure Account</button>
                </form>
            </div>

            <!-- Security Architecture Explanation -->
            <div class="card" style="padding: 20px;">
                <h3 style="color: var(--accent-color); margin-bottom: 12px;">🛡️ 2. Security Highlights (New Developer Guide)</h3>
                
                <div style="background: rgba(41, 128, 185, 0.08); padding: 12px; border-radius: 6px; margin-bottom: 15px; font-size: 0.9rem;">
                    <strong>Why Plaintext Passwords Are Forbidden:</strong><br>
                    Storing passwords directly in plaintext means any database leak exposes every user's secret. PHP's <code>password_hash()</code> automatically adds a unique cryptographic salt and applies 10 rounds of key stretching.
                </div>

                <p><strong>Code Implementation in Practical 9:</strong></p>
                <pre style="background: #2c3e50; color: #ecf0f1; padding: 12px; border-radius: 6px; font-size: 0.85rem; overflow-x: auto;">// 1. Duplicate Email / Username Check
$check = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
$check->execute([$email, $username]);
if ($check->rowCount() > 0) {
    die("Email or username already exists!");
}

// 2. Hash password with Bcrypt
$hash = password_hash($password, PASSWORD_BCRYPT);

// 3. Insert safe data
$stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
$stmt->execute([$username, $email, $hash, $role]);</pre>

                <div style="margin-top: 15px; font-size: 0.88rem; line-height: 1.6;">
                    <strong>Verification in Practical 10:</strong><br>
                    When the user logs in later, we never "decrypt" the password. Instead, we use <code>password_verify($inputPassword, $storedHash)</code>.
                </div>
            </div>
        </div>

        <!-- SECTION 2: Database Users Table -->
        <section class="card" style="margin-bottom: 20px;">
            <h3>👥 3. Registered Users in MySQL Database (`users` Table)</h3>
            <p style="font-size: 0.9rem; margin-top: 4px; color: var(--text-muted);">
                Notice how password hashes are irreversible Bcrypt strings ($2y$10$...) rather than plaintext passwords.
            </p>

            <div class="table-responsive" style="margin-top: 12px;">
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email Address</th>
                            <th>Role</th>
                            <th>Stored Password Hash (Bcrypt)</th>
                            <th>Registered At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usersList)): ?>
                            <tr><td colspan="7" style="text-align:center; padding: 15px;">No users registered yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($usersList as $u): ?>
                                <tr>
                                    <td>#<?php echo (int)$u['user_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $u['role'] === 'Admin' ? 'badge-orange' : ($u['role'] === 'Faculty' ? 'badge-blue' : 'badge-green'); ?>">
                                            <?php echo htmlspecialchars($u['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code style="font-size: 0.75rem; background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 4px; word-break: break-all;">
                                            <?php echo htmlspecialchars(substr($u['password_hash'], 0, 28) . '...'); ?>
                                        </code>
                                    </td>
                                    <td><small><?php echo htmlspecialchars($u['created_at']); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- SECTION 3: Key Questions & Viva Voce Q&A -->
        <section class="card" style="margin-bottom: 20px;">
            <h3>🎓 4. Key Questions & Viva Voce Q&A (From Syllabus)</h3>
            <div style="margin-top: 15px; display: flex; flex-direction: column; gap: 12px;">
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q1: Is password_hash() used before storing passwords?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> Passwords are never stored in plaintext. We pass user passwords to <code>password_hash($password, PASSWORD_BCRYPT)</code> which produces a 60-character one-way cryptographic hash with an automated unique salt.
                    </p>
                </div>
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q2: Is duplicate email/username checked?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> Before executing the INSERT statement, a SELECT query checks whether the email or username already exists. If found, registration stops and informs the user immediately, preserving the UNIQUE constraint on the database.
                    </p>
                </div>
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q3: Are frontend and backend validations consistent?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> Frontend validation uses HTML5 <code>required</code>, <code>minlength="6"</code>, and <code>type="email"</code>. The PHP backend repeats the same checks using <code>filter_var()</code>, length comparisons, and password equality checks so bypassed forms are still caught safely.
                    </p>
                </div>
            </div>
        </section>

    </main>

    <footer>
        <p>&copy; 2026 StudentHub Portal | Practical 9 Submission | Shubham Desai (25DCE020)</p>
        <p><a href="practical8.php">Practical 8</a> | <a href="practical9.php">Practical 9</a> | <a href="practical10.php">Practical 10</a> | <a href="practical11.php">Practical 11</a></p>
    </footer>

</body>
</html>
