<?php
/**
 * Postgres/Supabase Database Connection
 * Loads credentials from environment variables
 */

// Load environment variables from .env file
require_once __DIR__ . '/../includes/env_loader.php';

// Supabase connection details - Using Transaction Pooler (IPv4 compatible)
// Transaction pooler is required for IPv4-only environments like XAMPP
$host = getenv('DB_HOST') ?: 'aws-1-us-east-2.pooler.supabase.com';
$port = getenv('DB_PORT') ?: '6543'; // Transaction pooler uses port 6543
$dbname = getenv('DB_NAME') ?: 'postgres';
$username = getenv('DB_USER') ?: '';
$password = getenv('DB_PASSWORD') ?: '';

// Prefer DATABASE_URL/SUPABASE_DB_URL if provided
$databaseUrl = getenv('DATABASE_URL') ?: getenv('SUPABASE_DB_URL');
if ($databaseUrl) {
    $parts = parse_url($databaseUrl);
    if ($parts !== false && isset($parts['scheme']) && ($parts['scheme'] === 'postgres' || $parts['scheme'] === 'postgresql')) {
        $host = $parts['host'] ?? $host;
        $port = (string)($parts['port'] ?? $port);
        $username = $parts['user'] ?? $username;
        $password = $parts['pass'] ?? $password;
        $dbname = ltrim($parts['path'] ?? ('/' . $dbname), '/');
    }
}

try {
    // Supabase requires SSL connection
    $dsn = 'pgsql:host=' . $host . ';' .
           ($port ? ('port=' . $port . ';') : '') .
           'dbname=' . $dbname . ';sslmode=require';
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Include RLS context helper functions
    require_once __DIR__ . '/setup_rls_context.php';
    
    // Initialize RLS context if user is logged in
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
        initializeRLSFromSession($pdo);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    // Show detailed error for debugging (comment out in production)
    die('Database connection failed: ' . $e->getMessage());
    // die('Database connection failed.');
}
?>
