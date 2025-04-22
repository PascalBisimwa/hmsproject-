<?php
require_once __DIR__ . '/../include/connection.php';
require_once __DIR__ . '/../include/security.php';

initSession();
checkAuth('doctor');

// Vérification CSRF
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = "Token de sécurité invalide";
    header("Location: create_report.php");
    exit();
}

try {
    // Validation
    if (empty($_POST['patient_id']) || empty($_POST['report_content'])) {
        throw new Exception("Tous les champs sont obligatoires");
    }

    $pdo = Database::getInstance();

    // Vérification que le patient existe et est actif
    $stmt = $pdo->prepare("
        SELECT 1 
        FROM patients p
        JOIN users u ON p.user_id = u.id
        WHERE p.user_id = ? AND u.is_active = 1
    ");
    $stmt->execute([(int)$_POST['patient_id']]);
    
    if (!$stmt->fetch()) {
        throw new Exception("Patient non valide ou inactif");
    }

    // Insertion du rapport
    $stmt = $pdo->prepare("
        INSERT INTO reports (
            patient_id, 
            doctor_id, 
            report_content,
            report_date,
            created_at
        ) VALUES (?, ?, ?, NOW(), NOW())
    ");
    
    $stmt->execute([
        (int)$_POST['patient_id'],
        (int)$_SESSION['user_id'],
        strip_tags(trim($_POST['report_content']))
    ]);

    // Notification
    $reportId = $pdo->lastInsertId();
    $message = "Nouveau rapport #$reportId par Dr. " . $_SESSION['full_name'];
    
    $pdo->prepare("
        INSERT INTO notifications (user_id, message, status, created_at)
        SELECT id, ?, 'unread', NOW()
        FROM users 
        WHERE role = 'admin'
    ")->execute([$message]);

    $_SESSION['success'] = "Rapport enregistré avec succès";
    header("Location: doctor_reports.php");
    exit();

} catch (PDOException $e) {
    error_log("Erreur DB: " . $e->getMessage());
    $_SESSION['error'] = "Erreur technique";
    header("Location: create_report.php");
    exit();
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: create_report.php");
    exit();
}