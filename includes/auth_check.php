<?php
// Common authentication and security functions

// Set security headers to prevent caching and back button access
function setSecurityHeaders() {
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");
}

// Check if user is logged in (general check)
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        setSecurityHeaders();
        header('Location: ../login.php');
        exit();
    }
}

// Check if user is admin
function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        setSecurityHeaders();
        header('Location: ../login.php');
        exit();
    }
}

// Check if user is customer
function requireCustomer() {
    requireLogin();
    if ($_SESSION['role'] !== 'customer') {
        setSecurityHeaders();
        header('Location: ../login.php');
        exit();
    }
}

// Validate session and regenerate ID for security
function validateSession() {
    if (isset($_SESSION['user_id'])) {
        // Regenerate session ID periodically for security
        if (!isset($_SESSION['last_regeneration']) || 
            (time() - $_SESSION['last_regeneration']) > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}
?>
