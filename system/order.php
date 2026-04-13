<?php
require 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$food_id = (int)$_GET['id'];

// Fetch food item
$stmt = $pdo->prepare("
    SELECT fi.*, cat.category 
    FROM food_items fi 
    LEFT JOIN (SELECT category, MAX(price) as max_price FROM food_items GROUP BY category) cat 
    ON fi.category = cat.category 
    WHERE fi.id = ? AND fi.is_available = 1 AND fi.stock > 0
");
$stmt->execute([$food_id]);
$food = $stmt->fetch();

if (!$food) {
    $_SESSION['error'] = 'Food item not available or out of stock!';
    header('Location: index.php');
    exit;
}

// Ensure price is whole number (remove decimals)
$food['price'] = (int)$food['price'];

// Fetch user details for default address
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT name, phone, address FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle order placement
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = (int)$_POST['quantity'];
    $delivery_address = sanitize($_POST['delivery_address']);
    $special_instructions = sanitize($_POST['special_instructions'] ?? '');
    
    // Validation
    if ($quantity < 1 || $quantity > $food['stock']) {
        $error = "Invalid quantity. Available: " . $food['stock'];
    } elseif (strlen($delivery_address) < 10) {
        $error = "Delivery address must be at least 10 characters";
    } else {
        $total_price = $quantity * $food['price'];
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, food_item_id, quantity, total_price, delivery_address, special_instructions) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if ($stmt->execute([$user_id, $food_id, $quantity, $total_price, $delivery_address, $special_instructions])) {
                $success = "Order placed successfully! Order ID: #" . $pdo->lastInsertId();
                // Clear form
                $_POST = [];
            } else {
                $error = "Failed to place order. Please try again.";
            }
        } catch (PDOException $e) {
            $error = "Order failed: " . $e->getMessage();
        }
    }
}

// Calculate dynamic pricing
$base_price = $food['price'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order <?php echo htmlspecialchars($food['name']); ?> - FoodHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ff6b6b;
            --primary-gradient: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .order-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: none;
            overflow: hidden;
        }
        
        .food-image {
            height: 300px;
            object-fit: cover;
            border-radius: 20px 20px 0 0;
        }
        
        .price-badge {
            background: var(--primary-gradient);
            color: white;
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .btn-order {
            background: var(--primary-gradient);
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .btn-order:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(255,107,107,0.4);
            color: white !important;
        }
        
        .quantity-btn {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: none;
            font-size: 1.2rem;
            transition: all 0.2s ease;
        }
        
        .quantity-btn:hover {
            transform: scale(1.1);
        }
        
        .stock-badge {
            font-size: 0.85rem;
        }
        
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
        }
        
        .peso-symbol {
            font-size: 0.8em;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .food-image { height: 250px; }
        }
    </style>
</head>
<body class="py-4 py-lg-5">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-transparent position-sticky top-0 z-3">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3" href="index.php">
                <i class="fas fa-utensils me-2"></i>FoodHub
            </a>
            <a href="index.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-arrow-left me-2"></i>Back to Menu
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9">
                <!-- Success/Error Messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4 shadow-sm" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle fa-2x me-3 text-success flex-shrink-0"></i>
                            <div>
                                <h5 class="mb-1">Order Placed Successfully!</h5>
                                <p class="mb-0"><?php echo $success; ?></p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <div class="mt-3">
                            <a href="profile.php" class="btn btn-success me-2">
                                <i class="fas fa-receipt me-1"></i>View Orders
                            </a>
                            <a href="index.php" class="btn btn-outline-success">
                                <i class="fas fa-plus me-1"></i>Order More
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Order Form -->
                <?php if (!$success): ?>
                <div class="order-card shadow-lg mb-5">
                    <div class="row g-0">
                        <!-- Food Image & Details -->
                        <div class="col-lg-5">
                            <img src="images/<?php echo htmlspecialchars($food['image']); ?>" 
                                 class="food-image w-100" 
                                 alt="<?php echo htmlspecialchars($food['name']); ?>"
                                 onerror="this.src='https://via.placeholder.com/400x300/ddd?text=No+Image'">
                            
                            <div class="p-4">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h1 class="h3 fw-bold mb-2"><?php echo htmlspecialchars($food['name']); ?></h1>
                                    <span class="badge bg-success stock-badge">
                                        <i class="fas fa-box me-1"></i>
                                        <?php echo $food['stock']; ?> available
                                    </span>
                                </div>
                                
                                <p class="lead mb-3"><?php echo htmlspecialchars($food['description']); ?></p>
                                
                                <div class="mb-3">
                                    <span class="badge bg-primary mb-2 d-block">
                                        <i class="fas fa-tag me-1"></i>
                                        <?php echo ucfirst($food['category']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order Form -->
                        <div class="col-lg-7 p-4 p-lg-5">
                            <form method="POST">
                                <!-- Quantity Selector -->
                                <div class="row align-items-center mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold fs-5 mb-2">Quantity</label>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="input-group">
                                            <button type="button" class="btn btn-outline-secondary quantity-btn" onclick="updateQuantity(-1)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" 
                                                   name="quantity" 
                                                   id="quantityInput"
                                                   class="form-control text-center fs-4 fw-bold border-0" 
                                                   min="1" max="<?php echo $food['stock']; ?>" 
                                                   value="1" required>
                                            <button type="button" class="btn btn-outline-secondary quantity-btn" onclick="updateQuantity(1)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                            <span class="input-group-text bg-transparent border-0">
                                                Max: <?php echo $food['stock']; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Price -->
                                <div class="mb-4 text-center">
                                    <div class="price-badge mb-2">
                                        <span id="unitPrice"><?php echo number_format($base_price); ?></span>
                                        <span class="peso-symbol">₱</span>
                                        <small class="ms-2">per item</small>
                                    </div>
                                    <div class="h4 fw-bold text-success mb-0">
                                        Total: <span id="totalPrice"><?php echo number_format($base_price); ?></span>
                                        <span class="peso-symbol">₱</span>
                                    </div>
                                </div>
                                
                                <!-- Delivery Address -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                        Delivery Address
                                    </label>
                                    <textarea name="delivery_address" 
                                              class="form-control" 
                                              rows="3"
                                              placeholder="Enter complete delivery address (street, apt, landmarks)"
                                              required><?php echo htmlspecialchars($user['address'] ?? $_POST['delivery_address'] ?? ''); ?></textarea>
                                </div>
                                
                                <!-- Special Instructions -->
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-sticky-note me-2 text-warning"></i>
                                        Special Instructions (Optional)
                                    </label>
                                    <textarea name="special_instructions" 
                                              class="form-control" 
                                              rows="2"
                                              placeholder="e.g., Call before delivery, leave at door, extra spicy, etc."
                                              maxlength="200"><?php echo htmlspecialchars($_POST['special_instructions'] ?? ''); ?></textarea>
                                    <div class="form-text">Max 200 characters</div>
                                </div>
                                
                                <!-- Order Button -->
                                <button type="submit" class="btn btn-order btn-lg w-100 text-white mb-3 shadow-lg">
                                    <i class="fas fa-shopping-cart me-2"></i>
                                    Place Order Now
                                </button>
                                
                                <div class="text-center">
                                    <small class="text-muted">
                                        <i class="fas fa-lock me-1"></i>
                                        Secure checkout | Estimated delivery: 25-35 mins
                                    </small>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const maxStock = <?php echo $food['stock']; ?>;
        const unitPrice = <?php echo $base_price; ?>;
        
        function updateQuantity(change) {
            const input = document.getElementById('quantityInput');
            let newValue = parseInt(input.value) + change;
            
            if (newValue < 1) newValue = 1;
            if (newValue > maxStock) newValue = maxStock;
            
            input.value = newValue;
            calculateTotal();
        }
        
        function calculateTotal() {
            const quantity = parseInt(document.getElementById('quantityInput').value);
            const total = quantity * unitPrice;
            
            document.getElementById('totalPrice').textContent = Math.round(total);
            document.getElementById('unitPrice').textContent = Math.round(unitPrice);
        }
        
        // Initialize
        document.getElementById('quantityInput').addEventListener('input', calculateTotal);
        calculateTotal();
        
        // Form validation
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const quantity = parseInt(document.getElementById('quantityInput').value);
            if (quantity < 1 || quantity > maxStock) {
                e.preventDefault();
                alert('Please select a valid quantity (1-' + maxStock + ')');
                return false;
            }
        });
    </script>
</body>
</html>