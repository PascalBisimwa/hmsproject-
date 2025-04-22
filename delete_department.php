<?php
define('HMS_ACCESS', true);
require __DIR__ . '/../include/security.php';
require __DIR__ . '/../include/connection.php';

// Vérification admin
checkAuth('admin');

// Initialisation PDO
$pdo = Database::getInstance();

// Vérification ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    $_SESSION['error'] = "ID de département invalide";
    header('Location: manage_departments.php');
    exit();
}

$department_id = (int)$_GET['id'];

// Vérification existence département
try {
    $stmt = $pdo->prepare("SELECT id FROM departement WHERE id = ?");
    $stmt->execute([$department_id]);
    
    if (!$stmt->fetch()) {
        $_SESSION['error'] = "Département introuvable";
        header('Location: manage_departments.php');
        exit();
    }

    // Suppression
    $stmt = $pdo->prepare("DELETE FROM departement WHERE id = ?");
    $success = $stmt->execute([$department_id]);
    
    if ($success) {
        $_SESSION['message'] = "Département supprimé avec succès";
    } else {
        $_SESSION['error'] = "La suppression a échoué";
    }
    
    header('Location: manage_departments.php');
    exit();

} catch (PDOException $e) {
    error_log("Erreur suppression département: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors de la suppression";
    header('Location: manage_departments.php');
    exit();
}