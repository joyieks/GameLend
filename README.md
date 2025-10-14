# GameLend: Video Game Borrow & Return System

A complete PHP + Postgres (Supabase) web application for managing video game borrowing and returning operations.

## Features

### Customer Features
- User registration and login
- Browse available games by platform
- Search and filter games
- Borrow games (up to 14 days)
- Return games
- View borrowing history
- Dashboard with current borrowed games

### Admin Features
- Admin dashboard with statistics
- Manage game inventory (add, edit, delete)
- View all transactions
- Generate reports and analytics
- Handle overdue games
- Manage users

## System Requirements

- **Web Server**: Apache + PHP
- **PHP Version**: 8.0 or higher
- **Database**: Postgres (Supabase)
- **Browser**: Modern web browser with JavaScript enabled

## Installation & Setup

### 1. Server
Use Apache/PHP locally (XAMPP/other) or Docker (see Dockerfile). Start Apache.

### 2. Project Setup
1. Copy the `GameLend` folder to your XAMPP htdocs directory:
   - Windows: `C:\xampp\htdocs\GameLend\`
   - macOS: `/Applications/XAMPP/htdocs/GameLend/`
   - Linux: `/opt/lampp/htdocs/GameLend/`

### 3. Database Setup (Supabase)
1. Create a Supabase project (Postgres database).
2. Open the Supabase SQL editor and run: `db/postgres_schema.sql`.
3. Optionally seed data manually via SQL inserts.

### 4. Configuration
Set environment variables (Apache, .env loader, or system env):

- `DB_HOST` = your Supabase host
- `DB_PORT` = 5432
- `DB_NAME` = your database name
- `DB_USER` = database user (e.g., postgres)
- `DB_PASSWORD` = database password
- Or `DATABASE_URL` = `postgres://USER:PASS@HOST:5432/DBNAME`

### 5. Access the Application
1. Open your web browser
2. Navigate to `http://localhost/GameLend/`
3. The system should now be fully functional

## Default Login Credentials

### Admin Account
- **Username**: admin
- **Password**: admin123
- **Email**: admin@gamelend.com

### Customer Account
- Register a new account through the registration page

## File Structure

```
GameLend/
├── admin/                 # Admin-only pages
│   ├── dashboard.php     # Admin dashboard
│   ├── games.php         # Manage games
│   ├── reports.php       # System reports
│   └── return_game.php   # Admin return functionality
├── customer/             # Customer-only pages
│   ├── dashboard.php     # Customer dashboard
│   └── return_game.php   # Customer return functionality
├── assets/               # Frontend assets
│   ├── css/
│   │   └── style.css    # Main stylesheet
│   └── js/
│       └── script.js    # JavaScript functionality
├── db/                   # Database files
│   ├── db_connect.php       # Database connection (Postgres)
│   ├── postgres_schema.sql  # Postgres schema
│   └── add_password_resets.sql # Optional: legacy migration
├── includes/             # Reusable components
│   ├── header.php       # Page header
│   └── footer.php       # Page footer
├── index.php            # Homepage
├── login.php            # User login
├── register.php         # User registration
├── logout.php           # User logout
├── games.php            # Games listing and borrowing
└── README.md            # This file
```

## Database Schema (Postgres)

### Users Table
- `id` - Primary key
- `username` - Unique username
- `email` - Unique email address
- `password` - Hashed password
- `role` - User role (admin/customer)
- `created_at` - Account creation timestamp

### Games Table
- `id` - Primary key
- `title` - Game title
- `platform` - Gaming platform
- `status` - Game status (available/borrowed/maintenance)
- `created_at` - Game addition timestamp

### Borrow Transactions Table
- `id` - Primary key
- `user_id` - Foreign key to users table
- `game_id` - Foreign key to games table
- `borrow_date` - When game was borrowed
- `return_date` - When game was returned (NULL if not returned)
- `status` - Transaction status (borrowed/returned/overdue)

## Key Features Explained

### Borrowing System
- Games can only be borrowed if they are marked as "available"
- Each user can borrow multiple games
- Borrow period is 14 days
- Games become "overdue" after 14 days

### Return System
- Customers can return games through their dashboard
- Admins can mark games as returned for any user
- Returning a game updates both the transaction and game status

### Security Features
- Password hashing using PHP's built-in `password_hash()`
- Session-based authentication
- Role-based access control
- SQL injection prevention using prepared statements

### Responsive Design
- Mobile-friendly interface
- Modern CSS with gradients and animations
- Font Awesome icons for better UX

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Verify XAMPP is running
   - Check database credentials in `db_connect.php`
   - Ensure database `gamelend_db` exists

2. **Page Not Found (404)**
   - Verify the GameLend folder is in the correct htdocs directory
   - Check Apache is running
   - Verify file permissions

3. **Login Issues**
   - Ensure the database was imported correctly
   - Check if the admin user exists in the database
   - Verify session handling is working

4. **Permission Denied**
   - Check file permissions on the GameLend folder
   - Ensure web server has read access to all files

### Performance Tips

1. **Database Optimization**
   - Add indexes on frequently queried columns
   - Use appropriate data types
   - Regular database maintenance

2. **Caching**
   - Implement Redis/Memcached for session storage
   - Cache frequently accessed data
   - Use CDN for static assets

## Customization

### Adding New Platforms
1. Edit the platform options in `admin/games.php`
2. Update the platform filter in `games.php`
3. Modify the platform display logic as needed

### Changing Borrow Period
1. Update the 14-day logic in relevant files
2. Modify overdue calculations
3. Update user-facing messages

### Styling Changes
1. Modify `assets/css/style.css`
2. Update color schemes and layouts
3. Customize responsive breakpoints

## Security Considerations

- Always use HTTPS in production
- Implement rate limiting for login attempts
- Regular security updates
- Input validation and sanitization
- Proper error handling (don't expose system information)

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Verify your XAMPP installation
3. Check PHP and MySQL error logs
4. Ensure all files are properly uploaded

## License

This project is provided as-is for educational and development purposes.

---

**Note**: This is a development system. For production use, implement additional security measures, proper error logging, and backup systems.
