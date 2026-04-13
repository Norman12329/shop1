<?php
require 'config.php';

if ($_POST) {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (strlen($password) < 6) {
        $error = "Password too short";
    } else {
        // ========== ADMIN LOGIN ==========
        if (strtolower($email) === 'admin@gmail.com' && $password === 'admin123') {
            $_SESSION['user_id'] = 999;
            $_SESSION['name'] = 'Admin';
            $_SESSION['role'] = 'admin';
            $_SESSION['is_logged_in'] = true;
            
            header('Location: dashboard.php');
            exit;
        }

        // ========== USER LOGIN ==========
        $stmt = $pdo->prepare("SELECT * FROM users");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $found = false;
        foreach ($users as $user) {
            $decryptedEmail = decryptEmail($user['email']);
            if ($decryptedEmail === $email) {
                // Check password
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['role'] = $user['role'] ?? 'user';
                    $_SESSION['is_logged_in'] = true;

                    header('Location: index.php');
                    exit;
                } else {
                    $error = "Invalid credentials";
                    $found = true;
                    break;
                }
            }
        }

        if (!$found && !isset($error)) {
            $error = "Invalid credentials";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Food Ordering System - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-card { 
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border: none;
            border-radius: 20px;
        }
    </style>
</head>
<body class="d-flex align-items-center min-vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card login-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="text-primary mb-3">🍕 FoodHub</h2>
                            <p class="text-muted lead">Order delicious food online</p>
                        </div>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label fw-bold fs-5">Email</label>
                                <input type="email" name="email" class="form-control form-control-lg" 
                                       placeholder="Enter your email"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                       required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold fs-5">Password</label>
                                <input type="password" name="password" class="form-control form-control-lg" 
                                       placeholder="Enter your password" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100 py-3 fw-bold shadow-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Login Now
                            </button>
                        </form>
                        
                        <div class="mt-4 text-center">
                            <small class="text-muted">
                                Don't have account? <a href="register.php" class="text-decoration-none fw-bold">Register here</a>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>