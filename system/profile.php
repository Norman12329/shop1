<?php
require 'config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Decrypt email for display
$user['email'] = decryptEmail($user['email']);

// Update profile
if ($_POST && isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $password = trim($_POST['password']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email";
    } elseif (!empty($password) && !preg_match('/^[a-zA-Z0-9]{4,8}$/', $password)) {
        $error = "Password must be 4-8 alphanumeric";
    } else {
        // Encrypt email before saving
        $encryptedEmail = encryptEmail($email);

        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, phone=?, address=?, password=? WHERE id=?");
            $stmt->execute([$name, $encryptedEmail, $phone, $address, $hashedPassword, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, phone=?, address=? WHERE id=?");
            $stmt->execute([$name, $encryptedEmail, $phone, $address, $user_id]);
        }

        $success = "Profile updated";
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        $user['email'] = decryptEmail($user['email']);
    }
}

// Cancel order
if (isset($_GET['delete_order'])) {
    $stmt = $pdo->prepare("UPDATE orders SET status='cancelled' WHERE id=? AND user_id=?");
    $stmt->execute([(int)$_GET['delete_order'], $user_id]);
    header('Location: profile.php');
    exit;
}

// Orders
$stmt = $pdo->prepare("SELECT o.*, f.name as food_name FROM orders o JOIN food_items f ON o.food_item_id=f.id WHERE o.user_id=? ORDER BY o.created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#f1f5f9; font-family:Arial; }
.card { border-radius:15px; border:none; box-shadow:0 10px 20px rgba(0,0,0,0.05);} 
.btn-primary { background:#6366f1; border:none; }
.order { border:1px solid #eee; border-radius:10px; padding:10px; margin-bottom:10px; }
</style>
</head>
<body>
<div class="container py-4">

<h3 class="mb-3">My Profile</h3>
<a href="index.php" class="btn btn-light mb-3">← Back</a>

<div class="row">
<div class="col-md-8">
<div class="card p-4">

<?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
<?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

<form method="POST">
<input type="hidden" name="update_profile" value="1">

<input class="form-control mb-2" name="name" value="<?= htmlspecialchars($user['name']) ?>" placeholder="Name" required>
<input class="form-control mb-2" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" placeholder="Phone" required>
<input class="form-control mb-2" name="email" value="<?= htmlspecialchars($user['email']) ?>" placeholder="Email" required>
<textarea class="form-control mb-2" name="address" placeholder="Address" required><?= htmlspecialchars($user['address']) ?></textarea>
<input class="form-control mb-3" name="password" placeholder="New Password">

<button class="btn btn-primary w-100">Update</button>
</form>

</div>
</div>

<div class="col-md-4">
<div class="card p-3">
<h5>Orders (<?= count($orders) ?>)</h5>

<?php if(empty($orders)): ?>
<p>No orders</p>
<?php else: ?>

<?php foreach(array_slice($orders,0,5) as $o): ?>
<div class="order">
<strong><?= htmlspecialchars($o['food_name']) ?></strong><br>
Qty: <?= $o['quantity'] ?><br>
₱<?= $o['total_price'] ?><br>
<span class="badge bg-secondary"><?= htmlspecialchars($o['status']) ?></span>

<?php if($o['status']=='pending'): ?>
<a href="?delete_order=<?= $o['id'] ?>" class="btn btn-sm btn-danger w-100 mt-2">Cancel</a>
<?php endif; ?>

</div>
<?php endforeach; ?>

<?php endif; ?>

</div>
</div>

</div>
</div>
</body>
</html>