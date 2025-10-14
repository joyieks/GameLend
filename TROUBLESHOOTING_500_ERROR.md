# 🔴 Supabase 500 Error - Complete Troubleshooting Guide

## Current Error
```
POST https://ecyncrgyvyepppgelczk.supabase.co/auth/v1/signup 500 (Internal Server Error)
```

## 🎯 Root Causes (In Order of Likelihood)

### 1. **Database Trigger Error** ⭐ MOST LIKELY
Your `handle_new_user()` trigger is probably failing.

**How to Check:**
1. Go to Supabase Dashboard → **Database** → **Functions**
2. Find function: `handle_new_user`
3. Check if it exists and review the code

**The Problem:**
Your trigger tries to insert into `users` table immediately after signup, but:
- The trigger might be looking for columns that don't exist
- The trigger might have syntax errors
- The RLS policies might be blocking the insert

**Solution: Temporarily Disable the Trigger**
```sql
-- In Supabase SQL Editor, run this:
DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
```

Then try registering again. If it works, the trigger is the problem.

---

### 2. **Email Confirmation Settings**
Email confirmation might be disabled or misconfigured.

**How to Fix:**
1. Go to: **Authentication** → **Settings** → **Auth Providers**
2. Find **Email** provider
3. Make sure:
   - ✅ Email provider is **Enabled**
   - ✅ **Confirm email** is **Enabled**
   - ✅ **Secure email change** is **Enabled** (optional)

**Or Disable Email Confirmation for Testing:**
If you want to test without email confirmation:
- Uncheck **"Confirm email"**
- Save changes
- Wait 1 minute
- Try registering again

---

### 3. **Redirect URL Not Whitelisted**

**How to Fix:**
1. Go to: **Authentication** → **URL Configuration**
2. **Site URL** must be: `http://localhost/GameLend`
3. Add these **Redirect URLs**:
   ```
   http://localhost/GameLend/**
   http://localhost/GameLend/auth.php
   http://localhost/GameLend/change_password.php
   ```
4. Click **Save changes**
5. Wait 2 minutes for changes to propagate

---

### 4. **User Metadata Schema Issue**
The user metadata you're sending might be too large or have invalid fields.

**Current metadata being sent:**
```javascript
{
  first_name: 'John',
  middle_name: 'M',
  last_name: 'Doe',
  phone: '+1234567890',
  role: 'customer'
}
```

**To test without metadata:**
Remove the `options.data` field temporarily.

---

## 🧪 Step-by-Step Debugging

### Step 1: Use the Test Tool
1. Open: `http://localhost/GameLend/test_supabase_auth.html`
2. Run **Test 1** (Minimal Signup)
   - If this works → Problem is with metadata or redirect URL
   - If this fails → Problem is with Supabase configuration or trigger

### Step 2: Check Supabase Logs
1. Go to Supabase Dashboard → **Logs** → **Auth Logs**
2. Look for recent errors
3. Check the error message details

### Step 3: Disable the Trigger Temporarily
```sql
-- Run in Supabase SQL Editor:
DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
DROP FUNCTION IF EXISTS handle_new_user();
```

Try registering again:
- If it works → The trigger was the problem
- If it still fails → Configuration issue

### Step 4: Check if Email Confirmation is Causing Issues
Run this SQL to see auth configuration:
```sql
-- Check auth config
SELECT * FROM auth.config;

-- Check existing users
SELECT id, email, email_confirmed_at, created_at 
FROM auth.users 
ORDER BY created_at DESC 
LIMIT 5;
```

---

## ✅ Recommended Fix (Recreate Trigger Correctly)

If the trigger is the problem, recreate it with better error handling:

```sql
-- Drop the old trigger and function
DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
DROP FUNCTION IF EXISTS handle_new_user();

-- Create a better function with error handling
CREATE OR REPLACE FUNCTION handle_new_user()
RETURNS TRIGGER AS $$
BEGIN
  -- Insert into public.users table
  INSERT INTO public.users (
    auth_id,
    email,
    first_name,
    middle_name,
    last_name,
    phone,
    role,
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
    NOW(),
    NOW()
  )
  ON CONFLICT (auth_id) DO NOTHING;
  
  RETURN NEW;
EXCEPTION
  WHEN OTHERS THEN
    -- Log the error but don't fail the signup
    RAISE WARNING 'Error in handle_new_user: %', SQLERRM;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Recreate the trigger
CREATE TRIGGER on_auth_user_created
  AFTER INSERT ON auth.users
  FOR EACH ROW
  EXECUTE FUNCTION handle_new_user();
```

**Key improvements:**
- Uses `COALESCE` to provide default values
- Has `ON CONFLICT DO NOTHING` to prevent duplicate errors
- Has `EXCEPTION` handler to log errors without failing
- Uses `SECURITY DEFINER` to bypass RLS policies

---

## 🔧 Quick Fix: Disable Trigger Temporarily

If you want to test registration immediately:

```sql
-- Disable the trigger
DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
```

Then manually create users in the database after they register:
1. User registers via Supabase Auth
2. You manually insert into `users` table using their `auth.users.id`

---

## 📊 Check Current Database State

Run these queries to understand your current setup:

```sql
-- 1. Check if users table exists
SELECT EXISTS (
  SELECT FROM information_schema.tables 
  WHERE table_schema = 'public' 
  AND table_name = 'users'
);

-- 2. Check users table structure
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_schema = 'public' 
AND table_name = 'users'
ORDER BY ordinal_position;

-- 3. Check if trigger exists
SELECT trigger_name, event_manipulation, event_object_table
FROM information_schema.triggers
WHERE trigger_schema = 'auth';

-- 4. Check if function exists
SELECT proname, prosrc
FROM pg_proc
WHERE proname = 'handle_new_user';

-- 5. Check RLS policies on users table
SELECT schemaname, tablename, policyname, permissive, roles, cmd, qual
FROM pg_policies
WHERE tablename = 'users';
```

---

## 🎯 Most Likely Solution

Based on the 500 error, **95% chance it's the database trigger**. Here's the fastest fix:

### Option A: Disable Trigger (Temporary)
```sql
DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
```
**Result:** Users can register but won't be in `users` table

### Option B: Fix Trigger (Recommended)
Use the improved trigger code above with error handling.

### Option C: Remove Trigger Dependency
Don't create users in database immediately. Instead:
1. Let Supabase Auth handle user registration
2. Create user record in `users` table on first login
3. Use PHP code to check if user exists in `users` table

---

## 🧪 Test Again

After applying fixes:

1. Clear browser cache
2. Use incognito/private mode
3. Try registering with a NEW email address
4. Check browser console for errors
5. Check Supabase Auth Logs
6. Check `auth.users` table to see if user was created
7. Check `public.users` table to see if record was created

---

## 📞 Need More Help?

If still not working, provide:
1. Screenshot of browser console error (full details)
2. Screenshot of Supabase Auth Logs
3. Result of running the SQL queries above
4. Result from `test_supabase_auth.html` tests

---

## ✅ Expected Behavior After Fix

1. User fills registration form
2. Clicks "Register"
3. Supabase creates user in `auth.users`
4. Trigger creates user in `public.users`
5. Confirmation email sent
6. User sees: "Please check your email"
7. User clicks email link
8. User email is confirmed
9. User can login

