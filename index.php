<?php
session_start();
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
    
    .hero {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        text-align: center;
        padding: 4rem 2rem;
        border-radius: 16px;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }
    
    .hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="%2300cec9" fill-opacity="0.1"/><path d="M0 0L100 100" stroke="%2300cec9" stroke-width="1" stroke-opacity="0.3"/><path d="M100 0L0 100" stroke="%2300cec9" stroke-width="1" stroke-opacity="0.3"/></svg>');
        background-size: cover;
        opacity: 0.3;
    }
    
    .hero > * {
        position: relative;
        z-index: 1;
    }
    
    .hero h1 {
        font-size: 3rem;
        margin-bottom: 1rem;
        font-weight: 800;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    }
    
    .hero p {
        font-size: 1.3rem;
        max-width: 700px;
        margin: 0 auto 2rem;
        opacity: 0.9;
    }
    
    .btn {
        display: inline-block;
        padding: 0.8rem 1.8rem;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        font-size: 1rem;
    }
    
    .btn-primary {
        background: var(--primary);
        color: white;
    }
    
    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(108, 92, 231, 0.4);
    }
    
    .btn-success {
        background: var(--success);
        color: white;
    }
    
    .btn-success:hover {
        background: #00a382;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 184, 148, 0.4);
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
        <a href="register.php" class="btn btn-primary">Get Started</a>
        <a href="login.php" class="btn btn-success" style="margin-left: 1rem;">Login</a>
    <?php endif; ?>
</div>

<div class="grid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Available Games</h3>
        </div>
        <div class="card-body">
            <p>Browse our extensive collection of video games across multiple platforms including PC, PlayStation, Nintendo Switch, and Xbox.</p>
            <div class="platform-tags">
                <span class="platform-tag">PC</span>
                <span class="platform-tag">PlayStation</span>
                <span class="platform-tag">Nintendo Switch</span>
                <span class="platform-tag">Xbox</span>
            </div>
            <a href="games.php" class="btn btn-primary">View Games</a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">How It Works</h3>
        </div>
        <div class="card-body">
            <ol>
                <li>Register for a free account</li>
                <li>Browse available games</li>
                <li>Borrow games for up to 14 days</li>
                <li>Return games on time to avoid late fees</li>
            </ol>
            <a href="register.php" class="btn btn-success">Sign Up Now</a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Platforms</h3>
        </div>
        <div class="card-body">
            <ul>
                <li><strong>PC:</strong> Latest AAA titles and indie games</li>
                <li><strong>PlayStation:</strong> PS4 and PS5 exclusives</li>
                <li><strong>Nintendo Switch:</strong> Family-friendly and exclusive titles</li>
                <li><strong>Xbox:</strong> Game Pass favorites and exclusives</li>
            </ul>
            <a href="games.php" class="btn btn-primary">Explore Platforms</a>
        </div>
    </div>
</div>

<?php if(isset($_SESSION['user_id'])): ?>
    <div class="quick-actions">
        <h2 style="margin-top: 0;">Quick Actions</h2>
        <div class="grid">
            <?php if($_SESSION['role'] === 'admin'): ?>
                <a href="admin/dashboard.php" class="btn btn-primary">Admin Dashboard</a>
                <a href="admin/games.php" class="btn btn-success">Manage Games</a>
                <a href="admin/reports.php" class="btn btn-warning">View Reports</a>
            <?php else: ?>
                <a href="customer/dashboard.php" class="btn btn-primary">My Dashboard</a>
                <a href="customer/borrowed.php" class="btn btn-success">Borrowed Games</a>
                <a href="games.php" class="btn btn-warning">Browse Games</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>