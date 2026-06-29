<?php
// Admin Events Directory: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry\admin\events
// File Name: delete.php
// Purpose: Handles deletion of an event.

require_once '../../config/database.php';
require_once '../../includes/auth_check.php';

// Route security guard: Ensure only Admin can access
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['id'])) {
    // Collect ID (prefers POST for security, falls back to GET for simple links)
    $event_id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

    if ($event_id > 0) {
        $db = Database::getInstance();

        try {
            // Fetch name for user feedback before deleting
            $name_stmt = $db->prepare("SELECT title FROM events WHERE id = :id");
            $name_stmt->execute(['id' => $event_id]);
            $event = $name_stmt->fetch();

            if ($event) {
                $title = $event['title'];
                
                // Execute delete statement
                $stmt = $db->prepare("DELETE FROM events WHERE id = :id");
                $stmt->execute(['id' => $event_id]);

                $_SESSION['success_message'] = "Event '$title' and all associated registrations/QR codes have been deleted.";
            } else {
                $_SESSION['error_message'] = "Event not found.";
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Failed to delete event: " . $e->getMessage();
        }
    }
}

// Redirect back to admin dashboard
header("Location: ../dashboard.php");
exit();
?>
