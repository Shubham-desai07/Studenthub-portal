<?php
/**
 * ============================================================================
 * StudentHub Portal - Practical 7: Stored CSV & JSON Records Viewer
 * (Intermediate Extension Implementation)
 * ============================================================================
 * Student Name  : Shubham Desai
 * Enrollment No : 25DCE020
 * Semester      : 3rd Semester Computer Engineering
 * Course        : Web Development Lab (Practical 7)
 * ============================================================================
 */

// Helper function to safely read and parse CSV files
function read_csv_records($filePath) {
    $rows = [];
    if (file_exists($filePath) && ($handle = fopen($filePath, "r")) !== false) {
        $header = fgetcsv($handle);
        while (($data = fgetcsv($handle)) !== false) {
            if (!empty($data) && count($data) === count($header)) {
                $rows[] = array_combine($header, $data);
            }
        }
        fclose($handle);
    }
    return $rows;
}

// Helper function to safely read JSON files
function read_json_records($filePath) {
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        return json_decode($content, true) ?: [];
    }
    return [];
}

// Resolve primary file paths
$regCsvPath     = file_exists(__DIR__ . '/../data/registrations.csv') ? __DIR__ . '/../data/registrations.csv' : __DIR__ . '/data/registrations.csv';
$regJsonPath    = file_exists(__DIR__ . '/../data/registrations.json') ? __DIR__ . '/../data/registrations.json' : __DIR__ . '/data/registrations.json';
$contactCsvPath = file_exists(__DIR__ . '/../data/contacts.csv') ? __DIR__ . '/../data/contacts.csv' : __DIR__ . '/data/contacts.csv';
$contactJsonPath= file_exists(__DIR__ . '/../data/contacts.json') ? __DIR__ . '/../data/contacts.json' : __DIR__ . '/data/contacts.json';

// Fetch stored datasets
$registrationsCsv = read_csv_records($regCsvPath);
$registrationsJson= read_json_records($regJsonPath);
$contactsCsv      = read_csv_records($contactCsvPath);
$contactsJson     = read_json_records($contactJsonPath);

$totalRegistrations = max(count($registrationsCsv), count($registrationsJson));
$totalContacts      = max(count($contactsCsv), count($contactsJson));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub Portal - Stored Records (Practical 7)</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
    <style>
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
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
                <li><a href="register.php">Register</a></li>
                <li><a href="view_records.php" class="active">Stored Records</a></li>
                <li><a href="practical8.php">Practical 8</a></li>
            </ul>
        </nav>
        <button id="themeBtn" class="btn">Dark Mode</button>
    </header>

    <main id="main-content" class="container">
        
        <!-- Breadcrumbs -->
        <nav class="breadcrumbs">
            <a href="index.html">Home</a> &gt; <span>Stored CSV & JSON Records (Practical 7 Extension)</span>
        </nav>

        <!-- Practical 8 Banner -->
        <div class="alert alert-info" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
                🗄️ <strong>Practical 8 Database Available:</strong> Looking for normalized MySQL relational tables (Students, Events, Registrations) via PHP PDO?
            </div>
            <a href="practical8.php" class="btn" style="background-color: #27ae60; font-size: 0.85rem; padding: 6px 12px;">Open Practical 8 (Database)</a>
        </div>

        <h2>Stored Server-Side Data Records</h2>
        <p>Real-time view of records collected through PHP form processing and stored in CSV and JSON file formats.</p>

        <!-- Summary Statistics Grid -->
        <section class="grid" style="margin-top: 15px;">
            <div class="card stat-box">
                <div class="stat-number"><?= $totalRegistrations ?></div>
                <p><strong>Registered Students</strong></p>
                <small style="color: var(--text-muted);">From registrations.csv / .json</small>
            </div>
            <div class="card stat-box">
                <div class="stat-number"><?= $totalContacts ?></div>
                <p><strong>Helpdesk Queries</strong></p>
                <small style="color: var(--text-muted);">From contacts.csv / .json</small>
            </div>
            <div class="card stat-box">
                <div class="stat-number" style="color: var(--success-color);">CSV & JSON</div>
                <p><strong>Storage Formats Active</strong></p>
                <small style="color: var(--text-muted);">flock() Locked & Validated</small>
            </div>
        </section>

        <!-- Tab Controls for Switching Views -->
        <section class="card" style="margin-top: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                <div class="tab-controls" style="margin-bottom: 0;">
                    <button class="tab-btn active" onclick="switchTab('tab-registrations', this)">👨‍🎓 Student Registrations (<?= count($registrationsCsv) ?>)</button>
                    <button class="tab-btn" onclick="switchTab('tab-contacts', this)">✉️ Helpdesk Queries (<?= count($contactsCsv) ?>)</button>
                    <button class="tab-btn" onclick="switchTab('tab-json', this)">🔍 Raw JSON Viewer</button>
                </div>
                <div style="display: flex; gap: 8px;">
                    <a href="register.php" class="btn" style="font-size: 0.85rem; padding: 6px 12px;">+ New Registration</a>
                    <a href="contact.php" class="btn btn-secondary" style="font-size: 0.85rem; padding: 6px 12px;">+ New Query</a>
                </div>
            </div>

            <!-- TAB 1: Student Registrations Table -->
            <div id="tab-registrations" class="tab-content active">
                <h3>Student Registration Records (Parsed from CSV)</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 10px;">
                    Source File: <code>data/registrations.csv</code> &bull; Safe server-side append with duplicate prevention.
                </p>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Mobile</th>
                                <th>Course</th>
                                <th>Year</th>
                                <th>Gender</th>
                                <th>Registration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($registrationsCsv)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; color: var(--text-muted);">No student registrations found in CSV file.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($registrationsCsv as $row): ?>
                                    <tr>
                                        <td><strong>#<?= htmlspecialchars($row['ID'] ?? $row['id'] ?? '') ?></strong></td>
                                        <td><?= htmlspecialchars($row['Full Name'] ?? $row['fullName'] ?? '') ?></td>
                                        <td><a href="mailto:<?= htmlspecialchars($row['Email'] ?? $row['email'] ?? '') ?>"><?= htmlspecialchars($row['Email'] ?? $row['email'] ?? '') ?></a></td>
                                        <td><?= htmlspecialchars($row['Mobile'] ?? $row['mobile'] ?? '') ?></td>
                                        <td><span class="badge badge-blue"><?= htmlspecialchars($row['Course'] ?? $row['course'] ?? '') ?></span></td>
                                        <td><?= htmlspecialchars($row['Year'] ?? $row['year'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['Gender'] ?? $row['gender'] ?? '') ?></td>
                                        <td style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($row['Registration Date'] ?? $row['registeredAt'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: Helpdesk Queries Table -->
            <div id="tab-contacts" class="tab-content">
                <h3>Helpdesk Support Queries (Parsed from CSV)</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 10px;">
                    Source File: <code>data/contacts.csv</code> &bull; Inquiries submitted via PHP contact form.
                </p>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Ticket #</th>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Message / Query</th>
                                <th>Submitted At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($contactsCsv)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-muted);">No support queries found in CSV file.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($contactsCsv as $row): ?>
                                    <tr>
                                        <td><strong>#TKT-<?= htmlspecialchars($row['ID'] ?? $row['id'] ?? '') ?></strong></td>
                                        <td><?= htmlspecialchars($row['Full Name'] ?? $row['fullName'] ?? '') ?></td>
                                        <td><a href="mailto:<?= htmlspecialchars($row['Email'] ?? $row['email'] ?? '') ?>"><?= htmlspecialchars($row['Email'] ?? $row['email'] ?? '') ?></a></td>
                                        <td><strong><?= htmlspecialchars($row['Subject'] ?? $row['subject'] ?? '') ?></strong></td>
                                        <td><?= htmlspecialchars($row['Message'] ?? $row['message'] ?? '') ?></td>
                                        <td style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($row['Submitted At'] ?? $row['submittedAt'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 3: Raw JSON Storage Explorer -->
            <div id="tab-json" class="tab-content">
                <h3>Raw JSON Storage Explorer</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 10px;">
                    Direct view of structured JSON storage files written using <code>file_put_contents(..., LOCK_EX)</code>.
                </p>

                <h4 style="margin-top: 15px; margin-bottom: 6px; color: var(--accent-color);">📄 data/registrations.json</h4>
                <pre class="json-viewer"><?= htmlspecialchars(json_encode($registrationsJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>

                <h4 style="margin-top: 20px; margin-bottom: 6px; color: var(--accent-color);">📄 data/contacts.json</h4>
                <pre class="json-viewer"><?= htmlspecialchars(json_encode($contactsJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
            </div>

        </section>

    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 StudentHub Portal | Designed for Semester 3 Web Development Lab Submission</p>
        <p><a href="about.html">About Us</a> | <a href="contact.php">Contact Support</a> | <a href="faq.html">FAQ</a> | <a href="database.php">Practical 8 DB</a></p>
    </footer>

    <!-- Tab Switching Script -->
    <script>
        function switchTab(tabId, btnElement) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(function(el) {
                el.classList.remove('active');
            });
            // Remove active state from all buttons
            document.querySelectorAll('.tab-btn').forEach(function(btn) {
                btn.classList.remove('active');
            });
            // Activate selected tab and button
            var activeTab = document.getElementById(tabId);
            if (activeTab) activeTab.classList.add('active');
            if (btnElement) btnElement.classList.add('active');
        }
    </script>

</body>
</html>
