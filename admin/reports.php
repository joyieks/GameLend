<?php
session_start();
$page_title = "Reports";

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db/db_connect.php';

// Get various reports
$reports = [];

// Games by platform
$stmt = $pdo->query("SELECT platform, COUNT(*) as count FROM games GROUP BY platform ORDER BY count DESC");
$reports['games_by_platform'] = $stmt->fetchAll();

// Games by status
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM games GROUP BY status ORDER BY count DESC");
$reports['games_by_status'] = $stmt->fetchAll();

// Most borrowed games
$stmt = $pdo->query("SELECT g.title, g.platform, COUNT(bt.id) as borrow_count 
                     FROM games g 
                     LEFT JOIN borrow_transactions bt ON g.id = bt.game_id 
                     GROUP BY g.id, g.title, g.platform 
                     ORDER BY borrow_count DESC 
                     LIMIT 10");
$reports['most_borrowed'] = $stmt->fetchAll();

// Recent transactions
$stmt = $pdo->query("SELECT bt.*, u.username, g.title, g.platform 
                     FROM borrow_transactions bt 
                     JOIN users u ON bt.user_id = u.id 
                     JOIN games g ON bt.game_id = g.id 
                     ORDER BY bt.borrow_date DESC 
                     LIMIT 20");
$reports['recent_transactions'] = $stmt->fetchAll();

// Overdue games
$stmt = $pdo->query("SELECT bt.*, u.username, g.title, g.platform, 
                     DATEDIFF(NOW(), bt.borrow_date) as days_overdue
                     FROM borrow_transactions bt 
                     JOIN users u ON bt.user_id = u.id 
                     JOIN games g ON bt.game_id = g.id 
                     WHERE bt.status = 'borrowed' AND bt.borrow_date < DATE_SUB(NOW(), INTERVAL 14 DAY)
                     ORDER BY days_overdue DESC");
$reports['overdue_games'] = $stmt->fetchAll();

// Monthly borrowing trends (last 6 months)
$stmt = $pdo->query("SELECT DATE_FORMAT(borrow_date, '%Y-%m') as month, COUNT(*) as count 
                     FROM borrow_transactions 
                     WHERE borrow_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                     GROUP BY DATE_FORMAT(borrow_date, '%Y-%m') 
                     ORDER BY month DESC");
$reports['monthly_trends'] = $stmt->fetchAll();

include 'includes/admin_header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">System Reports & Analytics</h2>
    </div>
</div>

<!-- Games by Platform -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Games by Platform</h3>
    </div>
    
    <div class="grid">
        <?php foreach($reports['games_by_platform'] as $platform): ?>
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title"><?php echo htmlspecialchars($platform['platform']); ?></h4>
                </div>
                <p style="font-size: 2rem; font-weight: bold; color: #667eea;"><?php echo $platform['count']; ?></p>
                <p>Games</p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Games by Status -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Games by Status</h3>
    </div>
    
    <div class="grid">
        <?php foreach($reports['games_by_status'] as $status): ?>
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title"><?php echo ucfirst($status['status']); ?></h4>
                </div>
                <p style="font-size: 2rem; font-weight: bold; color: #28a745;"><?php echo $status['count']; ?></p>
                <p>Games</p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Most Borrowed Games -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Most Borrowed Games</h3>
    </div>
    
    <?php if(empty($reports['most_borrowed'])): ?>
        <p>No borrowing data available.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Game</th>
                    <th>Platform</th>
                    <th>Borrow Count</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($reports['most_borrowed'] as $game): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($game['title']); ?></td>
                        <td><?php echo htmlspecialchars($game['platform']); ?></td>
                        <td>
                            <span class="badge badge-primary"><?php echo $game['borrow_count']; ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Overdue Games -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Overdue Games</h3>
        <p class="text-danger">Games borrowed for more than 14 days</p>
    </div>
    
    <?php if(empty($reports['overdue_games'])): ?>
        <p class="text-success">No overdue games! All games are returned on time.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Game</th>
                    <th>Platform</th>
                    <th>Borrow Date</th>
                    <th>Days Overdue</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($reports['overdue_games'] as $game): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($game['username']); ?></td>
                        <td><?php echo htmlspecialchars($game['title']); ?></td>
                        <td><?php echo htmlspecialchars($game['platform']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($game['borrow_date'])); ?></td>
                        <td>
                            <span class="badge badge-danger"><?php echo $game['days_overdue']; ?> days</span>
                        </td>
                        <td>
                            <a href="return_game.php?id=<?php echo $game['id']; ?>" 
                               class="btn btn-success btn-sm" 
                               data-confirm="Mark this overdue game as returned?">
                                Mark Returned
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Monthly Trends -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Monthly Borrowing Trends (Last 6 Months)</h3>
    </div>
    
    <?php if(empty($reports['monthly_trends'])): ?>
        <p>No trend data available.</p>
    <?php else: ?>
        <div class="grid">
            <?php foreach($reports['monthly_trends'] as $trend): ?>
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title"><?php echo date('M Y', strtotime($trend['month'] . '-01')); ?></h4>
                    </div>
                    <p style="font-size: 2rem; font-weight: bold; color: #17a2b8;"><?php echo $trend['count']; ?></p>
                    <p>Borrows</p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Recent Transactions -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Recent Transactions</h3>
    </div>
    
    <?php if(empty($reports['recent_transactions'])): ?>
        <p>No recent transactions.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Game</th>
                    <th>Platform</th>
                    <th>Borrow Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($reports['recent_transactions'] as $transaction): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($transaction['username']); ?></td>
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

<!-- Quick Actions -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Quick Actions</h3>
    </div>
    
    <div class="grid">
        <a href="dashboard.php" class="btn btn-primary">
            <i class="fas fa-tachometer-alt"></i> Back to Dashboard
        </a>
        <a href="games.php" class="btn btn-success">
            <i class="fas fa-gamepad"></i> Manage Games
        </a>
        <a href="users.php" class="btn btn-warning">
            <i class="fas fa-users"></i> Manage Users
        </a>

    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
