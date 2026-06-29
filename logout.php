<?php
// Root Directory: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry
// File Name: logout.php
// Purpose: Destroys user session and logs out user, returning to login screen.

session_start();

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This completely clears the browser session identifiers.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Restart session just to pass a success message to the login page
session_start();
$_SESSION['success_message'] = "You have been successfully logged out.";

header("Location: login.php");
exit();
?>
