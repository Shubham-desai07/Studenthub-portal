<?php
/**
 * ============================================================================
 * StudentHub Portal - Practical 7: PHP Contact Form Processing & File Storage
 * ============================================================================
 * Student Name  : Shubham Desai
 * Enrollment No : 25DCE020
 * Semester      : 3rd Semester Computer Engineering
 * Course        : Web Development Lab (Practical 7)
 * ============================================================================
 */

// Start session for CSRF token security
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF Token if not present
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Variables & error tracking
$fullName       = "";
$email          = "";
$subject        = "";
$message        = "";
$errors         = [];
$successMessage = "";
$submittedQuery = null;

// Sanitization function
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Check POST submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 1. CSRF Token Validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors['csrf'] = "Security validation failed: Invalid CSRF token!";
    }

    // 2. Sanitize inputs
    $fullName = isset($_POST['fullName']) ? sanitize_input($_POST['fullName']) : '';
    $email    = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    $subject  = isset($_POST['subject']) ? sanitize_input($_POST['subject']) : '';
    $message  = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';

    // 3. Server-side validation
    if (empty($fullName)) {
        $errors['fullName'] = "Please enter your Full Name.";
    } elseif (!preg_match("/^[a-zA-Z\s]{3,50}$/", $fullName)) {
        $errors['fullName'] = "Full Name must be 3-50 characters with letters only.";
    }

    if (empty($email)) {
        $errors['email'] = "Email Address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address.";
    }

    if (empty($subject)) {
        $errors['subject'] = "Query Subject is required.";
    } elseif (strlen($subject) < 3) {
        $errors['subject'] = "Subject must be at least 3 characters long.";
    }

    if (empty($message)) {
        $errors['message'] = "Please provide your message / query description.";
    } elseif (strlen($message) < 10) {
        $errors['message'] = "Message must be at least 10 characters long.";
    }

    // 4. If valid, save to contacts.csv and contacts.json
    if (empty($errors)) {
        $timestamp = date("Y-m-d H:i:s");

        $primaryCsvPath  = __DIR__ . '/../data/contacts.csv';
        $primaryJsonPath = __DIR__ . '/../data/contacts.json';
        $localCsvPath    = __DIR__ . '/data/contacts.csv';
        $localJsonPath   = __DIR__ . '/data/contacts.json';

        // Calculate next ID
        $existingContacts = [];
        if (file_exists($primaryJsonPath)) {
            $jsonRaw = file_get_contents($primaryJsonPath);
            $existingContacts = json_decode($jsonRaw, true) ?: [];
        }
        $newId = count($existingContacts) > 0 ? (end($existingContacts)['id'] + 1) : 1;

        $newContact = [
            'id'          => $newId,
            'fullName'    => $fullName,
            'email'       => $email,
            'subject'     => $subject,
            'message'     => $message,
            'submittedAt' => $timestamp
        ];

        // Safe CSV writing with lock
        $csvPaths = [$primaryCsvPath, $localCsvPath];
        foreach ($csvPaths as $path) {
            $dir = dirname($path);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            $isNew = !file_exists($path) || filesize($path) === 0;
            $fh = fopen($path, 'a');
            if ($fh) {
                if (flock($fh, LOCK_EX)) {
                    if ($isNew) {
                        fputcsv($fh, ['ID', 'Full Name', 'Email', 'Subject', 'Message', 'Submitted At']);
                    }
                    fputcsv($fh, [$newId, $fullName, $email, $subject, $message, $timestamp]);
                    flock($fh, LOCK_UN);
                }
                fclose($fh);
            }
        }

        // Safe JSON writing
        $existingContacts[] = $newContact;
        $jsonFormatted = json_encode($existingContacts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        $jsonPaths = [$primaryJsonPath, $localJsonPath];
        foreach ($jsonPaths as $jPath) {
            $dir = dirname($jPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($jPath, $jsonFormatted, LOCK_EX);
        }

        // Practical 8 Extension: Also store query in MySQL Database via PDO Prepared Statements
        $savedToDb = false;
        try {
            require_once __DIR__ . '/db.php';
            $pdo = getPDOConnection(true);
            if ($pdo) {
                $dbStmt = $pdo->prepare("
                    INSERT INTO contacts (full_name, email, category, message, status)
                    VALUES (:full_name, :email, :category, :message, 'Pending')
                ");
                $dbStmt->execute([
                    'full_name' => $fullName,
                    'email'     => $email,
                    'category'  => $subject,
                    'message'   => $message
                ]);
                $savedToDb = true;
            }
        } catch (PDOException $e) {
            error_log("Practical 8 Contact DB Insert: " . $e->getMessage());
        }

        $successMessage = "Your query has been submitted and recorded in our CSV, JSON helpdesk storage" . ($savedToDb ? " & MySQL Database (Practical 8)!" : "!");
        $submittedQuery = $newContact;

        // Reset CSRF token & sticky values
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $fullName = $email = $subject = $message = "";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub Portal - Contact Support (PHP)</title>
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
                <li><a href="contact.php" class="active">Contact</a></li>
                <li><a href="register.php">Register</a></li>
                <li><a href="view_records.php">Stored Records</a></li>
                <li><a href="practical8.php">Practical 8</a></li>
            </ul>
        </nav>
        <button id="themeBtn" class="btn">Dark Mode</button>
    </header>

    <main id="main-content" class="container">
        
        <!-- Breadcrumbs -->
        <nav class="breadcrumbs">
            <a href="index.html">Home</a> &gt; <span>Contact & Helpdesk (PHP Form Processing - Practical 7)</span>
        </nav>

        <h2>Contact Campus Support & Helpdesk</h2>
        <p>Have an academic inquiry or portal technical issue? Submit your query via server-side PHP processing.</p>

        <!-- Success Alert -->
        <?php if (!empty($successMessage) && $submittedQuery !== null): ?>
            <div class="alert alert-success">
                <h3 style="color: #155724; margin-bottom: 6px;"> Query Submitted Successfully!</h3>
                <p><?= htmlspecialchars($successMessage) ?></p>
                <hr style="margin: 10px 0; border: 0; border-top: 1px solid rgba(0,0,0,0.1);">
                <p style="font-size: 0.85rem;"><strong>Ticket Reference ID:</strong> #TKT-<?= htmlspecialchars($submittedQuery['id']) ?></p>
                <p style="font-size: 0.85rem;"><strong>From:</strong> <?= htmlspecialchars($submittedQuery['fullName']) ?> (<?= htmlspecialchars($submittedQuery['email']) ?>)</p>
                <p style="font-size: 0.85rem;"><strong>Subject:</strong> <?= htmlspecialchars($submittedQuery['subject']) ?></p>
                <p style="font-size: 0.85rem;"><strong>Submitted At:</strong> <?= htmlspecialchars($submittedQuery['submittedAt']) ?></p>
                <div style="margin-top: 10px;">
                    <a href="view_records.php" class="btn" style="font-size: 0.85rem; padding: 6px 12px;">📂 View Helpdesk CSV/JSON Records</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Error Summary -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h4 style="margin-bottom: 6px;">⚠️ Submission Errors:</h4>
                <ul style="padding-left: 20px; font-size: 0.9rem;">
                    <?php foreach ($errors as $errMsg): ?>
                        <li><?= htmlspecialchars($errMsg) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <section class="grid" style="margin-top: 15px;">
            
            <!-- Campus Contact Info Card -->
            <article class="card">
                <h3> Department Address</h3>
                <p><strong>Department of Computer Engineering</strong></p>
                <p>CHARUSAT Campus, Changa - 388421</p>
                <p>Dist. Anand, Gujarat, India</p>
                <p style="margin-top: 10px;"><strong>Email:</strong> helpdesk@charusat.edu.in</p>
                <p><strong>Phone:</strong> +91 2697 265011</p>
                
                <div style="margin-top: 20px; padding: 12px; background: var(--bg-color); border-radius: 4px;">
                    <h4 style="font-size: 0.9rem; margin-bottom: 5px;">⚡ Practical 7 Server Features:</h4>
                    <ul style="font-size: 0.85rem; padding-left: 18px; color: var(--text-muted);">
                        <li>HTTP POST Method</li>
                        <li>Server-Side Sanitization</li>
                        <li>Regex & Filter Validation</li>
                        <li>CSV + JSON Safe File Storage</li>
                        <li>CSRF Protection Token</li>
                    </ul>
                </div>
            </article>

            <!-- PHP Contact Form -->
            <article class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h3 style="margin: 0;"> Send Support Message</h3>
                    <span class="badge-csrf">🔒 CSRF Protected</span>
                </div>

                <form action="contact.php" method="POST" novalidate>
                    
                    <!-- Hidden CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <div class="form-group">
                        <label for="fullName">Your Full Name *</label>
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

                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" 
                               id="subject" 
                               name="subject" 
                               placeholder="e.g. Attendance Correction / Exam Hall Ticket" 
                               value="<?= htmlspecialchars($subject) ?>" 
                               class="<?= isset($errors['subject']) ? 'invalid' : '' ?>" 
                               required>
                        <?php if (isset($errors['subject'])): ?>
                            <div class="field-error"><?= htmlspecialchars($errors['subject']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="message">Message / Query Description *</label>
                        <textarea id="message" 
                                  name="message" 
                                  rows="4" 
                                  placeholder="Describe your issue in detail (min 10 characters)..." 
                                  class="<?= isset($errors['message']) ? 'invalid' : '' ?>" 
                                  required><?= htmlspecialchars($message) ?></textarea>
                        <?php if (isset($errors['message'])): ?>
                            <div class="field-error"><?= htmlspecialchars($errors['message']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn" style="width: 100%;">
                        ✉️ Submit Support Query (PHP POST)
                    </button>
                </form>
            </article>

        </section>

    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 StudentHub Portal | Designed for Semester 3 Web Development Lab Submission</p>
        <p><a href="about.html">About Us</a> | <a href="contact.php">Contact Support</a> | <a href="faq.html">FAQ</a></p>
    </footer>

</body>
</html>
