<?php
define('HMS_ACCESS', true);
require __DIR__ . '/../include/security.php';
require __DIR__ . '/../include/connection.php';

// Vérification d'accès admin seulement
Security::checkAuth(['admin']);

// Récupération des données utilisateur
$pdo = Database::getInstance();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: /Hms/logout.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Admin | Kibris Aydin Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin1dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="text-center mb-4">
                <div class="profile-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                <div class="user-role">Administrateur</div>
            </div>
            
            <nav class="nav flex-column">
                <a class="nav-link active" href="admin_dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Tableau de bord
                </a>
                <a class="nav-link" href="manage_users.php">
                    <i class="fas fa-users-cog"></i> Utilisateurs
                </a>
                <a class="nav-link" href="manage_departments.php">
                    <i class="fas fa-building"></i> Départements
                </a>
                <a class="nav-link" href="manage_services.php">
                    <i class="fas fa-concierge-bell"></i> Services
                </a>
               
              <a class="nav-link" href="/Hms/admin/manage_reports.php" id="reports-link">
               <i class="fas fa-file-medical"></i> Rapports
                </a>
                <a class="nav-link" href="manage_inventory.php">
                    <i class="fas fa-warehouse"></i> Inventaire
                </a>
                
                <button class="btn btn-outline-light mt-3 w-100" onclick="toggleProfileForm()">
                    <i class="fas fa-user-edit"></i> Modifier profil
                </button>
            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
           <a href="/Hms/logout.php" class="logout-btn" 
   onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?');">
    <i class="fas fa-sign-out-alt"></i> Déconnexion
</a>
            
            <div class="hospital-header">
                <img src="/Hms/img/logo.png" alt="Logo Kibris Aydin Hospital" class="hospital-logo">
                <h1 class="hospital-name">Kibris Aydin Hospital</h1>
            </div>
            
            <h2 class="mb-4">
                <i class="fas fa-tachometer-alt me-2"></i> Tableau de bord
                <small class="text-muted"><?= date('d/m/Y H:i') ?></small>
            </h2>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <div id="profile-form" class="profile-form" style="display: none;">
                <h3 class="mb-3"><i class="fas fa-user-edit me-2"></i> Modifier le profil</h3>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom complet</label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?= htmlspecialchars($_SESSION['full_name']) ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?= htmlspecialchars($_SESSION['phone']) ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Sex</label>
                            <select name="sex" class="form-select" required>
                                <option value="M" <?= ($_SESSION['sex'] === 'M') ? 'selected' : '' ?>>Masculin</option>
                                <option value="F" <?= ($_SESSION['sex'] === 'F') ? 'selected' : '' ?>>Féminin</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Grade</label>
                            <input type="text" name="grade" class="form-control" 
                                   value="<?= htmlspecialchars($_SESSION['grade']) ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Departement</label>
                            <select name="departement_id" class="form-select">
                                <option value="">-- Sélectionnez --</option>
                                <?php foreach ($departements as $dept): ?>
                                    <option value="<?= $dept['id'] ?>" 
                                        <?= ($dept['id'] == $_SESSION['departement_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Service</label>
                            <select name="service_id" class="form-select">
                                <option value="">-- Sélectionnez --</option>
                                <?php foreach ($services as $srv): ?>
                                    <option value="<?= $srv['id'] ?>" 
                                        <?= ($srv['id'] == $_SESSION['service_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($srv['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Adresse</label>
                            <textarea name="address" class="form-control" rows="2"><?= 
                                htmlspecialchars($_SESSION['address']) 
                            ?></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Bio</label>
                            <textarea name="bio" class="form-control" rows="3"><?= 
                                htmlspecialchars($_SESSION['bio']) 
                            ?></textarea>
                        </div>
                        
                        <div class="col-12 mt-3">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Enregistrer
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="toggleProfileForm()">
                                <i class="fas fa-times me-2"></i> Annuler
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <a href="list_patients.php" class="text-decoration-none">
                        <div class="stat-card patients">
                            <div class="card-body text-center">
                                <h3 class="card-title">Patients</h3>
                                <div class="stat-value"><?= $stats['total_patients'] ?></div>
                                <small class="opacity-75"><?= $stats['new_patients_today'] ?> nouveaux</small>
                            </div>
                        </div>
                    </a>
                </div>
                
                <div class="col-md-3">
                    <a href="list_doctors.php" class="text-decoration-none">
                        <div class="stat-card doctors">
                            <div class="card-body text-center">
                                <h3 class="card-title">Médecins</h3>
                                <div class="stat-value"><?= $stats['total_doctors'] ?></div>
                                <small class="opacity-75">Spécialistes</small>
                            </div>
                        </div>
                    </a>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card revenue">
                        <div class="card-body text-center">
                            <h3 class="card-title">Revenus</h3>
                            <div class="stat-value"><?= number_format($stats['total_revenue'], 2) ?> €</div>
                            <small class="opacity-75">Total</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <a href="manage_inventory.php" class="text-decoration-none">
                        <div class="stat-card stock">
                            <div class="card-body text-center">
                                <h3 class="card-title">Stock</h3>
                                <div class="stat-value"><?= $stats['total_inventory'] ?></div>
                                <small class="opacity-75"><?= $stats['low_stock_items'] ?> alertes</small>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-history me-2"></i> Activité récente
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="p-3">
                                <h5 class="text-primary">Nouveaux patients</h5>
                                <div class="fs-3 fw-bold"><?= $stats['new_patients_today'] ?></div>
                                <small class="text-muted">Aujourd'hui</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3">
                                <h5 class="text-success">Rendez-vous</h5>
                                <div class="fs-3 fw-bold"><?= $stats['appointments_today'] ?></div>
                                <small class="text-muted">Aujourd'hui</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3">
                                <h5 class="text-danger">Alertes</h5>
                                <div class="fs-3 fw-bold"><?= $stats['system_alerts'] ?></div>
                                <small class="text-muted">Système</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleProfileForm() {
            const form = document.getElementById('profile-form');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
        
        document.getElementById('reports-link').addEventListener('click', function(e) {
            window.location.href = '/Hms/admin/manage_reports.php?force=' + Date.now();
            e.preventDefault();
            
            console.log('Redirection vers manage_reports.php');
            fetch('/Hms/admin/manage_reports.php')
                .then(response => {
                    if (!response.ok) throw new Error('Erreur réseau');
                    return response.text();
                })
                .then(data => {
                    console.log('Réponse reçue');
                    window.location.href = '/Hms/admin/manage_reports.php';
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    window.location.reload(true);
                });
        });
    </script>
</body>
</html>