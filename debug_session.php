<?php
require_once 'includes/session_config.php';

// Display all session variables for debugging
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Debug</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .debug-box {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        .session-data {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
        }
        .key {
            color: #e74c3c;
            font-weight: bold;
        }
        .value {
            color: #27ae60;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="debug-box">
        <h1>🔍 Session Debug Information</h1>
        
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
            <div class="info">
                ✅ <strong>Session Active:</strong> You are currently logged in.
            </div>
            
            <h2>Session Variables:</h2>
            <div class="session-data">
                <?php foreach ($_SESSION as $key => $value): ?>
                    <div>
                        <span class="key"><?php echo htmlspecialchars($key); ?>:</span> 
                        <span class="value"><?php echo htmlspecialchars(is_array($value) ? json_encode($value) : $value); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <h2>Role Check:</h2>
            <div class="session-data">
                <strong>Current Role:</strong> <?php echo htmlspecialchars($_SESSION['role'] ?? 'NOT SET'); ?><br>
                <strong>Is Admin:</strong> <?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'YES' : 'NO'; ?><br>
                <strong>Is Customer:</strong> <?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'customer') ? 'YES' : 'NO'; ?>
            </div>
            
            <h2>Expected Dashboard:</h2>
            <div class="session-data">
                <?php
                $expected_dashboard = ($_SESSION['role'] ?? '') === 'admin' 
                    ? '/GameLend/admin/dashboard.php' 
                    : '/GameLend/customer/dashboard.php';
                ?>
                <strong>You should be redirected to:</strong> 
                <a href="<?php echo htmlspecialchars($expected_dashboard); ?>"><?php echo htmlspecialchars($expected_dashboard); ?></a>
            </div>
            
        <?php else: ?>
            <div class="warning">
                ⚠️ <strong>No Active Session:</strong> You are not logged in.
            </div>
        <?php endif; ?>
        
        <h2>Quick Actions:</h2>
        <a href="customer/dashboard.php" class="btn">Go to Customer Dashboard</a>
        <a href="admin/dashboard.php" class="btn">Go to Admin Dashboard</a>
        <a href="logout.php" class="btn" style="background: #e74c3c;">Logout & Clear Session</a>
    </div>
</body>
</html>
