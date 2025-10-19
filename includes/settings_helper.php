<?php
/**
 * System Settings Helper Functions
 * 
 * Provides centralized access to system settings stored in the database.
 * Settings are cached during the request to avoid repeated database queries.
 */

// Cache for settings during the current request
$_settings_cache = [];

/**
 * Get a system setting value
 * 
 * @param string $key The setting key to retrieve
 * @param mixed $default Default value if setting not found
 * @return mixed The setting value or default
 */
function getSetting($key, $default = null) {
    global $_settings_cache, $pdo;
    
    // Return from cache if available
    if(isset($_settings_cache[$key])) {
        return $_settings_cache[$key];
    }
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value, setting_type FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($result) {
            // Cast value to appropriate type
            $value = $result['setting_value'];
            switch($result['setting_type']) {
                case 'integer':
                    $value = (int)$value;
                    break;
                case 'decimal':
                    $value = (float)$value;
                    break;
                case 'boolean':
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
                // 'string' and others remain as-is
            }
            
            // Cache and return
            $_settings_cache[$key] = $value;
            return $value;
        }
    } catch(Exception $e) {
        // Silently fall back to default on error
    }
    
    // Cache and return default
    $_settings_cache[$key] = $default;
    return $default;
}

/**
 * Update a system setting value
 * 
 * @param string $key The setting key to update
 * @param mixed $value The new value
 * @param int|null $user_id ID of user making the change
 * @return bool True on success, false on failure
 */
function updateSetting($key, $value, $user_id = null) {
    global $_settings_cache, $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW(), updated_by = ? WHERE setting_key = ?");
        $result = $stmt->execute([$value, $user_id, $key]);
        
        // Clear cache for this key
        if(isset($_settings_cache[$key])) {
            unset($_settings_cache[$key]);
        }
        
        return $result;
    } catch(Exception $e) {
        return false;
    }
}

/**
 * Get all system settings as an associative array
 * 
 * @return array Key-value pairs of all settings
 */
function getAllSettings() {
    global $_settings_cache, $pdo;
    
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value, setting_type FROM system_settings");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $settings = [];
        foreach($results as $row) {
            $value = $row['setting_value'];
            
            // Cast value to appropriate type
            switch($row['setting_type']) {
                case 'integer':
                    $value = (int)$value;
                    break;
                case 'decimal':
                    $value = (float)$value;
                    break;
                case 'boolean':
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;
            }
            
            $settings[$row['setting_key']] = $value;
            $_settings_cache[$row['setting_key']] = $value;
        }
        
        return $settings;
    } catch(Exception $e) {
        return [];
    }
}

/**
 * Convenience function to get borrow duration in days
 * 
 * @return int Number of days for borrow duration (default: 14)
 */
function getBorrowDuration() {
    return getSetting('borrow_duration_days', 14);
}

/**
 * Convenience function to get late fee per day
 * 
 * @return float Late fee amount per day (default: 2.00)
 */
function getLateFeePerDay() {
    return getSetting('late_fee_per_day', 2.00);
}

/**
 * Calculate the due date for a new borrow transaction
 * 
 * @param string|null $borrow_date Starting date (default: now)
 * @return string Due date in 'Y-m-d' format
 */
function calculateDueDate($borrow_date = null) {
    if($borrow_date === null) {
        $borrow_date = date('Y-m-d');
    }
    
    $duration = getBorrowDuration();
    $due_date = date('Y-m-d', strtotime($borrow_date . ' + ' . $duration . ' days'));
    
    return $due_date;
}

/**
 * Calculate late fee for an overdue game
 * 
 * @param string $due_date The due date of the borrow
 * @param string|null $return_date The return date (default: today)
 * @return float Total late fee amount
 */
function calculateLateFee($due_date, $return_date = null) {
    if($return_date === null) {
        $return_date = date('Y-m-d');
    }
    
    $due = new DateTime($due_date);
    $returned = new DateTime($return_date);
    
    if($returned <= $due) {
        return 0.00; // Not overdue
    }
    
    $days_overdue = $due->diff($returned)->days;
    $fee_per_day = getLateFeePerDay();
    
    return $days_overdue * $fee_per_day;
}
