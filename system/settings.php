<?php
require 'config.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$message = '';
$success_message = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['update_site_settings'])) {
        $site_name = trim($_POST['site_name']);
        $site_email = trim($_POST['site_email']);
        $delivery_fee = (float)$_POST['delivery_fee'];
        
        $stmt = $pdo->prepare("INSERT INTO settings (key_name, value) VALUES ('site_name', ?), ('site_email', ?), ('delivery_fee', ?) ON DUPLICATE KEY UPDATE value = VALUES(value)");
        if ($stmt->execute([$site_name, $site_email, $delivery_fee])) {
            $success_message = '<div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>Site settings updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                    $success_message = '<div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-lock me-2"></i>Password changed successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
                }
            } else {
                $message = '<div class="alert alert-danger">New passwords do not match!</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">Current password is incorrect!</div>';
        }
    }
}

// Fetch current settings
$current_settings = [
    'site_name' => 'Food Ordering System',
    'site_email' => 'admin@food.com',
    'delivery_fee' => 2.99
];

try {
    $stmt = $pdo->prepare("SELECT key_name, value FROM settings");
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $current_settings = array_merge($current_settings, $settings);
} catch (PDOException $e) {
    // Settings table doesn't exist yet
}

// FETCH STATS
$total_users = 0;
$total_orders = 0;
$total_menu_items = 0;

try {
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $total_menu_items = $pdo->query("SELECT COUNT(*) FROM food_items")->fetchColumn();
} catch (PDOException $e) {
    // Ignore errors
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Settings - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* YOUR EXACT SAME STYLES */
        .sidebar {
            height: 100vh;
            position: fixed;
            z-index: 1000;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 10px;
            margin: 5px 15px;
            padding: 12px 20px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateX(5px);
        }
        .main-content {
            margin-left: 180px;
            padding: 2rem;
            min-height: 100vh;
        }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
        .settings-card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
        .form-label { font-weight: 600; color: #495057; }
    </style>
</head>
<body>
    <!-- YOUR EXACT SAME SIDEBAR -->
    <nav id="sidebar" class="sidebar col-md-3 col-lg-2 d-md-block">
        <div class="position-sticky pt-4">
            <div class="text-center mb-4">
                <h4 class="text-white mb-0">👑 Admin Panel</h4>
                <small class="opacity-75">Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?>!</small>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php"><i class="fas fa-users me-2"></i> Users</a></li>
                <li class="nav-item"><a class="nav-link" href="products.php"><i class="fas fa-box me-2"></i> Products</a></li>
                <li class="nav-item"><a class="nav-link" href="orders.php"><i class="fas fa-shopping-cart me-2"></i> Orders</a></li>
                <li class="nav-item"><a class="nav-link active" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                <li class="nav-item mt-3">
                    <a class="nav-link text-danger bg-danger bg-opacity-25" href="logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <main class="main-content">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-3 mb-4 border-bottom">
            <div>
                <h1 class="h2 fw-bold text-dark mb-1">⚙️ Settings</h1>
                <p class="text-muted mb-0">Manage site configuration and account settings</p>
            </div>
        </div>

        <?php echo $message ?? ''; ?>
        <?php echo $success_message ?? ''; ?>

        <div class="row">
            <!-- Site Settings -->
            <div class="col-lg-6 mb-4">
                <div class="card settings-card h-100">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-globe me-2"></i>Site Settings</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Site Name</label>
                                <input type="text" class="form-control" name="site_name" value="<?php echo htmlspecialchars($current_settings['site_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Email</label>
                                <input type="email" class="form-control" name="site_email" value="<?php echo htmlspecialchars($current_settings['site_email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Default Delivery Fee ($)</label>
                                <input type="number" step="0.01" class="form-control" name="delivery_fee" value="<?php echo $current_settings['delivery_fee']; ?>" required>
                            </div>
                            <button type="submit" name="update_site_settings" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Admin Password Change -->
            <div class="col-lg-6 mb-4">
                <div class="card settings-card h-100">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-lock me-2"></i>Admin Password</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-success w-100">
                                <i class="fas fa-key me-2"></i>Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Info - PHP VERSION REMOVED -->
        <div class="row">
            <div class="col-12">
                <div class="card settings-card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>System Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Total Users:</strong><br>
                                <span class="badge bg-primary fs-6"><?php echo $total_users; ?></span>
                            </div>
                            <div class="col-md-4">
                                <strong>Total Orders:</strong><br>
                                <span class="badge bg-success fs-6"><?php echo $total_orders; ?></span>
                            </div>
                            <div class="col-md-4">
                                <strong>Menu Items:</strong><br>
                                <span class="badge bg-warning fs-6"><?php echo $total_menu_items; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>