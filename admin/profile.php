<?php
session_start();
$page_title = "My Profile";

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db/db_connect.php';

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle profile update
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $gender = $_POST['gender'];
    $email = trim($_POST['email']);
    
    // Validation
    if(empty($first_name) || empty($last_name) || empty($gender) || empty($email)) {
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
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, gender = ?, email = ?, updated_at = NOW() WHERE id = ?");
            
            if($stmt->execute([$first_name, $last_name, $gender, $email, $user_id])) {
                // Update session variables
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['gender'] = $gender;
                
                $message = 'Profile updated successfully';
                $message_type = 'success';
            } else {
                $message = 'Failed to update profile. Please try again.';
                $message_type = 'danger';
            }
        }
    }
}

// Handle password change
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = 'All password fields are required';
        $message_type = 'danger';
    } elseif($new_password !== $confirm_password) {
        $message = 'New passwords do not match';
        $message_type = 'danger';
    } elseif(strlen($new_password) < 6) {
        $message = 'New password must be at least 6 characters long';
        $message_type = 'danger';
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if(password_verify($current_password, $user['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            
            if($stmt->execute([$hashed_password, $user_id])) {
                $message = 'Password changed successfully';
                $message_type = 'success';
            } else {
                $message = 'Failed to change password. Please try again.';
                $message_type = 'danger';
            }
        } else {
            $message = 'Current password is incorrect';
            $message_type = 'danger';
        }
    }
}

// Get current user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get admin statistics
$stats = [];

// Total users
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
$stats['total_users'] = $stmt->fetch()['count'];

// Total games
$stmt = $pdo->query("SELECT COUNT(*) as count FROM games");
$stats['total_games'] = $stmt->fetch()['count'];

// Available games
$stmt = $pdo->query("SELECT COUNT(*) as count FROM games WHERE status = 'available'");
$stats['available_games'] = $stmt->fetch()['count'];

// Borrowed games
$stmt = $pdo->query("SELECT COUNT(*) as count FROM games WHERE status = 'borrowed'");
$stats['borrowed_games'] = $stmt->fetch()['count'];

// Total transactions
$stmt = $pdo->query("SELECT COUNT(*) as count FROM borrow_transactions");
$stats['total_transactions'] = $stmt->fetch()['count'];

// Overdue games
$stmt = $pdo->query("SELECT COUNT(*) as count FROM borrow_transactions 
                     WHERE status = 'borrowed' AND borrow_date < DATE_SUB(NOW(), INTERVAL 14 DAY)");
$stats['overdue_games'] = $stmt->fetch()['count'];

include 'includes/admin_header.php';
?>

<style>
    .profile-container {
        max-width: 1000px;
        margin: 0 auto;
    }
    
    .profile-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 2rem;
        border-radius: 15px;
        margin-bottom: 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .profile-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        opacity: 0.3;
    }
    
    .profile-avatar {
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
    
    .profile-name {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        position: relative;
        z-index: 1;
    }
    
    .profile-role {
        font-size: 1.1rem;
        opacity: 0.9;
        position: relative;
        z-index: 1;
        background: rgba(0, 206, 201, 0.2);
        padding: 0.3rem 1rem;
        border-radius: 20px;
        display: inline-block;
    }
    
    .profile-stats {
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
    
    .profile-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }
    
    .profile-section {
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
    
    .admin-badge {
        background: linear-gradient(135deg, #00cec9 0%, #00b894 100%);
        color: white;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-left: 0.5rem;
    }
    
    @media (max-width: 768px) {
        .profile-content {
            grid-template-columns: 1fr;
        }
        
        .profile-stats {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .profile-header {
            padding: 1.5rem;
        }
        
        .profile-name {
            font-size: 1.5rem;
        }
    }
</style>

<div class="profile-container">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar">
            <i class="fas fa-user-shield"></i>
        </div>
        <h1 class="profile-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
        <p class="profile-role">Administrator <span class="admin-badge">ADMIN</span></p>
        
        <div class="profile-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_games']; ?></div>
                <div class="stat-label">Total Games</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['available_games']; ?></div>
                <div class="stat-label">Available Games</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['borrowed_games']; ?></div>
                <div class="stat-label">Borrowed Games</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_transactions']; ?></div>
                <div class="stat-label">Total Transactions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['overdue_games']; ?></div>
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

    <div class="profile-content">
        <!-- Profile Information -->
        <div class="profile-section">
            <h2 class="section-title">
                <i class="fas fa-user"></i>
                Profile Information
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
                <span class="info-value">Administrator</span>
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

        <!-- Edit Profile Form -->
        <div class="profile-section">
            <h2 class="section-title">
                <i class="fas fa-edit"></i>
                Edit Profile
            </h2>
            
            <form method="POST">
                <input type="hidden" name="update_profile" value="1">
                
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
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>
    </div>

    <!-- Change Password Section -->
    <div class="profile-section" style="margin-top: 2rem;">
        <h2 class="section-title">
            <i class="fas fa-lock"></i>
            Change Password
        </h2>
        
        <form method="POST">
            <input type="hidden" name="change_password" value="1">
            
            <div class="form-group">
                <label for="current_password" class="form-label">Current Password</label>
                <div class="password-container">
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility('current_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
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
            
            <button type="submit" class="btn btn-success">
                <i class="fas fa-key"></i> Change Password
            </button>
        </form>
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
