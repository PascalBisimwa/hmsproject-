<?php
session_start();
// En tête de vos fichiers PHP
require_once __DIR__ . '/../include/connection.php';
require_once __DIR__ . '/../include/security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'doctor') {
    $sender_id = $_SESSION['user_id'];
    $recipient_id = $_POST['recipient_id'];
    $content = trim($_POST['content']);

    try {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, recipient_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$sender_id, $recipient_id, $content]);
        
        $_SESSION['success'] = "Message envoyé avec succès";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'envoi du message";
    }
}

header("Location: doctors_dashboard.php");
exit();
?>