-- ============================================
-- Simple Diagnostic: Check Why Trigger Isn't Working
-- ============================================

-- 1. Check if the function actually exists and in what schema
SELECT 
    n.nspname as schema_name,
    p.proname as function_name,
    pg_get_functiondef(p.oid) as function_definition
FROM pg_proc p
JOIN pg_namespace n ON p.pronamespace = n.oid
WHERE p.proname = 'handle_new_user';

-- 2. Check trigger details
SELECT 
    t.tgname as trigger_name,
    c.relname as table_name,
    n.nspname as schema_name,
    p.proname as function_name,
    t.tgenabled as enabled
FROM pg_trigger t
JOIN pg_class c ON t.tgrelid = c.oid
JOIN pg_namespace n ON c.relnamespace = n.oid
JOIN pg_proc p ON t.tgfoid = p.oid
WHERE t.tgname = 'on_auth_user_created';

-- 3. Try to manually execute the function to see if it errors
-- This simulates what the trigger would do
DO $$
DECLARE
    test_rec RECORD;
BEGIN
    -- Get a recent auth user
    SELECT * INTO test_rec FROM auth.users ORDER BY created_at DESC LIMIT 1;
    
    IF test_rec IS NOT NULL THEN
        RAISE NOTICE 'Testing with user: %', test_rec.email;
        
        -- Try the insert manually
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
            test_rec.id,
            test_rec.email,
            COALESCE(test_rec.raw_user_meta_data->>'first_name', 'User'),
            test_rec.raw_user_meta_data->>'middle_name',
            COALESCE(test_rec.raw_user_meta_data->>'last_name', 'User'),
            test_rec.raw_user_meta_data->>'phone',
            COALESCE(test_rec.raw_user_meta_data->>'role', 'customer'),
            'active',
            NOW(),
            NOW()
        )
        ON CONFLICT (auth_id) DO UPDATE SET
            email = EXCLUDED.email,
            updated_at = NOW();
            
        RAISE NOTICE 'Manual insert succeeded!';
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        RAISE NOTICE 'ERROR: % (SQLSTATE: %)', SQLERRM, SQLSTATE;
END $$;

-- 4. Check if it worked
SELECT 
    'After manual test:' as info,
    au.email,
    u.first_name,
    u.last_name,
    CASE WHEN u.id IS NULL THEN '❌ MISSING' ELSE '✅ EXISTS' END as status
FROM auth.users au
LEFT JOIN public.users u ON au.id = u.auth_id
ORDER BY au.created_at DESC
LIMIT 3;

-- 5. Check table permissions
SELECT 
    grantee,
    table_schema,
    table_name,
    privilege_type
FROM information_schema.table_privileges
WHERE table_schema = 'public'
  AND table_name = 'users'
ORDER BY grantee, privilege_type;

-- 6. Check sequence permissions
SELECT 
    grantee,
    privilege_type
FROM information_schema.usage_privileges
WHERE object_schema = 'public'
  AND object_name = 'users_id_seq';
