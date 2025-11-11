<?php
$pdo = new PDO('sqlite:/var/www/html/database/budget.db');

// Delete old user
$pdo->exec("DELETE FROM users WHERE email='test@example.com'");

// Create new test user
$hashedPassword = password_hash('test123', PASSWORD_BCRYPT);
$stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, currency, timezone, created_at) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->execute(['Test User', 'test@example.com', $hashedPassword, 'CZK', 'Europe/Prague', date('Y-m-d H:i:s')]);

// Verify it was created
$result = $pdo->query("SELECT id, email, password_hash FROM users WHERE email='test@example.com'");
$user = $result->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "✅ User created successfully\n";
    echo "Email: " . $user['email'] . "\n";

    // Test password
    $test = password_verify('test123', $user['password_hash']);
    echo "Password verify test: " . ($test ? "✅ PASS" : "❌ FAIL") . "\n";
    echo "Ready to login with: test@example.com / test123\n";
} else {
    echo "❌ Failed to create user\n";
}
?>
