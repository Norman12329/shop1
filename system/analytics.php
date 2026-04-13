<?php
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Get all stats
$total_revenue = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$delivered_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn();

// 7-DAY REVENUE DATA
$daily_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $revenue = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE DATE(created_at) = '$date' AND status != 'cancelled'")->fetchColumn();
    $daily_data[] = ['day' => date('M d', strtotime($date)), 'revenue' => $revenue];
}

// TOP PRODUCTS DATA
$top_products = $pdo->query("
    SELECT fi.name, COUNT(o.id) as order_count 
    FROM orders o 
    LEFT JOIN food_items fi ON o.food_item_id = fi.id 
    WHERE o.status != 'cancelled'
    GROUP BY o.food_item_id, fi.name 
    ORDER BY order_count DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$product_names = [];
$product_orders = [];
foreach ($top_products as $product) {
    $product_names[] = $product['name'];
    $product_orders[] = $product['order_count'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- FIXED PDF Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { 
            height: 100vh; 
            position: fixed; 
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%); 
            width: 280px; 
        }
        .main-content { margin-left: 280px; padding: 2rem; }
        .stat-card { border-left: 4px solid; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
        .chart-container { height: 350px; }
        .download-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: bold;
        }
        .download-btn:hover { transform: scale(1.05); color: white; }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <!-- FIXED SIDEBAR - DASHBOARD LOGO FIRST -->
    <div class="sidebar p-4 text-white">
        <h4 class="mb-4">👑 Admin Panel</h4>
        <ul class="nav flex-column">
            <!-- 🆕 DASHBOARD LOGO (Tachometer) - ACTIVE -->
            <li class="nav-item"><a class="nav-link text-white p-3 active" href="dashboard.php">📊 Dashboard</a></li>
            <!-- Analytics second -->
            <li class="nav-item"><a class="nav-link text-white p-3" href="analytics.php">📈 Analytics</a></li>
            <li class="nav-item"><a class="nav-link text-white p-3" href="orders.php">🛒 Orders</a></li>
            <li class="nav-item"><a class="nav-link text-white p-3" href="users.php">👥 Users</a></li>
            <li class="nav-item"><a class="nav-link text-white p-3" href="products.php">📦 Products</a></li>
            <li class="nav-item mt-4"><a class="nav-link text-danger p-3 bg-danger bg-opacity-25" href="logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <!-- 🆕 DASHBOARD TITLE -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1>📊 Dashboard</h1>
                <p>Business overview & key metrics</p>
            </div>
            <button class="download-btn shadow-lg" onclick="downloadPDF()">
                <i class="fas fa-file-pdf me-2"></i>Download PDF Report
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4" id="stats-section">
            <div class="col-md-3 mb-3">
                <div class="card stat-card border-left-primary bg-primary text-white shadow">
                    <div class="card-body text-center">
                        <h3>₱<?php echo number_format($total_revenue, 0); ?></h3>
                        <small>Total Revenue</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card border-left-success bg-success text-white shadow">
                    <div class="card-body text-center">
                        <h3><?php echo $total_orders; ?></h3>
                        <small>Total Orders</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card border-left-warning bg-warning text-dark shadow">
                    <div class="card-body text-center">
                        <h3><?php echo $pending_orders; ?></h3>
                        <small>Pending</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card border-left-info bg-info text-white shadow">
                    <div class="card-body text-center">
                        <h3><?php echo $delivered_orders; ?></h3>
                        <small>Delivered</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row" id="charts-section">
            <div class="col-md-6 mb-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h6>📈 Revenue - Last 7 Days</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h6>🍔 Top 5 Products</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="productsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Charts
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($daily_data, 'day')); ?>,
                datasets: [{ label: 'Revenue', data: <?php echo json_encode(array_column($daily_data, 'revenue')); ?>, 
                           borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,0.1)', fill: true, tension: 0.4 }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });

        new Chart(document.getElementById('productsChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($product_names); ?>,
                datasets: [{ label: 'Orders', data: <?php echo json_encode($product_orders); ?>, 
                           backgroundColor: ['#ff6384','#36a2eb','#ffcd56','#4bc0c0','#9966ff'] }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });

        // FIXED PDF - Perfect text
        async function downloadPDF() {
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF();
            
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(22);
            pdf.text('Dashboard Report', 20, 25);
            
            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(12);
            pdf.text('Generated: <?php echo date("M d, Y H:i A"); ?>', 20, 35);
            
            pdf.setFontSize(16);
            pdf.setFont('helvetica', 'bold');
            pdf.text('Key Metrics', 20, 55);
            
            pdf.setFontSize(14);
            pdf.setFont('helvetica', 'normal');
            pdf.text('Total Revenue: <?php echo number_format($total_revenue, 0); ?>', 20, 75);
            pdf.text('Total Orders: <?php echo $total_orders; ?>', 20, 90);
            pdf.text('Pending: <?php echo $pending_orders; ?>', 20, 105);
            pdf.text('Delivered: <?php echo $delivered_orders; ?>', 20, 120);
            
            pdf.save(`Dashboard_Report_<?php echo date('Y-m-d'); ?>.pdf`);
        }
    </script>
</body>
</html>