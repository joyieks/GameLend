<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>GameLend Admin</title>
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
            background-color: #f8f9fa;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .navbar {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 0.8rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-brand a {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            transition: var(--transition);
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
        
        .admin-badge {
            background: var(--secondary);
            color: var(--dark);
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-left: 0.5rem;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .navbar {
                padding: 0.8rem 1rem;
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.8rem;
            }
            
            .nav-menu a {
                font-size: 0.9rem;
                padding: 0.4rem 0.6rem;
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
                    GameLend Admin
                </a>
            </div>
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="games.php"><i class="fas fa-gamepad"></i> Manage Games</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="profile.php"><i class="fas fa-user-shield"></i> My Profile</a></li>
                <li><a href="../logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)
                </a></li>
            </ul>
        </nav>
    </header>
    <main class="container">
