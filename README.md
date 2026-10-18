# StudentHub Portal - Web Development Lab Project

A semester-long academic web portal designed for **Students**, **Faculty**, and **Admin** staff to manage attendance, grades, timetables, courses, assignments, and campus events.

---

## 📌 Project Overview & Objectives
- Provide a unified single-portal interface for students to track attendance, grades, and assignments.
- Allow faculty to mark daily attendance and manage course assignments.
- Provide accessible, responsive, and mobile-friendly UI using HTML5, CSS3, and JavaScript ES6.
- Implement secure server-side form validation, file storage (CSV/JSON), and CSRF tokens (PHP 8.x).
- Implement normalized relational database schema (MySQL), ER modeling, and secure database connectivity using **PHP Data Objects (PDO)** and **Prepared Statements**.

---

## 👥 User Roles & Responsibilities

| User Role | Responsibilities |
|---|---|
| **Student** | View enrolled courses, check real-time attendance %, view results, submit assignments, register for events. |
| **Faculty** | View student rosters, mark daily attendance, post assignments and notices. |
| **Admin** | Manage student registrations, oversee portal operations, inspect CSV/JSON files, and maintain MySQL database. |

---

## 🗺️ Project Sitemap (Navigation Flow)

```
StudentHub Portal (pages/)
├── index.html         (Home Page: Hero, Content Slider, Role Cards, Quick Stats)
├── about.html         (About Us: Objectives, Technologies, Developer Details)
├── login.html         (Portal Login: Role Selection, Student/Faculty/Admin login)
├── register.html      (Student Registration: Form Validation, Strength Meter)
├── register.php       (Practical 7 & 8: PHP Registration, CSRF, CSV/JSON & MySQL Storage)
├── contact.html       (Contact & Helpdesk: Department Address & Message Form)
├── contact.php        (Practical 7 & 8: PHP Helpdesk Processor, CSRF, CSV/JSON & MySQL)
├── view_records.php   (Practical 7 Extension: Stored CSV & JSON Live Data Records Viewer)
├── practical8.php     (Practical 8: MySQL Schema Design, ER Model, Prepared Statements)
├── practical9.php     (Practical 9: Secure Registration, Duplicate Check & Password Hashing)
├── practical10.php    (Practical 10: Secure Login/Logout, Sessions, RBAC & Timeout)
├── practical11.php    (Practical 11: Student Management CRUD with Search & Filter)
├── practical12.php    (Practical 12: Event Management CRUD with Poster Upload & Validation)
├── practical13.php    (Practical 13: PHP JSON API & Asynchronous Fetch API CRUD)
├── api.php            (Practical 13: RESTful JSON API Endpoint for Events)
├── practical14.php    (Practical 14: Role-Based Admin Dashboard with Dynamic Menu & Audit Log)
├── database.php       (Practical 8 Alias Entry Point)
├── db.php             (Practical 8-14: Secure PDO Database Connection Module)
├── studenthub_db.sql  (Practicals 8-14: MySQL Normalized Schema Dump & Seed Data)
├── dashboard.html     (Student Dashboard: Metrics, Today's Timetable, Quick Actions)
├── course.html        (Enrolled Courses: Master Table, Syllabus Modal)
├── attendance.html    (Attendance Record: Subject-wise %, Mark Attendance/Leave Form)
├── assignment.html    (Assignment Portal: Deadlines Table, File Upload Solution Form)
├── result.html        (Examination Results: Subject Marksheet, SGPA/CGPA Summary)
├── event.html         (Practical 6: Fetch API Events, Search, Filter, Sort, Pagination)
├── faq.html           (Practical 6: Fetch API FAQs, Accordion, Category Filter)
└── feedback.html      (Feedback: Course & Portal Rating Form)
```

---

## 🗄️ Practical 8: MySQL Schema Design, ER Model, PDO Connectivity, and Prepared Statements

### 📝 Problem Definition:
Design the relational database for **StudentHub** with normalized entities for `students`, `events`, and `registrations`. Establish secure PHP database connectivity using **PHP Data Objects (PDO)** and execute database operations safely using **Prepared Statements** to prevent SQL Injection attacks.

### 🎯 Academic & Course Outcome Mapping:
- **CO Mapping:** **CO1** (Design responsive client-server web apps), **CO5** (Database-driven dynamic backend integration)
- **Key Skills:** ER Modeling, MySQL Schema Design, 3NF Normalization, phpMyAdmin, PHP PDO Connectivity, Prepared Statements, Database Error Handling.
- **Tools / Technology:** MySQL, MariaDB, phpMyAdmin, PHP 8.x, XAMPP, Apache.
- **Engagement Hours:** Implementation: 4 hours | Total Engagement: 6 hours.
- **Post-Laboratory Deliverables:** SQL dump (`studenthub_db.sql`), connection module (`db.php`), interactive database portal (`database.php`), and live connection diagnostics.

---

### 📐 Entity-Relationship (ER) Architecture & Relational Schema:

```
+------------------------------------+             +--------------------------------------+
|            STUDENTS (1)            |             |              EVENTS (1)              |
+------------------------------------+             +--------------------------------------+
| PK | student_id   INT (Auto-Inc)   |             | PK | event_id      INT (Auto-Inc)    |
| UQ | roll_no      VARCHAR(20)      |             |    | title         VARCHAR(150)      |
|    | full_name    VARCHAR(100)     |             |    | category      VARCHAR(50)       |
| UQ | email        VARCHAR(100)     |             |    | event_date    DATE              |
|    | mobile       VARCHAR(15)      |             |    | event_time    VARCHAR(30)       |
|    | course       VARCHAR(50)      |             |    | venue         VARCHAR(150)      |
|    | semester     VARCHAR(20)      |             |    | description   TEXT              |
|    | gender       ENUM             |             |    | capacity      INT               |
|    | password_hash VARCHAR(255)    |             |    | fee           DECIMAL(8,2)      |
|    | created_at   TIMESTAMP        |             |    | status        ENUM              |
+------------------------------------+             |    | created_at    TIMESTAMP         |
                 |                                 +--------------------------------------+
                 | 1                                                  | 1
                 |                                                    |
                 | registers                                          | has
                 |                                                    |
                 v M                                                  v M
+-----------------------------------------------------------------------------------------+
|                                    REGISTRATIONS (M)                                    |
+-----------------------------------------------------------------------------------------+
| PK | registration_id    INT (Auto-Increment)                                            |
| FK | student_id         INT -> REFERENCES students(student_id) ON DELETE CASCADE        |
| FK | event_id           INT -> REFERENCES events(event_id) ON DELETE CASCADE            |
|    | registration_date  TIMESTAMP DEFAULT CURRENT_TIMESTAMP                              |
|    | attendance_status  ENUM('Registered', 'Attended', 'Cancelled') DEFAULT 'Registered'|
|    | notes              VARCHAR(255)                                                    |
| UQ | UNIQUE KEY (student_id, event_id)                                                  |
+-----------------------------------------------------------------------------------------+
```

---

### 🔑 Key Practical 8 Analysis, Interpretation & Viva Questions:

#### 1. Is the schema normalized properly?
**Yes.** The schema strictly adheres to **3rd Normal Form (3NF)**:
- **1st Normal Form (1NF):** All attributes contain atomic, indivisible values. No repeating multi-valued groups exist. Each table possesses a distinct Primary Key (`student_id`, `event_id`, `registration_id`).
- **2nd Normal Form (2NF):** Satisfies 1NF and contains **no partial dependencies**. The Many-to-Many relationship between students and events is factored out into the associative entity `registrations`, where all non-key attributes are fully functionally dependent on the primary key.
- **3rd Normal Form (3NF):** Satisfies 2NF and contains **no transitive dependencies**. Non-key attributes depend solely on the primary key, avoiding redundant data duplication across tables.

#### 2. Are Primary Keys and Foreign Keys correctly defined?
**Yes.** 
- Each table defines a surrogate `INT AUTO_INCREMENT PRIMARY KEY`.
- The `registrations` table enforces referential integrity through foreign keys:
  ```sql
  CONSTRAINT `fk_reg_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reg_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE
  ```
- A composite unique index `UNIQUE (student_id, event_id)` prevents accidental duplicate registrations for the same event by a student.

#### 3. Is the database connection handled securely?
**Yes.** Handled via `pages/db.php` incorporating four essential security layers:
- **PHP Data Objects (PDO):** Replaces legacy `mysql_*` functions with robust PDO abstraction.
- **Native Prepared Statements:** Configured with `PDO::ATTR_EMULATE_PREPARES => false`, forcing server-side compilation of SQL templates and binding parameters strictly as data literals.
- **Structured Exception Handling:** Configured with `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` inside `try-catch` blocks. Error details are written to `error_log()` rather than exposing raw database passwords or host details in frontend views.
- **UTF-8 (utf8mb4) Encoding:** Enforces `SET NAMES utf8mb4` to protect against multibyte injection vulnerabilities.

---

### 🛡️ Prepared Statements Security Comparison:

| Feature | Vulnerable Direct Concatenation ❌ | Secure PDO Prepared Statements ✅ |
|---|---|---|
| **Syntax** | `$sql = "SELECT * FROM users WHERE email='" . $email . "'";` | `$stmt = $pdo->prepare("SELECT * FROM users WHERE email=:email");` |
| **Execution** | Query template and user data parsed together. | Query compiled first; data bound separately. |
| **SQLi Defense** | Vulnerable to `' OR '1'='1` and subquery injections. | **100% Protected**; input is strictly treated as a literal value. |
| **Performance** | Engine re-parses every query execution. | Query execution plan is cached and reused. |

---

### 💡 Practical 7: PHP Form Processing, Validation & File Storage

#### Problem Definition:
Process submitted registration and contact form data using PHP backend scripts. Validate and sanitize inputs on the server side and store records safely in both CSV and JSON file formats with success/error alerts.

#### Key Highlights:
- **Sanitization & Validation:** `htmlspecialchars()`, `trim()`, `filter_var()`, and Regular Expressions.
- **Safe File Writing:** `flock(LOCK_EX)` file locking for concurrency protection.
- **CSRF Token Security:** Cryptographic session tokens verified using `hash_equals()`.

---

## 🛠️ Practical Implementation Summary

- **Practical 1:** Project Initiation, Sitemap, Folder Structure, README documentation.
- **Practical 2:** Semantic HTML5 (`<header>`, `<nav>`, `<main>`, `<section>`, `<footer>`), accessibility (`label for`, breadcrumbs).
- **Practical 3:** Responsive CSS design using Flexbox, CSS Grid, custom color variables (Light/Dark mode), and `@media` queries.
- **Practical 4:** JavaScript DOM manipulation (Theme switcher, hamburger menu, FAQ accordion, modal popup, content slider, dismissible banner).
- **Practical 5:** Registration form with HTML5 input types, Regular Expression validation (Name, Email, 10-digit Mobile, Complex Password), and password strength meter.
- **Practical 6:** Rendering external JSON data (`events.json`, `faqs.json`, `students.json`) using Fetch API, search query filtering, category filters, sorting, and pagination.
- **Practical 7:** Server-side PHP form processing, POST handling, input sanitization, regex validation, safe CSV/JSON file writing (`flock`), CSRF token security, and stored records viewer (`view_records.php`).
- **Practical 8:** MySQL relational schema design, 3NF normalization, ER modeling, PDO database connectivity module (`db.php`), SQL dump (`studenthub_db.sql`), prepared statements SQLi protection, and live relational data explorer (`practical8.php`).
- **Practical 9:** Secure user registration with frontend/backend validation, duplicate username/email check, Bcrypt password hashing (`password_hash`), and MySQL database insert (`practical9.php`).
- **Practical 10:** Secure login/logout system using PHP sessions, `password_verify` credential check, session fixation defense (`session_regenerate_id`), role-based access control (Admin, Student, Faculty), and inactivity session timeout (`practical10.php`).
- **Practical 11:** Full Student Management CRUD module (Create, Read, Update, Delete) with live multi-column search, course dropdown filter, and JavaScript delete confirmation (`practical11.php`).
- **Practical 12:** Event Management CRUD module with server-side poster upload, allowed file extension validation (`jpg, jpeg, png, webp`), 2MB size enforcement, safe renaming, and catalog display (`practical12.php`).
- **Practical 13:** RESTful PHP JSON API endpoint (`api.php`) consumed asynchronously via JavaScript Fetch API for event CRUD without full page reload, including loading indicators and JSON inspector (`practical13.php`).
- **Practical 14:** Role-Based Admin Dashboard with dynamic SQL metrics (`COUNT(*)`), restricted access controls, dynamic role navigation, and system audit trail logging (`audit_logs`) for traceability (`practical14.php`).

---

## 👨‍💻 Student Developer Details
- **Name:** Shubham Desai
- **Enrollment Number:** 25DCE020
- **Semester / Branch:** 3rd Semester; Computer Engineering
- **Institute:** CHARUSAT University, Changa
