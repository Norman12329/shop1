<?php
require 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

if ($_POST) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $category = trim($_POST['category']);

    if (empty($name)) {
        $error = 'Product name is required';
    } elseif ($price <= 0) {
        $error = 'Price must be greater than 0';
    } elseif (strlen($name) > 100) {
        $error = 'Product name too long (max 100 chars)';
    } else {
        // Handle image upload
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/products/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];

            if (in_array($ext, $allowed)) {
                $new_file = uniqid() . '.' . $ext;
                $image_path = $upload_dir . $new_file;
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                    $error = 'Failed to upload image';
                }
            } else {
                $error = 'Invalid image format. Use JPG, PNG, or WebP';
            }
        }

        if (empty($error)) {
            try {
                // Insert into admin products
                $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category, image, status, created_at) VALUES (?, ?, ?, ?, ?, 'active', NOW())");
                $stmt->execute([$name, $description, $price, $category, $image_path]);

                // Ensure user_menu exists
                $pdo->exec("CREATE TABLE IF NOT EXISTS user_menu (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    name VARCHAR(100) NOT NULL,
                    description TEXT,
                    price FLOAT NOT NULL,
                    category VARCHAR(50),
                    image VARCHAR(255),
                    status ENUM('active','inactive') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");

                // Insert into user_menu
                $user_stmt = $pdo->prepare("INSERT INTO user_menu (name, description, price, category, image, status) VALUES (?, ?, ?, ?, ?, 'active')");
                $user_stmt->execute([$name, $description, $price, $category, $image_path]);

                $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    ✅ Product <strong>"' . htmlspecialchars($name) . '"</strong> added to BOTH tables!
                    <div class="mt-2">
                        <a href="products.php" class="btn btn-sm btn-success me-2">View Admin Products</a>
                        <a href="index.php" class="btn btn-sm btn-primary">View User Menu</a>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';

            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Add Product - Admin Panel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
    /* Sidebar */
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
        margin-left: 280px;
        padding: 2rem;
        min-height: 100vh;
    }
    @media (max-width: 768px) {
        .sidebar { display: none; }
        .main-content { margin-left: 0; }
    }
    .form-card {
        border: none;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        border-radius: 20px;
        overflow: hidden;
    }
    .form-header {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        padding: 2rem;
    }
</style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar">
    <div class="position-sticky pt-4">
        <div class="text-center mb-4">
            <h4 class="text-white mb-0">👑 Admin Panel</h4>
            <small class="opacity-75">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></small>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="users.php"><i class="fas fa-users me-2"></i> Users</a></li>
            <li class="nav-item"><a class="nav-link active" href="products.php"><i class="fas fa-box me-2"></i> Products</a></li>
            <li class="nav-item"><a class="nav-link" href="orders.php"><i class="fas fa-shopping-cart me-2"></i> Orders</a></li>
            <li class="nav-item mt-3"><a class="nav-link text-danger bg-danger bg-opacity-25" href="login.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
        </ul>
    </div>
</nav>

<!-- Main content -->
<main class="main-content">
    <div class="container-fluid">
        <?php if ($message) echo $message; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-card">
            <div class="form-header">
                <h2><i class="fas fa-plus-circle me-3"></i>Add New Product</h2>
            </div>
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data">
                    <input type="text" name="name" class="form-control mb-3" placeholder="Product Name" required>
                    <input type="text" name="category" class="form-control mb-3" placeholder="Category">
                    <input type="number" name="price" class="form-control mb-3" placeholder="Price" required>
                    <textarea name="description" class="form-control mb-3" placeholder="Description"></textarea>
                    <input type="file" name="image" class="form-control mb-3">
                    <button class="btn btn-success"><i class="fas fa-save me-2"></i>Add Product</button>
                </form>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>