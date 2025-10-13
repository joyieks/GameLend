<?php
// Database connection configuration (uses environment variables in production)

// Prefer environment variables (Render) and fallback to local defaults
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'gamelend_db';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';

// Optional: Render provides DATABASE_URL sometimes; parse if present (mysql://user:pass@host:port/db)
$databaseUrl = getenv('DATABASE_URL');
if ($databaseUrl && str_starts_with($databaseUrl, 'mysql://')) {
    $parts = parse_url($databaseUrl);
    if ($parts !== false) {
        $host = ($parts['host'] ?? $host) . (isset($parts['port']) ? ':' . $parts['port'] : '');
        $username = $parts['user'] ?? $username;
        $password = $parts['pass'] ?? $password;
        $dbname = ltrim($parts['path'] ?? ('/' . $dbname), '/');
    }
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    http_response_code(500);
    die("Database connection failed.");
}
?>
