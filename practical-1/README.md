# StudentHub Portal

A web-based academic management system where **Students**, **Faculty**, and **Admin** each get a role-based dashboard to manage attendance, grades, timetable, courses, and notices.

##  Project Overview

StudentHub Portal is a web-based system that lets three types of users — Students, Faculty, and Admin — manage academic activities like attendance, grades, timetable, and course materials through role-based dashboards.

## Objectives

- Provide a single platform where students can check their attendance, grades, timetable, and assignments
- Allow faculty to mark attendance, upload grades, and post assignments/notices
- Give admin full control to manage students, faculty, courses, and reports

## User Roles

| Role | Description |
|---|---|
| Student | Views personal academic data, submits assignments, checks notices |
| Faculty | Manages assigned courses, marks attendance, uploads grades |
| Admin | Manages overall system data — users, courses, reports |

## Sitemap

```
HOME
│
├── About
├── Contact
├── Login
└── Register

Student Panel
│
├── Dashboard
├── My Courses
│   ├── Course Details
│   └── Study Materials
├── Attendance
├── Grades
├── Timetable
├── Assignments
├── Notices
└── Profile

Faculty Panel
│
├── Dashboard
├── My Courses
│   └── Student List
├── Mark Attendance
├── Upload Grades
├── Assignments
├── Timetable
├── Notices
└── Profile

Admin Panel
│
├── Dashboard
├── Manage Students
├── Manage Faculty
├── Manage Courses
├── Timetable Management
├── Reports
├── Notices
└── Profile
```

##  Functional Requirements

**Common (All Users)**
- Login with role-based redirection (Student/Faculty/Admin)
- Register (for new students/faculty, if allowed)
- View and edit own profile
- View notices

**Student**
- View list of enrolled courses and study materials
- View subject-wise attendance
- View grades/results
- View weekly timetable
- Submit assignments

**Faculty**
- View list of students in assigned courses
- Mark attendance for a class
- Upload/update student grades
- Post assignments
- View timetable

**Admin**
- Add/edit/remove student records
- Add/edit/remove faculty records
- Add/edit/remove course records and assign faculty
- Manage timetable
- Generate basic reports (attendance summary, results summary)
- Post/manage notices

## Non-Functional Requirements

- **Usability:** Simple, clean UI understandable by non-technical users (students/teachers)
- **Performance:** Pages should load without noticeable delay for small sample data
- **Security:** Basic login authentication; passwords should not be stored/shown in plain text on the UI
- **Portability:** Should run on any modern browser (Chrome, Firefox, Edge)
- **Maintainability:** Code should be organized by role (student/faculty/admin folders or pages) for easy updates

##  Assumptions & Constraints

- This is an academic mini-project — no real payment/fee processing is required
- Data can be stored using a simple database (e.g., MySQL) or even hardcoded/sample data for demo purposes if no backend is built
- Single login page handles all three roles based on a "role" field
- No mobile app — only responsive web pages

##  Tools & Technology

- **Frontend:** HTML, CSS, JavaScript (or Bootstrap for faster styling)
- **Backend (if included):** PHP / Node.js / Python (Flask or Django)
- **Database:** MySQL or SQLite
- **Others:** VS Code, XAMPP (for local PHP+MySQL testing) if going the PHP route

##  Out of Scope

- Real-time chat/messaging
- Online fee payment
- Mobile application
- Advanced analytics/AI-based reports
