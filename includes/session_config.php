<?php
/**
 * Session Configuration
 * 
 * This file configures PHP sessions to expire when the browser is closed
 * and implements additional security measures.
 */

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Load environment configuration
    $isProduction = (getenv('APP_ENV') === 'production');
    $sessionSecure = filter_var(getenv('SESSION_SECURE') ?: 'false', FILTER_VALIDATE_BOOLEAN);
    $sessionSamesite = getenv('SESSION_SAMESITE') ?: 'Lax';
    $sessionLifetime = (int)(getenv('SESSION_LIFETIME') ?: 0);
    
    // Configure session cookie parameters BEFORE starting the session
    // Set session cookie to expire when browser closes (0 = session cookie)
    ini_set('session.cookie_lifetime', (string)$sessionLifetime);
    
    // Prevent session fixation attacks
    ini_set('session.use_strict_mode', '1');
    
    // Prevent session ID from being passed in URL
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    
    // Use httponly to prevent JavaScript access to session cookie
    ini_set('session.cookie_httponly', '1');
    
    // Use secure cookies if on HTTPS (production)
    if ($sessionSecure || $isProduction) {
        ini_set('session.cookie_secure', '1');
    }
    
    // Prevent browsers from caching session pages
    session_cache_limiter('nocache');
    
    // Set session cookie parameters
    session_set_cookie_params([
        'lifetime' => $sessionLifetime,  // Expire when browser closes (0) or custom
        'path' => '/',
        'domain' => '',                   // Current domain
        'secure' => $sessionSecure || $isProduction,  // true for HTTPS
        'httponly' => true,               // Prevent JavaScript access
        'samesite' => $sessionSamesite    // CSRF protection (Lax or Strict)
    ]);
    
    // Start the session
    session_start();
    
    // Regenerate session ID periodically to prevent session fixation
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
        // Session started more than 30 minutes ago
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
    
    // Add session timeout (auto-logout after inactivity)
    $inactive_timeout = 3600; // 1 hour of inactivity
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive_timeout)) {
        // Last activity was more than 1 hour ago
        session_unset();
        session_destroy();
        
        // Redirect to login page if trying to access protected pages
        if (basename($_SERVER['PHP_SELF']) !== 'login.php' && 
            basename($_SERVER['PHP_SELF']) !== 'register.php' && 
            basename($_SERVER['PHP_SELF']) !== 'index.php') {
            header('Location: /GameLend/login.php?timeout=1');
            exit();
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}
?>
