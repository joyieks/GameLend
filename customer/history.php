<?php
session_start();
$page_title = "Transaction History";

// Check if user is logged in and is customer
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../login.php');
    exit();
}

require_once '../db/db_connect.php';

$user_id = $_SESSION['user_id'];

// Get all transaction history
$stmt = $pdo->prepare("SELECT bt.*, g.title, g.platform 
                       FROM borrow_transactions bt 
                       JOIN games g ON bt.game_id = g.id 
                       WHERE bt.user_id = ? 
                       ORDER BY bt.borrow_date DESC");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll();

include 'includes/customer_header.php';
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">My Transaction History</h2>
    </div>
    
    <?php if(empty($transactions)): ?>
        <div class="alert alert-info">
            <p>No transaction history found.</p>
            <a href="../games.php" class="btn btn-primary">Browse Available Games</a>
        </div>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Game</th>
                    <th>Platform</th>
                    <th>Borrow Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                    <th>Duration</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($transactions as $transaction): ?>
                    <?php
                    $borrow_date = new DateTime($transaction['borrow_date']);
                    $return_date = $transaction['return_date'] ? new DateTime($transaction['return_date']) : null;
                    $now = new DateTime();
                    
                    if($return_date) {
                        $duration = $borrow_date->diff($return_date)->days;
                    } elseif($transaction['status'] === 'borrowed') {
                        $duration = $borrow_date->diff($now)->days;
                    } else {
                        $duration = 0;
                    }
                    ?>
                    
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
                            <?php if($transaction['status'] === 'borrowed' && $duration > 14): ?>
                                <span class="badge badge-danger">Overdue</span>
                            <?php else: ?>
                                <span class="badge badge-<?php echo $transaction['status']; ?>">
                                    <?php echo ucfirst($transaction['status']); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($duration > 0): ?>
                                <?php echo $duration; ?> day<?php echo $duration != 1 ? 's' : ''; ?>
                                <?php if($transaction['status'] === 'borrowed' && $duration > 14): ?>
                                    <br><small class="text-danger">(<?php echo $duration - 14; ?> days overdue)</small>
                                <?php endif; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 2rem;">
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="borrowed.php" class="btn btn-success">
                <i class="fas fa-hand-holding"></i> View Borrowed Games
            </a>
            <a href="../games.php" class="btn btn-warning">
                <i class="fas fa-gamepad"></i> Browse Games
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/customer_footer.php'; ?>
