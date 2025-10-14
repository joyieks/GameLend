-- GameLend Database Schema with Row Level Security (RLS)
-- This schema supports both Supabase Auth and custom user management

-- ==============================================
-- 1. USERS TABLE (Integrated with Supabase Auth)
-- ==============================================
-- This table stores application-specific user data
-- Authentication is handled by Supabase Auth
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    auth_id UUID UNIQUE NOT NULL, -- Links to auth.users in Supabase
    email VARCHAR(255) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role VARCHAR(20) DEFAULT 'customer' CHECK (role IN ('admin', 'customer')),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'suspended', 'inactive')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create index for faster lookups
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_auth_id ON users(auth_id);
CREATE INDEX idx_users_role ON users(role);

-- ==============================================
-- 2. GAMES TABLE
-- ==============================================
CREATE TABLE IF NOT EXISTS games (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    platform VARCHAR(100) NOT NULL,
    total_quantity INTEGER DEFAULT 1 CHECK (total_quantity >= 0),
    available_quantity INTEGER DEFAULT 1 CHECK (available_quantity >= 0),
    status VARCHAR(20) DEFAULT 'available' CHECK (status IN ('available', 'borrowed', 'maintenance')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX idx_games_title ON games(title);
CREATE INDEX idx_games_platform ON games(platform);
CREATE INDEX idx_games_status ON games(status);

-- Constraint to ensure available_quantity <= total_quantity
ALTER TABLE games ADD CONSTRAINT check_available_quantity 
    CHECK (available_quantity <= total_quantity);

-- ==============================================
-- 3. BORROW TRANSACTIONS TABLE
-- ==============================================
CREATE TABLE IF NOT EXISTS borrow_transactions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    game_id INTEGER NOT NULL REFERENCES games(id) ON DELETE CASCADE,
    borrow_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    due_date TIMESTAMP NOT NULL,
    return_date TIMESTAMP,
    status VARCHAR(20) DEFAULT 'borrowed' CHECK (status IN ('borrowed', 'returned', 'overdue')),
    late_fee DECIMAL(10, 2) DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for faster queries
CREATE INDEX idx_borrow_user ON borrow_transactions(user_id);
CREATE INDEX idx_borrow_game ON borrow_transactions(game_id);
CREATE INDEX idx_borrow_status ON borrow_transactions(status);
CREATE INDEX idx_borrow_dates ON borrow_transactions(borrow_date, due_date);

-- ==============================================
-- 4. TRIGGER TO UPDATE GAME AVAILABILITY
-- ==============================================
-- Function to update game availability when borrowed
CREATE OR REPLACE FUNCTION update_game_availability()
RETURNS TRIGGER AS $$
BEGIN
    IF (TG_OP = 'INSERT' AND NEW.status = 'borrowed') THEN
        -- Decrease available quantity when game is borrowed
        UPDATE games 
        SET available_quantity = available_quantity - 1,
            status = CASE WHEN available_quantity - 1 = 0 THEN 'borrowed' ELSE status END
        WHERE id = NEW.game_id;
    ELSIF (TG_OP = 'UPDATE' AND OLD.status = 'borrowed' AND NEW.status = 'returned') THEN
        -- Increase available quantity when game is returned
        UPDATE games 
        SET available_quantity = available_quantity + 1,
            status = CASE WHEN available_quantity + 1 > 0 THEN 'available' ELSE status END
        WHERE id = NEW.game_id;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create trigger
DROP TRIGGER IF EXISTS trigger_update_game_availability ON borrow_transactions;
CREATE TRIGGER trigger_update_game_availability
    AFTER INSERT OR UPDATE ON borrow_transactions
    FOR EACH ROW
    EXECUTE FUNCTION update_game_availability();

-- ==============================================
-- 5. TRIGGER TO AUTO-UPDATE UPDATED_AT
-- ==============================================
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Apply to all tables
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_games_updated_at BEFORE UPDATE ON games
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_borrow_transactions_updated_at BEFORE UPDATE ON borrow_transactions
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- ==============================================
-- 6. ROW LEVEL SECURITY (RLS) POLICIES
-- ==============================================

-- Enable RLS on all tables
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE games ENABLE ROW LEVEL SECURITY;
ALTER TABLE borrow_transactions ENABLE ROW LEVEL SECURITY;

-- ============== USERS TABLE POLICIES ==============

-- Policy: Admins can view all users
CREATE POLICY "Admins can view all users" ON users
    FOR SELECT
    USING (
        EXISTS (
            SELECT 1 FROM users AS u
            WHERE u.id = current_setting('app.user_id')::INTEGER
            AND u.role = 'admin'
        )
    );

-- Policy: Users can view their own profile
CREATE POLICY "Users can view own profile" ON users
    FOR SELECT
    USING (id = current_setting('app.user_id')::INTEGER);

-- Policy: Admins can insert users
CREATE POLICY "Admins can insert users" ON users
    FOR INSERT
    WITH CHECK (
        EXISTS (
            SELECT 1 FROM users AS u
            WHERE u.id = current_setting('app.user_id')::INTEGER
            AND u.role = 'admin'
        )
    );

-- Policy: Admins can update users
CREATE POLICY "Admins can update users" ON users
    FOR UPDATE
    USING (
        EXISTS (
            SELECT 1 FROM users AS u
            WHERE u.id = current_setting('app.user_id')::INTEGER
            AND u.role = 'admin'
        )
    );

-- Policy: Users can update their own profile (except role)
CREATE POLICY "Users can update own profile" ON users
    FOR UPDATE
    USING (id = current_setting('app.user_id')::INTEGER)
    WITH CHECK (
        id = current_setting('app.user_id')::INTEGER
        AND role = (SELECT role FROM users WHERE id = current_setting('app.user_id')::INTEGER)
    );

-- Policy: Admins can delete users
CREATE POLICY "Admins can delete users" ON users
    FOR DELETE
    USING (
        EXISTS (
            SELECT 1 FROM users AS u
            WHERE u.id = current_setting('app.user_id')::INTEGER
            AND u.role = 'admin'
        )
    );

-- ============== GAMES TABLE POLICIES ==============

-- Policy: Everyone can view available games
CREATE POLICY "Everyone can view games" ON games
    FOR SELECT
    USING (true);

-- Policy: Admins can insert games
CREATE POLICY "Admins can insert games" ON games
    FOR INSERT
    WITH CHECK (
        EXISTS (
            SELECT 1 FROM users AS u
            WHERE u.id = current_setting('app.user_id')::INTEGER
            AND u.role = 'admin'
        )
    );

-- Policy: Admins can update games
CREATE POLICY "Admins can update games" ON games
    FOR UPDATE
    USING (
        EXISTS (
            SELECT 1 FROM users AS u
            WHERE u.id = current_setting('app.user_id')::INTEGER
            AND u.role = 'admin'
        )
    );

-- Policy: Admins can delete games
CREATE POLICY "Admins can delete games" ON games
    FOR DELETE
    USING (
        EXISTS (
            SELECT 1 FROM users AS u
            WHERE u.id = current_setting('app.user_id')::INTEGER
            AND u.role = 'admin'
        )
    );

-- ============== BORROW_TRANSACTIONS TABLE POLICIES ==============

-- Policy: Users can view their own transactions
CREATE POLICY "Users can view own transactions" ON borrow_transactions
    FOR SELECT
    USING (user_id = current_setting('app.user_id')::INTEGER);

-- Policy: Admins can view all transactions
CREATE POLICY "Admins can view all transactions" ON borrow_transactions
    FOR SELECT
    USING (
        EXISTS (
            SELECT 1 FROM users AS u
            WHERE u.id = current_setting('app.user_id')::INTEGER
            AND u.role = 'admin'
        )
    );

-- Policy: Customers can create their own borrow transactions
CREATE POLICY "Customers can create own transactions" ON borrow_transactions
    FOR INSERT
    WITH CHECK (
        user_id = current_setting('app.user_id')::INTEGER
        AND EXISTS (
            SELECT 1 FROM users AS u
            WHERE u.id = current_setting('app.user_id')::INTEGER
            AND u.role = 'customer'
            AND u.status = 'active'
        )
    );

-- Policy: Admins can create any transaction
CREATE POLICY "Admins can create transactions" ON borrow_transactions
    FOR INSERT
    WITH CHECK (
        EXISTS (
            SELECT 1 FROM users AS u
            WHERE u.id = current_setting('app.user_id')::INTEGER
            AND u.role = 'admin'
        )
    );

-- Policy: Users can update their own transactions (for returns)
CREATE POLICY "Users can update own transactions" ON borrow_transactions
    FOR UPDATE
    USING (user_id = current_setting('app.user_id')::INTEGER);

-- Policy: Admins can update any transaction
CREATE POLICY "Admins can update transactions" ON borrow_transactions
    FOR UPDATE
    USING (
        EXISTS (
            SELECT 1 FROM users AS u
            WHERE u.id = current_setting('app.user_id')::INTEGER
            AND u.role = 'admin'
        )
    );

-- Policy: Admins can delete transactions
CREATE POLICY "Admins can delete transactions" ON borrow_transactions
    FOR DELETE
    USING (
        EXISTS (
            SELECT 1 FROM users AS u
            WHERE u.id = current_setting('app.user_id')::INTEGER
            AND u.role = 'admin'
        )
    );

-- ==============================================
-- 7. TRIGGER FOR NEW AUTH USERS
-- ==============================================
-- Automatically create a user record when someone signs up via Supabase Auth
CREATE OR REPLACE FUNCTION handle_new_user()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO public.users (auth_id, email, first_name, last_name, role, status)
    VALUES (
        NEW.id,
        NEW.email,
        COALESCE(NEW.raw_user_meta_data->>'first_name', 'User'),
        COALESCE(NEW.raw_user_meta_data->>'last_name', 'Name'),
        COALESCE(NEW.raw_user_meta_data->>'role', 'customer'),
        'active'
    );
    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Create trigger on auth.users table
DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
CREATE TRIGGER on_auth_user_created
    AFTER INSERT ON auth.users
    FOR EACH ROW
    EXECUTE FUNCTION handle_new_user();

-- ==============================================
-- 8. SAMPLE ADMIN USER (Manual Creation)
-- ==============================================
-- Note: You'll need to create the admin user through Supabase Auth UI or API first
-- Then manually link it here or use the application to set role to 'admin'
COMMENT ON TABLE users IS 'Application user data linked to Supabase Auth. Create admin users through Supabase Auth, then update their role to admin.';

-- ==============================================
-- 9. USEFUL VIEWS FOR REPORTING
-- ==============================================

-- View: Currently borrowed games with user details
CREATE OR REPLACE VIEW v_active_borrows AS
SELECT 
    bt.id,
    bt.user_id,
    CONCAT(u.first_name, ' ', COALESCE(u.middle_name || ' ', ''), u.last_name) AS full_name,
    u.email,
    bt.game_id,
    g.title,
    g.platform,
    bt.borrow_date,
    bt.due_date,
    CASE 
        WHEN bt.due_date < CURRENT_TIMESTAMP THEN 'overdue'
        ELSE 'active'
    END AS borrow_status,
    EXTRACT(DAY FROM (CURRENT_TIMESTAMP - bt.due_date))::INTEGER AS days_overdue
FROM borrow_transactions bt
JOIN users u ON bt.user_id = u.id
JOIN games g ON bt.game_id = g.id
WHERE bt.status = 'borrowed';

-- View: Game statistics
CREATE OR REPLACE VIEW v_game_statistics AS
SELECT 
    g.id,
    g.title,
    g.platform,
    g.total_quantity,
    g.available_quantity,
    COUNT(bt.id) AS total_borrows,
    COUNT(CASE WHEN bt.status = 'borrowed' THEN 1 END) AS current_borrows,
    COUNT(CASE WHEN bt.status = 'overdue' THEN 1 END) AS overdue_count
FROM games g
LEFT JOIN borrow_transactions bt ON g.id = bt.game_id
GROUP BY g.id;

-- ==============================================
-- 10. HELPER FUNCTIONS
-- ==============================================

-- Function to check if game is available
CREATE OR REPLACE FUNCTION is_game_available(game_id_param INTEGER)
RETURNS BOOLEAN AS $$
DECLARE
    available_qty INTEGER;
BEGIN
    SELECT available_quantity INTO available_qty
    FROM games
    WHERE id = game_id_param;
    
    RETURN (available_qty > 0);
END;
$$ LANGUAGE plpgsql;

-- Function to calculate late fee
CREATE OR REPLACE FUNCTION calculate_late_fee(due_date_param TIMESTAMP)
RETURNS DECIMAL(10, 2) AS $$
DECLARE
    days_late INTEGER;
    fee DECIMAL(10, 2);
BEGIN
    days_late := EXTRACT(DAY FROM (CURRENT_TIMESTAMP - due_date_param))::INTEGER;
    
    IF days_late <= 0 THEN
        RETURN 0.00;
    ELSE
        -- $2 per day late fee
        fee := days_late * 2.00;
        RETURN fee;
    END IF;
END;
$$ LANGUAGE plpgsql;

-- ==============================================
-- SCHEMA CREATION COMPLETE
-- ==============================================

-- Instructions for creating admin user:
-- 1. Go to Supabase Dashboard -> Authentication -> Users
-- 2. Click "Add user" and create admin@gamelend.com
-- 3. After creation, run this query to make them admin:
--    UPDATE users SET role = 'admin' WHERE email = 'admin@gamelend.com';

-- Grant necessary permissions (adjust as needed for your environment)
-- For Supabase, these are handled automatically by the platform

COMMENT ON TABLE users IS 'Stores user accounts for both customers and administrators (integrated with Supabase Auth)';
COMMENT ON TABLE games IS 'Catalog of all games available for borrowing';
COMMENT ON TABLE borrow_transactions IS 'Records of all game borrowing and return transactions';
COMMENT ON VIEW v_active_borrows IS 'Currently borrowed games with overdue status';
COMMENT ON VIEW v_game_statistics IS 'Statistical summary of game borrowing activity';
