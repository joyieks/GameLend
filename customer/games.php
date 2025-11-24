<?php
require_once '../includes/session_config.php';
$page_title = "Games";

// Include authentication check
require_once '../includes/auth_check.php';

// Set security headers
setSecurityHeaders();

// Validate session and require customer access
validateSession();
requireCustomer();

require_once '../db/db_connect.php';
require_once '../includes/settings_helper.php';

// Handle borrow action
if(isset($_POST['borrow_game'])) {
    $game_id = $_POST['game_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if game is available
    $stmt = $pdo->prepare("SELECT status, available_quantity FROM games WHERE id = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch();
    
    if($game && $game['status'] !== 'maintenance' && $game['available_quantity'] > 0) {
        // Check if user already has this game borrowed
        $stmt = $pdo->prepare("SELECT id FROM borrow_transactions WHERE user_id = ? AND game_id = ? AND status = 'borrowed'");
        $stmt->execute([$user_id, $game_id]);
        
        if($stmt->rowCount() == 0) {
            // Begin transaction
            $pdo->beginTransaction();
            
            try {
                // Get borrow duration from settings (defaults to 14 if table doesn't exist)
                $borrow_duration = getBorrowDuration();
                
                // Calculate due date using PHP
                $due_date = date('Y-m-d H:i:s', strtotime("+$borrow_duration days"));
                
                // Manually update game availability FIRST (don't rely on trigger)
                $stmt = $pdo->prepare("UPDATE games SET available_quantity = available_quantity - 1, status = CASE WHEN available_quantity - 1 = 0 THEN 'borrowed' ELSE status END WHERE id = ?");
                $updateResult = $stmt->execute([$game_id]);
                
                if (!$updateResult) {
                    throw new Exception("Failed to update game availability");
                }
                
                // Create borrow transaction
                $stmt = $pdo->prepare("INSERT INTO borrow_transactions (user_id, game_id, borrow_date, due_date, status) VALUES (?, ?, NOW(), ?, 'borrowed')");
                $insertResult = $stmt->execute([$user_id, $game_id, $due_date]);
                
                if (!$insertResult) {
                    throw new Exception("Failed to insert borrow transaction");
                }
                
                $pdo->commit();
                $success_message = "Game borrowed successfully! Please return within $borrow_duration days.";
            } catch(Exception $e) {
                $pdo->rollback();
                // Log detailed error for debugging
                error_log("Borrow Error - User: $user_id, Game: $game_id, Error: " . $e->getMessage());
                $error_message = "Failed to borrow game. Please try again. Error: " . $e->getMessage();
            }
        } else {
            $error_message = "You already have this game borrowed.";
        }
    } else {
        $error_message = "Game is not available for borrowing.";
    }
}

// Get games with search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$platform_filter = isset($_GET['platform']) ? $_GET['platform'] : '';

$where_conditions = [];
$params = [];

if($search) {
    $where_conditions[] = "(title LIKE ? OR platform LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if($platform_filter) {
    $where_conditions[] = "platform = ?";
    $params[] = $platform_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$sql = "SELECT * FROM games ORDER BY title";
if($where_clause) {
    $sql = "SELECT * FROM games $where_clause ORDER BY title";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$games = $stmt->fetchAll();

// Get unique platforms for filter
$platforms_stmt = $pdo->query("SELECT DISTINCT platform FROM games ORDER BY platform");
$platforms = $platforms_stmt->fetchAll();

include 'includes/customer_header.php';
?>

<style>
    :root {
        --primary: #6c5ce7;
        --primary-dark: #5649c9;
        --secondary: #00cec9;
        --accent: #fd79a8;
        --dark: #2d3436;
        --light: #f5f6fa;
        --success: #00b894;
        --warning: #fdcb6e;
        --danger: #d63031;
        --gray: #dfe6e9;
        --available: #00b894;
        --borrowed: #fdcb6e;
        --maintenance: #d63031;
    }
    
    .games-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }
    
    .page-header {
        text-align: center;
        margin-bottom: 2.5rem;
        padding: 2rem;
    }
    
    .page-title {
        font-size: 2.5rem;
        color: white;
        margin-bottom: 0.5rem;
        font-weight: 800;
        text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.8);
    }
    
    .page-subtitle {
        color: white;
        font-size: 1.1rem;
        font-weight: 500;
        text-shadow: 2px 2px 6px rgba(0, 0, 0, 0.7);
    }
    
    .filter-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        border-left: 4px solid #667eea;
    }
    
    .filter-form {
        display: flex;
        gap: 1rem;
        align-items: flex-end;
        flex-wrap: wrap;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-width: 200px;
    }
    
    .button-group {
        display: flex;
        gap: 0.5rem;
        align-items: flex-end;
        flex-shrink: 0;
        margin-bottom: 0;
    }
    
    .button-group .btn {
        white-space: nowrap;
        padding: 0.875rem 1.5rem;
        margin: 0;
        height: auto;
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
        border: 2px solid #f0b44a;
    }
    
    .btn-warning:hover {
        background: #f0b44a;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(253, 203, 110, 0.4);
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
    
    .alert {
        padding: 1rem 1.25rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        font-weight: 500;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .alert-success {
        background: linear-gradient(135deg, #f0fff4 0%, #e6ffec 100%);
        color: #276749;
        border-left: 4px solid #38a169;
    }
    
    .alert-success i {
        color: #38a169;
        margin-right: 8px;
    }
    
    .alert-danger {
        background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%);
        color: #c53030;
        border-left: 4px solid #e53e3e;
    }
    
    .alert-danger i {
        color: #e53e3e;
        margin-right: 8px;
    }
    
    .alert-warning {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
    
    .games-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 2rem;
    }
    
    .game-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
    }
    
    .game-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }
    
    .game-card-image {
        width: 100%;
        height: 220px;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        position: relative;
        background-color: #f0f2f5;
    }
    
    .game-card-image::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.4) 100%);
    }
    
    .no-image-placeholder {
        width: 100%;
        height: 220px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        color: white;
    }
    
    .no-image-placeholder i {
        font-size: 4rem;
        margin-bottom: 0.5rem;
        opacity: 0.8;
    }
    
    .no-image-placeholder p {
        margin: 0;
        font-weight: 600;
        opacity: 0.9;
    }
    
    .game-card-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 1.2rem;
        text-align: center;
    }
    
    .game-card-title {
        margin: 0;
        font-size: 1.4rem;
        font-weight: 700;
    }
    
    .game-card-body {
        padding: 1.5rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    
    .game-info {
        margin-bottom: 1rem;
    }
    
    .game-info p {
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
    }
    
    .game-info strong {
        min-width: 80px;
        display: inline-block;
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
    
    .badge-available {
        color: white;
        background-color: var(--available);
    }
    
    .badge-borrowed {
        color: var(--dark);
        background-color: var(--borrowed);
    }
    
    .badge-maintenance {
        color: white;
        background-color: var(--maintenance);
    }
    
    .game-card-footer {
        margin-top: auto;
        padding: 0 1.5rem 1.5rem;
    }
    
    .text-muted {
        color: #6c757d !important;
    }
    
    @media (max-width: 768px) {
        .filter-form {
            flex-direction: column;
        }
        
        .form-group {
            width: 100%;
            min-width: 100%;
        }
        
        .button-group {
            width: 100%;
        }
        
        .button-group .btn {
            flex: 1;
        }
        
        .games-grid {
            grid-template-columns: 1fr;
        }
        
        .page-header {
            padding: 1.5rem;
        }
        
        .page-title {
            font-size: 2rem;
        }
    }
</style>

<div class="games-container">
    <div class="page-header">
        <h1 class="page-title">Game Library</h1>
        <p class="page-subtitle">Browse and borrow from our extensive collection</p>
    </div>
    
    <?php if(isset($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>
    
    <!-- Search and Filter -->
    <div class="filter-card">
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label for="search" class="form-label">Search Games</label>
                <input type="text" id="search" name="search" class="form-control" 
                       placeholder="Search by title or platform..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="form-group">
                <label for="platform" class="form-label">Platform</label>
                <select id="platform" name="platform" class="form-control">
                    <option value="">All Platforms</option>
                    <?php foreach($platforms as $platform): ?>
                        <option value="<?php echo htmlspecialchars($platform['platform']); ?>" 
                                <?php echo $platform_filter === $platform['platform'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($platform['platform']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="button-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="games.php" class="btn btn-warning">
                    <i class="fas fa-times"></i> Clear
                </a>
            </div>
        </form>
    </div>
    
    <?php if(empty($games)): ?>
        <div class="alert alert-warning">No games found matching your criteria.</div>
    <?php else: ?>
        <div class="games-grid">
            <?php foreach($games as $game): ?>
                <div class="game-card">
                    <!-- Game Cover Image -->
                    <?php if(!empty($game['image_url'])): ?>
                        <div class="game-card-image" style="background-image: url('<?php echo htmlspecialchars($game['image_url']); ?>');">
                        </div>
                    <?php else: ?>
                        <div class="no-image-placeholder">
                            <i class="fas fa-gamepad"></i>
                            <p>No Image Available</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="game-card-header">
                        <h3 class="game-card-title"><?php echo htmlspecialchars($game['title']); ?></h3>
                    </div>
                    
                    <div class="game-card-body">
                        <div class="game-info">
                            <p><strong>Platform:</strong> <?php echo htmlspecialchars($game['platform']); ?></p>
                            <p><strong>Available:</strong> <?php echo $game['available_quantity']; ?> / <?php echo $game['total_quantity']; ?></p>
                            <p><strong>Status:</strong> 
                                <?php if($game['status'] === 'maintenance'): ?>
                                    <span class="badge badge-maintenance">Maintenance</span>
                                <?php elseif($game['available_quantity'] > 0): ?>
                                    <span class="badge badge-available">Available</span>
                                <?php else: ?>
                                    <span class="badge badge-borrowed">All Borrowed</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="game-card-footer">
                        <?php if($game['status'] !== 'maintenance' && $game['available_quantity'] > 0): ?>
                            <form method="POST">
                                <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                <button type="submit" name="borrow_game" class="btn btn-success" 
                                        style="width: 100%;"
                                        data-confirm="Are you sure you want to borrow this game?">
                                    <i class="fas fa-hand-holding"></i> Borrow Game
                                </button>
                            </form>
                        <?php elseif($game['status'] === 'maintenance'): ?>
                            <p class="text-muted" style="text-align: center;">Under maintenance</p>
                        <?php else: ?>
                            <p class="text-muted" style="text-align: center;">All copies borrowed</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/customer_footer.php'; ?>
