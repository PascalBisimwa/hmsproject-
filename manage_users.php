<?php
define('HMS_ACCESS', true);
require __DIR__ . '/../include/security.php';
require __DIR__ . '/../include/connection.php';
require __DIR__ . '/../include/functions.php';

$pdo = Database::getInstance();
initSession();
checkAuth(['admin']);

// Gestion des actions (activation/désactivation, suppression)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_status'])) {
        $userId = (int)$_POST['user_id'];
        $newStatus = (int)$_POST['new_status'];
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$newStatus, $userId]);
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Statut utilisateur mis à jour avec succès'
            ];
        } catch (PDOException $e) {
            error_log("Erreur de changement de statut: " . $e->getMessage());
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => 'Erreur lors de la mise à jour du statut'
            ];
        }
        header("Location: manage_users.php");
        exit;
    }
    
    if (isset($_POST['delete_user'])) {
        $userId = (int)$_POST['user_id'];
        
        try {
            // Commencer une transaction
            $pdo->beginTransaction();
            
            // Supprimer les données liées selon le rôle
            $role = $pdo->query("SELECT role FROM users WHERE id = $userId")->fetchColumn();
            
            if ($role === 'doctor') {
                $pdo->exec("DELETE FROM doctors WHERE user_id = $userId");
            } elseif ($role === 'patient') {
                $pdo->exec("DELETE FROM patients WHERE user_id = $userId");
            }
            
            // Supprimer l'utilisateur
            $pdo->exec("DELETE FROM users WHERE id = $userId");
            
            $pdo->commit();
            
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Utilisateur supprimé avec succès'
            ];
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur de suppression: " . $e->getMessage());
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => 'Erreur lors de la suppression'
            ];
        }
        header("Location: manage_users.php");
        exit;
    }
}

// Comptage des utilisateurs par rôle
$roleCounts = [
    'admin' => 0,
    'doctor' => 0,
    'patient' => 0,
    'receptionist' => 0
];

try {
    $roles = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($roles as $role) {
        $roleCounts[$role['role']] = $role['count'];
    }
} catch (PDOException $e) {
    error_log("Erreur de comptage des rôles: " . $e->getMessage());
}

// Paramètres de filtrage et tri
$search = sanitizeInput($_GET['search'] ?? '');
$roleFilter = sanitizeInput($_GET['role'] ?? '');
$statusFilter = sanitizeInput($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$sortField = sanitizeInput($_GET['sort'] ?? 'created_at');
$sortOrder = strtoupper(sanitizeInput($_GET['order'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

// Validation des champs de tri
$allowedSortFields = ['id', 'username', 'first_name', 'last_name', 'email', 'role', 'created_at'];
$sortField = in_array($sortField, $allowedSortFields) ? $sortField : 'created_at';

// Construction de la requête
$sql = "SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.role, 
               u.created_at, u.is_active,
               CASE 
                 WHEN u.role = 'doctor' THEN d.speciality
                 WHEN u.role = 'patient' THEN p.insurance_number
                 ELSE NULL
               END as details
        FROM users u
        LEFT JOIN doctors d ON u.id = d.user_id AND u.role = 'doctor'
        LEFT JOIN patients p ON u.id = p.user_id AND u.role = 'patient'
        WHERE 1=1";

$countSql = "SELECT COUNT(*) FROM users u WHERE 1=1";

// Filtres
if (!empty($search)) {
    $searchTerm = "%$search%";
    $sql .= " AND (u.username LIKE :search OR u.email LIKE :search OR 
                  u.first_name LIKE :search OR u.last_name LIKE :search)";
    $countSql .= " AND (u.username LIKE :search OR u.email LIKE :search OR 
                       u.first_name LIKE :search OR u.last_name LIKE :search)";
}

if (!empty($roleFilter)) {
    $sql .= " AND u.role = :role";
    $countSql .= " AND u.role = :role";
}

if ($statusFilter !== '') {
    $statusValue = $statusFilter === 'active' ? 1 : 0;
    $sql .= " AND u.is_active = :status";
    $countSql .= " AND u.is_active = :status";
}

// Tri
$sql .= " ORDER BY u.$sortField $sortOrder LIMIT :limit OFFSET :offset";

// Exécution des requêtes
try {
    $stmt = $pdo->prepare($sql);
    $countStmt = $pdo->prepare($countSql);

    if (!empty($search)) {
        $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
        $countStmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
    }

    if (!empty($roleFilter)) {
        $stmt->bindValue(':role', $roleFilter, PDO::PARAM_STR);
        $countStmt->bindValue(':role', $roleFilter, PDO::PARAM_STR);
    }

    if ($statusFilter !== '') {
        $stmt->bindValue(':status', $statusValue, PDO::PARAM_INT);
        $countStmt->bindValue(':status', $statusValue, PDO::PARAM_INT);
    }

    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalUsers = $countStmt->fetchColumn();
    $totalPages = max(1, ceil($totalUsers / $perPage));

} catch (PDOException $e) {
    error_log("Erreur de récupération des utilisateurs: " . $e->getMessage());
    $users = [];
    $totalUsers = 0;
    $totalPages = 1;
}

// Fonction pour générer l'URL de tri
function buildSortUrl($field) {
    global $search, $roleFilter, $statusFilter, $page, $sortField, $sortOrder;
    
    $params = [
        'search' => $search,
        'role' => $roleFilter,
        'status' => $statusFilter,
        'page' => $page,
        'sort' => $field,
        'order' => ($sortField === $field && $sortOrder === 'ASC') ? 'DESC' : 'ASC'
    ];
    
    return http_build_query(array_filter($params));
}

// Fonction pour l'icône de tri
function getSortIcon($field) {
    global $sortField, $sortOrder;
    
    if ($sortField !== $field) {
        return '<i class="fas fa-sort"></i>';
    }
    
    return $sortOrder === 'ASC' 
        ? '<i class="fas fa-sort-up"></i>' 
        : '<i class="fas fa-sort-down"></i>';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs | Kibris Aydin Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        /* Stats cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--primary);
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .stat-value {
            color: var(--primary);
            font-size: 24px;
            font-weight: bold;
        }
        
        /* Search form */
        .search-form {
            margin-bottom: 2rem;
            display: flex;
            max-width: 600px;
        }
        
        .search-form input {
            padding: 0.5rem 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            flex-grow: 1;
            font-size: 0.95rem;
        }
        
        .search-form button {
            padding: 0 1.25rem;
            background-color: var(--secondary);
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Table styles */
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }
        
        .users-table th {
            background-color: var(--primary);
            color: white;
            padding: 0.75rem;
            text-align: left;
            font-weight: 500;
        }
        
        .users-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        .users-table tr:not(:last-child) td {
            border-bottom: 1px solid #eee;
        }
        
        .users-table tr:hover {
            background-color: rgba(0,0,0,0.02);
        }
        
        /* Action buttons */
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            transition: all 0.2s;
            margin: 0 2px;
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .action-btn i {
            font-size: 14px;
        }
        
        .edit-btn {
            background-color: var(--success);
        }
        
        .delete-btn {
            background-color: var(--danger);
        }
        
        .view-btn {
            background-color: var(--info);
        }
        
        /* Add button */
        .add-btn {
            background-color: var(--secondary);
            color: white;
            padding: 0.5rem 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        
        .add-btn:hover {
            background-color: #2188d9;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 1rem;
            gap: 5px;
        }
        
        .pagination a, .pagination span {
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            color: var(--primary);
            text-decoration: none;
            border: 1px solid #ddd;
        }
        
        .pagination a:hover {
            background-color: #f0f0f0;
        }
        
        .pagination .active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
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
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        /* Toggle switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--success);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
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
            
            .search-form {
                width: 100%;
            }
            
            .stats-container {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="text-center mb-4">
                <div class="profile-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h5><?= safeOutput($_SESSION['full_name'] ?? 'Admin') ?></h5>
                <small class="text-white-50">Administrateur</small>
            </div>
            
            <nav class="nav flex-column">
                <a class="nav-link" href="admin_dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Tableau de bord
                </a>
                <a class="nav-link active" href="manage_users.php">
                    <i class="fas fa-users-cog"></i> Utilisateurs
                </a>
                <a class="nav-link" href="manage_departments.php">
                    <i class="fas fa-building"></i> Départements
                </a>
                <a class="nav-link" href="manage_services.php">
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
            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Header -->
            <a href="logout.php" class="logout-btn btn btn-outline-danger">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
            
            <div class="hospital-header">
                <img src="/Hms/img/logo.png" alt="Logo Kibris Aydin Hospital" class="hospital-logo">
                <div>
                    <h1 class="hospital-name mb-0">Kibris Aydin Hospital</h1>
                    <small class="text-muted">Gestion des utilisateurs</small>
                </div>
            </div>
            
            <!-- Bouton d'ajout -->
            <a href="add_user.php" class="add-btn">
                <i class="fas fa-user-plus"></i> Ajouter un utilisateur
            </a>
            
            <!-- Affichage des messages d'alerte -->
            <?php if (isset($_SESSION['alert'])): ?>
                <div class="alert alert-<?= safeOutput($_SESSION['alert']['type']) ?>">
                    <?= safeOutput($_SESSION['alert']['message']) ?>
                </div>
                <?php unset($_SESSION['alert']); ?>
            <?php endif; ?>
            
            <!-- Statistiques -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-label">Administrateurs</div>
                    <div class="stat-value"><?= $roleCounts['admin'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="stat-label">Médecins</div>
                    <div class="stat-value"><?= $roleCounts['doctor'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-procedures"></i>
                    </div>
                    <div class="stat-label">Patients</div>
                    <div class="stat-value"><?= $roleCounts['patient'] ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="stat-label">Réceptionnistes</div>
                    <div class="stat-value"><?= $roleCounts['receptionist'] ?></div>
                </div>
            </div>
            
            <!-- Barre de recherche et filtres -->
            <form method="GET" action="manage_users.php" class="search-form mb-3">
                <input type="text" name="search" placeholder="Rechercher un utilisateur..." 
                       value="<?= safeOutput($search) ?>">
                <button type="submit">
                    <i class="fas fa-search"></i> Rechercher
                </button>
                <?php if (!empty($search)): ?>
                    <a href="manage_users.php" class="btn btn-outline-secondary ml-2">
                        <i class="fas fa-times"></i> Réinitialiser
                    </a>
                <?php endif; ?>
            </form>
            
            <!-- Filtres avancés -->
            <div class="advanced-filters mb-4">
                <form method="GET" action="manage_users.php" class="row g-3">
                    <input type="hidden" name="search" value="<?= safeOutput($search) ?>">
                    
                    <div class="col-md-3">
                        <label for="roleFilter" class="form-label">Filtrer par rôle :</label>
                        <select name="role" id="roleFilter" class="form-select" onchange="this.form.submit()">
                            <option value="">Tous les rôles</option>
                            <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Administrateurs</option>
                            <option value="doctor" <?= $roleFilter === 'doctor' ? 'selected' : '' ?>>Médecins</option>
                            <option value="patient" <?= $roleFilter === 'patient' ? 'selected' : '' ?>>Patients</option>
                            <option value="receptionist" <?= $roleFilter === 'receptionist' ? 'selected' : '' ?>>Réceptionnistes</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="statusFilter" class="form-label">Filtrer par statut :</label>
                        <select name="status" id="statusFilter" class="form-select" onchange="this.form.submit()">
                            <option value="">Tous les statuts</option>
                            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Actifs</option>
                            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactifs</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter"></i> Appliquer
                        </button>
                        <a href="manage_users.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Réinitialiser
                        </a>
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-end justify-content-end">
                        <button type="button" class="btn btn-success" onclick="exportToCSV()">
                            <i class="fas fa-download"></i> Exporter CSV
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Tableau des utilisateurs -->
            <div class="table-responsive">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>
                                <a href="?<?= buildSortUrl('id') ?>">ID 
                                    <?= getSortIcon('id') ?>
                                </a>
                            </th>
                            <th>
                                <a href="?<?= buildSortUrl('username') ?>">Nom d'utilisateur
                                    <?= getSortIcon('username') ?>
                                </a>
                            </th>
                            <th>
                                <a href="?<?= buildSortUrl('first_name') ?>">Prénom
                                    <?= getSortIcon('first_name') ?>
                                </a>
                            </th>
                            <th>
                                <a href="?<?= buildSortUrl('last_name') ?>">Nom
                                    <?= getSortIcon('last_name') ?>
                                </a>
                            </th>
                            <th>
                                <a href="?<?= buildSortUrl('email') ?>">Email
                                    <?= getSortIcon('email') ?>
                                </a>
                            </th>
                            <th>
                                <a href="?<?= buildSortUrl('role') ?>">Rôle
                                    <?= getSortIcon('role') ?>
                                </a>
                            </th>
                            <th>Détails</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    Aucun utilisateur trouvé
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= safeOutput($user['id']) ?></td>
                                <td><?= safeOutput($user['username']) ?></td>
                                <td><?= safeOutput($user['first_name']) ?></td>
                                <td><?= safeOutput($user['last_name']) ?></td>
                                <td><?= safeOutput($user['email']) ?></td>
                                <td>
                                    <span class="badge 
                                        <?= match($user['role']) {
                                            'admin' => 'bg-primary',
                                            'doctor' => 'bg-success', 
                                            'patient' => 'bg-info',
                                            'receptionist' => 'bg-warning',
                                            default => 'bg-secondary'
                                        } ?>">
                                        <?= safeOutput($user['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['details']): ?>
                                        <?= safeOutput($user['details']) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" action="manage_users.php" class="toggle-form">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= $user['is_active'] ? '0' : '1' ?>">
                                        <label class="switch">
                                            <input type="checkbox" <?= $user['is_active'] ? 'checked' : '' ?> 
                                                   onchange="this.form.submit()">
                                            <span class="slider"></span>
                                        </label>
                                        <input type="hidden" name="toggle_status" value="1">
                                    </form>
                                </td>
                                <td class="text-center">
                                    <a href="view_user.php?id=<?= $user['id'] ?>" 
                                       class="action-btn view-btn"
                                       title="Voir détails">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_user.php?id=<?= $user['id'] ?>" 
                                       class="action-btn edit-btn"
                                       title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="manage_users.php" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="delete_user" value="1">
                                        <button type="submit" class="action-btn delete-btn" title="Supprimer"
                                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination améliorée -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center mt-4">
                    <!-- Premier -->
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=1&<?= http_build_query(array_filter([
                            'search' => $search,
                            'role' => $roleFilter,
                            'status' => $statusFilter,
                            'sort' => $sortField,
                            'order' => $sortOrder
                        ])) ?>">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                    </li>
                    
                    <!-- Précédent -->
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page-1 ?>&<?= http_build_query(array_filter([
                            'search' => $search,
                            'role' => $roleFilter,
                            'status' => $statusFilter,
                            'sort' => $sortField,
                            'order' => $sortOrder
                        ])) ?>">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    </li>
                    
                    <!-- Pages -->
                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    
                    if ($start > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    
                    for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(array_filter([
                                'search' => $search,
                                'role' => $roleFilter,
                                'status' => $statusFilter,
                                'sort' => $sortField,
                                'order' => $sortOrder
                            ])) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; 
                    
                    if ($end < $totalPages) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    ?>
                    
                    <!-- Suivant -->
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page+1 ?>&<?= http_build_query(array_filter([
                            'search' => $search,
                            'role' => $roleFilter,
                            'status' => $statusFilter,
                            'sort' => $sortField,
                            'order' => $sortOrder
                        ])) ?>">
                            <i class="fas fa-angle-right"></i>
                        </a>
                    </li>
                    
                    <!-- Dernier -->
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $totalPages ?>&<?= http_build_query(array_filter([
                            'search' => $search,
                            'role' => $roleFilter,
                            'status' => $statusFilter,
                            'sort' => $sortField,
                            'order' => $sortOrder
                        ])) ?>">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour exporter en CSV
        function exportToCSV() {
            // Créer une URL avec les mêmes paramètres que la page actuelle
            const params = new URLSearchParams(window.location.search);
            params.set('format', 'csv');
            
            // Ouvrir l'export dans un nouvel onglet
            window.open('manage_users.php?' + params.toString(), '_blank');
        }
        
        // Gestion des formulaires de toggle avec fetch pour une meilleure UX
        document.querySelectorAll('.toggle-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const submitButton = this.querySelector('[type="submit"]');
                
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                }
                
                fetch(this.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (response.redirected) {
                        window.location.href = response.url;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = '<i class="fas fa-sync-alt"></i>';
                    }
                    alert('Une erreur est survenue. Veuillez réessayer.');
                });
            });
        });
    </script>
</body>
</html>