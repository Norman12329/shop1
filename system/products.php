<?php
require 'config.php';

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle form actions
$message = '';
if ($_POST) {
    if (isset($_POST['delete_product'])) {
        $product_id = (int)$_POST['product_id'];
        $stmt = $pdo->prepare("DELETE FROM user_menu WHERE id = ?");
        if ($stmt->execute([$product_id])) {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>Product deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        }
    }
}

// Fetch products from user_menu
$products = [];
$total_products = 0;

try {
    $stmt = $pdo->prepare("
        SELECT id, name, ROUND(price) as price, category, image, status, created_at 
        FROM user_menu 
        ORDER BY created_at ASC 
        LIMIT 50
    ");
    $stmt->execute();
    $products = $stmt->fetchAll();

    $total_products = $pdo->query("SELECT COUNT(*) FROM user_menu")->fetchColumn();

} catch (PDOException $e) {
    $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-info-circle me-2"></i>user_menu table not found. 
        <a href="setup_user_menu.php" class="alert-link fw-bold">Create table now?</a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Products - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* SIDEBAR & DESIGN - UNCHANGED */
        .sidebar { height: 100vh; position: fixed; z-index: 1000; background: linear-gradient(180deg, #667eea 0%, #764ba2 100%); color: white; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); border-radius: 10px; margin: 5px 15px; padding: 12px 20px; transition: all 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: rgba(255,255,255,0.2); color: white; transform: translateX(5px); }
        .main-content { margin-left: 180px; padding: 2rem; min-height: 100vh; }
        @media (max-width: 768px) { .sidebar { display: none; } .main-content { margin-left: 0; } }
        .card { border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .table th { background: #f8f9fc; font-weight: 600; border-top: none; }
        .product-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .product-img-placeholder { width: 60px; height: 60px; border-radius: 8px; background: #eee; display: flex; align-items: center; justify-content: center; }
        .price-badge { background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 0.5rem 1rem; border-radius: 25px; font-weight: bold; }
        .price-badge::before { content: '₱'; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<nav id="sidebar" class="sidebar col-md-3 col-lg-2 d-md-block">
    <div class="position-sticky pt-4">
        <div class="text-center mb-4">
            <h4 class="text-white mb-0">👑 Admin Panel</h4>
            <small class="opacity-75">Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?>!</small>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="users.php"><i class="fas fa-users me-2"></i> Users</a></li>
            <li class="nav-item"><a class="nav-link active" href="products.php"><i class="fas fa-box me-2"></i> Products</a></li>
        </ul>
    </div>
</nav>

<!-- MAIN CONTENT -->
<main class="main-content">
<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <div>
        <h1 class="h2 fw-bold">Product Management</h1>
        <p class="text-muted">Manage your food menu items (<?php echo $total_products; ?> total)</p>
    </div>
    <a href="products_add.php" class="btn btn-success btn-lg"><i class="fas fa-plus me-2"></i>Add New Product</a>
</div>

<?php echo $message; ?>

<div class="card">
<div class="card-body p-0">
<table class="table table-hover mb-0">
<thead>
<tr>
<th>#</th>
<th>Image</th>
<th>Name</th>
<th>Category</th>
<th>Price</th>
<th>Status</th>
<th>Added</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($products as $index => $product): ?>
<tr>
<td><strong class="text-primary"><?php echo $index + 1; ?></strong></td>
<td>
<?php if (!empty($product['image'])): ?>
<img src="<?php echo htmlspecialchars($product['image']); ?>" class="product-img">
<?php else: ?>
<div class="product-img-placeholder">🍕</div>
<?php endif; ?>
</td>
<td><strong><?php echo htmlspecialchars($product['name']); ?></strong><br><small>ID: <?php echo $product['id']; ?></small></td>
<td><?php echo htmlspecialchars($product['category']); ?></td>
<td><div class="price-badge"><?php echo number_format($product['price']); ?></div></td>
<td><span class="badge bg-success"><?php echo ucfirst($product['status']); ?></span></td>
<td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
<td>
<form method="POST" onsubmit="return confirm('Delete <?php echo addslashes($product['name']); ?>?')">
<input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
<button type="submit" name="delete_product" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

</main>
</body>
</html>