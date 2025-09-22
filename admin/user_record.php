<?php
session_start();
$page_title = "User Record";

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db/db_connect.php';

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$message_type = '';

if($user_id <= 0) {
    header('Location: users.php');
    exit();
}

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if(!$user) {
    header('Location: users.php');
    exit();
}

// Handle user profile update
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $gender = $_POST['gender'];
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    
    // Validation
    if(empty($first_name) || empty($last_name) || empty($gender) || empty($email) || empty($role)) {
        $message = 'All fields are required';
        $message_type = 'danger';
    } elseif(strlen($first_name) < 2) {
        $message = 'First name must be at least 2 characters long';
        $message_type = 'danger';
    } elseif(strlen($last_name) < 2) {
        $message = 'Last name must be at least 2 characters long';
        $message_type = 'danger';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address';
        $message_type = 'danger';
    } else {
        // Check if email already exists for another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        
        if($stmt->rowCount() > 0) {
            $message = 'Email already exists for another user';
            $message_type = 'danger';
        } else {
            // Update user profile
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, gender = ?, email = ?, role = ?, updated_at = NOW() WHERE id = ?");
            
            if($stmt->execute([$first_name, $last_name, $gender, $email, $role, $user_id])) {
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                $message = 'User profile updated successfully';
                $message_type = 'success';
            } else {
                $message = 'Failed to update user profile. Please try again.';
                $message_type = 'danger';
            }
        }
    }
}

// Handle password reset
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(empty($new_password) || empty($confirm_password)) {
        $message = 'Password fields are required';
        $message_type = 'danger';
    } elseif($new_password !== $confirm_password) {
        $message = 'Passwords do not match';
        $message_type = 'danger';
    } elseif(strlen($new_password) < 6) {
        $message = 'Password must be at least 6 characters long';
        $message_type = 'danger';
    } else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        
        if($stmt->execute([$hashed_password, $user_id])) {
            $message = 'Password reset successfully';
            $message_type = 'success';
        } else {
            $message = 'Failed to reset password. Please try again.';
            $message_type = 'danger';
        }
    }
}

// Handle user status toggle
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_status'])) {
    $new_status = $_POST['new_status'];
    
    // Don't allow admin to disable themselves
    if($user_id == $_SESSION['user_id'] && $new_status === 'disabled') {
        $message = 'You cannot disable your own account';
        $message_type = 'danger';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
        
        if($stmt->execute([$new_status, $user_id])) {
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            $status_text = $new_status === 'active' ? 'enabled' : 'disabled';
            $message = "User {$status_text} successfully";
            $message_type = 'success';
        } else {
            $message = 'Failed to update user status. Please try again.';
            $message_type = 'danger';
        }
    }
}

// Get user statistics
$stmt = $pdo->prepare("SELECT 
    COUNT(CASE WHEN status = 'borrowed' THEN 1 END) as active_borrows,
    COUNT(CASE WHEN status = 'returned' THEN 1 END) as total_returns,
    COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_count,
    COUNT(*) as total_transactions
    FROM borrow_transactions WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Get currently borrowed games
$stmt = $pdo->prepare("SELECT bt.*, g.title, g.platform, g.status as game_status
                       FROM borrow_transactions bt 
                       JOIN games g ON bt.game_id = g.id 
                       WHERE bt.user_id = ? AND bt.status = 'borrowed' 
                       ORDER BY bt.borrow_date DESC");
$stmt->execute([$user_id]);
$borrowed_games = $stmt->fetchAll();

// Get recent transaction history
$stmt = $pdo->prepare("SELECT bt.*, g.title, g.platform 
                       FROM borrow_transactions bt 
                       JOIN games g ON bt.game_id = g.id 
                       WHERE bt.user_id = ? 
                       ORDER BY bt.borrow_date DESC LIMIT 20");
$stmt->execute([$user_id]);
$transaction_history = $stmt->fetchAll();

// Calculate overdue games
$overdue_count = 0;
foreach($borrowed_games as $game) {
    $borrow_date = new DateTime($game['borrow_date']);
    $now = new DateTime();
    $diff = $now->diff($borrow_date);
    if($diff->days > 14) {
        $overdue_count++;
    }
}

include 'includes/admin_header.php';
?>

<style>
    .user-record-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .user-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .user-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        opacity: 0.3;
    }
    
    .user-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        margin: 0 auto 1rem;
        border: 4px solid rgba(255, 255, 255, 0.3);
        position: relative;
        z-index: 1;
    }
    
    .user-name {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        position: relative;
        z-index: 1;
    }
    
    .user-role {
        font-size: 1.1rem;
        opacity: 0.9;
        position: relative;
        z-index: 1;
        background: rgba(0, 206, 201, 0.2);
        padding: 0.3rem 1rem;
        border-radius: 20px;
        display: inline-block;
    }
    
    .user-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-top: 2rem;
    }
    
    .stat-card {
        background: rgba(255, 255, 255, 0.1);
        padding: 1rem;
        border-radius: 10px;
        text-align: center;
        backdrop-filter: blur(10px);
        position: relative;
        z-index: 1;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        font-size: 0.9rem;
        opacity: 0.8;
    }
    
    .content-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    .content-section {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        border: 1px solid #f0f0f0;
    }
    
    .section-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        color: #333;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .section-title i {
        color: #2c3e50;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #555;
    }
    
    .form-control {
        width: 100%;
        padding: 0.8rem;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: #f8f9fa;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #2c3e50;
        background: white;
        box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.1);
    }
    
    .btn {
        padding: 0.8rem 1.5rem;
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        text-align: center;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(44, 62, 80, 0.4);
    }
    
    .btn-success {
        background: #28a745;
        color: white;
    }
    
    .btn-success:hover {
        background: #218838;
        transform: translateY(-2px);
    }
    
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    
    .btn-danger:hover {
        background: #c82333;
        transform: translateY(-2px);
    }
    
    .btn-warning {
        background: #ffc107;
        color: #212529;
    }
    
    .btn-warning:hover {
        background: #e0a800;
        transform: translateY(-2px);
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }
    
    .alert {
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        font-weight: 500;
    }
    
    .alert-success {
        background: rgba(40, 167, 69, 0.1);
        color: #155724;
        border: 1px solid rgba(40, 167, 69, 0.2);
    }
    
    .alert-danger {
        background: rgba(220, 53, 69, 0.1);
        color: #721c24;
        border: 1px solid rgba(220, 53, 69, 0.2);
    }
    
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.8rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        font-weight: 600;
        color: #555;
    }
    
    .info-value {
        color: #333;
    }
    
    .badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
    }
    
    .badge-primary {
        background-color: #007bff;
        color: white;
    }
    
    .badge-danger {
        background-color: #dc3545;
        color: white;
    }
    
    .badge-warning {
        background-color: #ffc107;
        color: #212529;
    }
    
    .badge-success {
        background-color: #28a745;
        color: white;
    }
    
    .badge-info {
        background-color: #17a2b8;
        color: white;
    }
    
    .disabled-user {
        opacity: 0.6;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }
    
    .disabled-user .user-header {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    }
    
    .disabled-user .user-avatar {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.2);
    }
    
    .table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .table th,
    .table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .table th {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        font-weight: 600;
    }
    
    .table tr:hover {
        background-color: #f8f9fa;
    }
    
    .password-container {
        position: relative;
    }
    
    .toggle-password {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #6c757d;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 50%;
        transition: all 0.3s ease;
    }
    
    .toggle-password:hover {
        background: #e9ecef;
        color: #2c3e50;
    }
    
    .back-button {
        margin-bottom: 2rem;
    }
    
    @media (max-width: 768px) {
        .content-grid {
            grid-template-columns: 1fr;
        }
        
        .user-stats {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .user-header {
            padding: 1.5rem;
        }
        
        .user-name {
            font-size: 1.5rem;
        }
    }
</style>

<div class="user-record-container <?php echo ($user['status'] ?? 'active') === 'disabled' ? 'disabled-user' : ''; ?>">
    <!-- Back Button -->
    <div class="back-button">
        <a href="users.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>
        <a href="create_admin.php" class="btn btn-success">
            <i class="fas fa-user-plus"></i> Create New User
        </a>
    </div>

    <!-- User Header -->
    <div class="user-header">
        <div class="user-avatar">
            <i class="fas fa-<?php echo $user['role'] === 'admin' ? 'user-shield' : 'user'; ?>"></i>
        </div>
        <h1 class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
        <p class="user-role">
            <?php echo ucfirst($user['role']); ?>
            <?php if($user['id'] == $_SESSION['user_id']): ?>
                <span class="badge badge-info">You</span>
            <?php endif; ?>
        </p>
        
        <div class="user-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['active_borrows']; ?></div>
                <div class="stat-label">Active Borrows</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_returns']; ?></div>
                <div class="stat-label">Games Returned</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_transactions']; ?></div>
                <div class="stat-label">Total Transactions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $overdue_count; ?></div>
                <div class="stat-label">Overdue Games</div>
            </div>
        </div>
    </div>

    <?php if($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="content-grid">
        <!-- User Information -->
        <div class="content-section">
            <h2 class="section-title">
                <i class="fas fa-user"></i>
                User Information
            </h2>
            
            <div class="info-item">
                <span class="info-label">Full Name:</span>
                <span class="info-value"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Username:</span>
                <span class="info-value"><?php echo htmlspecialchars($user['username']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Email:</span>
                <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Gender:</span>
                <span class="info-value"><?php echo ucfirst(str_replace('_', ' ', $user['gender'])); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Role:</span>
                <span class="info-value">
                    <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    <span class="badge badge-<?php echo ($user['status'] ?? 'active') === 'active' ? 'success' : 'danger'; ?>">
                        <?php echo ucfirst($user['status'] ?? 'active'); ?>
                    </span>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Member Since:</span>
                <span class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Last Updated:</span>
                <span class="info-value"><?php echo date('F j, Y g:i A', strtotime($user['updated_at'])); ?></span>
            </div>
        </div>

        <!-- Edit User Form -->
        <div class="content-section">
            <h2 class="section-title">
                <i class="fas fa-edit"></i>
                Edit User
            </h2>
            
            <form method="POST">
                <input type="hidden" name="update_user" value="1">
                
                <div class="form-group">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" 
                           value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" 
                           value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="gender" class="form-label">Gender</label>
                    <select id="gender" name="gender" class="form-control" required>
                        <option value="male" <?php echo $user['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo $user['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                        <option value="other" <?php echo $user['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                        <option value="prefer_not_to_say" <?php echo $user['gender'] === 'prefer_not_to_say' ? 'selected' : ''; ?>>Prefer not to say</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="role" class="form-label">Role</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="customer" <?php echo $user['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update User
                </button>
            </form>
        </div>
    </div>

    <!-- Password Reset Section -->
    <div class="content-section" style="margin-bottom: 2rem;">
        <h2 class="section-title">
            <i class="fas fa-key"></i>
            Reset Password
        </h2>
        
        <form method="POST">
            <input type="hidden" name="reset_password" value="1">
            
            <div class="form-group">
                <label for="new_password" class="form-label">New Password</label>
                <div class="password-container">
                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('new_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <div class="password-container">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-key"></i> Reset Password
            </button>
        </form>
    </div>

    <!-- User Status Management -->
    <div class="content-section" style="margin-bottom: 2rem;">
        <h2 class="section-title">
            <i class="fas fa-user-cog"></i>
            User Status Management
        </h2>
        
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <strong>Current Status:</strong>
                <span class="badge badge-<?php echo ($user['status'] ?? 'active') === 'active' ? 'success' : 'danger'; ?>" style="margin-left: 0.5rem;">
                    <?php echo ucfirst($user['status'] ?? 'active'); ?>
                </span>
            </div>
        </div>
        
        <?php if($user['id'] != $_SESSION['user_id']): ?>
            <div style="display: flex; gap: 1rem;">
                <?php if(($user['status'] ?? 'active') === 'active'): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="toggle_status" value="1">
                        <input type="hidden" name="new_status" value="disabled">
                        <button type="submit" class="btn btn-danger" 
                                onclick="return confirm('Are you sure you want to disable this user? They will not be able to login until re-enabled.')">
                            <i class="fas fa-user-times"></i> Disable User
                        </button>
                    </form>
                <?php else: ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="toggle_status" value="1">
                        <input type="hidden" name="new_status" value="active">
                        <button type="submit" class="btn btn-success" 
                                onclick="return confirm('Are you sure you want to enable this user?')">
                            <i class="fas fa-user-check"></i> Enable User
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p style="color: #6c757d; font-style: italic;">
                <i class="fas fa-info-circle"></i> You cannot disable your own account.
            </p>
        <?php endif; ?>
    </div>

    <!-- Currently Borrowed Games -->
    <div class="content-section" style="margin-bottom: 2rem;">
        <h2 class="section-title">
            <i class="fas fa-hand-holding"></i>
            Currently Borrowed Games
        </h2>
        
        <?php if(empty($borrowed_games)): ?>
            <p>No currently borrowed games.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Game</th>
                        <th>Platform</th>
                        <th>Borrow Date</th>
                        <th>Days Borrowed</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($borrowed_games as $game): ?>
                        <?php
                        $borrow_date = new DateTime($game['borrow_date']);
                        $now = new DateTime();
                        $diff = $now->diff($borrow_date);
                        $days_borrowed = $diff->days;
                        $is_overdue = $days_borrowed > 14;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($game['title']); ?></td>
                            <td><?php echo htmlspecialchars($game['platform']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($game['borrow_date'])); ?></td>
                            <td><?php echo $days_borrowed; ?> days</td>
                            <td>
                                <?php if($is_overdue): ?>
                                    <span class="badge badge-danger">OVERDUE</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Active</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Transaction History -->
    <div class="content-section">
        <h2 class="section-title">
            <i class="fas fa-history"></i>
            Transaction History
        </h2>
        
        <?php if(empty($transaction_history)): ?>
            <p>No transaction history.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Game</th>
                        <th>Platform</th>
                        <th>Borrow Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($transaction_history as $transaction): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($transaction['title']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['platform']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($transaction['borrow_date'])); ?></td>
                            <td>
                                <?php if($transaction['return_date']): ?>
                                    <?php echo date('M j, Y', strtotime($transaction['return_date'])); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $transaction['status']; ?>">
                                    <?php echo ucfirst($transaction['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
function togglePasswordVisibility(inputId) {
    const passwordInput = document.getElementById(inputId);
    const toggleButton = passwordInput.nextElementSibling;
    const icon = toggleButton.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php include 'includes/admin_footer.php'; ?>
