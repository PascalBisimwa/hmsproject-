<?php
// 1. Définition de la constante de sécurité
define('HMS_ACCESS', true);

// 2. Inclusion des fichiers nécessaires dans le bon ordre
require_once __DIR__.'/../include/connection.php';  // D'abord la connexion DB
require_once __DIR__.'/../include/functions.php';   // Ensuite les fonctions
require_once __DIR__.'/../include/security.php';    // Enfin la sécurité

// 3. Initialisation
$pdo = Database::getInstance();
initSession();
checkAuth(['admin', 'doctor', 'receptionist']);

// 4. Initialisation des variables
$error = null;
$patients = [];
$doctors = [];
$csrfToken = generateCsrfToken();

try {
    // Récupération des patients actifs
    $stmt = $pdo->prepare("SELECT id, CONCAT(first_name, ' ', last_name) AS name 
                         FROM users 
                         WHERE role = 'patient' AND deleted_at IS NULL
                         ORDER BY last_name, first_name");
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupération des médecins actifs avec spécialité
    $stmt = $pdo->prepare("SELECT id, CONCAT(first_name, ' ', last_name) AS name, specialty
                         FROM users 
                         WHERE role = 'doctor' AND deleted_at IS NULL
                         ORDER BY last_name, first_name");
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors du chargement des données. Veuillez réessayer.";
    logError("Erreur création rapport (chargement données): " . $e->getMessage());
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Vérification CSRF
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            throw new Exception("Token de sécurité invalide. Veuillez rafraîchir la page.");
        }

        // Validation des données
        $requiredFields = [
            'patient_id' => "Patient requis",
            'report_content' => "Contenu du rapport requis"
        ];
        
        foreach ($requiredFields as $field => $message) {
            if (empty($_POST[$field])) {
                throw new Exception($message);
            }
        }

        // Vérification que le patient existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'patient' AND deleted_at IS NULL");
        $stmt->execute([(int)$_POST['patient_id']]);
        if (!$stmt->fetch()) {
            throw new Exception("Patient sélectionné invalide");
        }

        // Vérification du médecin si spécifié
        if (!empty($_POST['doctor_id'])) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'doctor' AND deleted_at IS NULL");
            $stmt->execute([(int)$_POST['doctor_id']]);
            if (!$stmt->fetch()) {
                throw new Exception("Médecin sélectionné invalide");
            }
        }

        // Préparation des données
        $data = [
            'patient_id' => (int)$_POST['patient_id'],
            'doctor_id' => !empty($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : null,
            'report_content' => sanitizeInput($_POST['report_content']),
            'priority' => in_array($_POST['priority'] ?? '', ['low', 'medium', 'high']) ? $_POST['priority'] : 'medium',
            'is_draft' => isset($_POST['is_draft']) ? 1 : 0,
            'author_id' => $_SESSION['user_id'],
            'status' => isset($_POST['is_draft']) ? 'draft' : 'submitted'
        ];

        // Insertion en base
        $stmt = $pdo->prepare("INSERT INTO reports 
                             (patient_id, doctor_id, report_content, priority, 
                              is_draft, author_id, status, created_at, updated_at) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        
        $stmt->execute([
            $data['patient_id'],
            $data['doctor_id'],
            $data['report_content'],
            $data['priority'],
            $data['is_draft'],
            $data['author_id'],
            $data['status']
        ]);

        // Redirection avec message de succès
        $_SESSION['success'] = "Rapport " . ($data['is_draft'] ? "enregistré comme brouillon" : "créé") . " avec succès";
        header("Location: manage_reports.php");
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
        logError("Erreur création rapport: " . $e->getMessage());
        
        // Conservation des données du formulaire en cas d'erreur
        $_POST = array_map('safeOutput', $_POST);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Rapport | Kibris Aydin Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        }
        .required-field::after {
            content: " *";
            color: red;
        }
        textarea {
            min-height: 300px;
        }
        .is-invalid {
            border-color: #dc3545;
        }
        .invalid-feedback {
            color: #dc3545;
            display: none;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../include/navbar.php'; ?>
    
    <div class="container">
        <div class="form-container">
            <h2 class="mb-4"><i class="fas fa-file-medical me-2"></i> Nouveau Rapport Médical</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= safeOutput($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="post" id="reportForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= safeOutput($csrfToken) ?>">
                
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="patient_id" class="form-label required-field">Patient</label>
                        <select name="patient_id" id="patient_id" class="form-select" required>
                            <option value="">Sélectionner un patient</option>
                            <?php foreach ($patients as $patient): ?>
                                <option value="<?= safeOutput($patient['id']) ?>" 
                                    <?= ($_POST['patient_id'] ?? '') == $patient['id'] ? 'selected' : '' ?>>
                                    <?= safeOutput($patient['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Veuillez sélectionner un patient</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="doctor_id" class="form-label">Médecin assigné</label>
                        <select name="doctor_id" id="doctor_id" class="form-select">
                            <option value="">Non assigné</option>
                            <?php foreach ($doctors as $doctor): ?>
                                <option value="<?= safeOutput($doctor['id']) ?>" 
                                    <?= ($_POST['doctor_id'] ?? '') == $doctor['id'] ? 'selected' : '' ?>
                                    data-specialty="<?= safeOutput($doctor['specialty'] ?? '') ?>">
                                    <?= safeOutput($doctor['name']) ?>
                                    <?= !empty($doctor['specialty']) ? '('.safeOutput($doctor['specialty']).')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="priority" class="form-label">Priorité</label>
                        <select name="priority" id="priority" class="form-select">
                            <option value="low" <?= ($_POST['priority'] ?? '') == 'low' ? 'selected' : '' ?>>Basse</option>
                            <option value="medium" <?= ($_POST['priority'] ?? 'medium') == 'medium' ? 'selected' : '' ?>>Moyenne</option>
                            <option value="high" <?= ($_POST['priority'] ?? '') == 'high' ? 'selected' : '' ?>>Haute</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_draft" id="is_draft" value="1" 
                                   <?= ($_POST['is_draft'] ?? 0) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_draft">Enregistrer comme brouillon</label>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <label for="report_content" class="form-label required-field">Contenu du rapport</label>
                        <textarea name="report_content" id="report_content" class="form-control" required
                                  placeholder="Détails du rapport médical..."><?= $_POST['report_content'] ?? '' ?></textarea>
                        <div class="invalid-feedback">Le contenu du rapport est requis (minimum 50 caractères)</div>
                    </div>
                    
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-save me-2"></i> Enregistrer
                        </button>
                        <a href="manage_reports.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i> Annuler
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('reportForm');
            const reportContent = document.getElementById('report_content');
            
            // Validation en temps réel
            reportContent.addEventListener('input', function() {
                if (this.value.trim().length >= 50) {
                    this.classList.remove('is-invalid');
                    this.nextElementSibling.style.display = 'none';
                }
            });
            
            // Validation à la soumission
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Validation des champs requis
                const requiredFields = form.querySelectorAll('[required]');
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        field.nextElementSibling.style.display = 'block';
                        isValid = false;
                    }
                });
                
                // Validation spécifique du contenu
                if (reportContent.value.trim().length < 50) {
                    reportContent.classList.add('is-invalid');
                    reportContent.nextElementSibling.style.display = 'block';
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                    
                    // Scroll vers la première erreur
                    const firstInvalid = form.querySelector('.is-invalid');
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'center' 
                        });
                    }
                }
            });
        });
    </script>
</body>
</html>