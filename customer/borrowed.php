<?php
require_once '../includes/session_config.php';
$page_title = "My Borrowed Games";

// Include authentication check
require_once '../includes/auth_check.php';

// Set security headers
setSecurityHeaders();

// Validate session and require customer access
validateSession();
requireCustomer();

require_once '../db/db_connect.php';

$user_id = $_SESSION['user_id'];

// Get currently borrowed games
$stmt = $pdo->prepare("SELECT bt.*, g.title, g.platform, g.status 
                       FROM borrow_transactions bt 
                       JOIN games g ON bt.game_id = g.id 
                       WHERE bt.user_id = ? AND bt.status = 'borrowed' 
                       ORDER BY bt.borrow_date DESC");
$stmt->execute([$user_id]);
$borrowed_games = $stmt->fetchAll();

include 'includes/customer_header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">My Borrowed Games</h2>
    </div>
    
    <?php if(empty($borrowed_games)): ?>
        <div class="alert alert-info">
            <p>You haven't borrowed any games yet.</p>
            <a href="../games.php" class="btn btn-primary">Browse Available Games</a>
        </div>
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
                        <h3 class="card-title"><?php echo htmlspecialchars($game['title']); ?></h3>
                    </div>
                    
                    <p><strong>Platform:</strong> <?php echo htmlspecialchars($game['platform']); ?></p>
                    <p><strong>Borrowed:</strong> <?php echo date('M j, Y', strtotime($game['borrow_date'])); ?></p>
                    
                    <?php if($is_overdue): ?>
                        <div class="alert alert-danger">
                            <strong>OVERDUE!</strong> This game is <?php echo $diff->days - 14; ?> days overdue.
                            Please return it as soon as possible to avoid late fees.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <strong>Days remaining:</strong> <?php echo $days_remaining; ?> days
                        </div>
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
        
        <div style="margin-top: 2rem;">
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="../games.php" class="btn btn-success">
                <i class="fas fa-gamepad"></i> Browse More Games
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/customer_footer.php'; ?>
