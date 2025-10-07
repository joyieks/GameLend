<?php
session_start();
$page_title = "Manage Users";

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db/db_connect.php';

$message = '';
$message_type = '';

// Handle user deletion
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    
    // Don't allow admin to delete themselves
    if($user_id == $_SESSION['user_id']) {
        $message = 'You cannot delete your own account';
        $message_type = 'danger';
    } else {
        // Check if user has any active borrows
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM borrow_transactions WHERE user_id = ? AND status = 'borrowed'");
        $stmt->execute([$user_id]);
        $active_borrows = $stmt->fetch()['count'];
        
        if($active_borrows > 0) {
            $message = 'Cannot delete user with active borrowed games';
            $message_type = 'danger';
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if($stmt->execute([$user_id])) {
                $message = 'User deleted successfully';
                $message_type = 'success';
            } else {
                $message = 'Failed to delete user';
                $message_type = 'danger';
            }
        }
    }
}

// Handle user status toggle
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_status'])) {
    $user_id = $_POST['user_id'];
    $new_status = $_POST['new_status'];
    
    // Don't allow admin to disable themselves
    if($user_id == $_SESSION['user_id'] && $new_status === 'disabled') {
        $message = 'You cannot disable your own account';
        $message_type = 'danger';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
        
        if($stmt->execute([$new_status, $user_id])) {
            $status_text = $new_status === 'active' ? 'enabled' : 'disabled';
            $message = "User {$status_text} successfully";
            $message_type = 'success';
        } else {
            $message = 'Failed to update user status. Please try again.';
            $message_type = 'danger';
        }
    }
}

// Get all users
$stmt = $pdo->query("SELECT u.*, 
                     COUNT(CASE WHEN bt.status = 'borrowed' THEN 1 END) as active_borrows,
                     COUNT(bt.id) as total_transactions
                     FROM users u 
                     LEFT JOIN borrow_transactions bt ON u.id = bt.user_id 
                     GROUP BY u.id 
                     ORDER BY u.created_at DESC");
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
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    }
    .user-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: #fff;
        border-bottom: none;
        padding: 0.9rem 1rem;
        text-align: left;
        font-weight: 700;
        white-space: nowrap;
    }
    .user-table tbody td {
        padding: 0.9rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f2f5;
        white-space: nowrap;
    }
    .user-table tbody tr:hover {
        background: #fafbff;
    }
    .user-meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .badge {
        border-radius: 999px;
        padding: 0.25rem 0.6rem;
        font-weight: 700;
        letter-spacing: .2px;
    }
    .badge-info { background:#e3f2fd; color:#1e88e5; }
    .badge-primary { background:#ede9fe; color:#6d28d9; }
    .badge-danger { background:#fee2e2; color:#b91c1c; }
    .badge-success { background:#dcfce7; color:#166534; }
    .badge-warning { background:#fff7ed; color:#c2410c; }
    .actions {
        display: flex;
        gap: .5rem;
        align-items: center;
        flex-wrap: wrap;
    }
    .btn.btn-sm {
        padding: .45rem .7rem;
        border-radius: 8px;
        font-weight: 700;
        box-shadow: 0 4px 10px rgba(0,0,0,.08);
    }
    .btn-primary.btn-sm { background: linear-gradient(135deg,#8b5cf6,#6366f1); border: none; }
    .btn-warning.btn-sm { background: #f59e0b; border: none; color:#fff; }
    .btn-success.btn-sm { background: #10b981; border: none; }
    .btn-danger.btn-sm { background: #ef4444; border: none; }
    .table-wrap { border-radius: 12px; overflow: visible; }
    @media (max-width: 992px) {
        .hide-lg { display: none; }
        .user-table thead th.hide-lg { display: none; }
    }
</style>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Manage Users</h2>
    </div>
    
    <?php if($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">User List</h3>
        </div>
        
        <?php if(empty($users)): ?>
            <p>No users found.</p>
        <?php else: ?>
            <div class="table-wrap">
            <table class="table user-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th class="hide-lg">Gender</th>
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
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="hide-lg">
                                <span class="badge badge-info"><?php echo ucfirst(str_replace('_', ' ', $user['gender'])); ?></span>
                            </td>
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
                                            <form method="POST" onsubmit="return confirm('Disable this user? They will not be able to login until re-enabled.')">
                                                <input type="hidden" name="toggle_status" value="1">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="new_status" value="disabled">
                                                <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-user-times"></i> Disable</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" onsubmit="return confirm('Enable this user?')">
                                                <input type="hidden" name="toggle_status" value="1">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="new_status" value="active">
                                                <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-user-check"></i> Enable</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" onsubmit="return confirm('Delete this user? This action cannot be undone.')">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">Current User</span>
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
