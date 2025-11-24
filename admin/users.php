<?php
require_once '../includes/session_config.php';
$page_title = "Manage Users";

// Include authentication check
require_once '../includes/auth_check.php';

// Set security headers
setSecurityHeaders();

// Validate session and require admin access
validateSession();
requireAdmin();

require_once '../db/db_connect.php';

$message = '';
$message_type = '';

// Handle user status toggle
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_status'])) {
    $user_id = $_POST['user_id'];
    $new_status = $_POST['new_status'];
    
    // Validate status value (must match database constraint)
    $valid_statuses = ['active', 'suspended', 'inactive'];
    if (!in_array($new_status, $valid_statuses)) {
        $message = 'Invalid status value';
        $message_type = 'danger';
    } elseif($user_id == $_SESSION['user_id'] && $new_status !== 'active') {
        // Don't allow admin to disable/suspend themselves
        $message = 'You cannot disable or suspend your own account';
        $message_type = 'danger';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
        
        if($stmt->execute([$new_status, $user_id])) {
            $status_text = $new_status === 'active' ? 'activated' : $new_status;
            $message = "User status changed to '{$status_text}' successfully";
            $message_type = 'success';
        } else {
            $message = 'Failed to update user status. Please try again.';
            $message_type = 'danger';
        }
    }
}

// Get all users (excluding admins from list, or include them - your choice)
// Option 1: Show only customers
$stmt = $pdo->query("SELECT u.*, 
                     COUNT(CASE WHEN bt.status = 'borrowed' THEN 1 END) as active_borrows,
                     COUNT(bt.id) as total_transactions
                     FROM users u 
                     LEFT JOIN borrow_transactions bt ON u.id = bt.user_id 
                     WHERE u.role = 'customer'
                     GROUP BY u.id 
                     ORDER BY u.created_at DESC");

// Option 2: Show all users including admins (comment out above and uncomment below)
// $stmt = $pdo->query("SELECT u.*, 
//                      COUNT(CASE WHEN bt.status = 'borrowed' THEN 1 END) as active_borrows,
//                      COUNT(bt.id) as total_transactions
//                      FROM users u 
//                      LEFT JOIN borrow_transactions bt ON u.id = bt.user_id 
//                      GROUP BY u.id 
//                      ORDER BY u.role DESC, u.created_at DESC");

$users = $stmt->fetchAll();

include 'includes/admin_header.php';
?>

<style>
    /* Users page UI refinements */
    /* Expand page width so no horizontal scroll is needed */
    main.container { max-width: 100%; padding: 2rem; }
    .user-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: rgba(255, 255, 255, 0.4);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
    .user-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: rgba(102, 126, 234, 0.75);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        color: #fff;
        border-bottom: none;
        padding: 1rem 1.25rem;
        text-align: left;
        font-weight: 700;
        white-space: nowrap;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        position: relative;
    }
    
    .user-table thead th::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.3) 0%, rgba(118, 75, 162, 0.3) 100%);
        z-index: -1;
    }
    
    .user-table tbody td {
        padding: 1rem 1.25rem;
        vertical-align: middle;
        border-bottom: 1px solid rgba(240, 242, 245, 0.5);
        white-space: nowrap;
        background: rgba(255, 255, 255, 0.3);
    }
    
    .user-table tbody tr {
        background: rgba(255, 255, 255, 0.2);
        transition: all 0.2s ease;
    }
    
    .user-table tbody tr:nth-child(even) {
        background: rgba(255, 255, 255, 0.3);
    }
    
    .user-table tbody tr:hover {
        background: rgba(248, 249, 255, 0.7) !important;
        transform: scale(1.005);
    }
    .user-meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .badge {
        border-radius: 999px;
        padding: 0.35rem 0.75rem;
        font-weight: 600;
        letter-spacing: 0.3px;
        font-size: 0.8rem;
        text-transform: uppercase;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .badge-info { 
        background: rgba(227, 242, 253, 0.9); 
        color: #1e88e5;
        border: 1px solid rgba(30, 136, 229, 0.2);
    }
    .badge-primary { 
        background: rgba(237, 233, 254, 0.9); 
        color: #6d28d9;
        border: 1px solid rgba(109, 40, 217, 0.2);
    }
    .badge-danger { 
        background: rgba(254, 226, 226, 0.9); 
        color: #b91c1c;
        border: 1px solid rgba(185, 28, 28, 0.2);
    }
    .badge-success { 
        background: rgba(220, 252, 231, 0.9); 
        color: #166534;
        border: 1px solid rgba(22, 101, 52, 0.2);
    }
    .badge-warning { 
        background: rgba(255, 247, 237, 0.9); 
        color: #c2410c;
        border: 1px solid rgba(194, 65, 12, 0.2);
    }
    .actions {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .actions form {
        margin: 0;
    }
    .btn.btn-sm {
        padding: 0.5rem 1rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.85rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }
    
    .btn.btn-sm:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    }
    
    .btn-primary.btn-sm { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .btn-primary.btn-sm:hover {
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }
    
    .btn-warning.btn-sm { 
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
    }
    
    .btn-warning.btn-sm:hover {
        box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
    }
    
    .btn-success.btn-sm { 
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    
    .btn-success.btn-sm:hover {
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    }
    .table-wrap { 
        border-radius: 12px; 
        overflow: hidden;
        margin-bottom: 2rem;
    }
    
    .alert {
        padding: 1rem 1.5rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        font-weight: 500;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    
    .alert-success {
        background: rgba(212, 237, 218, 0.8);
        color: #155724;
        border: 1px solid rgba(195, 230, 203, 0.5);
    }
    
    .alert-danger {
        background: rgba(248, 215, 218, 0.8);
        color: #721c24;
        border: 1px solid rgba(245, 198, 203, 0.5);
    }
    @media (max-width: 992px) {
        .hide-lg { display: none; }
        .user-table thead th.hide-lg { display: none; }
    }
</style>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <h2 class="card-title" style="margin: 0;">Manage Users</h2>
        <a href="add_user.php" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-user-plus"></i> Add New User
        </a>
    </div>
    
    <?php if($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if(empty($users)): ?>
        <p>No users found.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table class="table user-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th class="hide-lg">Active Borrows</th>
                        <th class="hide-lg">Total Transactions</th>
                        <th>Member Since</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                <?php if($user['id'] == $_SESSION['user_id']): ?>
                                    <span class="badge badge-info">You</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo !empty($user['phone']) ? htmlspecialchars($user['phone']) : '<em style="color:#999;">Not provided</em>'; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo ($user['status'] ?? 'active') === 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($user['status'] ?? 'active'); ?>
                                </span>
                            </td>
                            <td class="hide-lg">
                                <?php if($user['active_borrows'] > 0): ?>
                                    <span class="badge badge-warning"><?php echo $user['active_borrows']; ?></span>
                                <?php else: ?>
                                    <span class="badge badge-success">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="hide-lg">
                                <span class="badge badge-info"><?php echo $user['total_transactions']; ?></span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="actions">
                                    <a href="user_record.php?id=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if($user['id'] != $_SESSION['user_id']): ?>
                                        <?php if(($user['status'] ?? 'active') === 'active'): ?>
                                            <form method="POST" onsubmit="return confirm('Disable this user? They will not be able to login until reactivated.')" style="display: inline;">
                                                <input type="hidden" name="toggle_status" value="1">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="new_status" value="inactive">
                                                <button type="submit" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-ban"></i> Disable
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" onsubmit="return confirm('Reactivate this user? They will be able to login again.')" style="display: inline;">
                                                <input type="hidden" name="toggle_status" value="1">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="new_status" value="active">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="fas fa-user-check"></i> Reactivate
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- User Statistics -->
<div class="card">
        <div class="card-header">
            <h3 class="card-title">User Statistics</h3>
        </div>
        
        <div class="grid">
            <?php
            $total_users = count($users);
            $admin_count = count(array_filter($users, function($u) { return $u['role'] === 'admin'; }));
            $customer_count = $total_users - $admin_count;
            $active_borrowers = count(array_filter($users, function($u) { return $u['active_borrows'] > 0; }));
            $disabled_users = count(array_filter($users, function($u) { return ($u['status'] ?? 'active') === 'disabled'; }));
            $active_users = $total_users - $disabled_users;
            ?>
            
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Total Users</h4>
                </div>
                <p style="font-size: 2rem; font-weight: bold; color: #667eea;"><?php echo $total_users; ?></p>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Customers</h4>
                </div>
                <p style="font-size: 2rem; font-weight: bold; color: #28a745;"><?php echo $customer_count; ?></p>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Admins</h4>
                </div>
                <p style="font-size: 2rem; font-weight: bold; color: #dc3545;"><?php echo $admin_count; ?></p>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Active Users</h4>
                </div>
                <p style="font-size: 2rem; font-weight: bold; color: #28a745;"><?php echo $active_users; ?></p>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Disabled Users</h4>
                </div>
                <p style="font-size: 2rem; font-weight: bold; color: #dc3545;"><?php echo $disabled_users; ?></p>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Active Borrowers</h4>
                </div>
                <p style="font-size: 2rem; font-weight: bold; color: #ffc107;"><?php echo $active_borrowers; ?></p>
            </div>
        </div>
    </div>
    
    
</div>

<?php include 'includes/admin_footer.php'; ?>
