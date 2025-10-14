-- ============================================
-- DIAGNOSTIC: Check Why Trigger Isn't Working
-- ============================================
-- Run these queries ONE BY ONE to diagnose the issue

-- STEP 1: Check if users table exists and its structure
SELECT 
    column_name, 
    data_type, 
    is_nullable,
    column_default
FROM information_schema.columns
WHERE table_schema = 'public' 
  AND table_name = 'users'
ORDER BY ordinal_position;

-- Expected columns:
-- id, auth_id, email, first_name, middle_name, last_name, phone, role, status, created_at, updated_at


-- STEP 2: Check if the trigger exists
SELECT 
    trigger_name, 
    event_manipulation, 
    event_object_schema,
    event_object_table,
    action_statement
FROM information_schema.triggers
WHERE event_object_schema = 'auth'
  AND event_object_table = 'users';

-- Expected: on_auth_user_created trigger should exist


-- STEP 3: Check if the function exists
SELECT 
    proname as function_name,
    prosrc as function_code
FROM pg_proc
WHERE proname = 'handle_new_user';

-- Expected: handle_new_user function should exist


-- STEP 4: Check recent auth.users (people who registered)
SELECT 
    id,
    email,
    email_confirmed_at,
    raw_user_meta_data,
    created_at
FROM auth.users
ORDER BY created_at DESC
LIMIT 5;

-- This shows who registered recently


-- STEP 5: Check if they appear in public.users
SELECT 
    u.id,
    u.auth_id,
    u.email,
    u.first_name,
    u.middle_name,
    u.last_name,
    u.phone,
    u.role,
    u.created_at
FROM public.users u
ORDER BY u.created_at DESC
LIMIT 5;

-- Compare with auth.users - should match


-- STEP 6: Check for MISSING users (in auth but not in public)
SELECT 
    au.id as auth_user_id,
    au.email,
    au.created_at as registered_at,
    CASE 
        WHEN u.auth_id IS NULL THEN '❌ MISSING'
        ELSE '✅ EXISTS'
    END as status_in_public_users
FROM auth.users au
LEFT JOIN public.users u ON au.id = u.auth_id
ORDER BY au.created_at DESC
LIMIT 10;

-- If you see ❌ MISSING, the trigger didn't work for those users


-- STEP 7: Check RLS policies on users table
SELECT 
    schemaname,
    tablename,
    policyname,
    permissive,
    roles,
    cmd,
    qual
FROM pg_policies
WHERE schemaname = 'public'
  AND tablename = 'users';

-- RLS policies might be blocking the trigger insert


-- STEP 8: Check table permissions
SELECT 
    grantee,
    privilege_type
FROM information_schema.role_table_grants
WHERE table_schema = 'public'
  AND table_name = 'users';

-- Service_role should have INSERT permission


-- STEP 9: Test the trigger manually (won't actually insert auth user)
-- This tests if the function has syntax errors
DO $$
BEGIN
    RAISE NOTICE 'Testing trigger function...';
    PERFORM handle_new_user();
EXCEPTION
    WHEN OTHERS THEN
        RAISE NOTICE 'Error in trigger: %', SQLERRM;
END $$;


-- STEP 10: Check PostgreSQL logs for trigger errors
-- Note: You may need to check Supabase Dashboard > Logs > Database Logs
SELECT 'Check Supabase Dashboard > Logs > Database Logs for trigger errors' as next_step;
