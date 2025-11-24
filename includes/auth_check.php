<?php
/**
 * Authentication and Authorization Check
 * Provides security functions for protected pages
 */

/**
 * Set security headers to prevent XSS, clickjacking, etc.
 */
function setSecurityHeaders() {
    // Get Supabase URL from environment for CSP
    $supabaseUrl = getenv('SUPABASE_URL') ?: 'https://*.supabase.co';
    
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (adjust as needed)
    header("Content-Security-Policy: default-src 'self' $supabaseUrl https://cdn.jsdelivr.net; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net $supabaseUrl; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com;");
}

/**
 * Validate that the user has an active session
 * Redirects to login if session is invalid or expired
 */
function validateSession() {
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        // Store the current page to redirect back after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login with timeout message
        header('Location: ../login.php?timeout=1');
        exit();
    }
    
    // Check session timeout (1 hour of inactivity)
    $timeout_duration = 3600; // 1 hour in seconds
    
    if (isset($_SESSION['last_activity'])) {
        $elapsed_time = time() - $_SESSION['last_activity'];
        
        if ($elapsed_time > $timeout_duration) {
            // Session expired, destroy it
            session_unset();
            session_destroy();
            
            header('Location: ../login.php?timeout=1');
            exit();
        }
    }
    
    // Check user status in database (verify account is still active)
    // This ensures disabled users are kicked out immediately, even if already logged in
    // Only check every 30 seconds to avoid excessive database queries
    $should_check_status = !isset($_SESSION['last_status_check']) || 
                          (time() - $_SESSION['last_status_check']) > 30;
    
    if ($should_check_status) {
        try {
            // Use global $pdo if available, otherwise create connection
            global $pdo;
            
            if (!isset($pdo)) {
                // Create database connection
                require_once __DIR__ . '/../includes/env_loader.php';
                
                $host = getenv('DB_HOST') ?: 'aws-1-us-east-2.pooler.supabase.com';
                $port = getenv('DB_PORT') ?: '6543';
                $dbname = getenv('DB_NAME') ?: 'postgres';
                $username = getenv('DB_USER') ?: '';
                $password = getenv('DB_PASSWORD') ?: '';
                
                $databaseUrl = getenv('DATABASE_URL') ?: getenv('SUPABASE_DB_URL');
                if ($databaseUrl) {
                    $parts = parse_url($databaseUrl);
                    if ($parts !== false && isset($parts['scheme'])) {
                        $host = $parts['host'] ?? $host;
                        $port = (string)($parts['port'] ?? $port);
                        $username = $parts['user'] ?? $username;
                        $password = $parts['pass'] ?? $password;
                        $dbname = ltrim($parts['path'] ?? ('/' . $dbname), '/');
                    }
                }
                
                $dsn = 'pgsql:host=' . $host . ';' .
                       ($port ? ('port=' . $port . ';') : '') .
                       'dbname=' . $dbname . ';sslmode=require';
                       
                $pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            }
            
            $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if (!$user || $user['status'] !== 'active') {
                // User account is disabled, suspended, or deleted
                $status = $user['status'] ?? 'inactive';
                
                // Destroy session
                session_unset();
                session_destroy();
                
                // Redirect to login with appropriate message
                $message = urlencode('Your account has been disabled. Please contact the administrator.');
                header("Location: ../login.php?error=$message");
                exit();
            }
            
            // Update session status and last check time
            $_SESSION['status'] = $user['status'];
            $_SESSION['last_status_check'] = time();
            
        } catch (Exception $e) {
            // Log error but don't break the application
            error_log("Session validation error: " . $e->getMessage());
            // Continue with existing session if database check fails
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

/**
 * Require admin role
 * Redirects to customer dashboard if user is not an admin
 */
function requireAdmin() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        // Not an admin, redirect to customer dashboard
        header('Location: ../customer/dashboard.php');
        exit();
    }
}

/**
 * Require customer role
 * Redirects to admin dashboard if user is an admin
 */
function requireCustomer() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
        // Not a customer, redirect to admin dashboard
        header('Location: ../admin/dashboard.php');
        exit();
    }
}

/**
 * Get user's full name from session
 * @return string Full name
 */
function getUserFullName() {
    $first_name = $_SESSION['first_name'] ?? '';
    $middle_name = $_SESSION['middle_name'] ?? '';
    $last_name = $_SESSION['last_name'] ?? '';
    
    $full_name = $first_name;
    if (!empty($middle_name)) {
        $full_name .= ' ' . $middle_name;
    }
    if (!empty($last_name)) {
        $full_name .= ' ' . $last_name;
    }
    
    return trim($full_name);
}

/**
 * Check if user is logged in (without redirecting)
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Check if user is admin (without redirecting)
 * @return bool True if admin, false otherwise
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if user is customer (without redirecting)
 * @return bool True if customer, false otherwise
 */
function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

/**
 * Sanitize user input to prevent XSS
 * @param string $data Input data
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email format
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (basic validation)
 * @param string $phone Phone number to validate
 * @return bool True if valid, false otherwise
 */
function validatePhone($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if length is between 10-15 digits
    return strlen($phone) >= 10 && strlen($phone) <= 15;
}

/**
 * Generate CSRF token
 * @return string CSRF token
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token Token to verify
 * @return bool True if valid, false otherwise
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
