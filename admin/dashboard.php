<?php
session_start();
$page_title = "Admin Dashboard";

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

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

// Borrowed games
$stmt = $pdo->query("SELECT COUNT(*) as count FROM games WHERE status = 'borrowed'");
$stats['borrowed_games'] = $stmt->fetch()['count'];

// Total transactions
$stmt = $pdo->query("SELECT COUNT(*) as count FROM borrow_transactions");
$stats['total_transactions'] = $stmt->fetch()['count'];

// Overdue games (borrowed more than 14 days ago)
$stmt = $pdo->query("SELECT COUNT(*) as count FROM borrow_transactions 
                     WHERE status = 'borrowed' AND borrow_date < DATE_SUB(NOW(), INTERVAL 14 DAY)");
$stats['overdue_games'] = $stmt->fetch()['count'];

// Recent transactions
$stmt = $pdo->query("SELECT bt.*, u.username, g.title, g.platform 
                     FROM borrow_transactions bt 
                     JOIN users u ON bt.user_id = u.id 
                     JOIN games g ON bt.game_id = g.id 
                     ORDER BY bt.borrow_date DESC LIMIT 10");
$recent_transactions = $stmt->fetchAll();

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
            <a href="users.php" class="btn btn-primary">Manage Users</a>
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

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Recent Transactions</h3>
    </div>
    
    <?php if(empty($recent_transactions)): ?>
        <p>No recent transactions.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Game</th>
                    <th>Platform</th>
                    <th>Borrow Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recent_transactions as $transaction): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($transaction['username']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['title']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['platform']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($transaction['borrow_date'])); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $transaction['status']; ?>">
                                <?php echo ucfirst($transaction['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if($transaction['status'] === 'borrowed'): ?>
                                <a href="return_game.php?id=<?php echo $transaction['id']; ?>" 
                                   class="btn btn-success btn-sm" 
                                   data-confirm="Mark this game as returned?">
                                    Mark Returned
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Quick Actions</h3>
    </div>
    
    <div class="grid">
        <a href="games.php" class="btn btn-primary">
            <i class="fas fa-gamepad"></i> Manage Games
        </a>
        <a href="users.php" class="btn btn-success">
            <i class="fas fa-users"></i> Manage Users
        </a>
        <a href="reports.php" class="btn btn-warning">
            <i class="fas fa-chart-bar"></i> View Reports
        </a>
        <a href="transactions.php" class="btn btn-info">
            <i class="fas fa-exchange-alt"></i> View Transactions
        </a>
        <a href="overdue.php" class="btn btn-danger">
            <i class="fas fa-exclamation-triangle"></i> Overdue Games
        </a>
        <a href="create_admin.php" class="btn btn-secondary">
            <i class="fas fa-user-plus"></i> Create User
        </a>

    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
