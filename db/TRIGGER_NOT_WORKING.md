# 🔴 URGENT: Trigger Not Saving Users - Complete Fix

## The Problem
Users are registering successfully in Supabase Auth, but their information is NOT being saved to the `public.users` table. This means:
- ❌ Users can't login to your app
- ❌ Dashboard can't load user data
- ❌ RLS policies don't work

## Why It's Happening
The database trigger `handle_new_user()` is either:
1. **Not executing** (trigger doesn't exist)
2. **Failing silently** (RLS policies blocking it)
3. **Has errors** (function code has bugs)

---

## ✅ COMPLETE FIX (Follow These Steps)

### Step 1: Run Diagnostic (Find the Problem)

1. Open Supabase Dashboard
2. Go to **SQL Editor**
3. Copy and paste from: `db/diagnose_trigger.sql`
4. Run **STEP 6** to see if users are missing:

```sql
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
```

**If you see ❌ MISSING** → Trigger is not working!

---

### Step 2: Apply the Complete Fix

1. Still in **SQL Editor**
2. **Copy ALL of** `db/fix_trigger.sql` (the UPDATED version)
3. Paste and click **RUN**

**What this does:**
- ✅ Drops old broken trigger
- ✅ Disables RLS temporarily
- ✅ Creates new improved function with logging
- ✅ Grants all necessary permissions
- ✅ Re-enables RLS with service_role policy
- ✅ Adds detailed error logging

---

### Step 3: Test Registration

1. **Clear browser cache** (or use Incognito mode)
2. Go to: `http://localhost/GameLend/register.php`
3. Register with a **BRAND NEW email** (never used before)
4. Fill in all fields:
   - First Name: TestUser
   - Middle Name: M
   - Last Name: Smith
   - Phone: +1234567890
   - Email: testuser123@gmail.com
   - Password: test123456

5. Click **Register**

---

### Step 4: Verify It Worked

Run this SQL in Supabase:

```sql
-- Check if user appears in BOTH tables
SELECT 
    au.id as auth_id,
    au.email as auth_email,
    au.created_at as auth_created,
    u.id as user_id,
    u.email as user_email,
    u.first_name,
    u.middle_name,
    u.last_name,
    u.phone,
    u.role,
    u.created_at as user_created
FROM auth.users au
LEFT JOIN public.users u ON au.id = u.auth_id
WHERE au.email = 'testuser123@gmail.com';
```

**Expected Result:**
```
auth_id | auth_email           | user_id | first_name | last_name | role     |
--------|---------------------|---------|------------|-----------|----------|
123abc  | testuser123@gmail.com| 5       | TestUser   | Smith     | customer |
```

✅ **If you see data in BOTH columns** → IT WORKS!
❌ **If user_id is NULL** → Trigger still not working

---

### Step 5: Check Logs for Errors

If still not working, check the logs:

1. Go to Supabase Dashboard → **Logs** → **Postgres Logs**
2. Look for messages like:
   ```
   LOG: handle_new_user triggered for email: testuser123@gmail.com
   LOG: Extracted metadata - first_name: TestUser, last_name: Smith...
   LOG: Successfully inserted/updated user...
   ```
   **OR**
   ```
   ERROR: relation "public.users" does not exist
   ERROR: permission denied for table users
   WARNING: Failed to create user record...
   ```

---

## 🔧 Alternative: Manual Backfill

If the trigger still doesn't work, manually create users for existing auth records:

```sql
-- Backfill: Create public.users for existing auth.users
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
SELECT 
    au.id as auth_id,
    au.email,
    COALESCE(au.raw_user_meta_data->>'first_name', 'User') as first_name,
    au.raw_user_meta_data->>'middle_name' as middle_name,
    COALESCE(au.raw_user_meta_data->>'last_name', 'User') as last_name,
    au.raw_user_meta_data->>'phone' as phone,
    COALESCE(au.raw_user_meta_data->>'role', 'customer') as role,
    'active' as status,
    au.created_at,
    NOW() as updated_at
FROM auth.users au
WHERE NOT EXISTS (
    SELECT 1 FROM public.users u WHERE u.auth_id = au.id
)
ON CONFLICT (auth_id) DO NOTHING;
```

This creates users for everyone who registered but doesn't have a record in `public.users`.

---

## 📊 Common Issues and Solutions

### Issue 1: RLS Policies Blocking Trigger
**Symptom:** Trigger runs but doesn't insert anything

**Fix:** The updated `fix_trigger.sql` already handles this by:
- Using `SECURITY DEFINER`
- Adding service_role policy
- Temporarily disabling RLS during setup

### Issue 2: Missing Permissions
**Symptom:** "permission denied for table users"

**Fix:** Run these grants:
```sql
GRANT ALL ON public.users TO service_role;
GRANT USAGE, SELECT ON SEQUENCE public.users_id_seq TO service_role;
```

### Issue 3: Function Not Found
**Symptom:** "function handle_new_user() does not exist"

**Fix:** Make sure you're creating the function in the `public` schema:
```sql
CREATE OR REPLACE FUNCTION public.handle_new_user()
```

---

## ✅ Success Checklist

After running the fix, verify:

- [ ] Trigger exists in Supabase
- [ ] Function `public.handle_new_user()` exists
- [ ] Service_role has permissions on `users` table
- [ ] New registration creates record in `public.users`
- [ ] User can login after email confirmation
- [ ] Dashboard shows user first_name and last_name

---

## 🚨 If STILL Not Working

If after ALL these steps it still doesn't work:

### Last Resort: Use PHP Instead of Trigger

Update your `auth.php` or `login.php` to create users on first login:

```php
<?php
// After Supabase Auth login success:
$auth_user_id = $supabase_user['id'];
$email = $supabase_user['email'];
$metadata = $supabase_user['user_metadata'];

// Check if user exists in public.users
$stmt = $pdo->prepare("SELECT id FROM users WHERE auth_id = ?");
$stmt->execute([$auth_user_id]);

if (!$stmt->fetch()) {
    // Create user in database
    $stmt = $pdo->prepare("
        INSERT INTO users (auth_id, email, first_name, middle_name, last_name, phone, role, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 'customer', 'active', NOW(), NOW())
    ");
    $stmt->execute([
        $auth_user_id,
        $email,
        $metadata['first_name'] ?? 'User',
        $metadata['middle_name'] ?? null,
        $metadata['last_name'] ?? '',
        $metadata['phone'] ?? null
    ]);
}
?>
```

This way, users are created on first login instead of during registration.

---

## 📞 Need More Help?

Provide these details:
1. Result of Step 4 (verification query)
2. Any error messages from Postgres Logs
3. Result of `diagnose_trigger.sql` queries
4. Screenshot of SQL Editor after running `fix_trigger.sql`

