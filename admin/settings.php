<?php
require_once '../includes/session_config.php';
$page_title = "System Settings";

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

// Handle settings update
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    $borrow_duration = (int)$_POST['borrow_duration_days'];
    $late_fee = (float)$_POST['late_fee_per_day'];
    
    // Validation
    if($borrow_duration < 1 || $borrow_duration > 365) {
        $message = 'Borrow duration must be between 1 and 365 days';
        $message_type = 'danger';
    } elseif($late_fee < 0 || $late_fee > 1000) {
        $message = 'Late fee must be between $0 and $1000';
        $message_type = 'danger';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Update borrow duration
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW(), updated_by = ? WHERE setting_key = 'borrow_duration_days'");
            $stmt->execute([$borrow_duration, $_SESSION['user_id']]);
            
            // Update late fee
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW(), updated_by = ? WHERE setting_key = 'late_fee_per_day'");
            $stmt->execute([number_format($late_fee, 2, '.', ''), $_SESSION['user_id']]);
            
            $pdo->commit();
            
            $message = 'Settings updated successfully! Changes will apply to new transactions.';
            $message_type = 'success';
        } catch(Exception $e) {
            $pdo->rollback();
            $message = 'Failed to update settings. Please try again.';
            $message_type = 'danger';
        }
    }
}

// Get current settings
$stmt = $pdo->query("SELECT * FROM system_settings ORDER BY setting_key");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Default values if not found
$borrow_duration = $settings['borrow_duration_days'] ?? 14;
$late_fee = $settings['late_fee_per_day'] ?? 2.00;

include 'includes/admin_header.php';
?>

<style>
    .settings-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 2rem;
    }
    
    .settings-card {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        margin-bottom: 2rem;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--dark);
    }
    
    .form-control {
        width: 100%;
        padding: 0.75rem;
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
    
    .form-help {
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }
    
    .btn {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        font-weight: 600;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
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
    
    .info-box {
        background: #e7f3ff;
        border-left: 4px solid #2196F3;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    
    .info-box h4 {
        margin: 0 0 0.5rem 0;
        color: #1976D2;
    }
    
    .info-box ul {
        margin: 0.5rem 0 0 1.5rem;
        padding: 0;
    }
    
    .info-box li {
        margin-bottom: 0.25rem;
    }
</style>

<div class="settings-container">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-cog"></i> System Settings
            </h2>
        </div>
        
        <?php if($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <h4><i class="fas fa-info-circle"></i> Important Notes</h4>
            <ul>
                <li>Changes apply immediately to <strong>new</strong> borrow transactions</li>
                <li>Existing borrowed games keep their original due dates</li>
                <li>Late fees are calculated automatically for overdue games</li>
                <li>Users will see the updated duration when borrowing games</li>
            </ul>
        </div>
        
        <form method="POST">
            <div class="settings-card">
                <h3 style="margin-top: 0;">
                    <i class="fas fa-calendar-alt"></i> Borrowing Duration
                </h3>
                
                <div class="form-group">
                    <label for="borrow_duration_days" class="form-label">
                        Default Borrow Duration (Days)
                    </label>
                    <input type="number" id="borrow_duration_days" name="borrow_duration_days" 
                           class="form-control" min="1" max="365" 
                           value="<?php echo htmlspecialchars($borrow_duration); ?>" required>
                    <div class="form-help">
                        How many days customers can keep a game before it's considered overdue. 
                        Recommended: 7-30 days. Current: <strong><?php echo $borrow_duration; ?> days</strong>
                    </div>
                </div>
            </div>
            
            <div class="settings-card">
                <h3 style="margin-top: 0;">
                    <i class="fas fa-dollar-sign"></i> Late Fees
                </h3>
                
                <div class="form-group">
                    <label for="late_fee_per_day" class="form-label">
                        Late Fee Per Day ($)
                    </label>
                    <input type="number" id="late_fee_per_day" name="late_fee_per_day" 
                           class="form-control" min="0" max="1000" step="0.01"
                           value="<?php echo htmlspecialchars($late_fee); ?>" required>
                    <div class="form-help">
                        Amount charged per day for overdue games. Set to $0 to disable late fees.
                        Current: <strong>$<?php echo number_format($late_fee, 2); ?> per day</strong>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-warning">
                <strong>⚠️ Warning:</strong> Changing these settings will affect all future transactions. 
                Make sure to inform your team about any policy changes.
            </div>
            
            <div style="margin-top: 2rem;">
                <button type="submit" name="update_settings" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
                <a href="dashboard.php" class="btn" style="background: #6c757d; color: white; margin-left: 1rem;">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
    
    <!-- Current System Status -->
    <div class="settings-card">
        <h3 style="margin-top: 0;">
            <i class="fas fa-chart-line"></i> Current System Status
        </h3>
        
        <?php
        // Get statistics
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM borrow_transactions WHERE status = 'borrowed'");
        $active_borrows = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM borrow_transactions 
                            WHERE status = 'borrowed' AND borrow_date < NOW() - INTERVAL '{$borrow_duration} days'");
        $overdue_count = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM games WHERE status = 'available'");
        $available_games = $stmt->fetch()['count'];
        ?>
        
        <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Active Borrows</h4>
                </div>
                <p style="font-size: 2rem; font-weight: bold; color: #667eea;"><?php echo $active_borrows; ?></p>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Overdue Games</h4>
                </div>
                <p style="font-size: 2rem; font-weight: bold; color: #dc3545;"><?php echo $overdue_count; ?></p>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Available Games</h4>
                </div>
                <p style="font-size: 2rem; font-weight: bold; color: #28a745;"><?php echo $available_games; ?></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
