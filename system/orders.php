<?php
require 'config.php';

// ✅ ADMIN CHECK
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// HANDLE STATUS UPDATE
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $new_status = strtolower(trim($_POST['status'] ?? ''));

    $valid_statuses = ['pending', 'preparing', 'ready', 'delivered', 'cancelled'];

    if ($order_id > 0 && in_array($new_status, $valid_statuses)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
        $stmt->execute([
            ':status' => $new_status,
            ':id' => $order_id
        ]);

        header("Location: orders.php?success=1");
        exit();
    }
}

// SUCCESS MESSAGE
if (isset($_GET['success'])) {
    $message = '<div class="alert alert-success alert-dismissible fade show">
        ✅ Order updated successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

// FETCH DATA (ASCENDING ID)
try {
    $stmt = $pdo->prepare("
        SELECT 
            o.id as orderid,
            u.name as customer,
            fi.name as product,
            o.created_at as date,
            o.total_price as total,
            o.status,
            o.quantity
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN food_items fi ON o.food_item_id = fi.id
        ORDER BY o.id ASC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $total_revenue = round($pdo->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE status!='cancelled'")->fetchColumn());
    $pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();

} catch (PDOException $e) {
    $orders = [];
    $message = '<div class="alert alert-danger">DB Error</div>';
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Orders Management</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<style>
/* ✅ SAME DASHBOARD DESIGN */
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
    transition: 0.3s;
}
.sidebar .nav-link:hover, .sidebar .nav-link.active {
    background: rgba(255,255,255,0.2);
    color: white;
    transform: translateX(5px);
}
.main-content {
    margin-left: 180px;
    padding: 2rem;
}
.card { border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
.border-left-primary { border-left: 4px solid #4e73df; }
.border-left-success { border-left: 4px solid #1cc88a; }
.border-left-warning { border-left: 4px solid #f6c23e; }
.peso-sign::before { content: '₱'; margin-right: 4px; }
</style>
</head>

<body>

<!-- SIDEBAR -->
<nav class="sidebar col-md-3 col-lg-2 d-md-block">
    <div class="position-sticky pt-4">
        <div class="text-center mb-4">
            <h4>👑 Admin Panel</h4>
            <small>Welcome, <?= htmlspecialchars($_SESSION['name']) ?></small>
        </div>

        <ul class="nav flex-column">
            <li><a class="nav-link" href="dashboard.php">Dashboard</a></li>
            <li><a class="nav-link active" href="orders.php">Orders</a></li>
            <li><a class="nav-link" href="users.php">Users</a></li>
            <li><a class="nav-link" href="products.php">Products</a></li>
            <li><a class="nav-link" href="settings.php">Settings</a></li>
            <li class="mt-3"><a class="nav-link text-danger bg-danger bg-opacity-25" href="logout.php">Logout</a></li>
        </ul>
    </div>
</nav>

<!-- MAIN -->
<main class="main-content">

<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <h1 class="h2 fw-bold">🛒 Orders Management</h1>
</div>

<?= $message ?>

<!-- DASHBOARD-STYLE CARDS -->
<div class="row mb-5">
    <div class="col-md-4">
        <div class="card border-left-primary p-3">
            Total Orders<br><strong><?= $total_orders ?></strong>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-left-success p-3">
            Revenue<br><strong class="peso-sign"><?= number_format($total_revenue) ?></strong>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-left-warning p-3">
            Pending<br><strong><?= $pending_orders ?></strong>
        </div>
    </div>
</div>

<!-- ORDERS TABLE -->
<div class="card shadow">
<div class="card-header bg-primary text-white">
    📋 Orders List
</div>
<div class="card-body p-0">

<table class="table table-hover mb-0">
<thead class="table-dark">
<tr>
    <th>ID</th>
    <th>Customer</th>
    <th>Product</th>
    <th>Date</th>
    <th>Qty</th>
    <th>Total</th>
    <th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach ($orders as $order): 
    $status = strtolower(trim($order['status']));
?>
<tr>
<td>#<?= $order['orderid'] ?></td>
<td><?= htmlspecialchars($order['customer']) ?></td>
<td><?= htmlspecialchars($order['product']) ?></td>
<td><?= date('M j, Y', strtotime($order['date'])) ?></td>
<td><?= $order['quantity'] ?></td>
<td class="peso-sign"><?= number_format($order['total']) ?></td>
<td>
<form method="POST">
<input type="hidden" name="order_id" value="<?= $order['orderid'] ?>">
<input type="hidden" name="update_status" value="1">

<select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
<option value="pending" <?= $status=='pending'?'selected':'' ?>>Pending</option>
<option value="preparing" <?= $status=='preparing'?'selected':'' ?>>Preparing</option>
<option value="ready" <?= $status=='ready'?'selected':'' ?>>Ready</option>
<option value="delivered" <?= $status=='delivered'?'selected':'' ?>>Delivered</option>
<option value="cancelled" <?= $status=='cancelled'?'selected':'' ?>>Cancelled</option>
</select>
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