<?php
require 'config.php';
// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Fetch REAL stats - ROUND TO WHOLE PESO
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue = round($pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn());
$total_products = $pdo->query("SELECT COUNT(*) FROM food_items")->fetchColumn();
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

// 7-Day Revenue for chart - WHOLE PESO
$daily_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $revenue = round($pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE DATE(created_at) = '$date' AND status != 'cancelled'")->fetchColumn());
    $daily_data[] = ['day' => date('M d', strtotime($date)), 'revenue' => (int)$revenue];
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* YOUR EXACT SAME STYLES - NO CHANGES */
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
        .card { border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .border-left-primary { border-left: 4px solid #4e73df !important; }
        .border-left-success { border-left: 4px solid #1cc88a !important; }
        .border-left-info { border-left: 4px solid #36b9cc !important; }
        .border-left-warning { border-left: 4px solid #f6c23e !important; }
        .chart-container { height: 350px; position: relative; }
        .peso-sign::before { content: '₱'; margin-right: 4px; }
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
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users.php">
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
                <li class="nav-item">
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog me-2"></i> Settings
                    </a>
                </li>
                <li class="nav-item mt-3">
                    <a class="nav-link text-danger bg-danger bg-opacity-25" href="login.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-3 mb-4 border-bottom">
            <h1 class="h2 fw-bold text-dark">Dashboard Overview</h1>
        </div>

        <!-- STATS CARDS - PESO + WHOLE NUMBERS -->
        <div class="row mb-5">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-3">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-900"><?php echo number_format($total_users); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-3">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Orders</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-900"><?php echo number_format($total_orders); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-3">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Products</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-900"><?php echo number_format($total_products); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-box fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-3">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Revenue</div>
                                <div class="h4 mb-0 font-weight-bold text-gray-900 peso-sign"><?php echo number_format($total_revenue); ?> ₱</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-peso-sign fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row">
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">📈 Revenue - Last 7 Days</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-warning text-dark">
                        <h6 class="m-0 font-weight-bold">⏳ Pending Orders</h6>
                    </div>
                    <div class="card-body text-center py-4">
                        <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                        <div class="h2 font-weight-bold mb-2"><?php echo $pending_orders; ?></div>
                        <small class="text-muted">Orders waiting</small>
                        <a href="orders.php?status=pending" class="btn btn-warning mt-3">View Orders</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity & Quick Actions -->
        <div class="row">
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">Recent Activity</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Activity</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $recent = $pdo->query("SELECT 'New order placed' as activity, created_at, status FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll();
                                    foreach($recent as $activity): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($activity['activity']); ?></td>
                                        <td><?php echo date('M j', strtotime($activity['created_at'])); ?></td>
                                        <td><span class="badge bg-<?php echo $activity['status']=='delivered' ? 'success' : 'warning'; ?>"><?php echo ucfirst($activity['status']); ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-success text-white">
                        <h6 class="m-0 font-weight-bold">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <a href="users.php" class="btn btn-primary btn-lg w-100 mb-3">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </a>
                        <a href="products.php" class="btn btn-success btn-lg w-100 mb-3">
                            <i class="fas fa-box me-2"></i>Manage Products
                        </a>
                        <a href="orders.php" class="btn btn-info btn-lg w-100 mb-3">
                            <i class="fas fa-shopping-cart me-2"></i>View Orders
                        </a>
                        <a href="analytics.php" class="btn btn-warning btn-lg w-100">
                            <i class="fas fa-chart-line me-2"></i>Detailed Analytics
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Revenue Chart - PESO
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($daily_data, 'day')); ?>,
                datasets: [{
                    label: 'Revenue (₱)',
                    data: <?php echo json_encode(array_column($daily_data, 'revenue')); ?>,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { 
                    y: { 
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>