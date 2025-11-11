<?php
$dbPath = 'C:/ClaudeProjects/budget-control/budget-app/database/budget.db';

try {
    $pdo = new PDO("sqlite:$dbPath");

    // Count users
    $result = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $result->fetch(PDO::FETCH_ASSOC);
    echo "Total users: " . $count['count'] . "\n\n";

    // List users
    $result = $pdo->query("SELECT id, name, email FROM users LIMIT 10");
    $users = $result->fetchAll(PDO::FETCH_ASSOC);

    if (count($users) > 0) {
        echo "Users in database:\n";
        foreach ($users as $user) {
            echo "  - ID: {$user['id']}, Name: {$user['name']}, Email: {$user['email']}\n";
        }
    } else {
        echo "No users found in database!\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
