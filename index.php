<?php
require_once 'includes/session_config.php';
$page_title = "Home";
require_once 'db/db_connect.php';
include 'includes/header.php';
?>

<style>
    :root {
        --primary: #6c5ce7;
        --primary-dark: #5649c9;
        --secondary: #00cec9;
        --accent: #fd79a8;
        --dark: #2d3436;
        --light: #f5f6fa;
        --success: #00b894;
        --warning: #fdcb6e;
        --danger: #d63031;
        --gray: #dfe6e9;
    }
    
    body {
        background-color: #f8f9fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: var(--dark);
        line-height: 1.6;
    }
    
    /* Make the landing hero full-width on the home page */
    main.container {
        max-width: 100%;
        padding: 0;
    }
    
    .hero {
        /* Background image with a soft dark overlay for readability */
        background: linear-gradient(rgba(17, 24, 39, 0.55), rgba(17, 24, 39, 0.55)), url('assets/img/background.png') center/cover no-repeat;
        color: white;
        text-align: center;
        padding: 8rem 2rem;
        border-radius: 0;
        margin-bottom: 0;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        position: relative;
        overflow: hidden;
        min-height: 90vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    
    .hero > * {
        position: relative;
        z-index: 1;
    }
    
    .hero h1 {
        font-size: 3rem;
        margin-bottom: 1rem;
        font-weight: 800;
        text-shadow: 0 4px 14px rgba(0, 0, 0, 0.6);
    }
    
    .hero p {
        font-size: 1.3rem;
        max-width: 700px;
        margin: 0 auto 2rem;
        opacity: 0.95;
    }
    
    .btn {
        display: inline-block;
        padding: 1rem 2rem;
        border-radius: 999px;
        text-decoration: none;
        font-weight: 700;
        transition: all 0.25s ease;
        border: 1px solid rgba(255, 255, 255, 0.2);
        cursor: pointer;
        font-size: 1rem;
        letter-spacing: 0.3px;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.25);
        backdrop-filter: saturate(140%) blur(6px);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
        color: #fff;
    }
    
    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 30px rgba(99, 102, 241, 0.5);
    }
    
    .btn-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #fff;
    }
    
    .btn-success:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 30px rgba(5, 150, 105, 0.45);
    }

    .btn:focus-visible {
        outline: 3px solid rgba(255, 255, 255, 0.6);
        outline-offset: 2px;
    }

    .hero .actions {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .btn-warning {
        background: var(--warning);
        color: var(--dark);
    }
    
    .btn-warning:hover {
        background: #f0b44a;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(253, 203, 110, 0.4);
    }
    
    .grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    .card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }
    
    .card-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 1.2rem 1.5rem;
    }
    
    .card-title {
        margin: 0;
        font-size: 1.4rem;
        color: white !important;
        font-weight: 600;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .card-body p, .card-body ol, .card-body ul {
        margin-bottom: 1.5rem;
    }
    
    .card-body li {
        margin-bottom: 0.5rem;
    }
    
    .quick-actions {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        margin-bottom: 2rem;
    }
    
    .quick-actions .grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .platform-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin: 1rem 0;
    }
    
    .platform-tag {
        background: var(--secondary);
        color: white;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.9rem;
    }
    
    @media (max-width: 768px) {
        .hero h1 {
            font-size: 2.2rem;
        }
        
        .hero p {
            font-size: 1.1rem;
        }
        
        .grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="hero">
    <h1>Welcome to GameLend</h1>
    <p>Your premier video game borrowing and returning system</p>
    <?php if(!isset($_SESSION['user_id'])): ?>
        <div class="actions">
            <a href="register.php" class="btn btn-primary">Get Started</a>
            <a href="login.php" class="btn btn-success">Login</a>
        </div>
    <?php else: ?>
        <?php
        // User is logged in, show appropriate dashboard button
        $dashboard_url = ($_SESSION['role'] ?? 'customer') === 'admin' 
            ? 'admin/dashboard.php' 
            : 'customer/dashboard.php';
        $role_name = ($_SESSION['role'] ?? 'customer') === 'admin' ? 'Admin' : 'Customer';
        ?>
        <div class="actions">
            <a href="<?php echo $dashboard_url; ?>" class="btn btn-primary">
                <i class="fas fa-tachometer-alt"></i> Go to <?php echo $role_name; ?> Dashboard
            </a>
            <a href="logout.php" class="btn btn-warning">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Removed the informational cards section for a cleaner landing page -->

<?php include 'includes/footer.php'; ?>