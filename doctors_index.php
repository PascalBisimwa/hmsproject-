<?php
/**
 * Point d'entrée spécifique pour les docteurs
 * Redirige vers le dashboard après vérification
 */

// 1. Initialisation et sécurité
session_start();

// 2. Vérification de session
if (!isset($_SESSION['user_id'])) {
    header("Location: /Hms/login.php");
    exit();
}

// 3. Vérification du rôle
if ($_SESSION['role'] !== 'doctor') {
    // Journalisation de la tentative d'accès non autorisée
    error_log("Tentative d'accès non autorisée au dashboard docteur par user ID: ".$_SESSION['user_id']);
    header("Location: /Hms/access_denied.php");
    exit();
}

// 4. Vérification de l'état du compte (exemple)
require_once __DIR__.'/../include/connection.php';
try {
    $stmt = $pdo->prepare("SELECT is_active FROM doctors WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $status = $stmt->fetchColumn();
    
    if (!$status) {
        $_SESSION['error'] = "Votre compte docteur est désactivé";
        header("Location: /Hms/logout.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Erreur vérification statut docteur: ".$e->getMessage());
    header("Location: /Hms/error.php?code=500");
    exit();
}

// 5. Redirection vers le dashboard
header("Location: /Hms/doctors/doctors_dashboard.php");
exit();
?>