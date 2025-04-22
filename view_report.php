// view_report.php
<?php
session_start();
require __DIR__ . '/../include/connection.php';
require __DIR__ . '/../include/security.php';

$report = getReport($_GET['id'], $pdo); // Fonction à créer

if ($_SESSION['role'] === 'doctor') {
    // Affichage complet pour le docteur
    $content = json_decode($report['content'], true);
    ?>
    <div class="confidential-section">
        <h3>Notes confidentielles</h3>
        <p><?= nl2br(htmlspecialchars($content['private_notes'])) ?></p>
    </div>
    <?php
} elseif ($_SESSION['role'] === 'admin') {
    // Version allégée pour l'admin
    $content = json_decode($report['content'], true);
    ?>
    <div class="admin-view">
        <h3>Diagnostic (version admin)</h3>
        <p><?= nl2br(htmlspecialchars($content['diagnosis'])) ?></p>
        
        <h3>Traitement</h3>
        <p><?= nl2br(htmlspecialchars($content['treatment'])) ?></p>
    </div>
    <?php
}
<!-- Affichage détaillé du rapport -->
<div class="report-container">
    <h3>Rapport médical #<?= $report['id'] ?></h3>
    
    <div class="row">
        <div class="col-md-6">
            <p><strong>Médecin:</strong> <?= htmlspecialchars($report['doctor_name']) ?></p>
            <p><strong>Patient:</strong> <?= htmlspecialchars($report['patient_name']) ?></p>
        </div>
        <div class="col-md-6">
            <p><strong>Date:</strong> <?= date('d/m/Y H:i', strtotime($report['created_at'])) ?></p>
            <p><strong>Type:</strong> <?= htmlspecialchars($report['report_type']) ?></p>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">Détails médicaux</div>
        <div class="card-body">
            <h5>Motif</h5>
            <p><?= nl2br(htmlspecialchars($report['reason'])) ?></p>
            
            <h5 class="mt-4">Diagnostic</h5>
            <p><?= nl2br(htmlspecialchars($report['diagnosis'])) ?></p>
            
            <?php if (!empty($report['treatment'])): ?>
            <h5 class="mt-4">Traitement</h5>
            <p><?= nl2br(htmlspecialchars($report['treatment'])) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-4">
        <a href="approve_report.php?id=<?= $report['id'] ?>" class="btn btn-success">
            <i class="fas fa-check"></i> Valider le rapport
        </a>
        <a href="reports.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
</div>