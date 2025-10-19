-- Add system_settings table for configurable parameters
CREATE TABLE IF NOT EXISTS system_settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    setting_type VARCHAR(50) DEFAULT 'string',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by UUID REFERENCES auth.users(id)
);

-- Insert default borrow duration setting
INSERT INTO system_settings (setting_key, setting_value, setting_type, description)
VALUES ('borrow_duration_days', '14', 'integer', 'Default number of days for borrowing a game')
ON CONFLICT (setting_key) DO NOTHING;

-- Insert late fee per day setting
INSERT INTO system_settings (setting_key, setting_value, setting_type, description)
VALUES ('late_fee_per_day', '2.00', 'decimal', 'Late fee amount charged per day for overdue games')
ON CONFLICT (setting_key) DO NOTHING;

-- Create index for faster lookups
CREATE INDEX IF NOT EXISTS idx_system_settings_key ON system_settings(setting_key);

-- Grant permissions (adjust as needed)
COMMENT ON TABLE system_settings IS 'System-wide configurable settings managed by administrators';
