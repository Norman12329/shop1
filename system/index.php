<?php
require_once 'config.php';

// Fetch all categories from user_menu
$stmt = $pdo->query("SELECT DISTINCT category FROM user_menu ORDER BY category");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch all products
$stmt = $pdo->query("SELECT * FROM user_menu ORDER BY category, name");
$foodItems = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>FoodHub - Our Products</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .food-card img { height: 200px; object-fit: cover; }
        .category-nav .nav-link.active { background: #dc3545; color: white; }
    </style>
</head>
<body class="bg-light">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-danger">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-utensils me-2"></i>FoodHub
        </a>
        <div class="collapse navbar-collapse justify-content-end">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="#menu">Menu</a></li>
                <li class="nav-item"><a class="nav-link" href="#about">About Us</a></li>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="admin/products.php"><i class="fas fa-box"></i> Admin Products</a></li>
                        <li class="nav-item"><a class="nav-link" href="admin/dashboard.php"><i class="fas fa-cog"></i> Admin</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="profile.php"><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['name']); ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">

    <!-- Category Navigation -->
    <ul class="nav nav-pills category-nav mb-4 justify-content-center flex-wrap" id="categoryNav">
        <li class="nav-item"><a class="nav-link active" href="#all" data-category="all">All</a></li>
        <?php foreach ($categories as $category): ?>
            <li class="nav-item">
                <a class="nav-link" href="#<?= $category; ?>" data-category="<?= $category; ?>"><?= ucfirst($category); ?></a>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- Food Items -->
    <h2 id="menu" class="mb-4 text-center text-danger">🍕 Our Products</h2>
    <div class="row" id="foodContainer">
        <?php foreach ($foodItems as $item): 
            $available = isset($item['is_available']) ? $item['is_available'] : 1; // default available
            $stock = isset($item['stock']) ? $item['stock'] : 10; // default stock
        ?>
            <div class="col-lg-3 col-md-6 mb-4 food-item" data-category="<?= $item['category']; ?>">
                <div class="card food-card h-100 shadow-sm">
                    <img src="uploads/products/<?= $item['image']; ?>" class="card-img-top" alt="<?= $item['name']; ?>">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= $item['name']; ?></h5>
                        <p class="card-text flex-grow-1"><?= substr($item['description'], 0, 80); ?>...</p>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4 class="text-danger mb-0">₱<?= number_format($item['price']); ?></h4>
                            <span class="badge <?= ($available && $stock > 0) ? 'bg-success' : 'bg-secondary'; ?>">
                                Stock: <?= $stock; ?>
                            </span>
                        </div>

                        <?php if (isset($_SESSION['user_id']) && $available && $stock > 0): ?>
                            <a href="order.php?id=<?= $item['id']; ?>" class="btn btn-danger btn-lg w-100">
                                <i class="fas fa-shopping-cart me-2"></i>Order Now
                            </a>
                        <?php elseif (!isset($_SESSION['user_id'])): ?>
                            <button class="btn btn-secondary btn-lg w-100" disabled>Login to Order</button>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-lg w-100" disabled>Unavailable</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- About Us -->
    <div id="about" class="mt-5 p-5 bg-white rounded shadow">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2 class="text-danger mb-4">🍔 About FoodHub</h2>
                <p class="lead">Welcome to FoodHub, your ultimate destination for delicious food delivery! We offer fresh ingredients, tasty recipes, and fast delivery to your doorstep.</p>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check-circle text-success me-2"></i>Fresh ingredients daily</li>
                    <li><i class="fas fa-check-circle text-success me-2"></i>Fast delivery within 30 mins</li>
                    <li><i class="fas fa-check-circle text-success me-2"></i>100% satisfaction guaranteed</li>
                </ul>
            </div>
            <div class="col-md-6 text-center">
                <i class="fas fa-pizza-slice fa-10x text-danger opacity-75"></i>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Category filter
    document.querySelectorAll('.category-nav .nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.category-nav .nav-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            const category = this.dataset.category;
            document.querySelectorAll('.food-item').forEach(item => {
                item.style.display = (category === 'all' || item.dataset.category === category) ? 'block' : 'none';
            });
        });
    });
</script>
</body>
</html>