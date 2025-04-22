<?php
// Activation du reporting d'erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
// En tête de vos fichiers PHP
require_once __DIR__ . '/../include/connection.php';
require_once __DIR__ . '/../include/security.php';

// Vérification de base de la session
if (!isset($_SESSION['user_id'])) {
    header("Location: /Hms/login.php");
    exit();
}

try {
    // 1. Vérification de l'existence de l'utilisateur
    $stmt = $pdo->prepare("SELECT id, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception("Votre compte utilisateur n'est plus valide");
    }

    // 2. Vérification du rôle
    if ($user['role'] !== 'doctor') {
        throw new Exception("Accès réservé aux médecins");
    }

    // 3. Vérification des champs obligatoires
    $requiredFields = [
        'patient_id' => "Patient manquant",
        'report_type' => "Type de rapport manquant",
        'comments' => "Contenu du rapport vide",
        'service_id' => "Service non spécifié",
        'department_id' => "Département non spécifié"
    ];

    foreach ($requiredFields as $field => $error) {
        if (empty($_POST[$field])) {
            throw new Exception($error);
        }
    }

    // 4. Vérification de l'existence du patient
    $stmt = $pdo->prepare("SELECT id FROM patients WHERE user_id = ?");
    $stmt->execute([(int)$_POST['patient_id']]);
    if (!$stmt->fetch()) {
        throw new Exception("Patient introuvable");
    }

    // 5. Gestion du fichier joint (optionnel)
    $filePath = ''; // Valeur par défaut vide plutôt que null
    if (!empty($_FILES['report_file']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/reports/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['report_file']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (!move_uploaded_file($_FILES['report_file']['tmp_name'], $targetPath)) {
            throw new Exception("Échec du téléchargement du fichier");
        }
        $filePath = $fileName;
    }

    // 6. Insertion du rapport (version adaptée)
    $sql = "INSERT INTO reports (
            doctor_id,
            patient_id,
            report_type,
            report_content,
            service_id,
            departement_id,
            author_id,
            file_path,
            report_date,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), NOW())";
    
    $params = [
        $_SESSION['user_id'],  // doctor_id
        (int)$_POST['patient_id'],
        htmlspecialchars($_POST['report_type']),
        htmlspecialchars($_POST['comments']),
        (int)$_POST['service_id'],
        (int)$_POST['department_id'],
        $_SESSION['user_id'],  // author_id
        $filePath,             // Chaîne vide si pas de fichier
    ];

    $stmt = $pdo->prepare($sql);
    if (!$stmt->execute($params)) {
        $errorInfo = $stmt->errorInfo();
        throw new PDOException("Erreur SQL: " . $errorInfo[2]);
    }

    $_SESSION['success'] = "Rapport créé avec succès (ID: " . $pdo->lastInsertId() . ")";
    header("Location: doctor_reports.php");
    exit();

} catch (PDOException $e) {
    error_log("PDO Error: " . $e->getMessage());
    $_SESSION['error'] = "Erreur technique: " . $e->getMessage();
    header("Location: create_report.php");
    exit();
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: create_report.php");
    exit();
}