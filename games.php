    <?php
require_once 'includes/session_config.php';
$page_title = "Games";
require_once 'db/db_connect.php';

// Handle borrow action
if(isset($_POST['borrow_game']) && isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer') {
    $game_id = $_POST['game_id'];
    $user_id = $_SESSION['user_id'];
    
    // Check if game is available
    $stmt = $pdo->prepare("SELECT status FROM games WHERE id = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch();
    
    if($game && $game['status'] === 'available') {
        // Check if user already has this game borrowed
        $stmt = $pdo->prepare("SELECT id FROM borrow_transactions WHERE user_id = ? AND game_id = ? AND status = 'borrowed'");
        $stmt->execute([$user_id, $game_id]);
        
        if($stmt->rowCount() == 0) {
            // Begin transaction
            $pdo->beginTransaction();
            
            try {
                // Update game status
                $stmt = $pdo->prepare("UPDATE games SET status = 'borrowed' WHERE id = ?");
                $stmt->execute([$game_id]);
                
                // Create borrow transaction
                $stmt = $pdo->prepare("INSERT INTO borrow_transactions (user_id, game_id, borrow_date, status) VALUES (?, ?, NOW(), 'borrowed')");
                $stmt->execute([$user_id, $game_id]);
                
                $pdo->commit();
                $success_message = "Game borrowed successfully!";
            } catch(Exception $e) {
                $pdo->rollback();
                $error_message = "Failed to borrow game. Please try again.";
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

include 'includes/header.php';
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
        max-width: 100%;
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
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        border-left: 4px solid #2c3e50;
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
            align-items: stretch;
        }
        
        .form-group {
            min-width: 100%;
        }
        
        .games-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="games-container">
    <div class="page-header">
        <h1 class="page-title">Game Library</h1>
        <p class="page-subtitle">Browse and borrow from our extensive collection</p>
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
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="games.php" class="btn btn-warning">Clear</a>
            </div>
        </form>
    </div>
    
    <?php if(empty($games)): ?>
        <div class="alert alert-warning">No games found matching your criteria.</div>
    <?php else: ?>
        <div class="games-grid">
            <?php foreach($games as $game): ?>
                <div class="game-card">
                    <div class="game-card-header">
                        <h3 class="game-card-title"><?php echo htmlspecialchars($game['title']); ?></h3>
                    </div>
                    
                    <div class="game-card-body">
                        <div class="game-info">
                            <p><strong>Platform:</strong> <?php echo htmlspecialchars($game['platform']); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge badge-<?php echo $game['status']; ?>">
                                    <?php echo ucfirst($game['status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <div class="game-card-footer">
                        <?php if($game['status'] === 'available' && isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer'): ?>
                            <form method="POST">
                                <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                <button type="submit" name="borrow_game" class="btn btn-success" 
                                        style="width: 100%;"
                                        data-confirm="Are you sure you want to borrow this game?">
                                    <i class="fas fa-hand-holding"></i> Borrow Game
                                </button>
                            </form>
                        <?php elseif($game['status'] === 'borrowed'): ?>
                            <p class="text-muted" style="text-align: center;">Currently borrowed</p>
                        <?php elseif($game['status'] === 'maintenance'): ?>
                            <p class="text-muted" style="text-align: center;">Under maintenance</p>
                        <?php endif; ?>
                        
                        <?php if(!isset($_SESSION['user_id'])): ?>
                            <a href="login.php" class="btn btn-primary" style="width: 100%;">Login to Borrow</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>