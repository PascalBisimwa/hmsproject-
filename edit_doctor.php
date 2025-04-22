<?php
// Configuration et sécurité
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require __DIR__ . '/../include/connection.php';

// Récupération ID docteur
$doctorId = $_GET['id'] ?? 0;
if (!$doctorId) {
    header('Location: list_doctors.php');
    exit();
}

// Récupération infos docteur
$stmt = $pdo->prepare("SELECT u.*, d.license_number, d.speciality 
                      FROM users u JOIN doctors d ON u.id = d.user_id 
                      WHERE u.id = ?");
$stmt->execute([$doctorId]);
$doctor = $stmt->fetch();

if (!$doctor) {
    $_SESSION['message'] = "Docteur introuvable";
    header('Location: list_doctors.php');
    exit();
}

// Récupération services et départements
$services = $pdo->query("SELECT id, name FROM services")->fetchAll();
$departments = $pdo->query("SELECT id, name FROM departement")->fetchAll();

// Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'first_name' => trim($_POST['first_name']),
        'last_name' => trim($_POST['last_name']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone']),
        'address' => trim($_POST['address']),
        'sex' => $_POST['sex'],
        'service_id' => (int)$_POST['service_id'],
        'department_id' => (int)$_POST['department_id'],
        'license_number' => trim($_POST['license_number']),
        'speciality' => trim($_POST['speciality'])
    ];

    // Validation minimale
    if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email'])) {
        $errors[] = "Les champs obligatoires sont requis";
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Mise à jour users
            $pdo->prepare("UPDATE users SET 
                          first_name = ?, last_name = ?, email = ?, phone = ?,
                          address = ?, sex = ?, service_id = ?, departement_id = ?
                          WHERE id = ?")
               ->execute([
                   $data['first_name'], $data['last_name'], $data['email'], $data['phone'],
                   $data['address'], $data['sex'], $data['service_id'], $data['department_id'],
                   $doctorId
               ]);
            
            // Mise à jour doctors
            $pdo->prepare("UPDATE doctors SET 
                          license_number = ?, speciality = ?
                          WHERE user_id = ?")
               ->execute([$data['license_number'], $data['speciality'], $doctorId]);
            
            $pdo->commit();
            $_SESSION['message'] = "Docteur mis à jour";
            header("Location: view_doctor.php?id=$doctorId");
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = "Erreur: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Docteur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Modifier Docteur</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?= implode('<br>', $errors) ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <div class="row g-3">
                <div class="col-md-6">
                    <h4>Informations Personnelles</h4>
                    
                    <div class="mb-3">
                        <label class="form-label">Prénom *</label>
                        <input type="text" name="first_name" class="form-control" 
                               value="<?= htmlspecialchars($doctor['first_name']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="last_name" class="form-control" 
                               value="<?= htmlspecialchars($doctor['last_name']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($doctor['email']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" name="phone" class="form-control" 
                               value="<?= htmlspecialchars($doctor['phone']) ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Sexe</label>
                        <select name="sex" class="form-select">
                            <option value="M" <?= $doctor['sex'] === 'M' ? 'selected' : '' ?>>Masculin</option>
                            <option value="F" <?= $doctor['sex'] === 'F' ? 'selected' : '' ?>>Féminin</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h4>Informations Professionnelles</h4>
                    
                    <div class="mb-3">
                        <label class="form-label">Numéro de licence *</label>
                        <input type="text" name="license_number" class="form-control" 
                               value="<?= htmlspecialchars($doctor['license_number']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Spécialité</label>
                        <input type="text" name="speciality" class="form-control" 
                               value="<?= htmlspecialchars($doctor['speciality']) ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Service *</label>
                        <select name="service_id" class="form-select" required>
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($services as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $s['id'] == $doctor['service_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Département *</label>
                        <select name="department_id" class="form-select" required>
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $d['id'] == $doctor['departement_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="mb-3">
                        <label class="form-label">Adresse</label>
                        <input type="text" name="address" class="form-control" 
                               value="<?= htmlspecialchars($doctor['address']) ?>">
                    </div>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                    <a href="view_doctor.php?id=<?= $doctorId ?>" class="btn btn-secondary">Annuler</a>
                </div>
            </div>
        </form>
    </div>
</body>
</html>