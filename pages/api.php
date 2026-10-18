<?php
/**
 * ============================================================================
 * StudentHub Portal - Practical 13: PHP JSON API Endpoint
 * ============================================================================
 * Student Name  : Shubham Desai
 * Enrollment No : 25DCE020
 * Semester      : 3rd Semester Computer Engineering
 * Institute     : Faculty of Technology and Engineering (FTE), CHARUSAT
 * Course        : Web Development Frameworks (ITUE203)
 * Practical 13  : RESTful JSON API for Asynchronous AJAX/Fetch CRUD
 * ============================================================================
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

$pdo = getPDOConnection(true);

if (!$pdo) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed. Please ensure MySQL is running in XAMPP.'
    ]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    // 1. GET: Fetch List of Events
    if ($method === 'GET' && ($action === 'list_events' || $action === '')) {
        $stmt = $pdo->query("SELECT * FROM events ORDER BY event_id DESC");
        $events = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'count'   => count($events),
            'data'    => $events
        ]);
        exit();
    }

    // 2. POST: Add New Event via JSON or Form Data
    if ($method === 'POST' && $action === 'add_event') {
        // Handle JSON payload or form body
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: $_POST;

        $title      = trim($data['title'] ?? '');
        $category   = trim($data['category'] ?? 'Technical');
        $event_date = trim($data['event_date'] ?? date('Y-m-d'));
        $venue      = trim($data['venue'] ?? 'Auditorium Main');
        $fee        = (float)($data['fee'] ?? 0.0);

        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Event title is required!']);
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO events (title, category, event_date, event_time, venue, description, fee, status) 
                               VALUES (:title, :cat, :date, '10:00 AM - 04:00 PM', :venue, 'Added via JSON API without page reload', :fee, 'Upcoming')");
        $stmt->execute([
            ':title' => $title,
            ':cat'   => $category,
            ':date'  => $event_date,
            ':venue' => $venue,
            ':fee'   => $fee
        ]);

        $newId = (int)$pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => "Event #$newId created successfully via Fetch API!",
            'event_id' => $newId
        ]);
        exit();
    }

    // 3. POST / DELETE: Remove Event by ID
    if ($action === 'delete_event') {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: $_POST;
        $id = (int)($data['id'] ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Valid Event ID is required!']);
            exit();
        }

        $stmt = $pdo->prepare("DELETE FROM events WHERE event_id = :id");
        $stmt->execute([':id' => $id]);

        echo json_encode([
            'success' => true,
            'message' => "Event #$id removed via asynchronous API without reload!"
        ]);
        exit();
    }

    // Default Fallback
    echo json_encode([
        'success' => false,
        'message' => "Invalid API endpoint or action: '$action'"
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'API Database Error: ' . $e->getMessage()
    ]);
}
