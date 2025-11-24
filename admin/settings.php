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
            // Check if table exists, create if not
            $tableCheck = $pdo->query("SELECT to_regclass('public.system_settings')");
            $tableExists = $tableCheck->fetchColumn() !== null;
            
            if (!$tableExists) {
                // Create the table if it doesn't exist
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS system_settings (
                        id SERIAL PRIMARY KEY,
                        setting_key VARCHAR(100) UNIQUE NOT NULL,
                        setting_value TEXT NOT NULL,
                        setting_type VARCHAR(50) DEFAULT 'string',
                        description TEXT,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_by INTEGER REFERENCES users(id)
                    );
                    
                    CREATE INDEX IF NOT EXISTS idx_system_settings_key ON system_settings(setting_key);
                ");
            }
            
            $pdo->beginTransaction();
            
            // Upsert borrow duration
            $stmt = $pdo->prepare("
                INSERT INTO system_settings (setting_key, setting_value, setting_type, description, updated_at, updated_by)
                VALUES ('borrow_duration_days', ?, 'integer', 'Default number of days for borrowing a game', NOW(), ?)
                ON CONFLICT (setting_key) DO UPDATE SET setting_value = ?, updated_at = NOW(), updated_by = ?
            ");
            $stmt->execute([$borrow_duration, $_SESSION['user_id'], $borrow_duration, $_SESSION['user_id']]);
            
            // Upsert late fee
            $stmt = $pdo->prepare("
                INSERT INTO system_settings (setting_key, setting_value, setting_type, description, updated_at, updated_by)
                VALUES ('late_fee_per_day', ?, 'decimal', 'Late fee amount charged per day for overdue games', NOW(), ?)
                ON CONFLICT (setting_key) DO UPDATE SET setting_value = ?, updated_at = NOW(), updated_by = ?
            ");
            $late_fee_formatted = number_format($late_fee, 2, '.', '');
            $stmt->execute([$late_fee_formatted, $_SESSION['user_id'], $late_fee_formatted, $_SESSION['user_id']]);
            
            $pdo->commit();
            
            $message = 'Settings updated successfully! Changes will apply to new transactions.';
            $message_type = 'success';
        } catch(Exception $e) {
            $pdo->rollback();
            $message = 'Failed to update settings: ' . $e->getMessage();
            $message_type = 'danger';
            error_log("Settings update error: " . $e->getMessage());
        }
    }
}

// Get current settings with error handling
$settings = [];
try {
    // Check if table exists
    $tableCheck = $pdo->query("SELECT to_regclass('public.system_settings')");
    $tableExists = $tableCheck->fetchColumn() !== null;
    
    if ($tableExists) {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings ORDER BY setting_key");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
} catch (Exception $e) {
    error_log("Settings table error: " . $e->getMessage());
    // Table doesn't exist, will use defaults
}

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
        background: rgba(255, 255, 255, 0.4);
        backdrop-filter: blur(25px) saturate(180%);
        -webkit-backdrop-filter: blur(25px) saturate(180%);
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.4);
        margin-bottom: 2rem;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .settings-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(255, 255, 255, 0.4);
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
        padding: 0.875rem 1.25rem;
        font-size: 1rem;
        border: 2px solid rgba(225, 232, 237, 0.5);
        border-radius: 12px;
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
    }
    
    .form-control:focus {
        outline: none;
        border-color: #667eea;
        background: rgba(255, 255, 255, 0.85);
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1), 0 4px 12px rgba(0, 0, 0, 0.08);
        transform: translateY(-1px);
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
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
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
</div>

<?php include 'includes/admin_footer.php'; ?>
