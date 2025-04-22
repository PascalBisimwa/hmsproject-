<?php
define('HMS_ACCESS', true);
require __DIR__ . '/../include/security.php';
require __DIR__ . '/../include/connection.php';
require __DIR__ . '/../include/functions.php';

$pdo = Database::getInstance();
initSession();
checkAuth('admin');

$userId = (int)$_GET['id'];

// Récupérer l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Utilisateur introuvable'];
    header('Location: manage_users.php');
    exit();
}

// Récupérer les infos spécifiques
$roleData = [];
switch ($user['role']) {
    case 'doctor':
        $stmt = $pdo->prepare("SELECT * FROM doctors WHERE user_id = ?");
        $stmt->execute([$userId]);
        $roleData = $stmt->fetch();
        break;
    case 'patient':
        $stmt = $pdo->prepare("SELECT * FROM patients WHERE user_id = ?");
        $stmt->execute([$userId]);
        $roleData = $stmt->fetch();
        break;
    case 'receptionist':
        $stmt = $pdo->prepare("SELECT * FROM receptionists WHERE user_id = ?");
        $stmt->execute([$userId]);
        $roleData = $stmt->fetch();
        break;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails utilisateur | Kibris Aydin Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .user-details-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            border: none;
        }
        
        .user-details-header {
            background-color: #2c3e50;
            color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0 !important;
        }
        
        .detail-item {
            padding: 12px 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #2c3e50;
            min-width: 200px;
        }
        
        .detail-value {
            flex-grow: 1;
        }
        
        .sex-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .sex-M { background-color: #d4edff; color: #0056b3; }
        .sex-F { background-color: #f8d7da; color: #721c24; }
        .sex-other { background-color: #e2e3e5; color: #383d41; }
        
        @media (max-width: 768px) {
            .detail-item {
                flex-direction: column;
            }
            
            .detail-label {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- [SIDEBAR EXISTANTE] -->
        
        <div class="main-content">
            <!-- [HEADER EXISTANT] -->
            
            <div class="container mt-4">
                <h2>Détails de l'utilisateur</h2>
                
                <div class="user-details-card">
                    <div class="card-header user-details-header">
                        Informations de base
                    </div>
                    <div class="card-body">
                        <div class="detail-item">
                            <div class="detail-label">Nom d'utilisateur</div>
                            <div class="detail-value"><?= safeOutput($user['username']) ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Email</div>
                            <div class="detail-value"><?= safeOutput($user['email']) ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Rôle</div>
                            <div class="detail-value"><?= safeOutput(ucfirst($user['role'])) ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Prénom</div>
                            <div class="detail-value"><?= safeOutput($user['first_name']) ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Nom</div>
                            <div class="detail-value"><?= safeOutput($user['last_name']) ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Sexe</div>
                            <div class="detail-value">
                                <span class="sex-badge sex-<?= $user['sex'] ?? '' ?>">
                                    <?= match($user['sex'] ?? '') {
                                        'M' => 'Masculin',
                                        'F' => 'Féminin',
                                        'other' => 'Autre',
                                        default => 'Non spécifié'
                                    } ?>
                                </span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Date de naissance</div>
                            <div class="detail-value">
                                <?= $user['birth_date'] ? date('d/m/Y', strtotime($user['birth_date'])) : 'Non renseignée' ?>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Téléphone</div>
                            <div class="detail-value"><?= safeOutput($user['phone'] ?? 'Non renseigné') ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Adresse</div>
                            <div class="detail-value"><?= safeOutput($user['address'] ?? 'Non renseignée') ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Informations spécifiques au rôle -->
                <?php if ($user['role'] === 'doctor' && $roleData): ?>
                <div class="user-details-card mt-4">
                    <div class="card-header user-details-header">
                        Informations médicales
                    </div>
                    <div class="card-body">
                        <div class="detail-item">
                            <div class="detail-label">Spécialité</div>
                            <div class="detail-value"><?= safeOutput($roleData['speciality'] ?? 'Non renseignée') ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Numéro de licence</div>
                            <div class="detail-value"><?= safeOutput($roleData['license_number'] ?? 'Non renseigné') ?></div>
                        </div>
                    </div>
                </div>
                <?php elseif ($user['role'] === 'patient' && $roleData): ?>
                <div class="user-details-card mt-4">
                    <div class="card-header user-details-header">
                        Informations patient
                    </div>
                    <div class="card-body">
                        <div class="detail-item">
                            <div class="detail-label">Numéro d'assurance</div>
                            <div class="detail-value"><?= safeOutput($roleData['insurance_number'] ?? 'Non renseigné') ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="mt-4 d-flex gap-3">
                    <a href="edit_user.php?id=<?= $userId ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Modifier
                    </a>
                    <a href="manage_users.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>