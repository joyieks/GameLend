<?php
require_once '../includes/session_config.php';
$page_title = "Admin Dashboard";

require_once '../db/db_connect.php';

// Get statistics
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

// Borrowed games (count active borrow transactions)
$stmt = $pdo->query("SELECT COUNT(*) as count FROM borrow_transactions WHERE status = 'borrowed'");
$stats['borrowed_games'] = $stmt->fetch()['count'];

// Total transactions
$stmt = $pdo->query("SELECT COUNT(*) as count FROM borrow_transactions");
$stats['total_transactions'] = $stmt->fetch()['count'];

// Overdue games (borrowed more than 14 days ago)
$stmt = $pdo->query("SELECT COUNT(*) as count FROM borrow_transactions 
                     WHERE status = 'borrowed' AND borrow_date < NOW() - INTERVAL '14 days'");
$stats['overdue_games'] = $stmt->fetch()['count'];

include 'includes/admin_header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Admin Dashboard</h2>
    </div>
    
    <div class="grid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Total Users</h3>
            </div>
            <p style="font-size: 2rem; font-weight: bold; color: #667eea;"><?php echo $stats['total_users']; ?></p>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a href="users.php" class="btn btn-primary">Manage Users</a>
                <a href="add_user.php" class="btn btn-success"><i class="fas fa-user-plus"></i> Add User</a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Total Games</h3>
            </div>
            <p style="font-size: 2rem; font-weight: bold; color: #28a745;"><?php echo $stats['total_games']; ?></p>
            <a href="games.php" class="btn btn-success">Manage Games</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Available Games</h3>
            </div>
            <p style="font-size: 2rem; font-weight: bold; color: #17a2b8;"><?php echo $stats['available_games']; ?></p>
            <a href="games.php" class="btn btn-primary">View Games</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Borrowed Games</h3>
            </div>
            <p style="font-size: 2rem; font-weight: bold; color: #ffc107;"><?php echo $stats['borrowed_games']; ?></p>
            <a href="transactions.php" class="btn btn-warning">View Transactions</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Total Transactions</h3>
            </div>
            <p style="font-size: 2rem; font-weight: bold; color: #6f42c1;"><?php echo $stats['total_transactions']; ?></p>
            <a href="reports.php" class="btn btn-primary">View Reports</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Overdue Games</h3>
            </div>
            <p style="font-size: 2rem; font-weight: bold; color: #dc3545;"><?php echo $stats['overdue_games']; ?></p>
            <a href="overdue.php" class="btn btn-danger">View Overdue</a>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
