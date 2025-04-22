<?php
session_start();
require __DIR__ . '/../include/connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: /Hms/login.php");
    exit();
}

if (empty($_GET['id'])) {
    header("Location: reports.php");
    exit();
}

try {
    // Validation du rapport
    $stmt = $pdo->prepare("UPDATE reports SET 
                          status = 'approved',
                          approved_by = ?,
                          approved_at = NOW()
                          WHERE id = ?");
    $stmt->execute([$_SESSION['user_id'], $_GET['id']]);

    // Notification au médecin (exemple simplifié)
    $pdo->prepare("INSERT INTO notifications 
                  (user_id, message, created_at)
                  VALUES (?, ?, NOW())")
       ->execute([
           $pdo->query("SELECT doctor_id FROM reports WHERE id = " . (int)$_GET['id'])->fetchColumn(),
           "Votre rapport #" . $_GET['id'] . " a été approuvé"
       ]);

    $_SESSION['success'] = "Rapport validé avec succès";
    header("Location: reports.php");
    exit();

} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur: " . $e->getMessage();
    header("Location: view_report.php?id=" . $_GET['id']);
    exit();
}