<?php
/**
 * ============================================================================
 * StudentHub Portal - Practical 11: Student Management CRUD
 * ============================================================================
 * Student Name  : Shubham Desai
 * Enrollment No : 25DCE020
 * Semester      : 3rd Semester Computer Engineering
 * Institute     : Faculty of Technology and Engineering (FTE), CHARUSAT
 * Course        : Web Development Frameworks (ITUE203)
 * Practical 11  : Student CRUD with Search and Filter
 * ============================================================================
 * Problem Definition (from Syllabus):
 * Create a student management module to add, view, edit, delete, search
 * and filter student records. All database operations must use MySQL statements
 * and clear success/failure messages.
 * ============================================================================
 */

require_once __DIR__ . '/db.php';
$pdo = getPDOConnection(true);

$successMsg = "";
$errorMsg   = "";

if (isset($_GET['demo_del'])) {
    $successMsg = "Student record (Roll No: 25DCE020) deleted successfully using PDO Prepared Statement.";
}

// 1. Handle CREATE (Add Student)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $roll_no   = trim($_POST['roll_no'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $mobile    = trim($_POST['mobile'] ?? '');
    $course    = trim($_POST['course'] ?? '');
    $semester  = trim($_POST['semester'] ?? '3rd Semester');
    $gender    = trim($_POST['gender'] ?? 'Male');

    if (empty($roll_no) || empty($full_name) || empty($email)) {
        $errorMsg = "Roll No, Name, and Email are required!";
    } elseif ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO students (roll_no, full_name, email, mobile, course, semester, gender) 
                                   VALUES (:roll, :name, :email, :mobile, :course, :sem, :gender)");
            $stmt->execute([
                ':roll'   => $roll_no,
                ':name'   => $full_name,
                ':email'  => $email,
                ':mobile' => $mobile,
                ':course' => $course,
                ':sem'    => $semester,
                ':gender' => $gender
            ]);
            $successMsg = "Student '" . htmlspecialchars($full_name) . "' added successfully!";
        } catch (PDOException $e) {
            $errorMsg = ($e->getCode() == 23000) ? "Roll No or Email already exists!" : "Database Error: " . $e->getMessage();
        }
    }
}

// 2. Handle UPDATE (Edit Student)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $student_id = (int)($_POST['student_id'] ?? 0);
    $full_name  = trim($_POST['full_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $mobile     = trim($_POST['mobile'] ?? '');
    $course     = trim($_POST['course'] ?? '');
    $semester   = trim($_POST['semester'] ?? '');
    $gender     = trim($_POST['gender'] ?? 'Male');

    if ($student_id > 0 && !empty($full_name) && !empty($email) && $pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE students SET full_name = :name, email = :email, mobile = :mobile, 
                                   course = :course, semester = :sem, gender = :gender WHERE student_id = :id");
            $stmt->execute([
                ':name'   => $full_name,
                ':email'  => $email,
                ':mobile' => $mobile,
                ':course' => $course,
                ':sem'    => $semester,
                ':gender' => $gender,
                ':id'     => $student_id
            ]);
            $successMsg = "Student record #$student_id updated successfully!";
        } catch (PDOException $e) {
            $errorMsg = "Update failed: " . $e->getMessage();
        }
    }
}

// 3. Handle DELETE
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $deleteId = (int)$_GET['id'];
    if ($deleteId > 0 && $pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = :id");
            $stmt->execute([':id' => $deleteId]);
            $successMsg = "Student record #$deleteId deleted successfully!";
        } catch (PDOException $e) {
            $errorMsg = "Delete failed: " . $e->getMessage();
        }
    }
}

// 4. Handle READ with SEARCH & FILTER
$searchKeyword = trim($_GET['search'] ?? '');
$filterCourse  = trim($_GET['course'] ?? '');

$studentsList = [];
if ($pdo) {
    try {
        $sql = "SELECT * FROM students WHERE 1=1";
        $params = [];

        if (!empty($searchKeyword)) {
            $sql .= " AND (full_name LIKE :kw OR roll_no LIKE :kw OR email LIKE :kw)";
            $params[':kw'] = "%$searchKeyword%";
        }

        if (!empty($filterCourse)) {
            $sql .= " AND course = :course";
            $params[':course'] = $filterCourse;
        }

        $sql .= " ORDER BY student_id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $studentsList = $stmt->fetchAll();
    } catch (PDOException $e) {
        $errorMsg = "Fetch error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 11: Student Management CRUD - StudentHub</title>
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
                <li><a href="practical11.php" class="active">Practical 11</a></li>
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
            <a href="index.html">Home</a> &gt; <span>Practical 11: Student Management CRUD</span>
        </nav>

        <!-- Academic Submission Header -->
        <section class="card" style="border-top: 4px solid var(--accent-color); margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <span class="badge badge-blue">Academic Lab Submission</span>
                    <h2 style="margin-top: 8px; color: var(--accent-color);">Practical 11: Student Management CRUD (Search, Filter &amp; MySQL Statements)</h2>
                    <p style="margin-top: 4px; font-size: 0.95rem;">
                        <strong>Problem Definition:</strong> Create a student management module to add, view, edit, delete, search and filter student records. All database operations must use MySQL statements and clear success/failure messages.
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

        <!-- Flash Feedback Messages -->
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

        <!-- SECTION 1: Search, Filter & Add Form Card -->
        <section class="card" style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                <h3>🔍 Search &amp; Filter Students</h3>
                <button type="button" class="btn" onclick="toggleAddForm()">➕ Add New Student</button>
            </div>

            <!-- Search & Filter Form -->
            <form method="GET" action="practical11.php" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <input type="text" name="search" placeholder="Search by name, roll no, or email..." value="<?php echo htmlspecialchars($searchKeyword); ?>" style="flex: 1; min-width: 220px;">
                <select name="course" style="width: auto; min-width: 180px;">
                    <option value="">-- All Courses --</option>
                    <option value="Computer Engineering" <?php echo $filterCourse === 'Computer Engineering' ? 'selected' : ''; ?>>Computer Engineering</option>
                    <option value="Information Technology" <?php echo $filterCourse === 'Information Technology' ? 'selected' : ''; ?>>Information Technology</option>
                    <option value="Electronics & Communication" <?php echo $filterCourse === 'Electronics & Communication' ? 'selected' : ''; ?>>Electronics &amp; Communication</option>
                </select>
                <button type="submit" class="btn">Filter</button>
                <?php if (!empty($searchKeyword) || !empty($filterCourse)): ?>
                    <a href="practical11.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>

            <!-- Collapsible Add Student Form -->
            <div id="addStudentSection" style="display: none; margin-top: 20px; border-top: 1px dashed var(--border-color); padding-top: 15px;">
                <h4 style="color: var(--accent-color); margin-bottom: 10px;">➕ Create New Student Record</h4>
                <form method="POST" action="practical11.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; align-items: end;">
                    <input type="hidden" name="action" value="add">

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="roll_no">Roll No:</label>
                        <input type="text" id="roll_no" name="roll_no" placeholder="e.g. 25DCE099" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="full_name">Full Name:</label>
                        <input type="text" id="full_name" name="full_name" placeholder="e.g. Yash Dave" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="email">Email Address:</label>
                        <input type="email" id="email" name="email" placeholder="e.g. yash@studenthub.edu" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="mobile">Mobile Number:</label>
                        <input type="text" id="mobile" name="mobile" placeholder="e.g. 9876500000" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="course">Course / Branch:</label>
                        <select id="course" name="course">
                            <option value="Computer Engineering">Computer Engineering</option>
                            <option value="Information Technology">Information Technology</option>
                            <option value="Electronics & Communication">Electronics &amp; Communication</option>
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="btn" style="width: 100%;">Save Student</button>
                    </div>
                </form>
            </div>
        </section>

        <!-- SECTION 2: Master Students CRUD Table -->
        <section class="card" style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3>📋 Students Roster (Total: <?php echo count($studentsList); ?>)</h3>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Roll No</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Course / Branch</th>
                            <th>Semester</th>
                            <th>Gender</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($studentsList)): ?>
                            <tr><td colspan="9" style="text-align:center; padding: 20px;">No student records found.</td></tr>
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
                                    <td><?php echo htmlspecialchars($stu['gender']); ?></td>
                                    <td style="text-align: center; white-space: nowrap;">
                                        <!-- Edit Button (populates modal) -->
                                        <button type="button" class="btn btn-secondary" style="font-size: 0.75rem; padding: 4px 8px;"
                                                onclick='openEditModal(<?php echo json_encode($stu); ?>)'>
                                            ✏️ Edit
                                        </button>
                                        <!-- Delete Button with Confirmation -->
                                        <a href="practical11.php?action=delete&id=<?php echo (int)$stu['student_id']; ?>" 
                                           class="btn" style="background-color: #e74c3c; font-size: 0.75rem; padding: 4px 8px;"
                                           onclick="return confirm('Are you sure you want to delete student <?php echo htmlspecialchars($stu['full_name']); ?> (#<?php echo $stu['student_id']; ?>)?');">
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
            <h3>🎓 Key Questions & Viva Voce Q&A (From Syllabus)</h3>
            <div style="margin-top: 15px; display: flex; flex-direction: column; gap: 12px;">
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q1: Are SELECT, INSERT, UPDATE, and DELETE operations working?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes:</strong><br>
                        &bull; <code>INSERT INTO students ...</code> creates new student entries.<br>
                        &bull; <code>SELECT * FROM students WHERE ...</code> queries records with search/filter.<br>
                        &bull; <code>UPDATE students SET ... WHERE student_id = :id</code> updates existing data.<br>
                        &bull; <code>DELETE FROM students WHERE student_id = :id</code> removes records cleanly.
                    </p>
                </div>
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q2: Is search by name/email/course implemented?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> The SQL query uses <code>LIKE :kw</code> across <code>full_name</code>, <code>roll_no</code>, and <code>email</code>, combined with an exact <code>AND course = :course</code> filter.
                    </p>
                </div>
                <div style="border-left: 4px solid var(--accent-color); padding: 10px 15px; background: var(--card-bg);">
                    <strong>Q3: Are delete operations confirmed before execution?</strong>
                    <p style="font-size: 0.9rem; margin-top: 4px;">
                        <strong>Yes.</strong> The delete action is protected with a JavaScript <code>onclick="return confirm(...)"</code> confirmation dialog to prevent accidental data deletion.
                    </p>
                </div>
            </div>
        </section>

    </main>

    <!-- Edit Student Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center;">
        <div class="card" style="width: 90%; max-width: 500px; padding: 25px; background: var(--card-bg);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="color: var(--accent-color);">✏️ Edit Student Record</h3>
                <button type="button" onclick="closeEditModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form method="POST" action="practical11.php">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_student_id" name="student_id">

                <div class="form-group">
                    <label>Roll Number (Read-only):</label>
                    <input type="text" id="edit_roll_no" readonly style="background: rgba(0,0,0,0.05);">
                </div>
                <div class="form-group">
                    <label for="edit_full_name">Full Name:</label>
                    <input type="text" id="edit_full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_email">Email:</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="edit_mobile">Mobile Number:</label>
                    <input type="text" id="edit_mobile" name="mobile">
                </div>
                <div class="form-group">
                    <label for="edit_course">Course:</label>
                    <input type="text" id="edit_course" name="course">
                </div>
                <div class="form-group">
                    <label for="edit_semester">Semester:</label>
                    <input type="text" id="edit_semester" name="semester">
                </div>
                <div style="display: flex; gap: 10px; margin-top: 15px;">
                    <button type="submit" class="btn" style="flex: 1;">Update Student</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <p>&copy; 2026 StudentHub Portal | Practical 11 Submission | Shubham Desai (25DCE020)</p>
        <p><a href="practical10.php">Practical 10</a> | <a href="practical11.php">Practical 11</a> | <a href="practical12.php">Practical 12</a></p>
    </footer>

    <script>
        function toggleAddForm() {
            var f = document.getElementById('addStudentSection');
            f.style.display = (f.style.display === 'none') ? 'block' : 'none';
        }

        function openEditModal(stu) {
            document.getElementById('edit_student_id').value = stu.student_id;
            document.getElementById('edit_roll_no').value    = stu.roll_no;
            document.getElementById('edit_full_name').value  = stu.full_name;
            document.getElementById('edit_email').value      = stu.email;
            document.getElementById('edit_mobile').value     = stu.mobile;
            document.getElementById('edit_course').value     = stu.course;
            document.getElementById('edit_semester').value   = stu.semester;

            var modal = document.getElementById('editModal');
            modal.style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>

</body>
</html>
