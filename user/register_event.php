<?php
// User Directory: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry\user
// File Name: register_event.php
// Purpose: Handles backend logic for an attendee registering for a specific event.

require_once '../config/database.php';
require_once '../includes/auth_check.php';

// Route security guard: Ensure user is logged in
require_login();

// 1. Verify event ID exists in request
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Invalid event registration request.";
    header("Location: events.php");
    exit();
}

$event_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$db = Database::getInstance();

try {
    // 2. Fetch event capacity and active bookings (LEFT JOIN for speed)
    $stmt = $db->prepare("
        SELECT e.title, e.max_capacity, COUNT(r.id) AS registered_count 
        FROM events e 
        LEFT JOIN registrations r ON e.id = r.event_id AND r.status != 'rejected'
        WHERE e.id = :id
        GROUP BY e.id
    ");
    $stmt->execute(['id' => $event_id]);
    $event = $stmt->fetch();

    if (!$event) {
        $_SESSION['error_message'] = "Event not found.";
        header("Location: events.php");
        exit();
    }

    $capacity = intval($event['max_capacity']);
    $registered = intval($event['registered_count']);

    // Check capacity constraint
    if ($registered >= $capacity) {
        $_SESSION['error_message'] = "Sorry, '" . htmlspecialchars($event['title']) . "' is already full!";
        header("Location: events.php");
        exit();
    }

    // 3. Prevent duplicate registration
    $check_stmt = $db->prepare("SELECT id FROM registrations WHERE user_id = :user_id AND event_id = :event_id");
    $check_stmt->execute([
        'user_id' => $user_id,
        'event_id' => $event_id
    ]);

    if ($check_stmt->rowCount() > 0) {
        $_SESSION['error_message'] = "You are already registered for this event.";
        header("Location: dashboard.php");
        exit();
    }

    // 4. Save registration record to database
    // Default registration status is 'approved' for instant ticket generation
    $insert_stmt = $db->prepare("INSERT INTO registrations (user_id, event_id, status) VALUES (:user_id, :event_id, 'approved')");
    $insert_stmt->execute([
        'user_id' => $user_id,
        'event_id' => $event_id
    ]);

    // Get the newly created registration ID
    $registration_id = $db->lastInsertId();

    // 5. Generate secure QR Code (Module 7 Integration)
    require_once '../libs/phpqrcode/qrlib.php';

    // Generate a cryptographically secure, random token (32 chars)
    $qr_token = bin2hex(random_bytes(16));

    // Ensure upload directory exists
    $upload_dir = '../uploads/qrcodes/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Define image name and server paths
    $file_name = 'pass_' . $qr_token . '.png';
    $file_path = $upload_dir . $file_name;
    
    // Stored database path relative to project root (e.g. uploads/qrcodes/pass_abc.png)
    $db_image_path = 'uploads/qrcodes/' . $file_name;

    // Generate and save the QR code PNG image file to our directory
    // QRcode::png(text_to_encode, output_file_path, error_correction_level, size_multiplier)
    QRcode::png($qr_token, $file_path, QR_ECLEVEL_H, 6);

    // Save QR Code metadata to database
    $qr_stmt = $db->prepare("INSERT INTO qr_codes (registration_id, qr_token, qr_image_path) VALUES (:registration_id, :qr_token, :qr_image_path)");
    $qr_stmt->execute([
        'registration_id' => $registration_id,
        'qr_token' => $qr_token,
        'qr_image_path' => $db_image_path
    ]);

    $_SESSION['success_message'] = "Successfully registered! Your unique entry pass has been generated.";
    header("Location: dashboard.php");
    exit();

} catch (PDOException $e) {
    $_SESSION['error_message'] = "Registration failed due to a server database error: " . $e->getMessage();
    header("Location: events.php");
    exit();
}
?>
