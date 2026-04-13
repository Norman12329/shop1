<?php
session_start();
$host = 'localhost';
$dbname = 'system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function validatePassword($password) {
    return preg_match('/^[a-zA-Z0-9]{8,}$/', $password);
}

// --- Encryption settings ---
define('EMAIL_ENCRYPTION_KEY', '12345678901234567890123456789012'); // 32 bytes
define('EMAIL_ENCRYPTION_METHOD', 'AES-256-CBC');

function encryptEmail($email) {
    $ivLength = openssl_cipher_iv_length(EMAIL_ENCRYPTION_METHOD);
    $iv = openssl_random_pseudo_bytes($ivLength);
    $encrypted = openssl_encrypt($email, EMAIL_ENCRYPTION_METHOD, EMAIL_ENCRYPTION_KEY, 0, $iv);
    return base64_encode($iv . $encrypted); // store IV + ciphertext
}

function decryptEmail($encryptedEmail) {
    $data = base64_decode($encryptedEmail);
    $ivLength = openssl_cipher_iv_length(EMAIL_ENCRYPTION_METHOD);

    if (strlen($data) < $ivLength) return false; // corrupted data

    $iv = substr($data, 0, $ivLength);
    $ciphertext = substr($data, $ivLength);

    return openssl_decrypt($ciphertext, EMAIL_ENCRYPTION_METHOD, EMAIL_ENCRYPTION_KEY, 0, $iv);
}
?>