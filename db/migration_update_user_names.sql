-- Migration Script: Update to Supabase Auth Integration
-- This script:
-- 1. Adds auth_id column for Supabase Auth integration
-- 2. Converts full_name to first_name, middle_name, last_name
-- 3. Removes password_hash (security improvement - use Supabase Auth instead)

-- Step 1: Add auth_id column if it doesn't exist
DO $$ 
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='users' AND column_name='auth_id') THEN
        ALTER TABLE users ADD COLUMN auth_id UUID;
        -- Generate temporary UUIDs for existing users
        UPDATE users SET auth_id = gen_random_uuid() WHERE auth_id IS NULL;
        -- Make it unique after setting values
        ALTER TABLE users ADD CONSTRAINT users_auth_id_key UNIQUE (auth_id);
    END IF;
END $$;

-- Step 2: Add new name columns if they don't exist
DO $$ 
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='users' AND column_name='first_name') THEN
        ALTER TABLE users ADD COLUMN first_name VARCHAR(100);
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='users' AND column_name='middle_name') THEN
        ALTER TABLE users ADD COLUMN middle_name VARCHAR(100);
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='users' AND column_name='last_name') THEN
        ALTER TABLE users ADD COLUMN last_name VARCHAR(100);
    END IF;
END $$;

-- Step 3: Migrate data from full_name to new columns
-- This splits full_name into parts (handles 2 or 3+ part names)
UPDATE users
SET 
    first_name = CASE 
        WHEN full_name IS NOT NULL THEN 
            SPLIT_PART(full_name, ' ', 1)
        ELSE first_name
    END,
    middle_name = CASE 
        WHEN full_name IS NOT NULL AND ARRAY_LENGTH(STRING_TO_ARRAY(full_name, ' '), 1) > 2 THEN
            SPLIT_PART(full_name, ' ', 2)
        ELSE middle_name
    END,
    last_name = CASE 
        WHEN full_name IS NOT NULL AND ARRAY_LENGTH(STRING_TO_ARRAY(full_name, ' '), 1) = 2 THEN
            SPLIT_PART(full_name, ' ', 2)
        WHEN full_name IS NOT NULL AND ARRAY_LENGTH(STRING_TO_ARRAY(full_name, ' '), 1) > 2 THEN
            SPLIT_PART(full_name, ' ', 3)
        ELSE last_name
    END
WHERE full_name IS NOT NULL 
  AND (first_name IS NULL OR last_name IS NULL);

-- Step 4: Set defaults for NULL values
UPDATE users 
SET first_name = 'User', last_name = 'Name' 
WHERE first_name IS NULL OR last_name IS NULL;

-- Step 5: Make the new columns NOT NULL (after ensuring all data is migrated)
ALTER TABLE users ALTER COLUMN first_name SET NOT NULL;
ALTER TABLE users ALTER COLUMN last_name SET NOT NULL;

-- Step 6: Drop the old full_name column (OPTIONAL - uncomment to remove)
-- ALTER TABLE users DROP COLUMN IF EXISTS full_name;

-- Step 7: Drop the gender column if it exists (since new schema doesn't use it)
ALTER TABLE users DROP COLUMN IF EXISTS gender;

-- Step 8: DROP password_hash column (SECURITY IMPROVEMENT)
-- WARNING: After running this, you MUST use Supabase Auth for authentication
-- Existing users will need to reset their passwords via Supabase Auth
ALTER TABLE users DROP COLUMN IF EXISTS password_hash;
ALTER TABLE users DROP COLUMN IF EXISTS password; -- in case old column name exists

-- Step 9: Create index on auth_id
CREATE INDEX IF NOT EXISTS idx_users_auth_id ON users(auth_id);

-- Step 10: Add trigger for new Supabase Auth users (if not exists)
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
    )
    ON CONFLICT (auth_id) DO NOTHING;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
CREATE TRIGGER on_auth_user_created
    AFTER INSERT ON auth.users
    FOR EACH ROW
    EXECUTE FUNCTION handle_new_user();

-- Verification query - Run this to check the migration
SELECT 
    id, 
    auth_id,
    email, 
    first_name, 
    middle_name, 
    last_name, 
    role, 
    status,
    created_at
FROM users
ORDER BY id;

-- IMPORTANT POST-MIGRATION STEPS:
-- 1. All existing users need to sign up again via Supabase Auth
-- 2. Or import users into Supabase Auth and update auth_id column
-- 3. Create admin user in Supabase Auth Dashboard
-- 4. Run: UPDATE users SET role = 'admin' WHERE email = 'admin@gamelend.com';

COMMENT ON COLUMN users.auth_id IS 'Links to Supabase Auth user (auth.users.id)';
COMMENT ON COLUMN users.first_name IS 'User''s first name (required)';
COMMENT ON COLUMN users.middle_name IS 'User''s middle name (optional)';
COMMENT ON COLUMN users.last_name IS 'User''s last name (required)';
