<?php
session_start();
// En tête de vos fichiers PHP
require_once __DIR__ . '/../include/connection.php';
require_once __DIR__ . '/../include/security.php';
// 1. Récupération de l'ID du rapport
$report_id = (int)($_GET['report_id'] ?? 0);
if ($report_id <= 0) {
    die("ID de rapport invalide");
}

// 2. Requête améliorée pour récupérer le patient
try {
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            p.first_name,
            p.last_name,
            p.full_name,
            DATE_FORMAT(r.report_date, '%d/%m/%Y') as formatted_date
        FROM reports r
        JOIN patients p ON r.patient_id = p.user_id
        WHERE r.id = ? AND r.doctor_id = ?
    ");
    $stmt->execute([$report_id, $_SESSION['user_id']]);
    $report = $stmt->fetch();

    if (!$report) {
        die("Rapport introuvable ou accès refusé");
    }

    // 3. Formatage des données avec valeurs par défaut
    $patientName = htmlspecialchars(
        $report['full_name'] ?? 
        trim($report['first_name'] . ' ' . $report['last_name']) ?? 
        'Patient inconnu'
    );

} catch (PDOException $e) {
    die("Erreur base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport Médical</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .report-card {
            max-width: 700px;
            margin: 2rem auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        .report-header {
            background: #3498db;
            color: white;
            padding: 1.5rem;
        }
        .report-body {
            padding: 2rem;
            background: white;
        }
        .patient-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .report-content {
            white-space: pre-line;
            line-height: 1.8;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .btn-download {
            background: #28a745;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="report-card">
            <div class="report-header text-center">
                <h1 class="h4 mb-0">Rapport Médical</h1>
            </div>
            
            <div class="report-body">
                <!-- Section Patient -->
                <div class="patient-info">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Patient :</strong></p>
                            <p class="h5"><?= $patientName ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Date :</strong></p>
                            <p class="h5"><?= $report['formatted_date'] ?? 'Non spécifiée' ?></p>
                        </div>
                    </div>
                </div>

                <!-- Contenu du rapport -->
                <div class="mb-4">
                    <h2 class="h5 mb-3 text-primary">Observations Médicales</h2>
                    <div class="report-content">
                        <?= htmlspecialchars($report['report_content'] ?? 'Aucun contenu disponible') ?>
                    </div>
                </div>

                <!-- Fichier joint -->
                <?php if (!empty($report['file_path'])): ?>
                <div class="text-center mt-4">
                    <a href="/Hms/uploads/reports/<?= htmlspecialchars(basename($report['file_path'])) ?>" 
                       class="btn btn-download"
                       download>
                       <i class="fas fa-download"></i> Télécharger le dossier médical
                    </a>
                </div>
                <?php endif; ?>

                <!-- Bouton Retour -->
                <div class="text-end mt-4">
                    <a href="doctor_reports.php" class="btn btn-outline-primary">
                        ← Retour à la liste
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html>