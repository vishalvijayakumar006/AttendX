<?php
// Folder: C:\Users\Vaishnavi.V.A\.gemini\antigravity\scratch\smart-event-entry\includes
// File: auth_check.php
// Purpose: Protects private routes. Redirects unauthorized users back to the login page.

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require user to be logged in to view a page.
 * If not logged in, redirect to login page.
 */
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error_message'] = "Please log in to access this page.";
        header("Location: /smart-event-entry/login.php");
        exit();
    }
}

/**
 * Require user to have Admin role.
 * If not admin, redirect to user dashboard with error.
 */
function require_admin() {
    // First, verify they are logged in at all
    require_login();
    
    // Check role attribute
    if ($_SESSION['user_role'] !== 'admin') {
        $_SESSION['error_message'] = "Access denied. Administrative privileges required.";
        header("Location: /smart-event-entry/user/dashboard.php");
        exit();
    }
}

/**
 * Redirect user if they are already logged in.
 * Useful on login and registration pages to prevent double login.
 */
function redirect_if_logged_in() {
    if (isset($_SESSION['user_id'])) {
        if ($_SESSION['user_role'] === 'admin') {
            header("Location: /smart-event-entry/admin/dashboard.php");
        } else {
            header("Location: /smart-event-entry/user/dashboard.php");
        }
        exit();
    }
}
?>
