<?php
require 'config.php';

// Check admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Delete user
$message = '';
if ($_POST && isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    if ($stmt->execute([$user_id])) {
        $message = '<div class="alert alert-success alert-dismissible fade show">
            User deleted successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}

// Fetch users
$stmt = $pdo->prepare("
    SELECT id, name, email, role, created_at 
    FROM users 
    WHERE role != 'admin' OR role IS NULL
    ORDER BY created_at ASC
");
$stmt->execute();
$users = $stmt->fetchAll();

// Count
$total_users = count($users);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Users</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<style>
/* Sidebar & main content */
.sidebar {
    height: 100vh;
    position: fixed;
    z-index: 1000;
    background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
    color: white;
}
.sidebar .nav-link {
    color: rgba(255, 255, 255, 0.8);
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
.card { border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
.table th { background: #0940e7; font-weight: 600; }
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}
</style>
</head>

<body>

<!-- Sidebar -->
<nav id="sidebar" class="sidebar col-md-3 col-lg-2 d-md-block">
    <div class="position-sticky pt-4">
        <div class="text-center mb-4">
            <h4 class="text-white mb-0">👑 Admin Panel</h4>
            <small>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</small>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="users.php">
                    <i class="fas fa-users me-2"></i> Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="products.php">
                    <i class="fas fa-box me-2"></i> Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="orders.php">
                    <i class="fas fa-shopping-cart me-2"></i> Orders
                </a>
            </li>
            <li class="nav-item mt-3">
                <a class="nav-link text-danger bg-danger bg-opacity-25" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</nav>

<!-- Main content -->
<main class="main-content">

<div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
    <div>
        <h1 class="h2 fw-bold text-dark">User Management</h1>
        <p class="text-muted">Total Users: <strong><?php echo $total_users; ?></strong></p>
    </div>
</div>

<?php echo $message; ?>

<div class="card shadow">
<div class="card-body p-0">

<table class="table table-hover mb-0">
<thead>
<tr>
<th>#</th>
<th>Avatar</th>
<th>Name</th>
<th>Email</th>
<th>Role</th>
<th>Joined</th>
<th>Actions</th>
</tr>
</thead>
<tbody>

<?php if(empty($users)): ?>
<tr>
<td colspan="7" class="text-center py-4">No users found</td>
</tr>
<?php else: ?>
<?php foreach ($users as $i => $user): ?>
<tr class="align-middle">

<td><strong><?php echo $i+1; ?></strong></td>

<td>
<div class="user-avatar">
<?php echo strtoupper(substr($user['name'],0,2)); ?>
</div>
</td>

<td>
<strong><?php echo htmlspecialchars($user['name']); ?></strong>
<span class="text-muted">(#<?php echo $user['id']; ?>)</span>
</td>

<td>
    <small><?php echo encryptEmail($user['email']); ?></small>
</td>

<td><span class="badge bg-primary"><?php echo ucfirst($user['role'] ?? 'user'); ?></span></td>

<td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>

<td>
<form method="POST" onsubmit="return confirm('Delete this user?')">
<input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
<button type="submit" name="delete_user" class="btn btn-sm btn-outline-danger">
<i class="fas fa-trash"></i>
</button>
</form>
</td>

</tr>
<?php endforeach; ?>
<?php endif; ?>

</tbody>
</table>

</div>
</div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>