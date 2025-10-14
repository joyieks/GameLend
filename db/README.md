# GameLend Database Setup Guide

## Overview
This guide will help you set up the PostgreSQL database for GameLend with Row Level Security (RLS) policies on Supabase.

## Prerequisites
- Active Supabase project
- Database connection working (test by accessing http://localhost/GameLend/)

## Setup Steps

### Step 1: Access Supabase SQL Editor
1. Go to your Supabase dashboard: https://supabase.com/dashboard
2. Select your project
3. Click on **SQL Editor** in the left sidebar
4. Click **New Query**

### Step 2A: Fresh Installation (New Database)
If you're setting up a brand new database:

1. Open the file `db/schema.sql` in your code editor
2. Copy the **entire contents** of the file
3. Paste it into the Supabase SQL Editor
4. Click **Run** (or press Ctrl+Enter)

### Step 2B: Migrating Existing Database
If you already have a database with `full_name` and need to migrate to `first_name`, `middle_name`, `last_name`:

1. Open the file `db/migration_update_user_names.sql` in your code editor
2. Copy the **entire contents** of the file
3. Paste it into the Supabase SQL Editor
4. Click **Run** (or press Ctrl+Enter)

This migration will:
- ✅ Add new name columns (first_name, middle_name, last_name)
- ✅ Split existing full_name data into separate fields
- ✅ Handle 2-part names (First Last) and 3+ part names (First Middle Last)
- ✅ Remove the old `full_name` column (optional)
- ✅ Remove `gender` column (no longer used)

The script will create:
- ✅ Users table with roles (admin/customer)
- ✅ Games table with platform and quantity tracking
- ✅ Borrow transactions table
- ✅ Row Level Security (RLS) policies
- ✅ Triggers for automatic game availability updates
- ✅ Helper views for reporting
- ✅ Default admin user

### Step 3: Verify Installation
After running the script, verify the tables were created:

```sql
-- Check tables
SELECT table_name FROM information_schema.tables 
WHERE table_schema = 'public' 
ORDER BY table_name;

-- Check RLS is enabled
SELECT tablename, rowsecurity 
FROM pg_tables 
WHERE schemaname = 'public';
```

You should see:
- `users` (rowsecurity: true)
- `games` (rowsecurity: true)
- `borrow_transactions` (rowsecurity: true)

### Step 4: Test Default Admin Login
A default admin user is created with these credentials:

- **Email**: `admin@gamelend.com`
- **Password**: `password`

**⚠️ IMPORTANT**: Change this password immediately after first login!

## Row Level Security (RLS) Explained

### What is RLS?
Row Level Security allows you to control which rows users can see and modify based on their identity and role.

### RLS Policies in GameLend

#### Users Table
- ✅ Admins can view/edit all users
- ✅ Customers can only view/edit their own profile
- ✅ Customers cannot change their role

#### Games Table
- ✅ Everyone can view all games (public catalog)
- ✅ Only admins can add/edit/delete games

#### Borrow Transactions Table
- ✅ Customers can only view their own transactions
- ✅ Customers can only create transactions for themselves
- ✅ Admins can view/manage all transactions

### How RLS Works in Your App

The `db/setup_rls_context.php` file provides functions to set the user context:

```php
// After user logs in, set their context
setRLSContext($pdo, $userId);

// This tells PostgreSQL which user is making the query
// RLS policies then automatically filter the results
```

The `db/db_connect.php` file automatically initializes the RLS context when a user is logged in.

## Database Schema Details

### Users Table
```
- id (SERIAL PRIMARY KEY)
- email (VARCHAR UNIQUE)
- password_hash (VARCHAR)
- first_name (VARCHAR) *new*
- middle_name (VARCHAR, optional) *new*
- last_name (VARCHAR) *new*
- phone (VARCHAR)
- role (admin | customer)
- status (active | suspended | inactive)
- created_at, updated_at
```

### Games Table
```
- id (SERIAL PRIMARY KEY)
- title (VARCHAR)
- platform (VARCHAR)
- total_quantity (INTEGER)
- available_quantity (INTEGER)
- status (available | borrowed | maintenance)
- created_at, updated_at
```

### Borrow Transactions Table
```
- id (SERIAL PRIMARY KEY)
- user_id (FOREIGN KEY -> users)
- game_id (FOREIGN KEY -> games)
- borrow_date (TIMESTAMP)
- due_date (TIMESTAMP)
- return_date (TIMESTAMP)
- status (borrowed | returned | overdue)
- late_fee (DECIMAL)
- notes (TEXT)
- created_at, updated_at
```

## Automatic Features

### Triggers
1. **Game Availability Update**: Automatically updates `available_quantity` when games are borrowed/returned
2. **Auto-Update Timestamps**: Automatically updates `updated_at` on record changes

### Helper Functions
- `is_game_available(game_id)`: Check if a game is available for borrowing
- `calculate_late_fee(due_date)`: Calculate late fee ($2/day)

### Views
- `v_active_borrows`: Currently borrowed games with overdue status
- `v_game_statistics`: Statistical summary of game activity

## Testing the Setup

### 1. Add a Test Game (as admin)
```sql
INSERT INTO games (title, platform, total_quantity, available_quantity, status)
VALUES ('The Legend of Zelda', 'Nintendo Switch', 3, 3, 'available');
```

### 2. Create a Test Customer
```sql
INSERT INTO users (email, password_hash, full_name, role)
VALUES ('customer@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test Customer', 'customer');
```

### 3. Test RLS Context
```sql
-- Set context as customer (user_id = 2)
SET LOCAL app.user_id = '2';

-- This should only show the customer's own record
SELECT * FROM users;

-- This should show all games (public)
SELECT * FROM games;
```

## Troubleshooting

### Issue: "permission denied for table"
**Solution**: Make sure RLS policies are created and the user context is set correctly.

### Issue: "function current_setting does not exist"
**Solution**: The RLS context wasn't set. Call `setRLSContext($pdo, $userId)` after login.

### Issue: Queries return no results
**Solution**: 
1. Check if RLS context is set: `SHOW app.user_id;`
2. Verify the user_id is correct
3. Check if the user has the right role

### Issue: Cannot insert/update records
**Solution**: 
1. Verify user role matches the policy requirements
2. Check if the user status is 'active'
3. For customers, ensure they're not trying to modify other users' data

## Security Best Practices

1. **Change Default Password**: Immediately change the admin@gamelend.com password
2. **Use Environment Variables**: Store database credentials in environment variables
3. **Enable HTTPS**: Always use HTTPS in production
4. **Regular Backups**: Set up automatic backups in Supabase
5. **Monitor Logs**: Check Supabase logs regularly for suspicious activity
6. **Update Dependencies**: Keep all packages and libraries up to date

## Additional SQL Queries

### View All Overdue Books
```sql
SELECT * FROM v_active_borrows 
WHERE borrow_status = 'overdue'
ORDER BY days_overdue DESC;
```

### View Most Borrowed Games
```sql
SELECT title, platform, total_borrows 
FROM v_game_statistics 
ORDER BY total_borrows DESC 
LIMIT 10;
```

### Calculate Total Late Fees
```sql
SELECT SUM(late_fee) as total_fees 
FROM borrow_transactions 
WHERE status = 'returned' AND late_fee > 0;
```

## Support

If you encounter any issues:
1. Check the Supabase logs in your dashboard
2. Review the PHP error logs
3. Verify database connection settings
4. Ensure PostgreSQL PDO extension is enabled in PHP

## Next Steps

After setting up the database:
1. ✅ Test the admin login
2. ✅ Create some test games
3. ✅ Create a customer account
4. ✅ Test borrowing and returning games
5. ✅ Verify RLS policies are working correctly
