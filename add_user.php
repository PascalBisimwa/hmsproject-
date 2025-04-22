<?php
define('HMS_ACCESS', true);
require __DIR__ . '/../include/security.php';
require __DIR__ . '/../include/connection.php';
require __DIR__ . '/../include/functions.php';

$pdo = Database::getInstance();
initSession();
checkAuth(['admin']);

// Initialisation avec valeurs par défaut
$formData = [
    'username' => '', 'email' => '', 'password' => '', 'confirm_password' => '',
    'role' => 'doctor', 'first_name' => '', 'last_name' => '', 'phone' => '',
    'address' => '', 'sex' => 'M', 'speciality' => 'Généraliste',
    'license_number' => '', 'insurance_number' => '',
    'departement_id' => null, 'service_id' => null
];

// Récupération des listes avec jointure
try {
    $departements = $pdo->query("SELECT id, name FROM departement ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $services = $pdo->query("
        SELECT s.id, s.name, s.departement_id, d.name as departement_name 
        FROM services s
        JOIN departement d ON s.departement_id = d.id
        ORDER BY s.name
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur chargement listes: " . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Erreur de chargement des données'];
    header('Location: manage_users.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Token de sécurité invalide'];
        header('Location: add_user.php');
        exit();
    }

    // Nettoyage des données
    $formData = [
        'username' => sanitizeInput($_POST['username'] ?? ''),
        'email' => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'role' => in_array($_POST['role'] ?? '', ['admin', 'doctor', 'receptionist', 'patient']) 
                 ? $_POST['role'] : 'doctor',
        'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
        'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'address' => sanitizeInput($_POST['address'] ?? ''),
        'sex' => in_array($_POST['sex'] ?? '', ['M', 'F', 'O']) ? $_POST['sex'] : 'M',
        'speciality' => sanitizeInput($_POST['speciality'] ?? 'Généraliste'),
        'license_number' => sanitizeInput($_POST['license_number'] ?? ''),
        'insurance_number' => sanitizeInput($_POST['insurance_number'] ?? ''),
        'departement_id' => !empty($_POST['departement_id']) ? (int)$_POST['departement_id'] : null,
        'service_id' => !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null
    ];

    // Validation
    $errors = [];
    $requiredFields = ['username', 'email', 'password', 'confirm_password', 'first_name', 'last_name', 'role', 'sex'];
    
    foreach ($requiredFields as $field) {
        if (empty($formData[$field])) {
            $errors[] = "Le champ " . ucfirst(str_replace('_', ' ', $field)) . " est obligatoire";
        }
    }

    if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalide";
    }

    if (strlen($formData['password']) < 8) {
        $errors[] = "Mot de passe trop court (8 caractères minimum)";
    }

    if ($formData['password'] !== $formData['confirm_password']) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }

    // Validation spécifique rôle
    if ($formData['role'] === 'doctor') {
        if (empty($formData['license_number'])) $errors[] = "Numéro de licence obligatoire";
        if (empty($formData['departement_id'])) $errors[] = "Département obligatoire";
        
        // Vérification service
        if (empty($formData['service_id'])) {
            $errors[] = "Service obligatoire";
        } else {
            // Vérifier que le service appartient au département
            $serviceValid = false;
            foreach ($services as $service) {
                if ($service['id'] == $formData['service_id'] && $service['departement_id'] == $formData['departement_id']) {
                    $serviceValid = true;
                    break;
                }
            }
            if (!$serviceValid) {
                $errors[] = "Le service sélectionné n'appartient pas au département choisi";
            }
        }
    }

    if ($formData['role'] === 'patient' && empty($formData['insurance_number'])) {
        $errors[] = "Numéro d'assurance obligatoire";
    }

    if (!empty($errors)) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => implode('<br>', $errors)];
        $_SESSION['form_data'] = $formData;
        header('Location: add_user.php');
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Insertion dans users
        $sql = "INSERT INTO users (
            username, email, password, role,
            first_name, last_name, full_name,
            phone, address, sex,
            departement_id, service_id,
            created_at, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, CONCAT(?, ' ', ?), ?, ?, ?, ?, ?, NOW(), 1)";

        $params = [
            $formData['username'],
            $formData['email'],
            password_hash($formData['password'], PASSWORD_DEFAULT),
            $formData['role'],
            $formData['first_name'],
            $formData['last_name'],
            $formData['first_name'],
            $formData['last_name'],
            $formData['phone'],
            $formData['address'],
            $formData['sex'],
            $formData['departement_id'],
            $formData['service_id']
        ];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $userId = $pdo->lastInsertId();

        // Insertion spécifique selon le rôle
        switch ($formData['role']) {
            case 'doctor':
                $sql = "INSERT INTO doctors (
                    user_id, first_name, last_name, full_name,
                    sex, email, phone, address,
                    speciality, license_number,
                    departement_id, service_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $userId,
                    $formData['first_name'],
                    $formData['last_name'],
                    $formData['first_name'] . ' ' . $formData['last_name'],
                    $formData['sex'],
                    $formData['email'],
                    $formData['phone'],
                    $formData['address'],
                    $formData['speciality'],
                    $formData['license_number'],
                    $formData['departement_id'],
                    $formData['service_id']
                ]);
                break;
                
            case 'patient':
                $sql = "INSERT INTO patients (
                    user_id, first_name, last_name, full_name,
                    sex, email, phone, address,
                    insurance_number
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $userId,
                    $formData['first_name'],
                    $formData['last_name'],
                    $formData['first_name'] . ' ' . $formData['last_name'],
                    $formData['sex'],
                    $formData['email'],
                    $formData['phone'],
                    $formData['address'],
                    $formData['insurance_number']
                ]);
                break;
                
            case 'admin':
            case 'receptionist':
                // Pas d'insertion spécifique nécessaire pour ces rôles
                break;
        }

        $pdo->commit();
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Utilisateur créé avec succès'];
        header('Location: manage_users.php');
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("ERREUR SQL: " . $e->getMessage());
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Erreur technique: ' . $e->getMessage()];
        $_SESSION['form_data'] = $formData;
        header('Location: add_user.php');
        exit();
    }
}

// Récupération données existantes en cas d'erreur
if (isset($_SESSION['form_data'])) {
    $formData = array_merge($formData, $_SESSION['form_data']);
    unset($_SESSION['form_data']);
}

// Fonction helper pour affichage sécurisé
function safeValue($value) {
    return isset($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : '';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un utilisateur | Kibris Aydin Hospital</title>
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
            display: none;
            animation: fadeIn 0.3s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2>Ajouter un utilisateur</h2>

        <?php if (isset($_SESSION['alert'])): ?>
            <div class="alert alert-<?= safeValue($_SESSION['alert']['type']) ?>">
                <?= safeValue($_SESSION['alert']['message']) ?>
            </div>
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>

        <form method="POST" class="mt-4" id="user-form">
            <input type="hidden" name="csrf_token" value="<?= safeValue($_SESSION['csrf_token']) ?>">

            <div class="form-section">
                <h4>Informations de base</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label required-field">Rôle</label>
                        <select name="role" class="form-select" required id="role-selector">
                            <option value="doctor" <?= $formData['role'] === 'doctor' ? 'selected' : '' ?>>Médecin</option>
                            <option value="admin" <?= $formData['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                            <option value="receptionist" <?= $formData['role'] === 'receptionist' ? 'selected' : '' ?>>Réceptionniste</option>
                            <option value="patient" <?= $formData['role'] === 'patient' ? 'selected' : '' ?>>Patient</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required-field">Sexe</label>
                        <select name="sex" class="form-select" required>
                            <option value="M" <?= $formData['sex'] === 'M' ? 'selected' : '' ?>>Masculin</option>
                            <option value="F" <?= $formData['sex'] === 'F' ? 'selected' : '' ?>>Féminin</option>
                            <option value="O" <?= $formData['sex'] === 'O' ? 'selected' : '' ?>>Autre</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required-field">Nom d'utilisateur</label>
                        <input type="text" name="username" class="form-control" required 
                               value="<?= safeValue($formData['username']) ?>"
                               minlength="4" maxlength="50">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required-field">Email</label>
                        <input type="email" name="email" class="form-control" required 
                               value="<?= safeValue($formData['email']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required-field">Mot de passe</label>
                        <input type="password" name="password" class="form-control" required
                               minlength="8" id="password-field">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required-field">Confirmation</label>
                        <input type="password" name="confirm_password" class="form-control" required
                               minlength="8">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required-field">Prénom</label>
                        <input type="text" name="first_name" class="form-control" required 
                               value="<?= safeValue($formData['first_name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required-field">Nom</label>
                        <input type="text" name="last_name" class="form-control" required 
                               value="<?= safeValue($formData['last_name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" name="phone" class="form-control" 
                               value="<?= safeValue($formData['phone']) ?>"
                               pattern="[0-9]{10}" title="10 chiffres sans espaces">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Adresse</label>
                        <input type="text" name="address" class="form-control" 
                               value="<?= safeValue($formData['address']) ?>">
                    </div>
                </div>
            </div>

            <!-- Champs spécifiques aux médecins -->
            <div id="doctor-fields" class="role-specific">
                <div class="form-section">
                    <h4>Informations médicales</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required-field">Spécialité</label>
                            <input type="text" name="speciality" class="form-control" required
                                   value="<?= safeValue($formData['speciality']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required-field">Numéro de licence</label>
                            <input type="text" name="license_number" class="form-control" required
                                   value="<?= safeValue($formData['license_number']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required-field">Département</label>
                            <select name="departement_id" class="form-select" required id="departement-select">
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($departements as $dept): ?>
                                    <option value="<?= safeValue($dept['id']) ?>" <?= $formData['departement_id'] == $dept['id'] ? 'selected' : '' ?>>
                                        <?= safeValue($dept['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required-field">Service</label>
                            <select name="service_id" class="form-select" required id="service-select">
                                <option value="">-- Sélectionner un département d'abord --</option>
                                <?php 
                                if ($formData['departement_id']) {
                                    $servicesForDept = array_filter($services, function($s) use ($formData) {
                                        return $s['departement_id'] == $formData['departement_id'];
                                    });
                                    foreach ($servicesForDept as $service): ?>
                                        <option value="<?= safeValue($service['id']) ?>" <?= $formData['service_id'] == $service['id'] ? 'selected' : '' ?>>
                                            <?= safeValue($service['name']) ?>
                                        </option>
                                    <?php endforeach;
                                } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Champs spécifiques aux patients -->
            <div id="patient-fields" class="role-specific">
                <div class="form-section">
                    <h4>Informations patient</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required-field">Numéro d'assurance</label>
                            <input type="text" name="insurance_number" class="form-control" required
                                   value="<?= safeValue($formData['insurance_number']) ?>">
                        </div>
                    </div>
                </div>
            </div>

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
        // Gestion dynamique des champs par rôle
        function updateRoleFields() {
            const role = document.getElementById('role-selector').value;
            
            // Masquer tous les champs spécifiques
            document.querySelectorAll('.role-specific').forEach(el => {
                el.style.display = 'none';
                
                // Désactiver les required quand caché
                el.querySelectorAll('[required]').forEach(input => {
                    input.required = false;
                });
            });
            
            // Afficher les champs pour le rôle sélectionné
            if (role === 'doctor') {
                document.getElementById('doctor-fields').style.display = 'block';
                document.querySelectorAll('#doctor-fields [required]').forEach(input => {
                    input.required = true;
                });
            } else if (role === 'patient') {
                document.getElementById('patient-fields').style.display = 'block';
                document.querySelectorAll('#patient-fields [required]').forEach(input => {
                    input.required = true;
                });
            }
        }

        // Filtrage des services par département
        function filterServices() {
            const departementId = document.getElementById('departement-select').value;
            const serviceSelect = document.getElementById('service-select');
            
            // Réinitialiser
            serviceSelect.innerHTML = '<option value="">-- Sélectionner --</option>';
            
            if (!departementId) {
                serviceSelect.innerHTML = '<option value="">-- Sélectionnez d\'abord un département --</option>';
                return;
            }
            
            // Filtrer les services disponibles
            const allServices = <?= json_encode($services) ?>;
            const filteredServices = allServices.filter(s => s.departement_id == departementId);
            
            if (filteredServices.length === 0) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'Aucun service disponible pour ce département';
                option.disabled = true;
                serviceSelect.appendChild(option);
            } else {
                filteredServices.forEach(service => {
                    const option = document.createElement('option');
                    option.value = service.id;
                    option.textContent = service.name;
                    serviceSelect.appendChild(option);
                });
                
                // Sélectionner la valeur précédente si elle existe
                const previousValue = <?= $formData['service_id'] ? json_encode($formData['service_id']) : 'null' ?>;
                if (previousValue && filteredServices.some(s => s.id == previousValue)) {
                    serviceSelect.value = previousValue;
                }
            }
        }

        // Validation avant soumission
        document.getElementById('user-form').addEventListener('submit', function(e) {
            const role = document.getElementById('role-selector').value;
            let isValid = true;
            
            // Validation mot de passe
            const password = document.querySelector('input[name="password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            
            if (password !== confirmPassword) {
                alert('Les mots de passe ne correspondent pas');
                isValid = false;
            }
            
            // Validation spécifique rôle
            if (role === 'doctor') {
                const speciality = document.querySelector('input[name="speciality"]').value;
                const license = document.querySelector('input[name="license_number"]').value;
                const dept = document.querySelector('select[name="departement_id"]').value;
                const service = document.querySelector('select[name="service_id"]').value;
                
                if (!speciality || !license || !dept || !service) {
                    alert('Veuillez remplir tous les champs obligatoires pour les médecins');
                    isValid = false;
                }
            }
            
            if (role === 'patient' && !document.querySelector('input[name="insurance_number"]').value) {
                alert('Le numéro d\'assurance est obligatoire pour les patients');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            updateRoleFields();
            filterServices();
            
            // Écouteurs d'événements
            document.getElementById('role-selector').addEventListener('change', updateRoleFields);
            document.getElementById('departement-select').addEventListener('change', filterServices);
        });
    </script>
</body>
</html>