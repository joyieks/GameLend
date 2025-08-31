<?php
session_start();
$page_title = "Login";

if(isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'db/db_connect.php';
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if(empty($username) || empty($password)) {
        $error = 'Both username and password are required';
    } else {
        // Check user credentials
        $stmt = $pdo->prepare("SELECT id, username, password, first_name, last_name, gender, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['gender'] = $user['gender'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            if($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: customer/dashboard.php');
            }
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}

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
    
    .login-container {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }
    
    .login-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        width: 100%;
        max-width: 600px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .login-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
    }
    
    .card-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 1.5rem;
        text-align: center;
        position: relative;
    }
    
    .card-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--secondary), var(--accent));
    }
    
    .card-title {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 800;
        color: white !important;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .alert {
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        font-weight: 500;
    }
    
    .alert-danger {
        background: rgba(214, 48, 49, 0.1);
        color: var(--danger);
        border: 1px solid rgba(214, 48, 49, 0.2);
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--dark);
    }
    
    .form-control {
        width: 100%;
        padding: 0.8rem;
        font-size: 1rem;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        transition: all 0.3s ease;
        background: #f8f9fa;
    }
    
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        background: white;
        box-shadow: 0 0 0 3px rgba(108, 92, 238, 0.1);
    }
    
    .password-container {
        position: relative;
    }
    
    .toggle-password {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #6c757d;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 50%;
        transition: all 0.3s ease;
    }
    
    .toggle-password:hover {
        background: #e9ecef;
        color: var(--primary);
    }
    
    .btn {
        display: block;
        width: 100%;
        padding: 0.8rem;
        border-radius: 10px;
        border: none;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
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
    
    .register-link {
        text-align: center;
        margin-top: 1.5rem;
        color: #6c757d;
    }
    
    .register-link a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .register-link a:hover {
        color: var(--primary-dark);
        text-decoration: underline;
    }
    
    .demo-section {
        margin-top: 2rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 10px;
        border-left: 4px solid var(--secondary);
    }
    
    .demo-title {
        margin-top: 0;
        margin-bottom: 1rem;
        font-size: 1.1rem;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .demo-title i {
        color: var(--secondary);
    }
    
    .demo-account {
        display: flex;
        margin-bottom: 0.5rem;
        padding: 0.5rem;
        background: white;
        border-radius: 8px;
        align-items: center;
    }
    
    .demo-label {
        font-weight: 600;
        min-width: 70px;
        color: var(--primary);
    }
    
    .demo-value {
        color: #6c757d;
        font-family: 'Courier New', monospace;
    }
    
    @media (max-width: 576px) {
        .login-container {
            padding: 1rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
    }
</style>

<div class="login-container">
    <div class="login-card">
        <div class="card-header">
            <h2 class="card-title">Welcome Back</h2>
        </div>
        
        <div class="card-body">
            <?php if($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" data-validate>
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" id="username" name="username" class="form-control" required 
                           placeholder="Enter your username"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" class="form-control" required
                               placeholder="Enter your password">
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="register-link">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
            
            <div class="demo-section">
                <h4 class="demo-title">
                    <i class="fas fa-info-circle"></i> Demo Accounts
                </h4>
                
                <div class="demo-account">
                    <span class="demo-label">Admin:</span>
                    <span class="demo-value">username: admin, password: admin123</span>
                </div>
                
                <div class="demo-account">
                    <span class="demo-label">Customer:</span>
                    <span class="demo-value">Register a new account</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePasswordVisibility(inputId) {
        const passwordInput = document.getElementById(inputId);
        const toggleButton = passwordInput.nextElementSibling;
        const icon = toggleButton.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>

<?php include 'includes/footer.php'; ?>