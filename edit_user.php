<?php
define('HMS_ACCESS', true);
require __DIR__ . '/../include/security.php';
require __DIR__ . '/../include/connection.php';
require __DIR__ . '/../include/functions.php';

$pdo = Database::getInstance();
initSession();
checkAuth(['admin']);

$userId = (int)$_GET['id'];

// Fonction helper améliorée
function safeValue($value) {
    return $value !== null ? htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') : '';
}

// Récupérer l'utilisateur et données spécifiques
try {
    // Récupération utilisateur
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Utilisateur introuvable'];
        header('Location: manage_users.php');
        exit();
    }

    // Récupération données rôle spécifique avec valeurs par défaut
    $roleData = [
        'speciality' => '',
        'license_number' => '',
        'insurance_number' => '',
        'departement_id' => null,
        'service_id' => null
    ];
    
    if ($user['role'] === 'doctor') {
        $stmt = $pdo->prepare("SELECT * FROM doctors WHERE user_id = ?");
        $stmt->execute([$userId]);
        $doctorData = $stmt->fetch();
        if ($doctorData) {
            $roleData = array_merge($roleData, $doctorData);
        }
    } elseif ($user['role'] === 'patient') {
        $stmt = $pdo->prepare("SELECT * FROM patients WHERE user_id = ?");
        $stmt->execute([$userId]);
        $patientData = $stmt->fetch();
        if ($patientData) {
            $roleData = array_merge($roleData, $patientData);
        }
    }

    // Liste des spécialités pour les médecins
    $specialities = [];
    if ($user['role'] === 'doctor') {
        $specialities = $pdo->query("SELECT DISTINCT speciality FROM doctors WHERE speciality IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
    }

    // Récupération des listes
    $departements = $pdo->query("SELECT id, name FROM departement ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $services = $pdo->query("
        SELECT s.id, s.name, s.departement_id, d.name as departement_name 
        FROM services s
        JOIN departement d ON s.departement_id = d.id
        ORDER BY s.name
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erreur chargement données: " . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Erreur de chargement des données'];
    header('Location: manage_users.php');
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Token de sécurité invalide'];
        header("Location: edit_user.php?id=$userId");
        exit();
    }

    // Préparation données avec valeurs par défaut
    $formData = [
        'username' => sanitizeInput($_POST['username'] ?? ''),
        'email' => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
        'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
        'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'address' => sanitizeInput($_POST['address'] ?? ''),
        'sex' => in_array($_POST['sex'] ?? '', ['M', 'F', 'O']) ? $_POST['sex'] : 'M',
        'speciality' => sanitizeInput($_POST['speciality'] ?? ''),
        'license_number' => sanitizeInput($_POST['license_number'] ?? ''),
        'insurance_number' => sanitizeInput($_POST['insurance_number'] ?? ''),
        'departement_id' => !empty($_POST['departement_id']) ? (int)$_POST['departement_id'] : null,
        'service_id' => !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null
    ];

    // Validation des données
    $errors = [];
    $requiredFields = ['username', 'email', 'first_name', 'last_name'];
    
    foreach ($requiredFields as $field) {
        if (empty($formData[$field])) {
            $errors[] = "Le champ " . ucfirst(str_replace('_', ' ', $field)) . " est obligatoire";
        }
    }

    if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalide";
    }

    // Validation spécifique rôle
    if ($user['role'] === 'doctor') {
        if (empty($formData['speciality'])) {
            $errors[] = "Spécialité obligatoire pour les médecins";
        }
        if (empty($formData['license_number'])) {
            $errors[] = "Numéro de licence obligatoire";
        }
    }

    if (!empty($errors)) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => implode('<br>', $errors)];
        $_SESSION['form_data'] = $formData;
        header("Location: edit_user.php?id=$userId");
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Mise à jour table users
        $sql = "UPDATE users SET 
                username = ?, email = ?, first_name = ?, last_name = ?,
                phone = ?, address = ?, sex = ?,
                departement_id = ?, service_id = ?
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $formData['username'], $formData['email'], $formData['first_name'], $formData['last_name'],
            $formData['phone'], $formData['address'], $formData['sex'],
            $formData['departement_id'], $formData['service_id'],
            $userId
        ]);

        // Mise à jour table spécifique
        if ($user['role'] === 'doctor') {
            $sql = "UPDATE doctors SET 
                    first_name = ?, last_name = ?, full_name = CONCAT(?, ' ', ?),
                    sex = ?, email = ?, phone = ?, address = ?,
                    speciality = ?, license_number = ?,
                    departement_id = ?, service_id = ?
                    WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $formData['first_name'], $formData['last_name'],
                $formData['first_name'], $formData['last_name'],
                $formData['sex'], $formData['email'], $formData['phone'], $formData['address'],
                $formData['speciality'], $formData['license_number'],
                $formData['departement_id'], $formData['service_id'],
                $userId
            ]);
        } elseif ($user['role'] === 'patient') {
            $sql = "UPDATE patients SET 
                    first_name = ?, last_name = ?, full_name = CONCAT(?, ' ', ?),
                    sex = ?, email = ?, phone = ?, address = ?,
                    insurance_number = ?
                    WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $formData['first_name'], $formData['last_name'],
                $formData['first_name'], $formData['last_name'],
                $formData['sex'], $formData['email'], $formData['phone'], $formData['address'],
                $formData['insurance_number'],
                $userId
            ]);
        }

        $pdo->commit();
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Utilisateur mis à jour avec succès'];
        header("Location: edit_user.php?id=$userId");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erreur SQL: " . $e->getMessage());
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Erreur technique lors de la mise à jour'];
        header("Location: edit_user.php?id=$userId");
        exit();
    }
}

// Fusion des données pour l'affichage
$displayData = array_merge($user, $roleData);
if (isset($_SESSION['form_data'])) {
    $displayData = array_merge($displayData, $_SESSION['form_data']);
    unset($_SESSION['form_data']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier utilisateur | Kibris Aydin Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-section {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .required-field::after {
            content: " *";
            color: red;
        }
        .role-specific {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2>Modifier l'utilisateur</h2>

        <?php if (isset($_SESSION['alert'])): ?>
            <div class="alert alert-<?= safeValue($_SESSION['alert']['type']) ?>">
                <?= safeValue($_SESSION['alert']['message']) ?>
            </div>
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>

        <form method="POST" class="mt-4">
            <input type="hidden" name="csrf_token" value="<?= safeValue($_SESSION['csrf_token']) ?>">

            <div class="form-section">
                <h4>Informations de base</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required-field">Rôle</label>
                        <input type="text" class="form-control" value="<?= safeValue(ucfirst($displayData['role'])) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required-field">Sexe</label>
                        <select name="sex" class="form-select" required>
                            <option value="M" <?= ($displayData['sex'] ?? 'M') === 'M' ? 'selected' : '' ?>>Masculin</option>
                            <option value="F" <?= ($displayData['sex'] ?? 'M') === 'F' ? 'selected' : '' ?>>Féminin</option>
                            <option value="O" <?= ($displayData['sex'] ?? 'M') === 'O' ? 'selected' : '' ?>>Autre</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required-field">Nom d'utilisateur</label>
                        <input type="text" name="username" class="form-control" required 
                               value="<?= safeValue($displayData['username']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required-field">Email</label>
                        <input type="email" name="email" class="form-control" required 
                               value="<?= safeValue($displayData['email']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required-field">Prénom</label>
                        <input type="text" name="first_name" class="form-control" required 
                               value="<?= safeValue($displayData['first_name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required-field">Nom</label>
                        <input type="text" name="last_name" class="form-control" required 
                               value="<?= safeValue($displayData['last_name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" name="phone" class="form-control" 
                               value="<?= safeValue($displayData['phone']) ?>"
                               pattern="[0-9]{10}" title="10 chiffres sans espaces">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Adresse</label>
                        <input type="text" name="address" class="form-control" 
                               value="<?= safeValue($displayData['address']) ?>">
                    </div>
                </div>
            </div>

            <?php if ($user['role'] === 'doctor'): ?>
            <div class="form-section role-specific" id="doctor-fields">
                <h4>Informations médicales</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required-field">Spécialité</label>
                        <input list="specialities" name="speciality" class="form-control" required
                               value="<?= safeValue($displayData['speciality'] ?? '') ?>">
                        <datalist id="specialities">
                            <?php foreach ($specialities as $spec): ?>
                                <option value="<?= safeValue($spec) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required-field">Numéro de licence</label>
                        <input type="text" name="license_number" class="form-control" required
                               value="<?= safeValue($displayData['license_number'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Département</label>
                        <select name="departement_id" class="form-select" id="departement-select">
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($departements as $dept): ?>
                                <option value="<?= safeValue($dept['id']) ?>" 
                                    <?= ($displayData['departement_id'] ?? 0) == $dept['id'] ? 'selected' : '' ?>>
                                    <?= safeValue($dept['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Service</label>
                        <select name="service_id" class="form-select" id="service-select">
                            <option value="">-- Sélectionner --</option>
                            <?php 
                            if (!empty($displayData['departement_id'])) {
                                $servicesForDept = array_filter($services, function($s) use ($displayData) {
                                    return $s['departement_id'] == $displayData['departement_id'];
                                });
                                foreach ($servicesForDept as $service): ?>
                                    <option value="<?= safeValue($service['id']) ?>" 
                                        <?= ($displayData['service_id'] ?? 0) == $service['id'] ? 'selected' : '' ?>>
                                        <?= safeValue($service['name']) ?>
                                    </option>
                                <?php endforeach;
                            } ?>
                        </select>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($user['role'] === 'patient'): ?>
            <div class="form-section role-specific" id="patient-fields">
                <h4>Informations patient</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Numéro d'assurance</label>
                        <input type="text" name="insurance_number" class="form-control"
                               value="<?= safeValue($displayData['insurance_number'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Enregistrer
                </button>
                <a href="manage_users.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Annuler
                </a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filtrage des services par département
        document.getElementById('departement-select')?.addEventListener('change', function() {
            const departementId = this.value;
            const serviceSelect = document.getElementById('service-select');
            
            serviceSelect.innerHTML = '<option value="">-- Sélectionner --</option>';
            
            if (!departementId) return;
            
            const allServices = <?= json_encode($services) ?>;
            const filteredServices = allServices.filter(s => s.departement_id == departementId);
            
            filteredServices.forEach(service => {
                const option = document.createElement('option');
                option.value = service.id;
                option.textContent = service.name;
                serviceSelect.appendChild(option);
            });
            
            // Sélectionner la valeur précédente si elle existe
            const previousValue = <?= isset($displayData['service_id']) ? $displayData['service_id'] : 'null' ?>;
            if (previousValue && filteredServices.some(s => s.id == previousValue)) {
                serviceSelect.value = previousValue;
            }
        });
    </script>
</body>
</html>