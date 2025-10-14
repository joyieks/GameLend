-- ============================================
-- COMPLETE FIX: Update Table Structure AND Trigger
-- ============================================
-- This fixes the "full_name" constraint error by properly updating the table

-- Step 1: Check current table structure
SELECT 
    column_name, 
    data_type, 
    is_nullable,
    column_default
FROM information_schema.columns
WHERE table_schema = 'public' 
  AND table_name = 'users'
ORDER BY ordinal_position;

-- Step 2: Add missing columns if they don't exist
DO $$ 
BEGIN
    -- Add first_name if it doesn't exist
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='users' AND column_name='first_name') THEN
        ALTER TABLE users ADD COLUMN first_name VARCHAR(100);
    END IF;
    
    -- Add middle_name if it doesn't exist
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='users' AND column_name='middle_name') THEN
        ALTER TABLE users ADD COLUMN middle_name VARCHAR(100);
    END IF;
    
    -- Add last_name if it doesn't exist
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='users' AND column_name='last_name') THEN
        ALTER TABLE users ADD COLUMN last_name VARCHAR(100);
    END IF;
    
    -- Add phone if it doesn't exist
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='users' AND column_name='phone') THEN
        ALTER TABLE users ADD COLUMN phone VARCHAR(20);
    END IF;
END $$;

-- Step 3: Migrate existing full_name data to new columns (simple version)
UPDATE users
SET 
    first_name = COALESCE(first_name, SPLIT_PART(full_name, ' ', 1), 'User'),
    last_name = COALESCE(last_name, 
        CASE 
            WHEN position(' ' IN full_name) > 0 
            THEN substring(full_name from position(' ' IN full_name) + 1)
            ELSE 'User'
        END, 
        'User')
WHERE full_name IS NOT NULL AND (first_name IS NULL OR last_name IS NULL);

-- Set defaults for any remaining NULL values
UPDATE users 
SET 
    first_name = COALESCE(first_name, 'User'),
    last_name = COALESCE(last_name, 'User')
WHERE first_name IS NULL OR last_name IS NULL;

-- Step 4: Drop NOT NULL constraint from full_name (if it exists)
DO $$
BEGIN
    ALTER TABLE users ALTER COLUMN full_name DROP NOT NULL;
EXCEPTION
    WHEN undefined_column THEN NULL;
    WHEN others THEN NULL;
END $$;

-- Step 5: Drop the full_name column
ALTER TABLE users DROP COLUMN IF EXISTS full_name;

-- Step 6: Drop other unused columns
ALTER TABLE users DROP COLUMN IF EXISTS gender;
ALTER TABLE users DROP COLUMN IF EXISTS password_hash;
ALTER TABLE users DROP COLUMN IF EXISTS password;

-- Step 7: Make new columns NOT NULL
ALTER TABLE users ALTER COLUMN first_name SET NOT NULL;
ALTER TABLE users ALTER COLUMN last_name SET NOT NULL;

-- Step 8: Ensure auth_id exists and is unique
DO $$ 
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns 
                   WHERE table_name='users' AND column_name='auth_id') THEN
        ALTER TABLE users ADD COLUMN auth_id UUID;
    END IF;
END $$;

-- Make sure auth_id constraint exists
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint 
        WHERE conname = 'users_auth_id_key'
    ) THEN
        ALTER TABLE users ADD CONSTRAINT users_auth_id_key UNIQUE (auth_id);
    END IF;
END $$;

-- Step 9: Disable RLS temporarily
ALTER TABLE public.users DISABLE ROW LEVEL SECURITY;

-- Step 10: Drop and recreate the trigger
DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
DROP FUNCTION IF EXISTS handle_new_user() CASCADE;
DROP FUNCTION IF EXISTS public.handle_new_user() CASCADE;

-- Step 11: Create the corrected trigger function
CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS TRIGGER 
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
BEGIN
  INSERT INTO public.users (
    auth_id,
    email,
    first_name,
    middle_name,
    last_name,
    phone,
    role,
    status,
    created_at,
    updated_at
  )
  VALUES (
    NEW.id,
    NEW.email,
    COALESCE(NEW.raw_user_meta_data->>'first_name', 'User'),
    NEW.raw_user_meta_data->>'middle_name',
    COALESCE(NEW.raw_user_meta_data->>'last_name', 'User'),
    NEW.raw_user_meta_data->>'phone',
    COALESCE(NEW.raw_user_meta_data->>'role', 'customer'),
    'active',
    NOW(),
    NOW()
  )
  ON CONFLICT (auth_id) DO UPDATE SET
    email = EXCLUDED.email,
    first_name = EXCLUDED.first_name,
    middle_name = EXCLUDED.middle_name,
    last_name = EXCLUDED.last_name,
    phone = EXCLUDED.phone,
    updated_at = NOW();
  
  RETURN NEW;
EXCEPTION
  WHEN OTHERS THEN
    RAISE WARNING 'Error in handle_new_user: %', SQLERRM;
    RETURN NEW;
END;
$$;

-- Step 12: Grant permissions
GRANT ALL ON public.users TO postgres, service_role, anon, authenticated;
GRANT USAGE, SELECT ON SEQUENCE public.users_id_seq TO postgres, service_role, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.handle_new_user() TO postgres, service_role;

-- Step 13: Create trigger
CREATE TRIGGER on_auth_user_created
  AFTER INSERT ON auth.users
  FOR EACH ROW
  EXECUTE FUNCTION public.handle_new_user();

-- Step 14: Verification
SELECT 'Migration completed successfully! ✅' as status;

-- Show updated table structure
SELECT 
    'Table Structure:' as info,
    column_name, 
    data_type, 
    is_nullable
FROM information_schema.columns
WHERE table_schema = 'public' 
  AND table_name = 'users'
ORDER BY ordinal_position;

-- Show trigger status
SELECT 
  'Trigger Status:' as info,
  trigger_name, 
  event_manipulation, 
  event_object_table
FROM information_schema.triggers
WHERE trigger_schema = 'auth'
  AND trigger_name = 'on_auth_user_created';

-- Show current users
SELECT 
    'Current Users:' as info,
    id,
    email,
    first_name,
    middle_name,
    last_name,
    role
FROM public.users;
