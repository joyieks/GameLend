<?php
require_once '../includes/session_config.php';
$page_title = "Admin Transactions";

// Include authentication check
require_once '../includes/auth_check.php';

// Set security headers
setSecurityHeaders();

// Validate session and require admin access
validateSession();
requireAdmin();

require_once '../db/db_connect.php';

// Handle status updates
if(isset($_POST['update_status'])) {
    $transaction_id = $_POST['transaction_id'];
    $new_status = $_POST['new_status'];
    
    try {
        $pdo->beginTransaction();
        
        // Update transaction status
        $stmt = $pdo->prepare("UPDATE borrow_transactions SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $transaction_id]);
        
        // If marking as returned, increment game's available quantity
        if($new_status === 'returned') {
            // Get the game_id from the transaction
            $stmt = $pdo->prepare("SELECT game_id FROM borrow_transactions WHERE id = ?");
            $stmt->execute([$transaction_id]);
            $game_id = $stmt->fetchColumn();
            
            // Increment available_quantity
            $stmt = $pdo->prepare("UPDATE games SET available_quantity = available_quantity + 1 WHERE id = ?");
            $stmt->execute([$game_id]);
        }
        
        $pdo->commit();
        $success_message = "Transaction status updated successfully!";
    } catch(Exception $e) {
        $pdo->rollback();
        $error_message = "Failed to update transaction status.";
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$user_filter = isset($_GET['user']) ? trim($_GET['user']) : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Build query
$where_conditions = [];
$params = [];

if($status_filter) {
    $where_conditions[] = "bt.status = ?";
    $params[] = $status_filter;
}

if($user_filter) {
    $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$user_filter%";
    $params[] = "%$user_filter%";
    $params[] = "%$user_filter%";
}

if($date_filter) {
    $where_conditions[] = "DATE(bt.borrow_date) = ?";
    $params[] = $date_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$sql = "SELECT bt.*, u.first_name, u.last_name, u.email, g.title, g.platform, g.status as game_status
        FROM borrow_transactions bt 
        JOIN users u ON bt.user_id = u.id 
        JOIN games g ON bt.game_id = g.id 
        $where_clause
        ORDER BY bt.borrow_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Get unique statuses for filter
$statuses_stmt = $pdo->query("SELECT DISTINCT status FROM borrow_transactions ORDER BY status");
$statuses = $statuses_stmt->fetchAll();

include 'includes/admin_header.php';
?>

<style>
    .transactions-container {
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
    
    .filter-card {
        background: rgba(255, 255, 255, 0.4);
        backdrop-filter: blur(25px) saturate(180%);
        -webkit-backdrop-filter: blur(25px) saturate(180%);
        border-radius: 20px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.4);
        border-left: 4px solid rgba(102, 126, 234, 0.8);
    }
    
    .filter-form {
        display: flex;
        gap: 1rem;
        align-items: end;
        flex-wrap: wrap;
    }
    
    .form-group {
        flex: 1;
        min-width: 200px;
    }
    
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--dark);
    }
    
    .form-control {
        width: 100%;
        padding: 0.875rem 1rem;
        font-size: 1rem;
        border: 1px solid #ddd;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(108, 92, 238, 0.15);
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
    
    .btn-primary {
        background: var(--primary);
        color: white;
    }
    
    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(108, 92, 231, 0.4);
    }
    
    .btn-warning {
        background: #fdcb6e;
        color: var(--dark);
    }
    
    .btn-warning:hover {
        background: #f0b44a;
        transform: translateY(-2px);
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
    
    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    
    .alert-success {
        background: rgba(212, 237, 218, 0.8);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        color: #155724;
        border: 1px solid rgba(195, 230, 203, 0.5);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    
    .alert-danger {
        background: rgba(248, 215, 218, 0.8);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        color: #721c24;
        border: 1px solid rgba(245, 198, 203, 0.5);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    
    .alert-warning {
        background: rgba(255, 243, 205, 0.8);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        color: #856404;
        border: 1px solid rgba(255, 238, 186, 0.5);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    
    .table {
        width: 100%;
        border-collapse: collapse;
        background: rgba(255, 255, 255, 0.4);
        backdrop-filter: blur(25px) saturate(180%);
        -webkit-backdrop-filter: blur(25px) saturate(180%);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.4);
    }
    
    .table th,
    .table td {
        padding: 1rem;
        text-align: left;
    }
    
    .table tbody td {
        border-bottom: 1px solid rgba(233, 236, 239, 0.5);
        background: rgba(255, 255, 255, 0.3);
    }
    
    .table th {
        background: rgba(102, 126, 234, 0.75);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        color: white;
        font-weight: 700;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        position: relative;
    }
    
    .table th::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.3) 0%, rgba(118, 75, 162, 0.3) 100%);
        z-index: 0;
    }
    
    .table tbody tr {
        background: rgba(255, 255, 255, 0.2);
        transition: all 0.2s ease;
    }
    
    .table tbody tr:nth-child(even) {
        background: rgba(255, 255, 255, 0.3);
    }
    
    .table tr:hover {
        background: rgba(248, 249, 255, 0.7) !important;
        transform: scale(1.01);
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
    
    .badge-borrowed {
        color: white;
        background-color: #fdcb6e;
    }
    
    .badge-returned {
        color: white;
        background-color: #00b894;
    }
    
    .badge-overdue {
        color: white;
        background-color: #d63031;
    }
    
    .status-form {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }
    
    .status-select {
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 0.9rem;
    }
    
    @media (max-width: 768px) {
        .filter-form {
            flex-direction: column;
            align-items: stretch;
        }
        
        .form-group {
            min-width: 100%;
        }
        
        .table {
            font-size: 0.9rem;
        }
        
        .table th,
        .table td {
            padding: 0.5rem;
        }
        
        .status-form {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<div class="transactions-container">
    <div class="page-header">
        <h1 class="page-title">Transaction Management</h1>
        <p class="page-subtitle">View and manage all borrowing transactions</p>
    </div>
    
    <?php if(isset($success_message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    
    <?php if(isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    
    <!-- Search and Filter -->
    <div class="filter-card">
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <?php foreach($statuses as $status): ?>
                        <option value="<?php echo htmlspecialchars($status['status']); ?>" 
                                <?php echo $status_filter === $status['status'] ? 'selected' : ''; ?>>
                            <?php echo ucfirst($status['status']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="user" class="form-label">Username</label>
                <input type="text" id="user" name="user" class="form-control" 
                       placeholder="Search by username..." 
                       value="<?php echo htmlspecialchars($user_filter); ?>">
            </div>
            
            <div class="form-group">
                <label for="date" class="form-label">Borrow Date</label>
                <input type="date" id="date" name="date" class="form-control" 
                       value="<?php echo htmlspecialchars($date_filter); ?>">
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="transactions.php" class="btn btn-warning">Clear</a>
            </div>
        </form>
    </div>
    
    <?php if(empty($transactions)): ?>
        <div class="alert alert-warning">No transactions found matching your criteria.</div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All Transactions (<?php echo count($transactions); ?>)</h3>
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Game</th>
                            <th>Platform</th>
                            <th>Borrow Date</th>
                            <th>Status</th>
                            <th>Game Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($transaction['email']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($transaction['title']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($transaction['platform']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($transaction['borrow_date'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $transaction['status']; ?>">
                                        <?php echo ucfirst($transaction['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $transaction['game_status']; ?>">
                                        <?php echo ucfirst($transaction['game_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                        <select name="new_status" class="status-select">
                                            <option value="borrowed" <?php echo $transaction['status'] === 'borrowed' ? 'selected' : ''; ?>>Borrowed</option>
                                            <option value="returned" <?php echo $transaction['status'] === 'returned' ? 'selected' : ''; ?>>Returned</option>
                                            <option value="overdue" <?php echo $transaction['status'] === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn btn-primary btn-sm">
                                            Update
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/admin_footer.php'; ?>
