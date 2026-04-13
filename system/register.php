<?php
require 'config.php'; // contains sanitize(), encryptEmail(), decryptEmail(), etc.

$errors = [];
$success = '';

if ($_POST) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // --- Validation ---
    if (empty($name) || strlen($name) < 2) $errors[] = "Full name must be at least 2 characters long";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
    if (empty($phone) || !preg_match('/^\d{11}/', $phone)) $errors[] = "Phone number must be exactly 11 digits";
    if (empty($address) || strlen($address) < 10) $errors[] = "Delivery address must be at least 10 characters";
    if (empty($password) || !preg_match('/^[a-zA-Z0-9]{8}/', $password)) $errors[] = "Password must be exactly 8 alphanumeric characters";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";

    // --- Encrypt email and hash phone ---
    $encryptedEmail = encryptEmail($email);
    $hashedPhone = password_hash($phone, PASSWORD_DEFAULT);

    // --- Duplicate checks ---
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR name = ?");
        $stmt->execute([$encryptedEmail, $name]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if (decryptEmail($existing['email']) === $email) $errors[] = "Email is already registered. <a href='login.php'>Login here</a>";
            if ($existing['name'] === $name) $errors[] = "Full name/username is already used. Please choose another.";
        }

        // For phone, check manually because it's hashed
        $stmt2 = $pdo->query("SELECT phone FROM users");
        while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            if (password_verify($phone, $row['phone'])) {
                $errors[] = "Phone number is already registered. Use another number.";
                break;
            }
        }
    }

    // --- Insert user ---
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, address, password) VALUES (?, ?, ?, ?, ?)");
        try {
            if ($stmt->execute([$name, $encryptedEmail, $hashedPhone, $address, $hashedPassword])) {
                $success = "Registration successful! You can now <a href='login.php'>login</a>.";
                $_POST = []; // clear form
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FoodHub - Register</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
body { background: linear-gradient(135deg,#ff9a9e 0%,#fecfef 50%,#fecfef 100%); min-height:100vh; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;}
.register-card {box-shadow:0 20px 40px rgba(0,0,0,0.1);border:none;border-radius:20px;overflow:hidden;}
.card-header {background:linear-gradient(45deg,#ff6b6b,#ee5a24);color:white;border:none;}
.btn-register {background:linear-gradient(45deg,#ff6b6b,#ee5a24);border:none;padding:12px;font-weight:600;border-radius:10px;}
.btn-register:hover {transform:translateY(-2px);box-shadow:0 10px 20px rgba(255,107,107,0.4);}
.form-control:focus {border-color:#ff6b6b;box-shadow:0 0 0 0.2rem rgba(255,107,107,0.25);}
</style>
</head>
<body class="d-flex align-items-center min-vh-100 py-5">
<main class="container">
<div class="row justify-content-center">
<div class="col-lg-6 col-md-8 col-sm-10">
<div class="card register-card">
<div class="card-header text-center py-4">
<div class="mb-3">
<i class="fas fa-utensils fa-3x text-white mb-3"></i>
<h2 class="mb-1">Welcome to FoodHub</h2>
<p class="mb-0 opacity-90">Create your account to start ordering</p>
</div>
</div>
<div class="card-body p-4 p-md-5">

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
<i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
<ul class="mb-0">
<?php foreach ($errors as $error): ?><li><?php echo $error; ?></li><?php endforeach; ?>
</ul>
</div>
<?php endif; ?>

<form method="POST" novalidate>
<div class="row">
<div class="col-md-6 mb-3">
<label class="form-label fw-bold"><i class="fas fa-user me-1 text-danger"></i>Full Name / Username</label>
<input type="text" name="name" class="form-control" value="<?php echo $_POST['name'] ?? ''; ?>" placeholder="Enter your full name or username" required>
</div>
<div class="col-md-6 mb-3">
<label class="form-label fw-bold"><i class="fas fa-phone me-1 text-danger"></i>Phone Number</label>
<input type="tel" name="phone" class="form-control" value="<?php echo $_POST['phone'] ?? ''; ?>" placeholder="Enter 9-digit phone number" required>
<div class="form-text">Exactly 9 digits only</div>
</div>
</div>

<div class="mb-3">
<label class="form-label fw-bold"><i class="fas fa-envelope me-1 text-danger"></i>Email Address</label>
<input type="email" name="email" class="form-control" value="<?php echo $_POST['email'] ?? ''; ?>" placeholder="your.email@example.com" required>
<div class="form-text">We'll never share your email with anyone else.</div>
</div>

<div class="mb-3">
<label class="form-label fw-bold"><i class="fas fa-map-marker-alt me-1 text-danger"></i>Delivery Address</label>
<textarea name="address" class="form-control" rows="3" placeholder="Enter your complete delivery address" required><?php echo $_POST['address'] ?? ''; ?></textarea>
<div class="form-text">Include landmarks, apartment number, etc.</div>
</div>

<div class="row">
<div class="col-md-6 mb-3">
<label class="form-label fw-bold"><i class="fas fa-lock me-1 text-danger"></i>Password</label>
<input type="password" name="password" class="form-control" placeholder="Exactly 8 alphanumeric characters" required>
<div class="form-text">Must be exactly 8 letters & numbers</div>
</div>
<div class="col-md-6 mb-3">
<label class="form-label fw-bold"><i class="fas fa-lock me-1 text-danger"></i>Confirm Password</label>
<input type="password" name="confirm_password" class="form-control" placeholder="Repeat your password" required>
</div>
</div>

<button type="submit" class="btn btn-register w-100 text-white fw-bold py-3"><i class="fas fa-user-plus me-2"></i>Create My Account</button>
</form>

<div class="text-center mt-4">
<p class="mb-0 text-muted">Already have an account? <a href="login.php" class="text-danger fw-bold text-decoration-none"><i class="fas fa-sign-in-alt me-1"></i>Login Now</a></p>
</div>

</div>
<div class="card-footer bg-light text-center border-0"><small class="text-muted">🔒 Your data is secure with us | 🍕 Fast delivery guaranteed</small></div>
</div>
</div>
</div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>