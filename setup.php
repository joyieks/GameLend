<?php
// GameLend Setup Script
// This script helps verify your installation and provides setup guidance

$page_title = "Setup & Installation Guide";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - GameLend</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-brand">
                <a href="index.php">
                    <i class="fas fa-gamepad"></i>
                    GameLend Setup
                </a>
            </div>
        </nav>
    </header>
    
    <main class="container">
        <div class="hero">
            <h1>GameLend Setup & Installation</h1>
            <p>Welcome to GameLend! Let's get your system up and running.</p>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">System Requirements Check</h2>
            </div>
            
            <?php
            $requirements = [];
            
            // Check PHP version
            $php_version = phpversion();
            $requirements['PHP Version'] = [
                'required' => '7.4+',
                'current' => $php_version,
                'status' => version_compare($php_version, '7.4.0', '>=')
            ];
            
            // Check MySQL extension
            $requirements['MySQL Extension'] = [
                'required' => 'Installed',
                'current' => extension_loaded('pdo_mysql') ? 'Installed' : 'Not Installed',
                'status' => extension_loaded('pdo_mysql')
            ];
            
            // Check session support
            $requirements['Session Support'] = [
                'required' => 'Enabled',
                'current' => function_exists('session_start') ? 'Enabled' : 'Disabled',
                'status' => function_exists('session_start')
            ];
            
            // Check file permissions
            $requirements['File Permissions'] = [
                'required' => 'Readable',
                'current' => is_readable('db/db_connect.php') ? 'Readable' : 'Not Readable',
                'status' => is_readable('db/db_connect.php')
            ];
            
            // Check database connection
            $db_status = 'Not Tested';
            $db_working = false;
            if (file_exists('db/db_connect.php')) {
                try {
                    require_once 'db/db_connect.php';
                    $db_status = 'Connected Successfully';
                    $db_working = true;
                } catch (Exception $e) {
                    $db_status = 'Connection Failed: ' . $e->getMessage();
                }
            }
            
            $requirements['Database Connection'] = [
                'required' => 'Connected',
                'current' => $db_status,
                'status' => $db_working
            ];
            ?>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Requirement</th>
                        <th>Required</th>
                        <th>Current</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($requirements as $name => $req): ?>
                        <tr>
                            <td><?php echo $name; ?></td>
                            <td><?php echo $req['required']; ?></td>
                            <td><?php echo $req['current']; ?></td>
                            <td>
                                <?php if($req['status']): ?>
                                    <span class="badge badge-success">✓ Pass</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">✗ Fail</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php
            $all_passed = array_reduce($requirements, function($carry, $req) {
                return $carry && $req['status'];
            }, true);
            ?>
            
            <?php if($all_passed): ?>
                <div class="alert alert-success">
                    <h4>🎉 All Requirements Met!</h4>
                    <p>Your system is ready to run GameLend. You can now:</p>
                    <ul>
                        <li><a href="index.php">Visit the homepage</a></li>
                        <li><a href="login.php">Login as admin</a> (username: admin, password: admin123)</li>
                        <li><a href="register.php">Register a new customer account</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    <h4>⚠️ Some Requirements Not Met</h4>
                    <p>Please fix the issues above before proceeding.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Installation Steps</h2>
            </div>
            
            <div class="grid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">1. Install XAMPP</h3>
                    </div>
                    <p>Download and install XAMPP from <a href="https://www.apachefriends.org/" target="_blank">apachefriends.org</a></p>
                    <p>Make sure Apache and MySQL services are running.</p>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">2. Database Setup</h3>
                    </div>
                    <p>Open phpMyAdmin at <a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a></p>
                    <p>Create a new database named <strong>gamelend_db</strong></p>
                    <p>Import the SQL file: <code>db/gamelend_db.sql</code></p>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">3. Configuration</h3>
                    </div>
                    <p>Check database settings in <code>db/db_connect.php</code></p>
                    <p>Default settings work with XAMPP's default MySQL configuration.</p>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">4. Access System</h3>
                    </div>
                    <p>Navigate to <a href="index.php">http://localhost/GameLend/</a></p>
                    <p>Login as admin: username <strong>admin</strong>, password <strong>admin123</strong></p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Default Login Credentials</h2>
            </div>
            
            <div class="grid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Admin Account</h3>
                    </div>
                    <p><strong>Username:</strong> admin</p>
                    <p><strong>Password:</strong> admin123</p>
                    <p><strong>Email:</strong> admin@gamelend.com</p>
                    <a href="login.php" class="btn btn-primary">Login as Admin</a>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Customer Account</h3>
                    </div>
                    <p>Register a new customer account through the registration page.</p>
                    <a href="register.php" class="btn btn-success">Register New Account</a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Quick Links</h2>
            </div>
            
            <div class="grid">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Go to Homepage
                </a>
                <a href="login.php" class="btn btn-success">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="register.php" class="btn btn-warning">
                    <i class="fas fa-user-plus"></i> Register
                </a>
                <a href="games.php" class="btn btn-info">
                    <i class="fas fa-gamepad"></i> Browse Games
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Need Help?</h2>
            </div>
            
            <p>If you encounter any issues:</p>
            <ol>
                <li>Check that XAMPP is running (Apache + MySQL)</li>
                <li>Verify the database <strong>gamelend_db</strong> exists</li>
                <li>Check file permissions on the GameLend folder</li>
                <li>Review the README.md file for detailed instructions</li>
                <li>Check PHP and MySQL error logs</li>
            </ol>
            
            <p><strong>Common Issues:</strong></p>
            <ul>
                <li><strong>404 Error:</strong> Make sure the GameLend folder is in your htdocs directory</li>
                <li><strong>Database Connection Error:</strong> Verify MySQL is running and credentials are correct</li>
                <li><strong>Permission Denied:</strong> Check file permissions on the GameLend folder</li>
            </ul>
        </div>
    </main>
    
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 GameLend. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
