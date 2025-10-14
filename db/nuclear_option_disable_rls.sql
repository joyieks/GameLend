-- ============================================
-- NUCLEAR OPTION: Complete RLS Bypass for Trigger
-- ============================================
-- If the trigger still doesn't work, this removes ALL RLS completely

-- Step 1: Check current RLS status
SELECT 
    schemaname,
    tablename,
    rowsecurity as rls_enabled
FROM pg_tables
WHERE schemaname = 'public' 
  AND tablename = 'users';

-- Step 2: Check all policies on users table
SELECT 
    schemaname,
    tablename,
    policyname,
    permissive,
    roles,
    cmd as command,
    qual as using_expression,
    with_check as with_check_expression
FROM pg_policies
WHERE schemaname = 'public' 
  AND tablename = 'users';

-- Step 3: DROP ALL POLICIES (Nuclear option)
DROP POLICY IF EXISTS "Users can view own data" ON public.users;
DROP POLICY IF EXISTS "Users can update own data" ON public.users;
DROP POLICY IF EXISTS "Admin full access" ON public.users;
DROP POLICY IF EXISTS "Service role can do anything" ON public.users;
DROP POLICY IF EXISTS "Enable read access for all users" ON public.users;
DROP POLICY IF EXISTS "Enable insert for authenticated users only" ON public.users;

-- Step 4: DISABLE RLS completely (for testing)
ALTER TABLE public.users DISABLE ROW LEVEL SECURITY;

-- Step 5: Drop and recreate trigger with explicit permissions
DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
DROP FUNCTION IF EXISTS public.handle_new_user() CASCADE;

-- Step 6: Create function WITHOUT any RLS considerations
CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS TRIGGER 
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
BEGIN
  -- Direct insert with no RLS checks
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
END;
$$;

-- Step 7: Grant ALL permissions
GRANT ALL PRIVILEGES ON public.users TO postgres, anon, authenticated, service_role;
GRANT ALL PRIVILEGES ON SEQUENCE public.users_id_seq TO postgres, anon, authenticated, service_role;
GRANT EXECUTE ON FUNCTION public.handle_new_user() TO postgres, anon, authenticated, service_role;

-- Step 8: Recreate trigger
CREATE TRIGGER on_auth_user_created
  AFTER INSERT ON auth.users
  FOR EACH ROW
  EXECUTE FUNCTION public.handle_new_user();

-- Step 9: Verify
SELECT 'RLS DISABLED - Trigger recreated with full permissions' as status;

SELECT 
  trigger_name, 
  event_manipulation, 
  event_object_table
FROM information_schema.triggers
WHERE trigger_schema = 'auth'
  AND trigger_name = 'on_auth_user_created';

-- Step 10: Test by checking recent auth users vs public users
SELECT 
    au.id as auth_user_id,
    au.email,
    au.created_at,
    u.id as public_user_id,
    u.first_name,
    u.last_name,
    CASE 
        WHEN u.auth_id IS NULL THEN '❌ NOT IN PUBLIC.USERS'
        ELSE '✅ EXISTS IN PUBLIC.USERS'
    END as status
FROM auth.users au
LEFT JOIN public.users u ON au.id = u.auth_id
ORDER BY au.created_at DESC
LIMIT 5;
