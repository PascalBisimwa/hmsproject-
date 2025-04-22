<?php
define('HMS_ACCESS', true);
require __DIR__ . '/../include/security.php';
require __DIR__ . '/../include/connection.php';
require __DIR__ . '/../include/functions.php';

$pdo = Database::getInstance();
initSession();
checkAuth(['admin']);

// Vérification CSRF
if (!verifyCsrfToken($_GET['csrf_token'] ?? '')) {
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Token de sécurité invalide'];
    header('Location: manage_users.php');
    exit();
}

$userId = (int)$_GET['id'];

// Récupérer le rôle avant suppression
try {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Utilisateur introuvable'];
        header('Location: manage_users.php');
        exit();
    }

    $pdo->beginTransaction();

    // Suppression des données liées selon le rôle
    switch ($user['role']) {
        case 'doctor':
            $pdo->prepare("DELETE FROM doctors WHERE user_id = ?")->execute([$userId]);
            break;
        case 'patient':
            $pdo->prepare("DELETE FROM patients WHERE user_id = ?")->execute([$userId]);
            break;
        case 'receptionist':
            $pdo->prepare("DELETE FROM receptionists WHERE user_id = ?")->execute([$userId]);
            break;
    }

    // Suppression du compte principal
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);

    $pdo->commit();
    $_SESSION['alert'] = ['type' => 'success', 'message' => 'Utilisateur supprimé avec succès'];

} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()];
}

header('Location: manage_users.php');
exit();