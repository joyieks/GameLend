<?php
require_once '../includes/session_config.php';
$page_title = "Manage Games";

// Include authentication check
require_once '../includes/auth_check.php';

// Set security headers
setSecurityHeaders();

// Validate session and require admin access
validateSession();
requireAdmin();

require_once '../db/db_connect.php';

// Get Supabase config for image uploads
$supabase_config = require_once '../includes/supabase_config.php';
$supabaseUrl = $supabase_config['SUPABASE_URL'];
$supabaseAnonKey = $supabase_config['SUPABASE_ANON_KEY'];

$message = '';
$message_type = '';

// Add image_url column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE games ADD COLUMN IF NOT EXISTS image_url TEXT");
} catch (Exception $e) {
    // Column might already exist, ignore error
}

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_game'])) {
        $title = trim($_POST['title']);
        $status = $_POST['status'];
        $image_url = trim($_POST['image_url'] ?? '');
        $platforms = isset($_POST['platforms']) ? (array)$_POST['platforms'] : [];
        $quantities = isset($_POST['quantities']) ? (array)$_POST['quantities'] : [];

        // Validate inputs
        $pairs = [];
        for ($i = 0; $i < count($platforms); $i++) {
            $p = trim((string)$platforms[$i]);
            $q = isset($quantities[$i]) ? (int)$quantities[$i] : 0;
            if ($p !== '' && $q > 0) {
                if (!isset($pairs[$p])) { $pairs[$p] = 0; }
                $pairs[$p] += $q; // aggregate duplicates
            }
        }

        if (empty($title) || empty($pairs)) {
            $message = 'Title and at least one platform with quantity are required';
            $message_type = 'danger';
        } else {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO games (title, platform, total_quantity, available_quantity, status, image_url) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($pairs as $platform => $qty) {
                    $available = ($status === 'available') ? $qty : 0;
                    $stmt->execute([$title, $platform, $qty, $available, $status, $image_url]);
                }
                $pdo->commit();
                $message = 'Game(s) added successfully';
                $message_type = 'success';
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = 'Failed to add game(s): ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    } elseif(isset($_POST['edit_game'])) {
        $game_id = $_POST['game_id'];
        $title = trim($_POST['title']);
        $platform = isset($_POST['platform']) ? trim((string)$_POST['platform']) : '';
        $status = $_POST['status'];
        $image_url = trim($_POST['image_url'] ?? '');
        $total_quantity = isset($_POST['total_quantity']) ? max(0, (int)$_POST['total_quantity']) : null;
        $available_quantity = isset($_POST['available_quantity']) ? max(0, (int)$_POST['available_quantity']) : null;
        
        if(empty($title) || $platform === '' || $total_quantity === null || $available_quantity === null) {
            $message = 'Title and at least one platform are required';
            $message_type = 'danger';
        } else {
            if ($available_quantity > $total_quantity) { $available_quantity = $total_quantity; }
            $stmt = $pdo->prepare("UPDATE games SET title = ?, platform = ?, total_quantity = ?, available_quantity = ?, status = ?, image_url = ? WHERE id = ?");
            if($stmt->execute([$title, $platform, $total_quantity, $available_quantity, $status, $image_url, $game_id])) {
                $message = 'Game updated successfully';
                $message_type = 'success';
            } else {
                $message = 'Failed to update game';
                $message_type = 'danger';
            }
        }
    } elseif(isset($_POST['delete_game'])) {
        $game_id = $_POST['game_id'];
        
        // Check if game is currently borrowed
        $stmt = $pdo->prepare("SELECT status FROM games WHERE id = ?");
        $stmt->execute([$game_id]);
        $game = $stmt->fetch();
        
        if($game['status'] === 'borrowed') {
            $message = 'Cannot delete a game that is currently borrowed';
            $message_type = 'danger';
        } else {
            $stmt = $pdo->prepare("DELETE FROM games WHERE id = ?");
            if($stmt->execute([$game_id])) {
                $message = 'Game deleted successfully';
                $message_type = 'success';
            } else {
                $message = 'Failed to delete game';
                $message_type = 'danger';
            }
        }
    }
}

// Get all games
$stmt = $pdo->query("SELECT * FROM games ORDER BY title, platform");
$games = $stmt->fetchAll();

include 'includes/admin_header.php';
?>

<!-- Pass Supabase config to JavaScript -->
<script>
const SUPABASE_URL = <?php echo json_encode($supabaseUrl); ?>;
const SUPABASE_ANON_KEY = <?php echo json_encode($supabaseAnonKey); ?>;
</script>

<style>
    /* Expand width and refresh UI for Manage Games */
    main.container { max-width: 100%; padding: 2rem; }
    
    /* Enhanced Alert Styles */
    .alert {
        border-radius: 12px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        border: none;
        font-size: 0.95rem;
        font-weight: 500;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        animation: slideDown 0.3s ease-out;
    }
    
    .alert-success {
        background: linear-gradient(135deg, #f0fff4 0%, #e6ffec 100%);
        color: #276749;
        border-left: 4px solid #38a169;
    }
    
    .alert-danger {
        background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%);
        color: #c53030;
        border-left: 4px solid #e53e3e;
    }
    
    /* Add Game Card - Improved Layout */
    .add-game-card {
        background: rgba(255, 255, 255, 0.5);
        backdrop-filter: blur(25px) saturate(180%);
        -webkit-backdrop-filter: blur(25px) saturate(180%);
        border-radius: 20px;
        padding: 2.5rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.4);
        margin-bottom: 2rem;
    }
    
    .form-section {
        margin-bottom: 2rem;
    }
    
    .form-section-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #2d3436;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e9ecef;
    }
    
    .image-upload-area {
        border: 2px dashed #cbd5e0;
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        background: rgba(255, 255, 255, 0.7);
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .image-upload-area:hover {
        border-color: #667eea;
        background: rgba(102, 126, 234, 0.05);
    }
    
    .image-upload-area.dragover {
        border-color: #667eea;
        background: rgba(102, 126, 234, 0.1);
    }
    
    .image-preview {
        max-width: 200px;
        max-height: 200px;
        border-radius: 12px;
        margin: 1rem auto;
        display: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .upload-icon {
        font-size: 3rem;
        color: #667eea;
        margin-bottom: 1rem;
    }
    
    /* Platform Builder - Improved Design */
    .platform-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .platform-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.8);
        border-radius: 12px;
        border: 1px solid rgba(203, 213, 224, 0.5);
        transition: all 0.3s ease;
    }
    
    .platform-item:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }
    
    .platform-item select {
        flex: 1;
        min-width: 180px;
    }
    
    .platform-item input[type="number"] {
        width: 100px;
    }
    
    .platform-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 999px;
        font-size: 0.875rem;
        font-weight: 600;
    }
    
    .btn-add-platform {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-add-platform:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    }
    
    .btn-remove {
        background: #ef4444;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-remove:hover {
        background: #dc2626;
        transform: scale(1.05);
    }
    
    /* Games Table */
    .game-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: rgba(255, 255, 255, 0.4);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        border: 1px solid rgba(255, 255, 255, 0.3);
    }
    
    .game-table thead th {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: #fff;
        padding: 1rem;
        text-align: left;
        font-weight: 700;
        white-space: nowrap;
        border-bottom: none;
    }
    
    .game-table tbody td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f2f5;
    }
    
    .game-table tbody tr:hover {
        background: rgba(250, 251, 255, 0.8);
    }
    
    .game-thumbnail {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .game-title-cell {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .no-image {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #e0e7ff 0%, #cffafe 100%);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #667eea;
        font-size: 1.5rem;
    }
    
    .badge {
        border-radius: 999px;
        padding: .35rem .75rem;
        font-weight: 700;
        font-size: 0.85rem;
    }
    
    .badge-available {
        background: #dcfce7;
        color: #166534;
    }
    
    .badge-borrowed {
        background: #fff7ed;
        color: #c2410c;
    }
    
    .badge-maintenance {
        background: #fee2e2;
        color: #b91c1c;
    }
    
    .btn.btn-sm {
        padding: .5rem .8rem;
        border-radius: 8px;
        font-weight: 700;
        box-shadow: 0 4px 10px rgba(0,0,0,.08);
        transition: all 0.3s ease;
    }
    
    .btn-warning.btn-sm {
        background: #f59e0b;
        border: none;
        color: #fff;
    }
    
    .btn-warning.btn-sm:hover {
        background: #d97706;
        transform: translateY(-2px);
    }
    
    .btn-danger.btn-sm {
        background: #ef4444;
        border: none;
    }
    
    .btn-danger.btn-sm:hover {
        background: #dc2626;
        transform: translateY(-2px);
    }
    
    .btn-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: none;
        color: white;
        padding: 0.875rem 2rem;
        border-radius: 10px;
        font-weight: 700;
        font-size: 1.05rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
    }
    
    .qty { font-weight: 700; }
    .qty-wrap { font-size: .95rem; }
    .qty-wrap .sep { color: #64748b; }
    
    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(4px);
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .modal-content {
        background: white;
        margin: 5% auto;
        padding: 2.5rem;
        border-radius: 20px;
        width: 90%;
        max-width: 600px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: slideUp 0.3s ease;
    }
    
    @keyframes slideUp {
        from {
            transform: translateY(50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .modal h3 {
        margin-top: 0;
        color: #2d3436;
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .modal .btn-primary {
        background: linear-gradient(135deg, #8b5cf6, #6366f1);
        border: none;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
    }
    
    .modal .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
    }
    
    .modal .btn-secondary {
        background: #64748b;
        border: none;
        color: #fff;
        padding: 0.75rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
    }
    
    .card {
        border-radius: 20px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
    }
    
    .card-header {
        border-top-left-radius: 20px;
        border-top-right-radius: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .card + .card {
        margin-top: 1.5rem;
    }
    
    @media (max-width: 768px) {
        .add-game-card {
            padding: 1.5rem;
        }
        
        .platform-item {
            flex-wrap: wrap;
        }
        
        .platform-item select,
        .platform-item input {
            width: 100%;
        }
    }
</style>

<div class="card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-gamepad"></i> Manage Games</h2>
    </div>
    
    <?php if($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <!-- Add New Game Form - Redesigned -->
    <div class="add-game-card">
        <h3 style="margin-top: 0; color: #2d3436; font-size: 1.5rem;">
            <i class="fas fa-plus-circle" style="color: #667eea;"></i> Add New Game
        </h3>
        
        <form method="POST" id="addGameForm">
            <!-- Game Basic Info -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-info-circle"></i> Game Information
                </div>
                
                <div class="form-group">
                    <label for="title" class="form-label">Game Title *</label>
                    <input type="text" id="title" name="title" class="form-control" required 
                           placeholder="e.g., The Legend of Zelda: Breath of the Wild">
                </div>
            </div>
            
            <!-- Image Upload Section -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-image"></i> Game Cover Image
                </div>
                
                <div class="image-upload-area" id="imageUploadArea">
                    <input type="file" id="imageInput" accept="image/*" style="display: none;">
                    <input type="hidden" id="image_url" name="image_url">
                    
                    <div id="uploadPrompt">
                        <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                        <p style="margin: 0; font-weight: 600; color: #2d3436;">Click to upload or drag and drop</p>
                        <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; color: #6c757d;">PNG, JPG, GIF up to 5MB</p>
                    </div>
                    
                    <img id="imagePreview" class="image-preview" alt="Game cover preview">
                    
                    <div id="uploadProgress" style="display: none; margin-top: 1rem;">
                        <div style="background: #e0e7ff; border-radius: 999px; height: 8px; overflow: hidden;">
                            <div id="progressBar" style="background: linear-gradient(90deg, #667eea, #764ba2); height: 100%; width: 0%; transition: width 0.3s;"></div>
                        </div>
                        <p id="uploadStatus" style="margin: 0.5rem 0 0 0; font-size: 0.875rem; color: #667eea;"></p>
                    </div>
                </div>
            </div>
            
            <!-- Platforms and Quantities -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-database"></i> Platforms & Quantities
                </div>
                
                <div class="platform-list" id="platformList">
                    <div class="platform-item">
                        <select name="platforms[]" class="form-control">
                            <option value="PC">PC</option>
                            <option value="PlayStation 4">PlayStation 4</option>
                            <option value="PlayStation 5">PlayStation 5</option>
                            <option value="Nintendo Switch">Nintendo Switch</option>
                            <option value="Xbox One">Xbox One</option>
                            <option value="Xbox Series X">Xbox Series X</option>
                        </select>
                        <input type="number" name="quantities[]" class="form-control" min="1" value="1" placeholder="Qty">
                        <button type="button" class="btn-add-platform" onclick="addPlatformItem()">
                            <i class="fas fa-plus"></i> Add Platform
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Status Selection -->
            <div class="form-section">
                <div class="form-section-title">
                    <i class="fas fa-toggle-on"></i> Initial Status
                </div>
                
                <div class="form-group">
                    <select id="status" name="status" class="form-control" required>
                        <option value="available">Available</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                <button type="submit" name="add_game" class="btn btn-success">
                    <i class="fas fa-save"></i> Add Game
                </button>
            </div>
        </form>
    </div>
    
    <!-- Games List -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list"></i> Current Games Library</h3>
        </div>
        
        <?php if(empty($games)): ?>
            <div style="padding: 3rem; text-align: center; color: #6c757d;">
                <i class="fas fa-gamepad" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                <p style="margin: 0; font-size: 1.1rem;">No games in the library yet. Add your first game above!</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table game-table">
                <thead>
                    <tr>
                        <th>Game</th>
                        <th>Platform</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($games as $game): ?>
                        <tr>
                            <td>
                                <div class="game-title-cell">
                                    <?php if($game['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($game['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($game['title']); ?>" 
                                             class="game-thumbnail">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-gamepad"></i>
                                        </div>
                                    <?php endif; ?>
                                    <strong><?php echo htmlspecialchars($game['title']); ?></strong>
                                </div>
                            </td>
                            <td>
                                <span class="platform-badge">
                                    <i class="fas fa-desktop"></i>
                                    <?php echo htmlspecialchars($game['platform']); ?>
                                </span>
                            </td>
                            <td class="qty-wrap">
                                <span class="qty"><?php echo (int)$game['available_quantity']; ?></span>
                                <span class="sep">/</span>
                                <?php echo (int)$game['total_quantity']; ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $game['status']; ?>">
                                    <?php echo ucfirst($game['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button onclick="editGame(<?php echo $game['id']; ?>, <?php echo htmlspecialchars(json_encode($game)); ?>)" 
                                        class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Are you sure you want to delete this game?')">
                                    <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                    <button type="submit" name="delete_game" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Game Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-edit"></i> Edit Game</h3>
        
        <form method="POST" id="editGameForm">
            <input type="hidden" id="edit_game_id" name="game_id">
            
            <div class="form-group">
                <label for="edit_title" class="form-label">Game Title</label>
                <input type="text" id="edit_title" name="title" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="edit_image_url" class="form-label">Image URL</label>
                <input type="text" id="edit_image_url" name="image_url" class="form-control" placeholder="Optional">
                <img id="edit_image_preview" style="max-width: 100px; margin-top: 0.5rem; border-radius: 8px; display: none;">
            </div>
            
            <div class="form-group">
                <label for="edit_platform" class="form-label">Platform</label>
                <select id="edit_platform" name="platform" class="form-control" required>
                    <option value="PC">PC</option>
                    <option value="PlayStation 4">PlayStation 4</option>
                    <option value="PlayStation 5">PlayStation 5</option>
                    <option value="Nintendo Switch">Nintendo Switch</option>
                    <option value="Xbox One">Xbox One</option>
                    <option value="Xbox Series X">Xbox Series X</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="edit_total_quantity" class="form-label">Total Quantity</label>
                <input type="number" id="edit_total_quantity" name="total_quantity" class="form-control" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="edit_available_quantity" class="form-label">Available Quantity</label>
                <input type="number" id="edit_available_quantity" name="available_quantity" class="form-control" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="edit_status" class="form-label">Status</label>
                <select id="edit_status" name="status" class="form-control" required>
                    <option value="available">Available</option>
                    <option value="borrowed">Borrowed</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                <button type="button" onclick="closeEditModal()" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" name="edit_game" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Game
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Load Supabase JS SDK -->
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>

<script>
// Initialize Supabase
const supabase = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);

// Image Upload Functionality
const imageUploadArea = document.getElementById('imageUploadArea');
const imageInput = document.getElementById('imageInput');
const imagePreview = document.getElementById('imagePreview');
const imageUrlInput = document.getElementById('image_url');
const uploadPrompt = document.getElementById('uploadPrompt');
const uploadProgress = document.getElementById('uploadProgress');
const progressBar = document.getElementById('progressBar');
const uploadStatus = document.getElementById('uploadStatus');

imageUploadArea.addEventListener('click', () => {
    imageInput.click();
});

imageUploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    imageUploadArea.classList.add('dragover');
});

imageUploadArea.addEventListener('dragleave', () => {
    imageUploadArea.classList.remove('dragover');
});

imageUploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    imageUploadArea.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        uploadImage(file);
    }
});

imageInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
        uploadImage(file);
    }
});

async function uploadImage(file) {
    // Validate file size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
        alert('File size must be less than 5MB');
        return;
    }
    
    // Show progress
    uploadPrompt.style.display = 'none';
    uploadProgress.style.display = 'block';
    progressBar.style.width = '0%';
    uploadStatus.textContent = 'Uploading...';
    
    try {
        // Generate unique filename
        const fileExt = file.name.split('.').pop();
        const fileName = `${Date.now()}-${Math.random().toString(36).substring(7)}.${fileExt}`;
        const filePath = `game_images/${fileName}`;
        
        // Upload to Supabase Storage
        const { data, error } = await supabase.storage
            .from('game_images')
            .upload(filePath, file, {
                cacheControl: '3600',
                upsert: false
            });
        
        if (error) {
            throw error;
        }
        
        // Get public URL
        const { data: { publicUrl } } = supabase.storage
            .from('game_images')
            .getPublicUrl(filePath);
        
        // Update UI
        progressBar.style.width = '100%';
        uploadStatus.textContent = 'Upload complete!';
        
        imagePreview.src = publicUrl;
        imagePreview.style.display = 'block';
        imageUrlInput.value = publicUrl;
        
        setTimeout(() => {
            uploadProgress.style.display = 'none';
        }, 1500);
        
    } catch (error) {
        console.error('Upload error:', error);
        uploadStatus.textContent = 'Upload failed: ' + error.message;
        uploadStatus.style.color = '#ef4444';
        
        setTimeout(() => {
            uploadProgress.style.display = 'none';
            uploadPrompt.style.display = 'block';
        }, 2000);
    }
}

// Platform Management
function addPlatformItem() {
    const platformList = document.getElementById('platformList');
    const newItem = document.createElement('div');
    newItem.className = 'platform-item';
    newItem.innerHTML = `
        <select name="platforms[]" class="form-control">
            <option value="PC">PC</option>
            <option value="PlayStation 4">PlayStation 4</option>
            <option value="PlayStation 5">PlayStation 5</option>
            <option value="Nintendo Switch">Nintendo Switch</option>
            <option value="Xbox One">Xbox One</option>
            <option value="Xbox Series X">Xbox Series X</option>
        </select>
        <input type="number" name="quantities[]" class="form-control" min="1" value="1" placeholder="Qty">
        <button type="button" class="btn-remove" onclick="removePlatformItem(this)">
            <i class="fas fa-times"></i> Remove
        </button>
    `;
    platformList.appendChild(newItem);
}

function removePlatformItem(button) {
    const item = button.closest('.platform-item');
    if (document.querySelectorAll('.platform-item').length > 1) {
        item.remove();
    } else {
        alert('You must have at least one platform');
    }
}

// Edit Modal Functions
function editGame(id, gameData) {
    document.getElementById('edit_game_id').value = id;
    document.getElementById('edit_title').value = gameData.title;
    document.getElementById('edit_platform').value = gameData.platform;
    document.getElementById('edit_total_quantity').value = gameData.total_quantity;
    document.getElementById('edit_available_quantity').value = gameData.available_quantity;
    document.getElementById('edit_status').value = gameData.status;
    document.getElementById('edit_image_url').value = gameData.image_url || '';
    
    const editPreview = document.getElementById('edit_image_preview');
    if (gameData.image_url) {
        editPreview.src = gameData.image_url;
        editPreview.style.display = 'block';
    } else {
        editPreview.style.display = 'none';
    }
    
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Preview edit image URL
document.getElementById('edit_image_url').addEventListener('input', (e) => {
    const preview = document.getElementById('edit_image_preview');
    if (e.target.value) {
        preview.src = e.target.value;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?>
