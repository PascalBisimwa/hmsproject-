<?php
session_start();
require __DIR__ . '/../include/connection.php';

echo "<h2>Test de rôle</h2>";
echo "<pre>Session: ";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['user_id'])) {
    $pdo = Database::getInstance();
    $user = $pdo->query("SELECT * FROM users WHERE id = ".$_SESSION['user_id'])->fetch();
    
    echo "<pre>DB User: ";
    print_r($user);
    echo "</pre>";
    
    if ($user['role'] === 'doctor') {
        $doctor = $pdo->query("SELECT * FROM doctors WHERE user_id = ".$_SESSION['user_id'])->fetch();
        echo "<pre>Doctor Data: ";
        print_r($doctor);
        echo "</pre>";
    }
}