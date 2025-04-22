<?php
session_start();
// En tête de vos fichiers PHP
require_once __DIR__ . '/../include/connection.php';
require_once __DIR__ . '/../include/security.php';

if ($_SESSION['role'] !== 'doctor') {
    header("Location: /Hms/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: doctor_reports.php");
    exit();
}

// Récupération du rapport existant
$stmt = $pdo->prepare("SELECT * FROM reports WHERE id = ? AND doctor_id = ?");
$stmt->execute([$_GET['id'], $_SESSION['user_id']]);
$report = $stmt->fetch();

if (!$report) {
    $_SESSION['error'] = "Rapport introuvable";
    header("Location: doctor_reports.php");
    exit();
}

// Récupération de la liste des patients
$stmt = $pdo->prepare("SELECT user_id, full_name FROM patients ORDER BY full_name");
$stmt->execute();
$patients = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Traitement de la mise à jour (similaire à submit_report.php mais avec UPDATE)
    // ... [code de traitement similaire à submit_report.php]
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Rapport</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Modifier le rapport #<?= $report['id'] ?></h2>
        
        <form action="update_report.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
            
            <!-- Formulaire similaire à create_report.php mais avec valeurs pré-remplies -->
            <!-- ... -->
            
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Mettre à jour</button>
                <a href="view_report.php?id=<?= $report['id'] ?>" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</body>
</html>