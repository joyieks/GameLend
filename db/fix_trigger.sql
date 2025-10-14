-- ============================================
-- Fix for Supabase Auth Trigger - IMPROVED VERSION
-- ============================================
-- This script recreates the trigger with proper error handling
-- Run this in Supabase SQL Editor

-- Step 1: Drop the old broken trigger and function
DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
DROP FUNCTION IF EXISTS handle_new_user() CASCADE;

-- Step 2: Temporarily disable RLS on users table for trigger
ALTER TABLE public.users DISABLE ROW LEVEL SECURITY;

-- Step 3: Create improved function with better error handling
CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS TRIGGER 
SECURITY DEFINER
SET search_path = public
LANGUAGE plpgsql
AS $$
DECLARE
  v_first_name TEXT;
  v_middle_name TEXT;
  v_last_name TEXT;
  v_phone TEXT;
  v_role TEXT;
BEGIN
  -- Log the trigger execution for debugging
  RAISE LOG 'handle_new_user triggered for email: %', NEW.email;
  
  -- Extract metadata with safe defaults
  v_first_name := COALESCE(NEW.raw_user_meta_data->>'first_name', 'User');
  v_middle_name := NEW.raw_user_meta_data->>'middle_name';  -- Can be NULL
  v_last_name := COALESCE(NEW.raw_user_meta_data->>'last_name', '');
  v_phone := NEW.raw_user_meta_data->>'phone';  -- Can be NULL
  v_role := COALESCE(NEW.raw_user_meta_data->>'role', 'customer');

  RAISE LOG 'Extracted metadata - first_name: %, last_name: %, role: %', v_first_name, v_last_name, v_role;

  -- Insert into public.users table
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
    NEW.id,              -- auth_id links to auth.users
    NEW.email,           -- email from auth
    v_first_name,        -- from metadata
    v_middle_name,       -- from metadata (can be null)
    v_last_name,         -- from metadata
    v_phone,             -- from metadata (can be null)
    v_role,              -- from metadata, default 'customer'
    'active',            -- default status
    NOW(),
    NOW()
  )
  ON CONFLICT (auth_id) DO UPDATE SET
    email = EXCLUDED.email,
    first_name = EXCLUDED.first_name,
    middle_name = EXCLUDED.middle_name,
    last_name = EXCLUDED.last_name,
    phone = EXCLUDED.phone,
    role = EXCLUDED.role,
    updated_at = NOW();
  
  RAISE LOG 'Successfully inserted/updated user in public.users for: %', NEW.email;
  
  RETURN NEW;
  
EXCEPTION
  WHEN OTHERS THEN
    -- Log the error detail
    RAISE LOG 'Error in handle_new_user for user %: % (SQLSTATE: %)', NEW.email, SQLERRM, SQLSTATE;
    RAISE WARNING 'Failed to create user record for %: %', NEW.email, SQLERRM;
    -- Return NEW anyway to not block the auth signup
    RETURN NEW;
END;
$$;

-- Step 4: Grant execute permission to service_role
GRANT EXECUTE ON FUNCTION public.handle_new_user() TO service_role;
GRANT EXECUTE ON FUNCTION public.handle_new_user() TO postgres;

-- Step 5: Create the trigger
CREATE TRIGGER on_auth_user_created
  AFTER INSERT ON auth.users
  FOR EACH ROW
  EXECUTE FUNCTION public.handle_new_user();

-- Step 6: Grant necessary permissions on users table
GRANT ALL ON public.users TO postgres;
GRANT ALL ON public.users TO service_role;
GRANT USAGE, SELECT ON SEQUENCE public.users_id_seq TO service_role;
GRANT USAGE, SELECT ON SEQUENCE public.users_id_seq TO postgres;

-- Step 7: Re-enable RLS with permissive policy for service_role
ALTER TABLE public.users ENABLE ROW LEVEL SECURITY;

-- Create a permissive policy for service_role (used by triggers)
DROP POLICY IF EXISTS "Service role can do anything" ON public.users;
CREATE POLICY "Service role can do anything"
ON public.users
FOR ALL
TO service_role
USING (true)
WITH CHECK (true);

-- Step 8: Verify the trigger was created
SELECT 
  'Trigger recreated successfully!' as status,
  COUNT(*) as trigger_count
FROM information_schema.triggers
WHERE trigger_schema = 'auth'
  AND trigger_name = 'on_auth_user_created';

-- Step 9: Show the trigger details
SELECT 
  trigger_name, 
  event_manipulation, 
  event_object_table,
  action_timing,
  action_statement
FROM information_schema.triggers
WHERE trigger_schema = 'auth'
  AND trigger_name = 'on_auth_user_created';

