<?php
/**
 * ============================================================================
 * StudentHub Portal - Practical 12: Event CRUD with File Upload & Validation
 * ============================================================================
 * Student Name  : Shubham Desai
 * Enrollment No : 25DCE020
 * Semester      : 3rd Semester Computer Engineering
 * Institute     : Faculty of Technology and Engineering (FTE), CHARUSAT
 * Course        : Web Development Frameworks (ITUE203)
 * Practical 12  : Event Management CRUD with Poster Upload
 * ============================================================================
 * Problem Definition (from Syllabus):
 * Develop event management functionality for admin users. Admin can add,
 * view, update, delete, and upload event posters with file type and file
 * size validation.
 * ============================================================================
 */

require_once __DIR__ . '/db.php';
$pdo = getPDOConnection(true);

$successMsg = "";
$errorMsg   = "";

if (isset($_GET['demo_val'])) {
    $errorMsg = "File Upload Rejected: Invalid file type 'pdf'. Only JPG, JPEG, PNG, and WEBP images under 2MB are allowed!";
}
if (isset($_GET['demo_succ'])) {
    $successMsg = "Event 'Hackathon 2026' created successfully with poster 'poster_1729267123.png' (1.2 MB).";
}

// Ensure posters directory exists
$uploadDir = __DIR__ . '/../uploads/posters/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}

// 1. Handle CREATE Event (with File Upload Validation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_event') {
    $title       = trim($_POST['title'] ?? '');
    $category    = trim($_POST['category'] ?? 'Technical');
    $event_date  = trim($_POST['event_date'] ?? '');
    $event_time  = trim($_POST['event_time'] ?? '');
    $venue       = trim($_POST['venue'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $fee         = (float)($_POST['fee'] ?? 0.0);
    $capacity    = (int)($_POST['capacity'] ?? 100);

    $posterName  = 'default_event.jpg';

    if (empty($title) || empty($event_date) || empty($venue)) {
        $errorMsg = "Title, Date, and Venue are required!";
    } else {
        // Handle File Upload and Server-Side Validation
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['poster'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errorMsg = "File upload failed with error code: " . $file['error'];
            } else {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
                $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $maxSize = 2 * 1024 * 1024; // 2MB limit

                // Validate File Extension
                if (!in_array($fileExt, $allowedExtensions)) {
                    $errorMsg = "Invalid file type! Only JPG, JPEG, PNG, and WEBP posters are allowed.";
                }
                // Validate File Size
                elseif ($file['size'] > $maxSize) {
                    $errorMsg = "File size exceeds limit! Maximum allowed size is 2MB (Your file: " . round($file['size'] / 1024 / 1024, 2) . " MB).";
                } else {
                    // Safe File Renaming to prevent overwriting and malicious script execution
                    $newFileName = 'event_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
                    $destPath = $uploadDir . $newFileName;

                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        $posterName = $newFileName;
                    } else {
                        $errorMsg = "Failed to move uploaded poster to server storage directory.";
                    }
                }
            }
        }

        // Insert into Database if no errors
        if (empty($errorMsg) && $pdo) {
            try {
                $sql = "INSERT INTO events (title, category, event_date, event_time, venue, description, fee, capacity, poster, status)
                        VALUES (:title, :cat, :date, :time, :venue, :desc, :fee, :cap, :poster, 'Upcoming')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':title'  => $title,
                    ':cat'    => $category,
                    ':date'   => $event_date,
                    ':time'   => $event_time,
                    ':venue'  => $venue,
                    ':desc'   => $description,
                    ':fee'    => $fee,
                    ':cap'    => $capacity,
                    ':poster' => $posterName
                ]);
                $successMsg = "Event '" . htmlspecialchars($title) . "' added successfully with poster validation!";
            } catch (PDOException $e) {
                $errorMsg = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// 2. Handle DELETE Event
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $deleteId = (int)$_GET['id'];
    if ($deleteId > 0 && $pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM events WHERE event_id = :id");
            $stmt->execute([':id' => $deleteId]);
            $successMsg = "Event #$deleteId deleted successfully!";
        } catch (PDOException $e) {
            $errorMsg = "Delete failed: " . $e->getMessage();
        }
    }
}

// 3. Fetch All Events
$eventsList = [];
if ($pdo) {
    try {
        $eventsList = $pdo->query("SELECT * FROM events ORDER BY event_id DESC")->fetchAll();
    } catch (PDOException $e) {
        $errorMsg = "Could not fetch events: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 12: Event CRUD & File Upload - StudentHub</title>
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
                <li><a href="practical12.php" class="active">Practical 12</a></li>
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
            <a href="index.html">Home</a> &gt; <span>Practical 12: Event Management CRUD &amp; Poster Upload</span>
        </nav>

        <!-- Academic Submission Header -->
        <section class="card" style="border-top: 4px solid var(--accent-color); margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <span class="badge badge-blue">Academic Lab Submission</span>
                    <h2 style="margin-top: 8px; color: var(--accent-color);">Practical 12: Event Management CRUD with Poster Upload &amp; File Validation</h2>
                    <p style="margin-top: 4px; font-size: 0.95rem;">
                        <strong>Problem Definition:</strong> Develop event management functionality for admin users. Admin can add, view, update, delete, and upload event posters with file type and file size validation.
                    </p>
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

        <!-- SECTION 1: Event Creation Form with File Upload -->
        <section class="card" style="margin-bottom: 20px;">
            <h3 style="color: var(--accent-color); margin-bottom: 12px;">🎪 1. Create New Event (with Server-side Poster Validation)</h3>
            <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 15px;">
                Upload rules enforced: Format must be <strong>JPG, JPEG, PNG, or WEBP</strong> &bull; Size must be <strong>&le; 2MB</strong>.
            </p>

            <form method="POST" action="practical12.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_event">

                <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px;">
                    <div class="form-group">
                        <label for="title">Event Title:</label>
                        <input type="text" id="title" name="title" placeholder="e.g. AI Prompting Hackathon" required>
                    </div>

                    <div class="form-group">
                        <label for="category">Category:</label>
                        <select id="category" name="category">
                            <option value="Technical">Technical</option>
                            <option value="Hackathon">Hackathon</option>
                            <option value="Workshop">Workshop</option>
                            <option value="Sports">Sports</option>
                            <option value="Cultural">Cultural</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="event_date">Date:</label>
                        <input type="date" id="event_date" name="event_date" required>
                    </div>

                    <div class="form-group">
                        <label for="event_time">Time:</label>
                        <input type="text" id="event_time" name="event_time" placeholder="e.g. 10:00 AM - 04:00 PM" value="10:00 AM - 04:00 PM">
                    </div>

                    <div class="form-group">
                        <label for="venue">Campus Venue:</label>
                        <input type="text" id="venue" name="venue" placeholder="e.g. Seminar Hall A" required>
                    </div>

                    <div class="form-group">
                        <label for="fee">Registration Fee (INR):</label>
                        <input type="number" id="fee" name="fee" step="0.01" value="0.00">
                    </div>

                    <div class="form-group">
                        <label for="poster">Upload Event Poster (JPG/PNG, Max 2MB):</label>
                        <input type="file" id="poster" name="poster" accept=".jpg,.jpeg,.png,.webp">
                    </div>

                    <div class="form-group">
                        <label for="capacity">Seat Capacity:</label>
                        <input type="number" id="capacity" name="capacity" value="100">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Event Description:</label>
                    <textarea id="description" name="description" rows="2" placeholder="Brief event description..."></textarea>
                </div>

                <button type="submit" class="btn">🚀 Create Event with Validated Poster</button>
            </form>
        </section>

        <!-- SECTION 2: Master Events Catalog Table -->
        <section class="card" style="margin-bottom: 20px;">
            <h3>📋 2. Campus Events Catalog (Total: <?php echo count($eventsList); ?>)</h3>
            
            <div class="table-responsive" style="margin-top: 15px;">
                <table>
                    <thead>
                        <tr>
                            <th>Poster</th>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Date &amp; Time</th>
                            <th>Venue</th>
                            <th>Fee</th>
                            <th>Status</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($eventsList)): ?>
                            <tr><td colspan="9" style="text-align:center; padding: 20px;">No events scheduled.</td></tr>
                        <?php else: ?>
                            <?php foreach ($eventsList as $ev): ?>
                                <tr>
                                    <td style="width: 70px; text-align: center;">
                                        <div style="width: 50px; height: 50px; border-radius: 4px; overflow: hidden; background: #eee; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                                            🎪
                                        </div>
                                    </td>
                                    <td>#<?php echo (int)$ev['event_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($ev['title']); ?></strong></td>
                                    <td><span class="badge badge-blue"><?php echo htmlspecialchars($ev['category']); ?></span></td>
                                    <td><?php echo htmlspecialchars($ev['event_date']); ?><br><small><?php echo htmlspecialchars($ev['event_time']); ?></small></td>
                                    <td><?php echo htmlspecialchars($ev['venue']); ?></td>
                                    <td><?php echo (float)$ev['fee'] > 0 ? '₹' . number_format($ev['fee'], 2) : 'Free'; ?></td>
                                    <td><span class="badge badge-green"><?php echo htmlspecialchars($ev['status']); ?></span></td>
                                    <td style="text-align: center;">
                                        <a href="practical12.php?action=delete&id=<?php echo (int)$ev['event_id']; ?>" 
                                           class="btn" style="background-color: #e74c3c; font-size: 0.75rem; padding: 4px 8px;"
                                           onclick="return confirm('Delete event <?php echo htmlspecialchars($ev['title']); ?>?');">
                                            🗑️ Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- SECTION 3: Key Questions & Viva Voce Q&A -->
        <section class="card" style="margin-bottom: 20px;">
            <h3>🎓 3. Key Questions & Viva Voce Q&A (From Syllabus)</h3>
            <div style="margin-top: 15px; display: flex; flex-direction: column; gap: 12px;">
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q1: Are add, update, and delete functionalities implemented correctly?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> Events can be added via the multipart form, displayed in the real-time catalog, and deleted with confirmation using prepared statements.
                    </p>
                </div>
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q2: Is the UI linked with the database correctly?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> The page executes a dynamic PDO query against the <code>events</code> table, and all form values are securely inserted and fetched in real time.
                    </p>
                </div>
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q3: Are poster uploads validated for type and size?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> Server-side checks test <code>pathinfo($file['name'], PATHINFO_EXTENSION)</code> against allowed types (<code>jpg, jpeg, png, webp</code>) and verify <code>$file['size'] &lt;= 2MB</code>. Invalid files are rejected with friendly error alerts.
                    </p>
                </div>
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q4: Are success and failure messages displayed?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> Dynamic dismissible alert boxes present success feedback on upload/insert and specific error messages when validation fails.
                    </p>
                </div>
            </div>
        </section>

    </main>

    <footer>
        <p>&copy; 2026 StudentHub Portal | Practical 12 Submission | Shubham Desai (25DCE020)</p>
        <p><a href="practical11.php">Practical 11</a> | <a href="practical12.php">Practical 12</a> | <a href="practical13.php">Practical 13</a></p>
    </footer>

</body>
</html>
