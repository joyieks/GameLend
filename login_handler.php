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
    echo json_encode(['error' => 'Missing access token']);
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
        'error' => 'Invalid token or expired session. Please try logging in again.',
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
    echo json_encode(['error' => 'Invalid user data']);
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

    // Create PHP session
    $_SESSION['user_id'] = $dbUser['id'];
    $_SESSION['auth_id'] = $dbUser['auth_id'];
    $_SESSION['email'] = $dbUser['email'];
    $_SESSION['first_name'] = $dbUser['first_name'];
    $_SESSION['middle_name'] = $dbUser['middle_name'];
    $_SESSION['last_name'] = $dbUser['last_name'];
    $_SESSION['role'] = $dbUser['role'];
    $_SESSION['logged_in'] = true;

    // Determine redirect URL
    $redirectUrl = ($dbUser['role'] === 'admin') 
        ? '/GameLend/admin/dashboard.php' 
        : '/GameLend/customer/dashboard.php';

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
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
