<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>GameLend</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6c5ce7;
            --primary-dark: #5649c9;
            --secondary: #00cec9;
            --dark: #2d3436;
            --light: #f5f6fa;
            --gray: #dfe6e9;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(rgba(17, 24, 39, 0.65), rgba(17, 24, 39, 0.65)), url('../assets/img/background4.png') center/cover no-repeat;
            background-attachment: fixed;
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .navbar {
            background: rgba(44, 62, 80, 0.75) !important;
            backdrop-filter: blur(20px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
            color: white;
            padding: 0.8rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1) !important;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        
        .nav-brand a {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            transition: var(--transition);
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }
        
        .nav-brand a:hover {
            transform: scale(1.05);
        }
        
        .nav-brand i {
            margin-right: 0.5rem;
            font-size: 1.8rem;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            align-items: center;
            gap: 1.5rem;
        }
        
        .nav-menu a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 0.8rem;
            border-radius: 4px;
            transition: var(--transition);
            font-weight: 500;
            position: relative;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
        }
        
        .nav-menu a:not(.logout):hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
            color: var(--secondary);
        }
        
        .nav-menu a:not(.logout)::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 50%;
            background: var(--secondary);
            transition: var(--transition);
            transform: translateX(-50%);
        }
        
        .nav-menu a:not(.logout):hover::after {
            width: 80%;
        }
        
        .logout {
            background: rgba(231, 76, 60, 0.8);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .logout:hover {
            background: rgba(231, 76, 60, 1);
            transform: translateY(-2px);
        }
        
        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .customer-badge {
            background: var(--dark);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 0.5rem;
        }
        
        /* Mobile Navigation */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        
        .mobile-menu-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .mobile-menu-toggle i {
            transition: transform 0.3s ease;
        }
        
        .mobile-menu-toggle.active i {
            transform: rotate(90deg);
        }
        
        .nav-menu {
            transition: all 0.3s ease;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .navbar {
                padding: 0.8rem 1rem;
                position: relative;
            }
            
            .navbar .container {
                justify-content: space-between;
                align-items: center;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
            
            .nav-menu {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: rgba(44, 62, 80, 0.85) !important;
                backdrop-filter: blur(20px) saturate(180%) !important;
                -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
                flex-direction: column;
                padding: 1rem;
                box-shadow: 0 4px 30px rgba(0, 0, 0, 0.4) !important;
                border-radius: 0 0 15px 15px;
                transform: translateY(-100%);
                opacity: 0;
                visibility: hidden;
                z-index: 1000;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
            }
            
            .nav-menu.active {
                transform: translateY(0);
                opacity: 1;
                visibility: visible;
            }
            
            .nav-menu li {
                width: 100%;
                margin: 0;
            }
            
            .nav-menu a {
                display: block;
                padding: 1rem;
                margin: 0.25rem 0;
                border-radius: 10px;
                text-align: center;
                font-size: 1rem;
                font-weight: 600;
                background: rgba(255, 255, 255, 0.1);
                transition: all 0.3s ease;
                border: 2px solid transparent;
            }
            
            .nav-menu a:hover {
                background: rgba(255, 255, 255, 0.2);
                transform: translateX(5px);
                border-color: var(--secondary);
            }
            
            .nav-menu a.logout {
                background: rgba(231, 76, 60, 0.8);
                color: white;
            }
            
            .nav-menu a.logout:hover {
                background: rgba(231, 76, 60, 1);
                transform: translateX(5px);
            }
        }
        
        @media (max-width: 480px) {
            .navbar {
                padding: 0.6rem 0.8rem;
            }
            
            .nav-brand a {
                font-size: 1.3rem;
            }
            
            .mobile-menu-toggle {
                font-size: 1.3rem;
            }
            
            .nav-menu {
                padding: 0.8rem;
            }
            
            .nav-menu a {
                padding: 0.8rem;
                font-size: 0.9rem;
            }
        }
        
        /* Footer glassmorphism styling */
        .footer {
            background: rgba(44, 62, 80, 0.75) !important;
            backdrop-filter: blur(20px) saturate(180%) !important;
            -webkit-backdrop-filter: blur(20px) saturate(180%) !important;
            box-shadow: 0 -4px 30px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1) !important;
            border-top: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        
        .footer p {
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
            font-weight: 400;
            font-size: 0.8rem;
            margin: 0;
            padding: 0;
            line-height: 1.2;
        }
        
        /* Enhanced Game-Themed Form Styling */
        .card {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.4);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
            margin-bottom: 2rem;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(255, 255, 255, 0.4);
            background: rgba(255, 255, 255, 0.5);
        }
        
        .card-header {
            background: rgba(102, 126, 234, 0.75);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            color: white;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(255, 255, 255, 0.1);
        }
        
        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.3) 0%, rgba(118, 75, 162, 0.3) 100%);
            z-index: 0;
        }
        
        .card-header > * {
            position: relative;
            z-index: 1;
        }
        
        .card-title {
            color: white !important;
            font-weight: 700;
            font-size: 1.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 1;
            margin: 0;
        }
        
        .card-body {
            padding: 2rem;
            background: transparent;
        }
        
        .card-body p,
        .card-body strong,
        .card-body span,
        .card-body td {
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
        }
        
        /* Enhanced Form Controls */
        .form-group {
            margin-bottom: 1.75rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: #2d3436;
            font-size: 0.95rem;
            letter-spacing: 0.2px;
        }
        
        .form-control {
            width: 100%;
            padding: 0.875rem 1.25rem;
            font-size: 1rem;
            border: 2px solid rgba(225, 232, 237, 0.5);
            border-radius: 12px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: #2d3436;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1), 0 4px 12px rgba(0, 0, 0, 0.08);
            transform: translateY(-1px);
            background: rgba(255, 255, 255, 0.95);
        }
        
        /* Enhanced Buttons */
        .btn {
            padding: 0.875rem 1.75rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .btn-success:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .btn-danger:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }
        
        /* Enhanced Tables */
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(15px) saturate(180%);
            -webkit-backdrop-filter: blur(15px) saturate(180%);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .table thead th {
            background: rgba(102, 126, 234, 0.75);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            color: white;
            padding: 1rem 1.25rem;
            text-align: left;
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
        }
        
        .table thead th::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.3) 0%, rgba(118, 75, 162, 0.3) 100%);
            z-index: 0;
        }
        
        .table tbody td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(240, 242, 245, 0.5);
            vertical-align: middle;
            background: rgba(255, 255, 255, 0.3);
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .table tbody tr:hover {
            background: rgba(248, 249, 255, 0.7);
            transform: scale(1.01);
        }
        
        .table tbody tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .table tbody tr:nth-child(even):hover {
            background: rgba(248, 249, 255, 0.8);
        }
        
        /* Enhanced Badges */
        .badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.8rem;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        
        .badge-available, .badge-success {
            background: rgba(220, 252, 231, 0.8);
            color: #166534;
        }
        
        .badge-borrowed, .badge-warning {
            background: rgba(255, 247, 237, 0.8);
            color: #c2410c;
        }
        
        .badge-returned {
            background: rgba(220, 252, 231, 0.8);
            color: #166534;
        }
        
        .badge-overdue, .badge-danger {
            background: rgba(254, 226, 226, 0.8);
            color: #b91c1c;
        }
        
        /* Grid Layout */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .card-body {
                padding: 1.5rem;
            }
            
            .card-header {
                padding: 1.25rem 1.5rem;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-brand">
                <a href="dashboard.php">
                    <i class="fas fa-gamepad"></i>
                    GameLend
                </a>
            </div>
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> My Dashboard</a></li>
                <li><a href="games.php"><i class="fas fa-gamepad"></i> Browse Games</a></li>
                <li><a href="borrowed.php"><i class="fas fa-hand-holding"></i> Borrowed Games</a></li>
                <li><a href="history.php"><i class="fas fa-history"></i> History</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="../logout.php" class="logout" onclick="return confirm('Are you sure you want to logout?');">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a></li>
            </ul>
        </nav>
    </header>
    <main class="container">
