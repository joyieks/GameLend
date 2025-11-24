<?php
require_once 'includes/session_config.php';
require_once 'db/db_connect.php';

// Get Supabase config
$config = require_once 'includes/supabase_config.php';

header('Content-Type: application/json');

// Get the posted data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['access_token'])) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Authentication failed. Please try logging in again.',
        'message_type' => 'authentication_error'
    ]);
    exit;
}

$access_token = $data['access_token'];

// Verify the token with Supabase
$supabaseUrl = $config['SUPABASE_URL'];
$ch = curl_init($supabaseUrl . '/auth/v1/user');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $access_token,
    'apikey: ' . $config['SUPABASE_ANON_KEY']
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code(401);
    error_log("Supabase auth failed. HTTP Code: $httpCode, Response: $response, CURL Error: $curlError");
    echo json_encode([
        'error' => 'Your session has expired. Please log in again to continue.',
        'message_type' => 'session_expired',
        'debug' => [
            'http_code' => $httpCode,
            'response' => $response
        ]
    ]);
    exit;
}

$user = json_decode($response, true);

if (!$user || !isset($user['id'])) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Unable to verify your account. Please try logging in again.',
        'message_type' => 'user_data_error'
    ]);
    exit;
}

// Get user from database
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE auth_id = ?");
    $stmt->execute([$user['id']]);
    $dbUser = $stmt->fetch();

    if (!$dbUser) {
        // User doesn't exist in database, create them
        $metadata = $user['user_metadata'] ?? [];
        $stmt = $pdo->prepare("
            INSERT INTO users (auth_id, email, first_name, middle_name, last_name, phone, role, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, 'customer', 'active', NOW(), NOW())
        ");
        $stmt->execute([
            $user['id'],
            $user['email'],
            $metadata['first_name'] ?? 'User',
            $metadata['middle_name'] ?? null,
            $metadata['last_name'] ?? 'User',
            $metadata['phone'] ?? null
        ]);
        
        // Fetch the newly created user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE auth_id = ?");
        $stmt->execute([$user['id']]);
        $dbUser = $stmt->fetch();
    }

    // Check if user account is active
    if ($dbUser['status'] !== 'active') {
        http_response_code(403);
        
        // Provide clear, professional error messages based on account status
        $error_messages = [
            'inactive' => 'Your account has been disabled by an administrator. Please contact support for assistance.',
            'suspended' => 'Your account has been temporarily suspended. Please contact the administrator to resolve this issue.',
            'pending' => 'Your account is pending approval. Please wait for administrator verification.',
            'banned' => 'Your account has been permanently banned. Please contact support if you believe this is an error.'
        ];
        
        $status_message = $error_messages[$dbUser['status']] ?? 'Your account status is currently ' . ucfirst($dbUser['status']) . '. Please contact the administrator for assistance.';
        
        echo json_encode([
            'error' => $status_message,
            'status' => $dbUser['status'],
            'message_type' => 'account_status'
        ]);
        exit();
    }

    // Create PHP session
    $_SESSION['user_id'] = $dbUser['id'];
    $_SESSION['auth_id'] = $dbUser['auth_id'];
    $_SESSION['email'] = $dbUser['email'];
    $_SESSION['first_name'] = $dbUser['first_name'];
    $_SESSION['middle_name'] = $dbUser['middle_name'];
    $_SESSION['last_name'] = $dbUser['last_name'];
    $_SESSION['role'] = $dbUser['role'];
    $_SESSION['status'] = $dbUser['status'];
    $_SESSION['logged_in'] = true;

    // Determine redirect URL (use relative path to work with subdirectory)
    $redirectUrl = ($dbUser['role'] === 'admin') 
        ? 'admin/dashboard.php' 
        : 'customer/dashboard.php';

    echo json_encode([
        'success' => true,
        'redirect' => $redirectUrl,
        'user' => [
            'id' => $dbUser['id'],
            'email' => $dbUser['email'],
            'first_name' => $dbUser['first_name'],
            'last_name' => $dbUser['last_name'],
            'role' => $dbUser['role']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Login database error: " . $e->getMessage());
    echo json_encode([
        'error' => 'An error occurred while processing your login. Please try again or contact support if the problem persists.',
        'message_type' => 'database_error'
    ]);
}
?>
