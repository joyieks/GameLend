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

$message = '';
$message_type = '';

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_game'])) {
        $title = trim($_POST['title']);
        $status = $_POST['status'];
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
                $stmt = $pdo->prepare("INSERT INTO games (title, platform, total_quantity, available_quantity, status) VALUES (?, ?, ?, ?, ?)");
                foreach ($pairs as $platform => $qty) {
                    $available = ($status === 'available') ? $qty : 0;
                    $stmt->execute([$title, $platform, $qty, $available, $status]);
                }
                $pdo->commit();
                $message = 'Game(s) added successfully';
                $message_type = 'success';
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = 'Failed to add game(s)';
                $message_type = 'danger';
            }
        }
    } elseif(isset($_POST['edit_game'])) {
        $game_id = $_POST['game_id'];
        $title = trim($_POST['title']);
        $platform = isset($_POST['platform']) ? trim((string)$_POST['platform']) : '';
        $status = $_POST['status'];
        $total_quantity = isset($_POST['total_quantity']) ? max(0, (int)$_POST['total_quantity']) : null;
        $available_quantity = isset($_POST['available_quantity']) ? max(0, (int)$_POST['available_quantity']) : null;
        
        if(empty($title) || $platform === '' || $total_quantity === null || $available_quantity === null) {
            $message = 'Title and at least one platform are required';
            $message_type = 'danger';
        } else {
            if ($available_quantity > $total_quantity) { $available_quantity = $total_quantity; }
            $stmt = $pdo->prepare("UPDATE games SET title = ?, platform = ?, total_quantity = ?, available_quantity = ?, status = ? WHERE id = ?");
            if($stmt->execute([$title, $platform, $total_quantity, $available_quantity, $status, $game_id])) {
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
$stmt = $pdo->query("SELECT * FROM games ORDER BY title");
$games = $stmt->fetchAll();

include 'includes/admin_header.php';
?>

<style>
    /* Expand width and refresh UI for Manage Games */
    main.container { max-width: 100%; padding: 2rem; }
    .game-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    }
    .game-table thead th {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: #fff;
        padding: 0.9rem 1rem;
        text-align: left;
        font-weight: 700;
        white-space: nowrap;
        border-bottom: none;
    }
    .game-table tbody td {
        padding: 0.9rem 1rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f2f5;
        white-space: nowrap;
    }
    .game-table tbody tr:hover { background: #fafbff; }
    .badge { border-radius: 999px; padding: .25rem .6rem; font-weight: 700; }
    .badge-available { background:#dcfce7; color:#166534; }
    .badge-borrowed { background:#fff7ed; color:#c2410c; }
    .badge-maintenance { background:#fee2e2; color:#b91c1c; }
    .btn.btn-sm { padding: .45rem .7rem; border-radius: 8px; font-weight: 700; box-shadow: 0 4px 10px rgba(0,0,0,.08); }
    .btn-warning.btn-sm { background:#f59e0b; border:none; color:#fff; }
    .btn-danger.btn-sm { background:#ef4444; border:none; }
    .btn-success { background: #10b981; border: none; }
    .card h3.card-title { margin: 0; }
    .form-inline { display:flex; gap:1rem; align-items:end; flex-wrap:wrap; }
    .form-inline .form-group { min-width: 150px; }
    .qty { font-weight: 700; }
    @media (max-width: 992px) { .hide-lg { display:none; } }
    /* Modal polish */
    .modal .btn-primary { background: linear-gradient(135deg,#8b5cf6,#6366f1); border: none; }
    .modal .btn-secondary { background:#64748b; border:none; color:#fff; }
    .modal h3 { margin-top:0; }
    .modal-content { max-width: 640px !important; }
    .platform-row .btn-danger { background:#ef4444; border:none; }
    .btn-secondary { background:#475569; color:#fff; border:none; }
    .btn-success { background: linear-gradient(135deg,#10b981,#059669); }
    .card { border-radius: 12px; }
    .card-header { border-top-left-radius: 12px; border-top-right-radius: 12px; }
    .card + .card { margin-top: 1.25rem; }
    .alert { border-radius: 10px; }
    .qty-wrap { font-size: .95rem; }
    .qty-wrap .sep { color:#64748b; }
  </style>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Manage Games</h2>
    </div>
    
    <?php if($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <!-- Add New Game Form -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h3 class="card-title">Add New Game</h3>
        </div>
        
        <form method="POST" data-validate>
            <div class="form-inline">
                <div class="form-group" style="flex: 1; min-width: 220px;">
                    <label for="title" class="form-label">Game Title</label>
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>
                
                <div class="form-group" style="min-width: 260px;">
                    <label class="form-label">Platforms and Quantities</label>
                    <div id="platform_builder">
                        <div class="platform-row" style="display:flex; gap:.5rem; align-items:center; margin-bottom:.5rem;">
                            <select name="platforms[]" class="form-control" style="min-width:160px;">
                                <option value="PC">PC</option>
                                <option value="PlayStation 4">PlayStation 4</option>
                                <option value="PlayStation 5">PlayStation 5</option>
                                <option value="Nintendo Switch">Nintendo Switch</option>
                                <option value="Xbox One">Xbox One</option>
                                <option value="Xbox Series X">Xbox Series X</option>
                            </select>
                            <input type="number" name="quantities[]" class="form-control" min="1" value="1" style="width:100px;">
                            <button type="button" class="btn btn-secondary" onclick="addPlatformRow()">Add</button>
                        </div>
                    </div>
                </div>
                
                <div class="form-group" style="min-width: 150px;">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="available">Available</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="add_game" class="btn btn-success">Add Game</button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Games List -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Current Games</h3>
        </div>
        
        <?php if(empty($games)): ?>
            <p>No games found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table game-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Platform</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th class="hide-lg">Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($games as $game): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($game['title']); ?></td>
                            <td><?php echo htmlspecialchars($game['platform']); ?></td>
                            <td class="qty-wrap"><span class="qty"><?php echo (int)$game['available_quantity']; ?></span> <span class="sep">/</span> <?php echo (int)$game['total_quantity']; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $game['status']; ?>">
                                    <?php echo ucfirst($game['status']); ?>
                                </span>
                            </td>
                            <td class="hide-lg"><?php echo date('M j, Y', strtotime($game['created_at'])); ?></td>
                            <td>
                                <button onclick="editGame(<?php echo $game['id']; ?>, '<?php echo htmlspecialchars($game['title']); ?>', '<?php echo htmlspecialchars($game['platform']); ?>', <?php echo (int)$game['total_quantity']; ?>, <?php echo (int)$game['available_quantity']; ?>, '<?php echo $game['status']; ?>')" 
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
<div id="editModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: white; margin: 15% auto; padding: 2rem; border-radius: 10px; width: 80%; max-width: 500px;">
        <h3>Edit Game</h3>
        
        <form method="POST" data-validate>
            <input type="hidden" id="edit_game_id" name="game_id">
            
            <div class="form-group">
                <label for="edit_title" class="form-label">Game Title</label>
                <input type="text" id="edit_title" name="title" class="form-control" required>
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
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" name="edit_game" class="btn btn-primary">Update Game</button>
            </div>
        </form>
    </div>
</div>

<script>
function editGame(id, title, platform, totalQty, availableQty, status) {
    document.getElementById('edit_game_id').value = id;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_platform').value = platform;
    document.getElementById('edit_total_quantity').value = totalQty;
    document.getElementById('edit_available_quantity').value = availableQty;
    document.getElementById('edit_status').value = status;
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

function addPlatformRow() {
    const container = document.getElementById('platform_builder');
    const row = document.createElement('div');
    row.className = 'platform-row';
    row.style.cssText = 'display:flex; gap:.5rem; align-items:center; margin-bottom:.5rem;';
    row.innerHTML = `
        <select name="platforms[]" class="form-control" style="min-width:160px;">
            <option value="PC">PC</option>
            <option value="PlayStation 4">PlayStation 4</option>
            <option value="PlayStation 5">PlayStation 5</option>
            <option value="Nintendo Switch">Nintendo Switch</option>
            <option value="Xbox One">Xbox One</option>
            <option value="Xbox Series X">Xbox Series X</option>
        </select>
        <input type="number" name="quantities[]" class="form-control" min="1" value="1" style="width:100px;">
        <button type="button" class="btn btn-danger" onclick="removePlatformRow(this)">Remove</button>
    `;
    container.appendChild(row);
}

function removePlatformRow(button) {
    const row = button.closest('.platform-row');
    if (row) row.remove();
}
</script>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 15% auto;
    padding: 2rem;
    border-radius: 10px;
    width: 80%;
    max-width: 500px;
}
</style>

<?php include 'includes/admin_footer.php'; ?>
