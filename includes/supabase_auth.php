<?php
/**
 * Supabase Auth Configuration
 * 
 * Set these environment variables or update the defaults below
 */

// Supabase Project Configuration
define('SUPABASE_URL', getenv('SUPABASE_URL') ?: '');
define('SUPABASE_ANON_KEY', getenv('SUPABASE_ANON_KEY') ?: '');
define('SUPABASE_SERVICE_ROLE_KEY', getenv('SUPABASE_SERVICE_ROLE_KEY') ?: ''); // Optional, for admin operations

// Session Configuration
define('SESSION_COOKIE_NAME', 'gamelend_session');
define('SESSION_TIMEOUT', 3600 * 24 * 7); // 7 days

/**
 * Get Supabase JWT token from cookie/session
 */
function getSupabaseToken() {
    if (isset($_COOKIE[SESSION_COOKIE_NAME])) {
        return $_COOKIE[SESSION_COOKIE_NAME];
    }
    if (isset($_SESSION['supabase_token'])) {
        return $_SESSION['supabase_token'];
    }
    return null;
}

/**
 * Verify Supabase JWT token
 * Returns user data if valid, null otherwise
 */
function verifySupabaseToken($token) {
    if (empty($token)) {
        return null;
    }
    
    // Call Supabase API to verify token
    $ch = curl_init(SUPABASE_URL . '/auth/v1/user');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'apikey: ' . SUPABASE_ANON_KEY
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    return null;
}

/**
 * Get or create application user from Supabase auth user
 */
function getOrCreateAppUser($pdo, $authUser) {
    if (!$authUser || !isset($authUser['id'])) {
        return null;
    }
    
    $authId = $authUser['id'];
    $email = $authUser['email'];
    
    // Check if user exists in our database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE auth_id = ?");
    $stmt->execute([$authId]);
    $user = $stmt->fetch();
    
    if ($user) {
        return $user;
    }
    
    // User doesn't exist, create them
    $userMetadata = $authUser['user_metadata'] ?? [];
    $firstName = $userMetadata['first_name'] ?? 'User';
    $middleName = $userMetadata['middle_name'] ?? null;
    $lastName = $userMetadata['last_name'] ?? 'Name';
    $phone = $userMetadata['phone'] ?? null;
    $role = $userMetadata['role'] ?? 'customer';
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (auth_id, email, first_name, middle_name, last_name, phone, role, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
            RETURNING *
        ");
        $stmt->execute([$authId, $email, $firstName, $middleName, $lastName, $phone, $role]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error creating user: " . $e->getMessage());
        return null;
    }
}

/**
 * Initialize authentication session
 */
function initializeAuthSession($pdo) {
    $token = getSupabaseToken();
    
    if (!$token) {
        return false;
    }
    
    // Verify token with Supabase
    $authUser = verifySupabaseToken($token);
    
    if (!$authUser) {
        // Token invalid, clear session
        unset($_SESSION['user_id']);
        unset($_SESSION['supabase_token']);
        setcookie(SESSION_COOKIE_NAME, '', time() - 3600, '/');
        return false;
    }
    
    // Get or create application user
    $appUser = getOrCreateAppUser($pdo, $authUser);
    
    if (!$appUser) {
        return false;
    }
    
    // Set session variables
    $_SESSION['user_id'] = $appUser['id'];
    $_SESSION['auth_id'] = $appUser['auth_id'];
    $_SESSION['email'] = $appUser['email'];
    $_SESSION['first_name'] = $appUser['first_name'];
    $_SESSION['middle_name'] = $appUser['middle_name'];
    $_SESSION['last_name'] = $appUser['last_name'];
    $_SESSION['role'] = $appUser['status'] === 'active' ? $appUser['role'] : 'suspended';
    $_SESSION['status'] = $appUser['status'];
    $_SESSION['supabase_token'] = $token;
    
    // Set RLS context
    if (function_exists('setRLSContext')) {
        setRLSContext($pdo, $appUser['id']);
    }
    
    return true;
}

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && isset($_SESSION['auth_id']);
}

/**
 * Require authentication (redirect if not logged in)
 */
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: /GameLend/auth.php');
        exit();
    }
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isAuthenticated() && $_SESSION['role'] === 'admin';
}

/**
 * Require admin role
 */
function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        http_response_code(403);
        die('Access denied. Admin privileges required.');
    }
}

/**
 * Logout user
 */
function logout() {
    // Clear session
    $_SESSION = [];
    session_destroy();
    
    // Clear cookie
    setcookie(SESSION_COOKIE_NAME, '', time() - 3600, '/');
    
    // Note: Actual Supabase sign out happens on client-side via JavaScript
}
