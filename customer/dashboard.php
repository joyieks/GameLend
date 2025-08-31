<?php
session_start();
$page_title = "Customer Dashboard";

// Check if user is logged in and is customer
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../login.php');
    exit();
}

require_once '../db/db_connect.php';

$user_id = $_SESSION['user_id'];

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get currently borrowed games
$stmt = $pdo->prepare("SELECT bt.*, g.title, g.platform, g.status 
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
                       ORDER BY bt.borrow_date DESC LIMIT 10");
$stmt->execute([$user_id]);
$recent_history = $stmt->fetchAll();

// Calculate statistics
$total_borrowed = count($borrowed_games);
$overdue_count = 0;

foreach($borrowed_games as $game) {
    $borrow_date = new DateTime($game['borrow_date']);
    $now = new DateTime();
    $diff = $now->diff($borrow_date);
    if($diff->days > 14) {
        $overdue_count++;
    }
}

include 'includes/customer_header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h2>
    </div>
    
    <div class="grid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Currently Borrowed</h3>
            </div>
            <p style="font-size: 2rem; font-weight: bold; color: #667eea;"><?php echo $total_borrowed; ?></p>
            <a href="borrowed.php" class="btn btn-primary">View All</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Overdue Games</h3>
            </div>
            <p style="font-size: 2rem; font-weight: bold; color: #dc3545;"><?php echo $overdue_count; ?></p>
            <?php if($overdue_count > 0): ?>
                <p class="text-danger">Please return overdue games to avoid late fees!</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Account Info</h3>
            </div>
            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Gender:</strong> <?php echo ucfirst(str_replace('_', ' ', $user['gender'])); ?></p>
            <p><strong>Member Since:</strong> <?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
        </div>
    </div>
</div>

<!-- Currently Borrowed Games -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Currently Borrowed Games</h3>
    </div>
    
    <?php if(empty($borrowed_games)): ?>
        <p>You haven't borrowed any games yet.</p>
        <a href="../games.php" class="btn btn-primary">Browse Available Games</a>
    <?php else: ?>
        <div class="grid">
            <?php foreach($borrowed_games as $game): ?>
                <?php
                $borrow_date = new DateTime($game['borrow_date']);
                $now = new DateTime();
                $diff = $now->diff($borrow_date);
                $days_remaining = 14 - $diff->days;
                $is_overdue = $diff->days > 14;
                ?>
                
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title"><?php echo htmlspecialchars($game['title']); ?></h4>
                    </div>
                    
                    <p><strong>Platform:</strong> <?php echo htmlspecialchars($game['platform']); ?></p>
                    <p><strong>Borrowed:</strong> <?php echo date('M j, Y', strtotime($game['borrow_date'])); ?></p>
                    
                    <?php if($is_overdue): ?>
                        <p class="text-danger"><strong>Status:</strong> OVERDUE!</p>
                        <p class="text-danger">Days overdue: <?php echo $diff->days - 14; ?></p>
                    <?php else: ?>
                        <p class="text-success"><strong>Days remaining:</strong> <?php echo $days_remaining; ?></p>
                    <?php endif; ?>
                    
                    <form method="POST" action="return_game.php" style="margin-top: 1rem;">
                        <input type="hidden" name="transaction_id" value="<?php echo $game['id']; ?>">
                        <button type="submit" class="btn btn-success" 
                                data-confirm="Are you sure you want to return this game?">
                            <i class="fas fa-undo"></i> Return Game
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Recent History -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Recent Activity</h3>
    </div>
    
    <?php if(empty($recent_history)): ?>
        <p>No recent activity.</p>
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
                <?php foreach($recent_history as $transaction): ?>
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
        
        <p><a href="history.php" class="btn btn-primary">View Full History</a></p>
    <?php endif; ?>
</div>

<!-- Quick Actions -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Quick Actions</h3>
    </div>
    
    <div class="grid">
        <a href="../games.php" class="btn btn-primary">
            <i class="fas fa-gamepad"></i> Browse Games
        </a>
        <a href="borrowed.php" class="btn btn-success">
            <i class="fas fa-hand-holding"></i> My Borrowed Games
        </a>
        <a href="history.php" class="btn btn-warning">
            <i class="fas fa-history"></i> Transaction History
        </a>
        <a href="../index.php" class="btn btn-secondary">
            <i class="fas fa-home"></i> Back to Home
        </a>
    </div>
</div>

<?php include 'includes/customer_footer.php'; ?>
