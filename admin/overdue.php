<?php
session_start();
$page_title = "Admin Overdue Games";

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db/db_connect.php';

// Handle marking games as returned
if(isset($_POST['mark_returned'])) {
    $transaction_id = $_POST['transaction_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Update transaction status to returned
        $stmt = $pdo->prepare("UPDATE borrow_transactions SET status = 'returned' WHERE id = ?");
        $stmt->execute([$transaction_id]);
        
        // Update game status to available
        $stmt = $pdo->prepare("UPDATE games g 
                             JOIN borrow_transactions bt ON g.id = bt.game_id 
                             SET g.status = 'available' 
                             WHERE bt.id = ?");
        $stmt->execute([$transaction_id]);
        
        $pdo->commit();
        $success_message = "Game marked as returned successfully!";
    } catch(Exception $e) {
        $pdo->rollback();
        $error_message = "Failed to mark game as returned.";
    }
}

// Get overdue games (borrowed more than 14 days ago)
$sql = "SELECT bt.*, u.username, u.email, g.title, g.platform,
        DATEDIFF(NOW(), bt.borrow_date) as days_overdue
        FROM borrow_transactions bt 
        JOIN users u ON bt.user_id = u.id 
        JOIN games g ON bt.game_id = g.id 
        WHERE bt.status = 'borrowed' 
        AND bt.borrow_date < DATE_SUB(NOW(), INTERVAL 14 DAY)
        ORDER BY bt.borrow_date ASC";

$stmt = $pdo->query($sql);
$overdue_games = $stmt->fetchAll();

// Calculate total overdue days and potential fees
$total_overdue_days = 0;
$total_potential_fees = 0;
foreach($overdue_games as $game) {
    $total_overdue_days += $game['days_overdue'];
    $total_potential_fees += $game['days_overdue'] * 2; // $2 per day
}

include 'includes/admin_header.php';
?>

<style>
    .overdue-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }
    
    .page-header {
        text-align: center;
        margin-bottom: 2.5rem;
    }
    
    .page-title {
        font-size: 2.5rem;
        color: var(--dark);
        margin-bottom: 0.5rem;
        font-weight: 800;
    }
    
    .page-subtitle {
        color: #6c757d;
        font-size: 1.1rem;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        text-align: center;
        border-left: 4px solid #dc3545;
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: 800;
        color: #dc3545;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: #6c757d;
        font-size: 1.1rem;
        font-weight: 600;
    }
    
    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .alert-warning {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
    
    .table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    }
    
    .table th,
    .table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #e9ecef;
    }
    
    .table th {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        font-weight: 600;
    }
    
    .table tr:hover {
        background-color: #f8f9fa;
    }
    
    .badge {
        display: inline-block;
        padding: 0.35rem 0.65rem;
        font-size: 0.75rem;
        font-weight: 700;
        line-height: 1;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: 0.375rem;
    }
    
    .badge-overdue {
        color: white;
        background-color: #dc3545;
    }
    
    .btn {
        display: inline-block;
        padding: 0.8rem 1.5rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        font-size: 1rem;
    }
    
    .btn-success {
        background: var(--success);
        color: white;
    }
    
    .btn-success:hover {
        background: #00a382;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 184, 148, 0.4);
    }
    
    .btn-danger {
        background: var(--danger);
        color: white;
    }
    
    .btn-danger:hover {
        background: #c0392b;
        transform: translateY(-2px);
    }
    
    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
    
    .overdue-days {
        font-weight: 700;
        color: #dc3545;
    }
    
    .potential-fee {
        font-weight: 600;
        color: #dc3545;
    }
    
    .user-info {
        line-height: 1.4;
    }
    
    .user-info strong {
        color: var(--dark);
    }
    
    .user-info small {
        color: #6c757d;
    }
    
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .table {
            font-size: 0.9rem;
        }
        
        .table th,
        .table td {
            padding: 0.5rem;
        }
    }
</style>

<div class="overdue-container">
    <div class="page-header">
        <h1 class="page-title">Overdue Games Management</h1>
        <p class="page-subtitle">Monitor and manage games that are overdue for return</p>
    </div>
    
    <?php if(isset($success_message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if(isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo count($overdue_games); ?></div>
            <div class="stat-label">Total Overdue Games</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_overdue_days; ?></div>
            <div class="stat-label">Total Overdue Days</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number">$<?php echo $total_potential_fees; ?></div>
            <div class="stat-label">Potential Late Fees</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number">14</div>
            <div class="stat-label">Days Allowed</div>
        </div>
    </div>
    
    <?php if(empty($overdue_games)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> 
            Great news! No games are currently overdue.
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> 
            <strong><?php echo count($overdue_games); ?> games</strong> are currently overdue for return.
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Overdue Games List</h3>
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Game</th>
                            <th>Platform</th>
                            <th>Borrow Date</th>
                            <th>Days Overdue</th>
                            <th>Potential Fee</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($overdue_games as $game): ?>
                            <tr>
                                <td class="user-info">
                                    <strong><?php echo htmlspecialchars($game['username']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($game['email']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($game['title']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($game['platform']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($game['borrow_date'])); ?></td>
                                <td>
                                    <span class="overdue-days">
                                        <?php echo $game['days_overdue']; ?> days
                                    </span>
                                </td>
                                <td>
                                    <span class="potential-fee">
                                        $<?php echo $game['days_overdue'] * 2; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="transaction_id" value="<?php echo $game['id']; ?>">
                                        <button type="submit" name="mark_returned" class="btn btn-success btn-sm"
                                                data-confirm="Mark this game as returned? This will update both the transaction and game status.">
                                            <i class="fas fa-check"></i> Mark Returned
                                        </button>
                                    </form>
                                    
                                    <a href="mailto:<?php echo htmlspecialchars($game['email']); ?>?subject=Game Return Reminder&body=Hello <?php echo htmlspecialchars($game['username']); ?>,%0D%0A%0D%0AThis is a reminder that you have borrowed '<?php echo htmlspecialchars($game['title']); ?>' for <?php echo $game['days_overdue']; ?> days.%0D%0A%0D%0APlease return the game as soon as possible to avoid additional late fees.%0D%0A%0D%0AThank you,%0D%0AGameLend Team" 
                                       class="btn btn-danger btn-sm" style="margin-left: 0.5rem;">
                                        <i class="fas fa-envelope"></i> Send Reminder
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card" style="margin-top: 2rem;">
            <div class="card-header">
                <h3 class="card-title">Late Fee Policy</h3>
            </div>
            <div class="card-body">
                <ul>
                    <li><strong>Standard borrowing period:</strong> 14 days</li>
                    <li><strong>Late fee:</strong> $2 per day after the due date</li>
                    <li><strong>Maximum late fee:</strong> $50 per game</li>
                    <li><strong>Account suspension:</strong> After 30 days overdue</li>
                </ul>
                <p style="margin-top: 1rem; color: #6c757d;">
                    <i class="fas fa-info-circle"></i> 
                    Late fees are calculated automatically and will be applied to the user's account.
                </p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/admin_footer.php'; ?>
