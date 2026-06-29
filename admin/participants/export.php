<?php
// Admin Participants Directory: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry\admin\participants
// File Name: export.php
// Purpose: Exports the participant and attendance list of a specific event as a downloadable CSV spreadsheet.

require_once '../../config/database.php';
require_once '../../includes/auth_check.php';

// Route security guard: Ensure administrative privileges
require_admin();

// 1. Verify event ID is provided
if (!isset($_GET['event_id']) || empty($_GET['event_id'])) {
    die("Invalid event ID specified.");
}

$event_id = intval($_GET['event_id']);
$db = Database::getInstance();

try {
    // 2. Fetch event title to name the downloadable file
    $event_stmt = $db->prepare("SELECT title FROM events WHERE id = :id");
    $event_stmt->execute(['id' => $event_id]);
    $event_title = $event_stmt->fetchColumn();

    if (!$event_title) {
        die("Event not found.");
    }

    // Clean event title to make it safe for file systems
    $safe_title = preg_replace('/[^A-Za-z0-9_\-]/', '_', $event_title);
    $filename = "participants_" . $safe_title . "_" . date('Y-m-d') . ".csv";

    // 3. Set standard HTTP download headers for CSV format
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // 4. Open PHP output stream directly (writes directly to the HTTP response body)
    $output = fopen('php://output', 'w');

    // Write CSV Header row
    fputcsv($output, [
        'Registration ID',
        'Attendee Name',
        'Attendee Email',
        'Attendee Phone',
        'Registration Status',
        'Registered At',
        'Check-In Status',
        'Check-In Time'
    ]);

    // 5. Query and write database rows
    $query = "
        SELECT r.id AS reg_id, r.status, r.registered_at,
               u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
               a.marked_at
        FROM registrations r
        JOIN users u ON r.user_id = u.id
        LEFT JOIN attendance a ON r.id = a.registration_id
        WHERE r.event_id = :event_id
        ORDER BY u.name ASC
    ";
    $stmt = $db->prepare($query);
    $stmt->execute(['event_id' => $event_id]);

    while ($row = $stmt->fetch()) {
        $check_in_status = ($row['marked_at'] !== null) ? 'Present' : 'Absent';
        $check_in_time = ($row['marked_at'] !== null) ? date('M d, Y h:i A', strtotime($row['marked_at'])) : 'N/A';
        
        fputcsv($output, [
            '#SE-' . sprintf('%05d', $row['reg_id']),
            $row['user_name'],
            $row['user_email'],
            $row['user_phone'],
            ucfirst($row['status']),
            date('Y-m-d H:i:s', strtotime($row['registered_at'])),
            $check_in_status,
            $check_in_time
        ]);
    }

    // Close output stream
    fclose($output);
    exit();

} catch (PDOException $e) {
    die("Database export error: " . $e->getMessage());
}
?>
