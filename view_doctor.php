<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

require __DIR__ . '/../include/connection.php';

$doctorId = $_GET['id'] ?? 0;
if (!$doctorId) {
    header('Location: list_doctors.php');
    exit();
}

try {
    // REQUÊTE SIMPLIFIÉE SANS LES CHAMPS MANQUANTS
    $stmt = $pdo->prepare("SELECT 
            u.id, 
            u.first_name, 
            u.last_name,
            CONCAT(u.first_name, ' ', u.last_name) AS full_name,
            u.email, 
            u.phone,
            u.address,
            u.sex,
            u.service_id,
            u.departement_id,
            d.license_number,
            d.speciality,
            s.name AS service_name,
            dep.name AS department_name
        FROM users u
        JOIN doctors d ON u.id = d.user_id
        LEFT JOIN services s ON u.service_id = s.id
        LEFT JOIN departement dep ON u.departement_id = dep.id
        WHERE u.id = ?");
    
    $stmt->execute([$doctorId]);
    $doctor = $stmt->fetch();
    
    if (!$doctor) {
        $_SESSION['message'] = "Docteur introuvable";
        header('Location: list_doctors.php');
        exit();
    }
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Docteur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .info-card {
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .info-label {
            font-weight: bold;
            width: 150px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Détails Docteur</h2>
            <div>
                <a href="list_doctors.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
                <a href="edit_doctor.php?id=<?= $doctorId ?>" class="btn btn-primary ms-2">
                    <i class="fas fa-edit"></i> Modifier
                </a>
            </div>
        </div>

        <div class="card info-card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-user-md me-2"></i>
                    <?= htmlspecialchars($doctor['full_name']) ?>
                </h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-3"><i class="fas fa-id-card me-2"></i> Informations Personnelles</h5>
                        
                        <div class="mb-3 row">
                            <div class="col-sm-4 info-label">Nom complet:</div>
                            <div class="col-sm-8"><?= htmlspecialchars($doctor['full_name']) ?></div>
                        </div>
                        
                        <div class="mb-3 row">
                            <div class="col-sm-4 info-label">Email:</div>
                            <div class="col-sm-8"><?= htmlspecialchars($doctor['email']) ?></div>
                        </div>
                        
                        <div class="mb-3 row">
                            <div class="col-sm-4 info-label">Téléphone:</div>
                            <div class="col-sm-8"><?= htmlspecialchars($doctor['phone'] ?: 'Non renseigné') ?></div>
                        </div>
                        
                        <div class="mb-3 row">
                            <div class="col-sm-4 info-label">Sexe:</div>
                            <div class="col-sm-8">
                                <?= match($doctor['sex']) {
                                    'M' => 'Masculin',
                                    'F' => 'Féminin',
                                    default => 'Non spécifié'
                                } ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h5 class="mb-3"><i class="fas fa-briefcase me-2"></i> Informations Professionnelles</h5>
                        
                        <div class="mb-3 row">
                            <div class="col-sm-4 info-label">Spécialité:</div>
                            <div class="col-sm-8"><?= htmlspecialchars($doctor['speciality'] ?: 'Non spécifiée') ?></div>
                        </div>
                        
                        <div class="mb-3 row">
                            <div class="col-sm-4 info-label">Service:</div>
                            <div class="col-sm-8"><?= htmlspecialchars($doctor['service_name'] ?: 'Non affecté') ?></div>
                        </div>
                        
                        <div class="mb-3 row">
                            <div class="col-sm-4 info-label">Département:</div>
                            <div class="col-sm-8"><?= htmlspecialchars($doctor['department_name'] ?: 'Non affecté') ?></div>
                        </div>
                        
                        <div class="mb-3 row">
                            <div class="col-sm-4 info-label">N° Licence:</div>
                            <div class="col-sm-8"><?= htmlspecialchars($doctor['license_number']) ?></div>
                        </div>
                    </div>
                    
                    <div class="col-12 mt-3">
                        <h5 class="mb-3"><i class="fas fa-map-marker-alt me-2"></i> Adresse</h5>
                        <div class="mb-3 row">
                            <div class="col-sm-2 info-label">Adresse:</div>
                            <div class="col-sm-10"><?= htmlspecialchars($doctor['address'] ?: 'Non renseignée') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>