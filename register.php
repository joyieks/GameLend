<?php
session_start();
$page_title = "Register";

if(isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'db/db_connect.php';
    
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $gender = $_POST['gender'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if(empty($first_name) || empty($last_name) || empty($gender) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif(strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif(strlen($first_name) < 2) {
        $error = 'First name must be at least 2 characters long';
    } elseif(strlen($last_name) < 2) {
        $error = 'Last name must be at least 2 characters long';
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if($stmt->rowCount() > 0) {
            $error = 'Username or email already exists';
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, gender, username, email, password, role) VALUES (?, ?, ?, ?, ?, ?, 'customer')");
            
            if($stmt->execute([$first_name, $last_name, $gender, $username, $email, $hashed_password])) {
                $success = 'Registration successful! You can now login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

include 'includes/header.php';
?>

<style>
    .register-container {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }
    
    .register-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        width: 100%;
        max-width: 700px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .register-card:hover {
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
        background: linear-gradient(90deg, #00cec9, #fd79a8);
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
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #2d3436;
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
        border-color: #2c3e50;
        background: white;
        box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.1);
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
        background: #2c3e50;
        color: white;
    }
    
    .btn-primary:hover {
        background: #34495e;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(44, 62, 80, 0.4);
    }
    
    .alert {
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        font-weight: 500;
    }
    
    .alert-danger {
        background: rgba(214, 48, 49, 0.1);
        color: #d63031;
        border: 1px solid rgba(214, 48, 49, 0.2);
    }
    
    .alert-success {
        background: rgba(0, 184, 148, 0.1);
        color: #00b894;
        border: 1px solid rgba(0, 184, 148, 0.2);
    }
</style>

<div class="register-container">
    <div class="register-card">
        <div class="card-header">
            <h2 class="card-title">Create Account</h2>
        </div>
        <div class="card-body">
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <p><a href="login.php" class="btn btn-primary">Go to Login</a></p>
    <?php else: ?>
        <form method="POST" data-validate>
            <div class="form-group">
                <label for="first_name" class="form-label">First Name</label>
                <input type="text" id="first_name" name="first_name" class="form-control" required 
                       value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="last_name" class="form-label">Last Name</label>
                <input type="text" id="last_name" name="last_name" class="form-control" required 
                       value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="gender" class="form-label">Gender</label>
                <select id="gender" name="gender" class="form-control" required>
                    <option value="">Select Gender</option>
                    <option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                    <option value="other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                    <option value="prefer_not_to_say" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'prefer_not_to_say') ? 'selected' : ''; ?>>Prefer not to say</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-control" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" class="form-control" required>
                    <button type="button" onclick="togglePasswordVisibility('password')" 
                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div style="position: relative;">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    <button type="button" onclick="togglePasswordVisibility('confirm_password')" 
                            style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Register</button>
        </form>
        
        <p style="text-align: center; margin-top: 1rem;">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    <?php endif; ?>
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        field.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
