<?php
require_once __DIR__ . '/../include/connection.php';
require_once __DIR__ . '/../include/security.php';

initSession();
checkAuth('doctor');

$csrfToken = generateCsrfToken();

try {
    $pdo = Database::getInstance();
    
    // Récupérer la liste des patients actifs
    $stmt = $pdo->query("
        SELECT p.user_id, CONCAT(u.first_name, ' ', u.last_name) as full_name
        FROM patients p
        JOIN users u ON p.user_id = u.id
        WHERE u.is_active = 1
        ORDER BY full_name
    ");
    $patients = $stmt->fetchAll();

} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de chargement des patients";
    header("Location: doctor_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- Votre en-tête HTML existant -->
</head>
<body>
    <div class="container">
        <h2>Nouveau Rapport Médical</h2>
        
        <form action="save_report.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            
            <div class="mb-3">
                <label>Patient</label>
                <select name="patient_id" class="form-control" required>
                    <option value="">Choisir un patient...</option>
                    <?php foreach ($patients as $patient): ?>
                        <option value="<?= $patient['user_id'] ?>">
                            <?= sanitize($patient['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label>Contenu du rapport</label>
                <textarea name="report_content" class="form-control" rows="10" required></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </form>
    </div>
</body>
</html>