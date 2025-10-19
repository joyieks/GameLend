<?php
/**
 * Insert Default System Settings
 * Run this script to ensure default settings exist in the database
 */

require_once __DIR__ . '/db_connect.php';

try {
    echo "Checking system_settings table...\n";
    
    // Check if table exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM system_settings");
    $count = $stmt->fetchColumn();
    
    echo "Current settings count: $count\n";
    
    // Insert borrow duration if not exists
    $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description)
                          VALUES ('borrow_duration_days', '14', 'integer', 'Default number of days for borrowing a game')
                          ON CONFLICT (setting_key) DO NOTHING");
    $stmt->execute();
    echo "✓ Borrow duration setting checked/inserted\n";
    
    // Insert late fee if not exists
    $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description)
                          VALUES ('late_fee_per_day', '2.00', 'decimal', 'Late fee amount charged per day for overdue games')
                          ON CONFLICT (setting_key) DO NOTHING");
    $stmt->execute();
    echo "✓ Late fee setting checked/inserted\n";
    
    // Display all settings
    echo "\nCurrent system settings:\n";
    $stmt = $pdo->query("SELECT * FROM system_settings ORDER BY setting_key");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($settings as $setting) {
        echo sprintf("  - %s = %s (%s)\n", 
                    $setting['setting_key'], 
                    $setting['setting_value'], 
                    $setting['setting_type']);
    }
    
    echo "\n✅ SUCCESS: Default settings are now available!\n";
    
} catch(Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
