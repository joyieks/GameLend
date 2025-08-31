<?php
session_start();
$page_title = "Manage Games";

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../db/db_connect.php';

$message = '';
$message_type = '';

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_game'])) {
        $title = trim($_POST['title']);
        $platform = trim($_POST['platform']);
        $status = $_POST['status'];
        
        if(empty($title) || empty($platform)) {
            $message = 'Title and platform are required';
            $message_type = 'danger';
        } else {
            $stmt = $pdo->prepare("INSERT INTO games (title, platform, status) VALUES (?, ?, ?)");
            if($stmt->execute([$title, $platform, $status])) {
                $message = 'Game added successfully';
                $message_type = 'success';
            } else {
                $message = 'Failed to add game';
                $message_type = 'danger';
            }
        }
    } elseif(isset($_POST['edit_game'])) {
        $game_id = $_POST['game_id'];
        $title = trim($_POST['title']);
        $platform = trim($_POST['platform']);
        $status = $_POST['status'];
        
        if(empty($title) || empty($platform)) {
            $message = 'Title and platform are required';
            $message_type = 'danger';
        } else {
            $stmt = $pdo->prepare("UPDATE games SET title = ?, platform = ?, status = ? WHERE id = ?");
            if($stmt->execute([$title, $platform, $status, $game_id])) {
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
            <div style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label for="title" class="form-label">Game Title</label>
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>
                
                <div class="form-group" style="min-width: 150px;">
                    <label for="platform" class="form-label">Platform</label>
                    <select id="platform" name="platform" class="form-control" required>
                        <option value="">Select Platform</option>
                        <option value="PC">PC</option>
                        <option value="PlayStation 4">PlayStation 4</option>
                        <option value="PlayStation 5">PlayStation 5</option>
                        <option value="Nintendo Switch">Nintendo Switch</option>
                        <option value="Xbox One">Xbox One</option>
                        <option value="Xbox Series X">Xbox Series X</option>
                    </select>
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
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Platform</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($games as $game): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($game['title']); ?></td>
                            <td><?php echo htmlspecialchars($game['platform']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $game['status']; ?>">
                                    <?php echo ucfirst($game['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($game['created_at'])); ?></td>
                            <td>
                                <button onclick="editGame(<?php echo $game['id']; ?>, '<?php echo htmlspecialchars($game['title']); ?>', '<?php echo htmlspecialchars($game['platform']); ?>', '<?php echo $game['status']; ?>')" 
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
function editGame(id, title, platform, status) {
    document.getElementById('edit_game_id').value = id;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_platform').value = platform;
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
