# 🔧 Trigger Fix Guide

## The Problem
Your database trigger `handle_new_user()` is failing, causing 500 errors during registration.

## Why You Need the Trigger

**Without the trigger:**
- ❌ Users register in Supabase Auth (`auth.users`) ✅
- ❌ BUT no record in your app's `users` table (`public.users`) ❌
- ❌ Login fails (PHP can't find user data)
- ❌ Dashboard errors (no first_name, last_name, etc.)
- ❌ RLS policies don't work

**With the trigger:**
- ✅ User registers in Supabase Auth
- ✅ Trigger automatically creates record in `public.users`
- ✅ Login works
- ✅ Dashboard displays user info
- ✅ RLS policies work correctly

---

## ✅ Solution 1: Fix the Trigger (RECOMMENDED)

### Step 1: Run the Fix Script

1. Open Supabase Dashboard
2. Go to **SQL Editor**
3. Copy and paste contents from: `db/fix_trigger.sql`
4. Click **Run**

### Step 2: Test Registration

1. Go to: `http://localhost/GameLend/register.php`
2. Register with a NEW email (one you haven't used before)
3. Should work now! ✅

### What the Fixed Trigger Does Better:

✅ **Better error handling** - Won't fail if metadata is missing
✅ **Default values** - Provides defaults for required fields
✅ **ON CONFLICT** - Prevents duplicate errors
✅ **EXCEPTION handler** - Logs errors without breaking signup
✅ **SECURITY DEFINER** - Bypasses RLS policies during insert

---

## 🔧 Solution 2: Alternative Approach (If Trigger Still Fails)

If the trigger still doesn't work, use **PHP to create users on first login** instead:

### Update your login.php:

```php
<?php
require_once 'includes/session_config.php';
require_once 'db/db_connect.php';

// After successful Supabase Auth login:
// Check if user exists in public.users table
$stmt = $pdo->prepare("SELECT * FROM users WHERE auth_id = ?");
$stmt->execute([$supabase_user_id]);
$user = $stmt->fetch();

if (!$user) {
    // User doesn't exist in public.users, create them now
    $stmt = $pdo->prepare("
        INSERT INTO users (auth_id, email, first_name, last_name, role, created_at, updated_at)
        VALUES (?, ?, ?, ?, 'customer', NOW(), NOW())
    ");
    $stmt->execute([
        $supabase_user_id,
        $supabase_user_email,
        $supabase_user_metadata['first_name'] ?? 'User',
        $supabase_user_metadata['last_name'] ?? ''
    ]);
}
```

**Pros:**
- ✅ No trigger dependency
- ✅ More control in PHP
- ✅ Can handle errors better

**Cons:**
- ❌ User record only created on first login (not on signup)
- ❌ More code in PHP
- ❌ Slight delay on first login

---

## 🧪 Testing After Fix

### Test 1: New Registration
1. Clear browser cache
2. Go to register page
3. Use a **completely new email**
4. Fill in: First Name, Last Name, Email, Password
5. Click Register
6. Should see: "Please check your email"
7. ✅ SUCCESS!

### Test 2: Verify Database
Run this SQL in Supabase:

```sql
-- Check if user was created in auth.users
SELECT id, email, email_confirmed_at, created_at
FROM auth.users
ORDER BY created_at DESC
LIMIT 1;

-- Check if user was created in public.users (by trigger)
SELECT u.id, u.auth_id, u.email, u.first_name, u.last_name, u.role, u.created_at
FROM public.users u
ORDER BY u.created_at DESC
LIMIT 1;

-- Verify they match
SELECT 
  au.id as auth_id,
  au.email as auth_email,
  u.auth_id as user_auth_id,
  u.email as user_email,
  u.first_name,
  u.last_name
FROM auth.users au
LEFT JOIN public.users u ON au.id = u.auth_id
ORDER BY au.created_at DESC
LIMIT 3;
```

**Expected result:**
- ✅ User exists in both `auth.users` AND `public.users`
- ✅ `public.users.auth_id` matches `auth.users.id`
- ✅ Email matches in both tables

---

## 📋 Common Trigger Errors and Fixes

### Error 1: Column doesn't exist
**Symptom:** Trigger fails with "column X does not exist"

**Fix:** Check your `users` table has all these columns:
```sql
SELECT column_name, data_type 
FROM information_schema.columns 
WHERE table_name = 'users';
```

Make sure you have: `auth_id`, `email`, `first_name`, `middle_name`, `last_name`, `phone`, `role`, `status`, `created_at`, `updated_at`

### Error 2: RLS policy blocking insert
**Symptom:** Trigger fails silently, no user created

**Fix:** The trigger function needs `SECURITY DEFINER` (already in fix script)

### Error 3: Unique constraint violation
**Symptom:** "duplicate key value violates unique constraint"

**Fix:** Add `ON CONFLICT DO NOTHING` or `ON CONFLICT DO UPDATE` (already in fix script)

---

## 🚀 For Production Deployment

When deploying to production:

1. ✅ **Run the fixed trigger script** in production database
2. ✅ **Test registration** with a real email
3. ✅ **Verify email confirmation** works
4. ✅ **Check logs** for any trigger errors
5. ✅ **Monitor** auth.users and public.users stay in sync

---

## 💡 Pro Tip: Backfill Existing Users

If you already have users in `auth.users` but not in `public.users`:

```sql
-- Backfill: Create public.users records for existing auth.users
INSERT INTO public.users (auth_id, email, first_name, last_name, role, status, created_at, updated_at)
SELECT 
  au.id as auth_id,
  au.email,
  COALESCE(au.raw_user_meta_data->>'first_name', 'User') as first_name,
  COALESCE(au.raw_user_meta_data->>'last_name', '') as last_name,
  COALESCE(au.raw_user_meta_data->>'role', 'customer') as role,
  'active' as status,
  au.created_at,
  NOW() as updated_at
FROM auth.users au
WHERE NOT EXISTS (
  SELECT 1 FROM public.users u WHERE u.auth_id = au.id
);
```

---

## ✅ Summary

1. **Run** `db/fix_trigger.sql` in Supabase SQL Editor
2. **Test** registration with a new email
3. **Verify** user appears in both `auth.users` and `public.users`
4. **Deploy** the fixed trigger to production

The trigger is **essential** for your app to work correctly. Don't deploy without it! 🚀
