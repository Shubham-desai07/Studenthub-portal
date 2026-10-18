<?php
/**
 * ============================================================================
 * StudentHub Portal - Practical 7: PHP Form Processing & File Storage
 * ============================================================================
 * Student Name  : Shubham Desai
 * Enrollment No : 25DCE020
 * Semester      : 3rd Semester Computer Engineering
 * Course        : Web Development Lab (Practical 7)
 * ============================================================================
 * Features Implemented:
 * 1. HTTP POST form data handling.
 * 2. Server-side validation and sanitization using htmlspecialchars(), trim(),
 *    filter_var(), and Regular Expressions.
 * 3. Safe file writing to CSV (fputcsv with flock LOCK_EX) and JSON (file_put_contents).
 * 4. Display of success receipt, server error feedback, and sticky input values.
 * 5. Advanced Extension: CSRF Token generation and verification.
 * 6. Intermediate Extension: Integration with Stored Records Viewer.
 * ============================================================================
 */

// Start session for CSRF token security
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF Token if not already set (Advanced Extension)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize form variables and error tracking
$fullName        = "";
$email           = "";
$mobile          = "";
$password        = "";
$confirmPassword = "";
$course          = "";
$year            = "2nd Year";
$gender          = "Male";
$terms           = false;

$errors          = [];
$successMessage  = "";
$submittedData   = null;

/**
 * Helper function to sanitize string inputs on the server
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Check if form was submitted using POST method
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 1. Validate CSRF Token (Security Check)
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors['csrf'] = "Security validation failed: Invalid or expired CSRF token!";
    }

    // 2. Sanitize and retrieve form inputs
    $fullName        = isset($_POST['fullName']) ? sanitize_input($_POST['fullName']) : '';
    $email           = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    $mobile          = isset($_POST['mobile']) ? sanitize_input($_POST['mobile']) : '';
    $password        = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';
    $course          = isset($_POST['course']) ? sanitize_input($_POST['course']) : '';
    $year            = isset($_POST['year']) ? sanitize_input($_POST['year']) : '2nd Year';
    $gender          = isset($_POST['gender']) ? sanitize_input($_POST['gender']) : 'Male';
    $terms           = isset($_POST['terms']);

    // 3. Server-Side Validation Rules
    // Full Name: 3 to 50 alphabetic characters with spaces
    if (empty($fullName)) {
        $errors['fullName'] = "Full Name is required.";
    } elseif (!preg_match("/^[a-zA-Z\s]{3,50}$/", $fullName)) {
        $errors['fullName'] = "Full Name must contain only letters and spaces (min 3 characters).";
    }

    // Email: Valid email format check
    if (empty($email)) {
        $errors['email'] = "Email Address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address (e.g. student@gmail.com).";
    }

    // Mobile: 10 digit Indian mobile starting with 6, 7, 8, or 9
    if (empty($mobile)) {
        $errors['mobile'] = "Mobile Number is required.";
    } elseif (!preg_match("/^[6-9]\d{9}$/", $mobile)) {
        $errors['mobile'] = "Mobile Number must be a valid 10-digit number starting with 6-9.";
    }

    // Password: Min 8 chars, at least 1 uppercase, 1 lowercase, 1 number, 1 special char
    if (empty($password)) {
        $errors['password'] = "Password is required.";
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $password)) {
        $errors['password'] = "Password must be 8+ chars and contain uppercase, lowercase, number & symbol.";
    }

    // Confirm Password: Must match Password
    if (empty($confirmPassword)) {
        $errors['confirmPassword'] = "Please confirm your password.";
    } elseif ($confirmPassword !== $password) {
        $errors['confirmPassword'] = "Passwords do not match.";
    }

    // Course: Must be from valid list
    $validCourses = [
        "B.Tech Computer Engineering",
        "B.Tech Information Technology",
        "B.Tech AI & Data Science"
    ];
    if (empty($course) || !in_array($course, $validCourses)) {
        $errors['course'] = "Please select a valid academic course.";
    }

    // Year: Must be from valid list
    $validYears = ["1st Year", "2nd Year", "3rd Year", "4th Year"];
    if (empty($year) || !in_array($year, $validYears)) {
        $errors['year'] = "Please select a valid year of study.";
    }

    // Gender: Must be Male, Female, or Other
    $validGenders = ["Male", "Female", "Other"];
    if (!in_array($gender, $validGenders)) {
        $errors['gender'] = "Please select a valid gender option.";
    }

    // Terms Checkbox
    if (!$terms) {
        $errors['terms'] = "You must agree to the Terms & Conditions and Privacy Policy.";
    }

    // 4. If Validation Passes -> Save to CSV and JSON Files Safely
    if (empty($errors)) {
        $timestamp = date("Y-m-d H:i:s");
        $clientIp  = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        // File paths (both root and pages relative path safety)
        $primaryCsvPath  = __DIR__ . '/../data/registrations.csv';
        $primaryJsonPath = __DIR__ . '/../data/registrations.json';
        $localCsvPath    = __DIR__ . '/data/registrations.csv';
        $localJsonPath   = __DIR__ . '/data/registrations.json';

        // Calculate next ID from JSON
        $existingRecords = [];
        if (file_exists($primaryJsonPath)) {
            $jsonRaw = file_get_contents($primaryJsonPath);
            $existingRecords = json_decode($jsonRaw, true) ?: [];
        }
        $newId = count($existingRecords) > 0 ? (end($existingRecords)['id'] + 1) : 1;

        // New record array
        $newRecord = [
            'id'           => $newId,
            'fullName'     => $fullName,
            'email'        => $email,
            'mobile'       => $mobile,
            'course'       => $course,
            'year'         => $year,
            'gender'       => $gender,
            'registeredAt' => $timestamp,
            'ipAddress'    => $clientIp
        ];

        // Safe CSV writing with flock (LOCK_EX)
        $csvPathsToWrite = [$primaryCsvPath, $localCsvPath];
        foreach ($csvPathsToWrite as $path) {
            $dir = dirname($path);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $isNewFile = !file_exists($path) || filesize($path) === 0;
            $fileHandle = fopen($path, 'a');
            if ($fileHandle) {
                if (flock($fileHandle, LOCK_EX)) {
                    if ($isNewFile) {
                        fputcsv($fileHandle, ['ID', 'Full Name', 'Email', 'Mobile', 'Course', 'Year', 'Gender', 'Registration Date', 'IP Address']);
                    }
                    fputcsv($fileHandle, [
                        $newId,
                        $fullName,
                        $email,
                        $mobile,
                        $course,
                        $year,
                        $gender,
                        $timestamp,
                        $clientIp
                    ]);
                    flock($fileHandle, LOCK_UN);
                }
                fclose($fileHandle);
            }
        }

        // Safe JSON writing with file_put_contents (LOCK_EX)
        $existingRecords[] = $newRecord;
        $jsonDataFormatted = json_encode($existingRecords, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        $jsonPathsToWrite = [$primaryJsonPath, $localJsonPath];
        foreach ($jsonPathsToWrite as $jPath) {
            $jDir = dirname($jPath);
            if (!file_exists($jDir)) {
                mkdir($jDir, 0777, true);
            }
            file_put_contents($jPath, $jsonDataFormatted, LOCK_EX);
        }

        // Practical 8 Extension: Also store in MySQL Database using PDO Prepared Statements
        $savedToDb = false;
        $dbRollNo = '25DCE' . str_pad($newId + 20, 3, '0', STR_PAD_LEFT);
        try {
            require_once __DIR__ . '/db.php';
            $pdo = getPDOConnection(true);
            if ($pdo) {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                $dbSemester = ($year === '1st Year') ? '1st Semester' : (($year === '2nd Year') ? '3rd Semester' : '5th Semester');
                $dbStmt = $pdo->prepare("
                    INSERT INTO students (roll_no, full_name, email, mobile, course, semester, gender, password_hash)
                    VALUES (:roll_no, :full_name, :email, :mobile, :course, :semester, :gender, :password_hash)
                    ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), mobile = VALUES(mobile), course = VALUES(course)
                ");
                $dbStmt->execute([
                    'roll_no'       => $dbRollNo,
                    'full_name'     => $fullName,
                    'email'         => $email,
                    'mobile'        => $mobile,
                    'course'        => $course,
                    'semester'      => $dbSemester,
                    'gender'        => $gender,
                    'password_hash' => $passwordHash
                ]);
                $savedToDb = true;
            }
        } catch (PDOException $e) {
            error_log("Practical 8 Register DB Insert: " . $e->getMessage());
        }

        // Set success state
        $successMessage = "Student registration successfully processed and stored in CSV, JSON records" . ($savedToDb ? " & MySQL Database (Practical 8)!" : "!");
        $submittedData  = $newRecord;

        // Regenerate CSRF Token for security after successful submission
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        // Clear sticky values after successful save
        $fullName = $email = $mobile = $password = $confirmPassword = $course = "";
        $year = "2nd Year";
        $gender = "Male";
        $terms = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub Portal - Student Registration (PHP)</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>

    <!-- Header & Navigation -->
    <header>
        <h1><a href="index.html"> StudentHub</a></h1>
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
                <li><a href="register.php" class="active">Register</a></li>
                <li><a href="view_records.php">Stored Records</a></li>
                <li><a href="practical8.php">Practical 8</a></li>
            </ul>
        </nav>
        <button id="themeBtn" class="btn">Dark Mode</button>
    </header>

    <main id="main-content" class="container">
        
        <!-- Breadcrumbs -->
        <nav class="breadcrumbs">
            <a href="index.html">Home</a> &gt; <span>Student Registration (PHP Form Processing - Practical 7)</span>
        </nav>

        <section class="card" style="max-width: 600px; margin: 15px auto;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;">
                <h2 style="margin: 0;">Student Registration Form</h2>
                <span class="badge-csrf">🔒 CSRF Protected</span>
            </div>
            
            <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 15px;">
                Processed server-side using <strong>PHP POST</strong>, validated against regex rules, sanitized, and stored securely in CSV & JSON files.
            </p>

            <!-- Success Alert Box with Registration Receipt (Practical 7) -->
            <?php if (!empty($successMessage) && $submittedData !== null): ?>
                <div class="alert alert-success">
                    <h3 style="color: #155724; margin-bottom: 8px;"> Registration Successful!</h3>
                    <p><?= htmlspecialchars($successMessage) ?></p>
                    <hr style="margin: 10px 0; border: 0; border-top: 1px solid rgba(0,0,0,0.1);">
                    <p style="font-size: 0.85rem; margin-bottom: 4px;"><strong>Registration ID:</strong> #<?= htmlspecialchars($submittedData['id']) ?></p>
                    <p style="font-size: 0.85rem; margin-bottom: 4px;"><strong>Student Name:</strong> <?= htmlspecialchars($submittedData['fullName']) ?></p>
                    <p style="font-size: 0.85rem; margin-bottom: 4px;"><strong>Email:</strong> <?= htmlspecialchars($submittedData['email']) ?></p>
                    <p style="font-size: 0.85rem; margin-bottom: 4px;"><strong>Course:</strong> <?= htmlspecialchars($submittedData['course']) ?> (<?= htmlspecialchars($submittedData['year']) ?>)</p>
                    <p style="font-size: 0.85rem; margin-bottom: 10px;"><strong>Recorded At:</strong> <?= htmlspecialchars($submittedData['registeredAt']) ?></p>
                    <div style="margin-top: 10px; display: flex; gap: 8px;">
                        <a href="view_records.php" class="btn" style="font-size: 0.85rem; padding: 6px 12px;">📂 View All Stored Records</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Global Error Summary Alert (Practical 7) -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h4 style="margin-bottom: 6px;">⚠️ Please correct the following errors:</h4>
                    <ul style="padding-left: 20px; font-size: 0.9rem;">
                        <?php foreach ($errors as $field => $errMsg): ?>
                            <li><?= htmlspecialchars($errMsg) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- PHP Registration Form (POST method with CSRF Token) -->
            <form action="register.php" method="POST" novalidate>
                
                <!-- Hidden CSRF Security Token (Advanced Extension) -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <!-- Full Name -->
                <div class="form-group">
                    <label for="fullName">Full Name *</label>
                    <input type="text" 
                           id="fullName" 
                           name="fullName" 
                           placeholder="e.g. Shubham Desai" 
                           value="<?= htmlspecialchars($fullName) ?>" 
                           class="<?= isset($errors['fullName']) ? 'invalid' : '' ?>" 
                           required>
                    <?php if (isset($errors['fullName'])): ?>
                        <div class="field-error"><?= htmlspecialchars($errors['fullName']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           placeholder="e.g. shubham@gmail.com" 
                           value="<?= htmlspecialchars($email) ?>" 
                           class="<?= isset($errors['email']) ? 'invalid' : '' ?>" 
                           required>
                    <?php if (isset($errors['email'])): ?>
                        <div class="field-error"><?= htmlspecialchars($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Mobile -->
                <div class="form-group">
                    <label for="mobile">Mobile Number *</label>
                    <input type="tel" 
                           id="mobile" 
                           name="mobile" 
                           placeholder="10-digit mobile number starting with 6-9" 
                           maxlength="10" 
                           value="<?= htmlspecialchars($mobile) ?>" 
                           class="<?= isset($errors['mobile']) ? 'invalid' : '' ?>" 
                           required>
                    <?php if (isset($errors['mobile'])): ?>
                        <div class="field-error"><?= htmlspecialchars($errors['mobile']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Password with Client-side Strength Meter + Server Validation -->
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Min 8 chars with uppercase, lowercase, number, symbol" 
                           class="<?= isset($errors['password']) ? 'invalid' : '' ?>" 
                           required>
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthFill"></div>
                    </div>
                    <div id="strengthLabel" style="font-size: 0.8rem; font-weight: bold; margin-top: 3px;"></div>
                    <?php if (isset($errors['password'])): ?>
                        <div class="field-error"><?= htmlspecialchars($errors['password']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password *</label>
                    <input type="password" 
                           id="confirmPassword" 
                           name="confirmPassword" 
                           placeholder="Re-enter password" 
                           class="<?= isset($errors['confirmPassword']) ? 'invalid' : '' ?>" 
                           required>
                    <?php if (isset($errors['confirmPassword'])): ?>
                        <div class="field-error"><?= htmlspecialchars($errors['confirmPassword']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Course -->
                <div class="form-group">
                    <label for="course">Select Course *</label>
                    <select id="course" name="course" class="<?= isset($errors['course']) ? 'invalid' : '' ?>" required>
                        <option value="">-- Choose Course --</option>
                        <option value="B.Tech Computer Engineering" <?= ($course === 'B.Tech Computer Engineering') ? 'selected' : '' ?>>B.Tech Computer Engineering</option>
                        <option value="B.Tech Information Technology" <?= ($course === 'B.Tech Information Technology') ? 'selected' : '' ?>>B.Tech Information Technology</option>
                        <option value="B.Tech AI & Data Science" <?= ($course === 'B.Tech AI & Data Science') ? 'selected' : '' ?>>B.Tech AI & Data Science</option>
                    </select>
                    <?php if (isset($errors['course'])): ?>
                        <div class="field-error"><?= htmlspecialchars($errors['course']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Year of Study -->
                <div class="form-group">
                    <label for="year">Year of Study</label>
                    <select id="year" name="year" class="<?= isset($errors['year']) ? 'invalid' : '' ?>">
                        <option value="1st Year" <?= ($year === '1st Year') ? 'selected' : '' ?>>1st Year</option>
                        <option value="2nd Year" <?= ($year === '2nd Year') ? 'selected' : '' ?>>2nd Year</option>
                        <option value="3rd Year" <?= ($year === '3rd Year') ? 'selected' : '' ?>>3rd Year</option>
                        <option value="4th Year" <?= ($year === '4th Year') ? 'selected' : '' ?>>4th Year</option>
                    </select>
                    <?php if (isset($errors['year'])): ?>
                        <div class="field-error"><?= htmlspecialchars($errors['year']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Gender -->
                <div class="form-group">
                    <label>Gender:</label>
                    <div style="display: flex; gap: 15px; margin-top: 4px;">
                        <label style="font-weight: normal;">
                            <input type="radio" name="gender" value="Male" <?= ($gender === 'Male') ? 'checked' : '' ?>> Male
                        </label>
                        <label style="font-weight: normal;">
                            <input type="radio" name="gender" value="Female" <?= ($gender === 'Female') ? 'checked' : '' ?>> Female
                        </label>
                        <label style="font-weight: normal;">
                            <input type="radio" name="gender" value="Other" <?= ($gender === 'Other') ? 'checked' : '' ?>> Other
                        </label>
                    </div>
                    <?php if (isset($errors['gender'])): ?>
                        <div class="field-error"><?= htmlspecialchars($errors['gender']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Terms & Conditions Checkbox -->
                <div class="form-group">
                    <label style="font-weight: normal; font-size: 0.85rem;">
                        <input type="checkbox" id="terms" name="terms" value="1" <?= $terms ? 'checked' : '' ?>> 
                        I accept the Terms & Conditions and Privacy Policy. *
                    </label>
                    <?php if (isset($errors['terms'])): ?>
                        <div class="field-error"><?= htmlspecialchars($errors['terms']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn" style="width: 100%; padding: 10px; font-size: 1rem; margin-top: 5px;">
                    🚀 Submit Registration (Server-Side PHP)
                </button>
            </form>

            <div style="margin-top: 15px; text-align: center;">
                <a href="view_records.php" style="font-size: 0.85rem; color: var(--accent-color); text-decoration: none;">
                    📊 View All Stored CSV / JSON Records &rarr;
                </a>
            </div>

        </section>

    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 StudentHub Portal | Designed for Semester 3 Web Development Lab Submission</p>
        <p><a href="about.html">About Us</a> | <a href="contact.php">Contact Support</a> | <a href="faq.html">FAQ</a></p>
    </footer>

</body>
</html>
