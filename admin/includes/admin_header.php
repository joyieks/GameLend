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
            background: linear-gradient(rgba(17, 24, 39, 0.65), rgba(17, 24, 39, 0.65)), url('../assets/img/background3.png') center/cover no-repeat;
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
        
        .admin-badge {
            background: var(--secondary);
            color: var(--dark);
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
        
        .form-label.required::after {
            content: ' *';
            color: #ef4444;
            font-weight: 700;
        }
        
        .form-control {
            width: 100%;
            padding: 0.875rem 1.25rem;
            font-size: 1rem;
            border: 2px solid rgba(225, 232, 237, 0.5);
            border-radius: 12px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.7);
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
            background: rgba(255, 255, 255, 0.85);
        }
        
        .form-control::placeholder {
            color: #a0aec0;
        }
        
        select.form-control {
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667eea' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding-right: 2.5rem;
            appearance: none;
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
        
        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn:active::before {
            width: 300px;
            height: 300px;
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
        
        .btn-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        
        .btn-warning:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .btn-danger:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #64748b 0%, #475569 100%);
            color: white;
        }
        
        .btn-secondary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(100, 116, 139, 0.4);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        /* Enhanced Alerts */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-success {
            background: linear-gradient(135deg, #f0fff4 0%, #e6ffed 100%);
            color: #22543d;
            border-left: 4px solid #38a169;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fee 0%, #fdd 100%);
            color: #c53030;
            border-left: 4px solid #c53030;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #e6f3ff 0%, #d1e7ff 100%);
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }
        
        .alert i {
            font-size: 1.2rem;
        }
        
        /* Enhanced Tables */
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
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
            background: rgba(255, 255, 255, 0.5);
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
            background: rgba(255, 255, 255, 0.3);
        }
        
        .table tbody tr:hover {
            background: rgba(248, 249, 255, 0.7);
            transform: scale(1.01);
        }
        
        .table tbody tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.4);
        }
        
        .table tbody tr:nth-child(even):hover {
            background: rgba(248, 249, 255, 0.8);
        }
        
        .table tbody tr:last-child td {
            border-bottom: none;
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
        
        /* Enhanced Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            margin: 10% auto;
            padding: 2.5rem;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal h3 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: #2d3436;
            font-weight: 700;
        }
        
        /* Form Help Text */
        .form-help {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-help i {
            font-size: 0.9rem;
        }
        
        /* Loading Spinner */
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Platform Row Styling */
        .platform-row {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            margin-bottom: 0.75rem;
            padding: 0.75rem;
            background: rgba(248, 249, 250, 0.4);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }
        
        .platform-row:hover {
            background: rgba(241, 243, 245, 0.6);
            transform: translateX(5px);
            border-color: rgba(255, 255, 255, 0.4);
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
                    GameLend Admin
                </a>
            </div>
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="games.php"><i class="fas fa-gamepad"></i> Manage Games</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Manage Users</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="profile.php"><i class="fas fa-user-shield"></i> My Profile</a></li>
                <li><a href="../logout.php" class="logout" onclick="return confirm('Are you sure you want to logout?');">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a></li>
            </ul>
        </nav>
    </header>
    <main class="container">
