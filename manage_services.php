<?php
// Protection contre l'accès direct
define('HMS_ACCESS', true);
require __DIR__ . '/../include/security.php';
require __DIR__ . '/../include/connection.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
checkAuth('admin');

// Initialisation PDO
$pdo = Database::getInstance();

// Récupérer la liste des services avec leurs départements
$services = $pdo->query("
    SELECT s.id, s.name, s.description, s.is_active, s.is_night_service,
           d.name AS department_name, d.id AS department_id,
           COUNT(u.id) AS total_staff
    FROM services s
    LEFT JOIN departement d ON s.departement_id = d.id
    LEFT JOIN users u ON s.id = u.service_id
    GROUP BY s.id
    ORDER BY d.name, s.name
")->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$stats = [
    'total_services' => count($services),
    'active_services' => count(array_filter($services, fn($s) => $s['is_active'])),
    'night_services' => count(array_filter($services, fn($s) => $s['is_night_service'])),
    'total_staff' => array_sum(array_column($services, 'total_staff'))
];

// Messages flash
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Services | Kibris Aydin Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
     <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --night-blue: #1a237e;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
        }
        
        .dashboard-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }
        
        .sidebar {
            background: var(--primary);
            color: white;
            padding: 1.5rem;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            height: 100vh;
        }
        
        .main-content {
            padding: 2rem;
            background-color: white;
            position: relative;
        }
        
        .profile-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: rgba(255,255,255,0.8);
            text-align: center;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1rem;
            margin: 0.25rem 0;
            border-radius: 5px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
            text-decoration: none;
        }
        
        .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .hospital-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            gap: 15px;
        }
        
        .hospital-logo {
            height: 50px;
            object-fit: contain;
        }
        
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Stats cards - Style amélioré mais couleurs identiques */
        .stat-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
            color: white;
            text-align: center;
            padding: 1.5rem 1rem;
            border: none;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            background-color: rgba(255,255,255,0.2);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        
        .stat-total {
            background: linear-gradient(45deg, var(--primary), var(--secondary));
        }
        
        .stat-active {
            background: linear-gradient(45deg, var(--success), #2ecc71);
        }
        
        .stat-night {
            background: linear-gradient(45deg, var(--night-blue), #283593);
        }
        
        .stat-staff {
            background: linear-gradient(45deg, #e67e22, var(--warning));
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        /* Services grid - Réorganisé mais couleurs identiques */
        .services-container {
            margin-top: 2rem;
        }
        
        .department-group {
            margin-bottom: 2.5rem;
        }
        
        .department-title {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 30px 0 20px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.15);
        }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .service-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            background: white;
            display: flex;
            flex-direction: column;
            height: 100%;
            border: 1px solid #eee;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            border-color: var(--secondary);
        }
        
        .card-header-custom {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            padding: 15px;
            border-bottom: none;
            position: relative;
        }
        
        .service-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0;
        }
        
        .service-status {
            position: absolute;
            top: 15px;
            right: 15px;
            display: flex;
            gap: 8px;
        }
        
        .status-badge {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255,255,255,0.2);
            font-size: 0.8rem;
        }
        
        .service-active {
            color: var(--success);
        }
        
        .service-inactive {
            color: var(--danger);
        }
        
        .service-night {
            color: var(--night-blue);
        }
        
        .card-body-custom {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .service-description {
            margin-bottom: 20px;
            color: #555;
            flex-grow: 1;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        
        .staff-info {
            background-color: rgba(52, 152, 219, 0.08);
            border-radius: 8px;
            padding: 12px;
            margin-top: auto;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .staff-icon {
            width: 32px;
            height: 32px;
            background-color: rgba(52, 152, 219, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary);
        }
        
        .staff-count {
            font-weight: 600;
            color: var(--secondary);
        }
        
        .card-footer-custom {
            padding: 15px;
            background-color: #f9fafc;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* Action buttons - Style amélioré mais couleurs identiques */
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 6px;
            color: white;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .action-btn i {
            font-size: 14px;
        }
        
        .edit-btn {
            background-color: var(--success);
        }
        
        .edit-btn:hover {
            background-color: #218838;
        }
        
        .delete-btn {
            background-color: var(--danger);
        }
        
        .delete-btn:hover {
            background-color: #c82333;
        }
        
        /* Add button */
        .add-btn {
            background-color: var(--secondary);
            color: white;
            padding: 0.75rem 1.25rem;
            margin-bottom: 1.5rem;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }
        
        .add-btn:hover {
            background-color: #2188d9;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        /* Alert messages */
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
            border: 1px solid transparent;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        
        /* Responsive adjustments */
        @media (max-width: 992px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .hospital-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        
        @media (max-width: 768px) {
            .services-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 1.5rem;
            }
            
            .services-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                padding: 1.25rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="profile-card">
                <div class="profile-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h5 class="mb-1"><?= htmlspecialchars($_SESSION['full_name']) ?></h5>
                <small class="text-muted">Administrateur</small>
            </div>
            
            <nav class="nav flex-column">
                <a class="nav-link" href="admin_dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Tableau de bord
                </a>
                <a class="nav-link" href="manage_users.php">
                    <i class="fas fa-users-cog"></i> Utilisateurs
                </a>
                <a class="nav-link" href="manage_departments.php">
                    <i class="fas fa-building"></i> Départements
                </a>
                <a class="nav-link active" href="manage_services.php">
                    <i class="fas fa-concierge-bell"></i> Services
                </a>
                <a class="nav-link" href="list_patients.php">
                    <i class="fas fa-procedures"></i> Patients
                </a>
                <a class="nav-link" href="list_doctors.php">
                    <i class="fas fa-user-md"></i> Médecins
                </a>
                <a class="nav-link" href="manage_reports.php">
                    <i class="fas fa-file-medical"></i> Rapports
                </a>
                <a class="nav-link" href="manage_inventory.php">
                    <i class="fas fa-warehouse"></i> Inventaire
                </a>
                
                <button class="btn btn-outline-primary mt-3 w-100" onclick="toggleProfileForm()">
                    <i class="fas fa-user-edit"></i> Modifier profil
                </button>
            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
            <a href="logout.php" class="logout-btn btn btn-sm">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
            
            <div class="hospital-header">
                <img src="/Hms/img/logo.png" alt="Logo Kibris Aydin Hospital" class="hospital-logo">
                <div>
                    <h1 class="hospital-name mb-0">Kibris Aydin Hospital</h1>
                    <small class="text-muted">Gestion des services</small>
                </div>
            </div>
            
            <!-- Messages flash -->
            <?php if ($alert) : ?>
                <div class="alert alert-<?= $alert['type'] ?>">
                    <i class="fas fa-check-circle me-2"></i> <?= $alert['message'] ?>
                </div>
            <?php endif; ?>
            
            <!-- Bouton d'ajout avec icône -->
            <button onclick="window.location.href='add_service.php'" class="add-btn">
                <i class="fas fa-plus-circle"></i> Ajouter un service
            </button>
            
            <!-- Statistiques -->
            <div class="row mb-4 g-4">
                <div class="col-md-3 col-6">
                    <div class="stat-card stat-total">
                        <div class="stat-icon">
                            <i class="fas fa-hospital"></i>
                        </div>
                        <div class="stat-value"><?= $stats['total_services'] ?></div>
                        <div class="stat-label">Services</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card stat-active">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value"><?= $stats['active_services'] ?></div>
                        <div class="stat-label">Actifs</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card stat-night">
                        <div class="stat-icon">
                            <i class="fas fa-moon"></i>
                        </div>
                        <div class="stat-value"><?= $stats['night_services'] ?></div>
                        <div class="stat-label">De nuit</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card stat-staff">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?= $stats['total_staff'] ?></div>
                        <div class="stat-label">Membres</div>
                    </div>
                </div>
            </div>
            
            <!-- Liste des services -->
            <div class="services-container">
                <?php
                $currentDept = null;
                foreach ($services as $service) :
                    if ($service['department_name'] !== $currentDept) :
                        $currentDept = $service['department_name'];
                ?>
                        <div class="department-group">
                            <h2 class="department-title">
                                <i class="fas fa-diagram-project"></i> <?= htmlspecialchars($currentDept ?? 'Non attribué') ?>
                            </h2>
                            <div class="services-grid">
                    <?php endif; ?>
                    
                    <div class="service-card">
                        <div class="card-header-custom">
                            <h3 class="service-title"><?= htmlspecialchars($service['name']) ?></h3>
                            <div class="service-status">
                                <?php if ($service['is_night_service']) : ?>
                                    <span class="status-badge status-night" title="Service de nuit">
                                        <i class="fas fa-moon"></i>
                                    </span>
                                <?php endif; ?>
                                <?php if ($service['is_active']) : ?>
                                    <span class="status-badge status-active" title="Service actif">
                                        <i class="fas fa-check"></i>
                                    </span>
                                <?php else : ?>
                                    <span class="status-badge status-inactive" title="Service inactif">
                                        <i class="fas fa-times"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body-custom">
                            <p class="service-description">
                                <?= htmlspecialchars($service['description'] ?? 'Aucune description disponible') ?>
                            </p>
                            <div class="staff-info">
                                <div class="staff-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <span class="staff-count"><?= $service['total_staff'] ?></span>
                                    <span>membre<?= $service['total_staff'] > 1 ? 's' : '' ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer-custom">
                            <a href="edit_service.php?id=<?= $service['id'] ?>" 
                               class="action-btn edit-btn" 
                               title="Modifier">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="delete_service.php" style="display: inline;">
                                <input type="hidden" name="id" value="<?= $service['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <button type="submit" class="action-btn delete-btn" 
                                        title="Supprimer"
                                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce service ?');">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <?php 
                    // Vérifier si c'est le dernier service ou si le prochain a un département différent
                    $nextIndex = array_search($service, $services) + 1;
                    if ($nextIndex >= count($services) || $services[$nextIndex]['department_name'] !== $currentDept) :
                    ?>
                            </div> <!-- Fermer services-grid -->
                        </div> <!-- Fermer department-group -->
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle profile form
        function toggleProfileForm() {
            const form = document.getElementById('profile-form');
            if (form) {
                form.style.display = form.style.display === 'none' ? 'block' : 'none';
            }
        }
    </script>
</body>
</html>