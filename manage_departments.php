<?php
define('HMS_ACCESS', true);
require __DIR__ . '/../include/security.php';
require __DIR__ . '/../include/connection.php';
require __DIR__ . '/../include/functions.php';

$pdo = Database::getInstance();
initSession();

// Vérification session admin
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Configuration pagination
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;
$search = trim($_GET['search'] ?? '');

try {
    // Requête principale simplifiée
    $sql = "SELECT d.* FROM departement d";
    
    if ($search) {
        $sql .= " WHERE d.name LIKE :search OR d.description LIKE :search";
    }
    
    $sql .= " ORDER BY d.name ASC LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    
    if ($search) {
        $searchTerm = "%$search%";
        $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
    }
    
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Requête séparée pour compter les médecins
    foreach ($departments as &$dept) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE departement_id = ? AND role = 'doctor'");
        $stmt->execute([$dept['id']]);
        $dept['doctor_count'] = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name FROM users WHERE departement_id = ? AND role = 'doctor'");
        $stmt->execute([$dept['id']]);
        $dept['doctors'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    unset($dept);

    // Comptage total
    $countSql = "SELECT COUNT(*) FROM departement";
    if ($search) {
        $countSql .= " WHERE name LIKE :search OR description LIKE :search";
    }
    $countStmt = $pdo->prepare($countSql);
    if ($search) {
        $countStmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $total = $countStmt->fetchColumn();
    $totalPages = max(1, ceil($total / $perPage));

} catch (PDOException $e) {
    die("Erreur base de données : " . $e->getMessage());
}

// CSRF Token
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Départements | Kibris Aydin Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar style */
        .sidebar {
            width: 280px;
            background: var(--primary);
            color: white;
            padding: 1.5rem;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
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
        
        /* Main content */
        .main-content {
            flex: 1;
            padding: 2rem;
            background-color: white;
        }
        
        /* Table styles */
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            background-color: var(--primary);
            color: white;
            padding: 12px 15px;
            text-align: left;
        }
        
        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        
        .table tr:hover {
            background-color: #f8f9fa;
        }
        
        .badge-count {
            background-color: var(--secondary);
            color: white;
            border-radius: 50%;
            padding: 3px 8px;
            font-size: 0.8rem;
            display: inline-block;
        }
        
        .doctor-list {
            font-size: 0.85rem;
            color: #555;
            margin-top: 5px;
        }
        
        .search-box {
            max-width: 400px;
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                padding: 1rem;
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
                <h5><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></h5>
                <small class="text-white-50">Administrateur</small>
            </div>
            
            <nav class="nav flex-column">
                <a class="nav-link" href="admin_dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Tableau de bord
                </a>
                <a class="nav-link" href="manage_users.php">
                    <i class="fas fa-users-cog"></i> Utilisateurs
                </a>
                <a class="nav-link active" href="manage_departments.php">
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
                
                <a href="../logout.php" class="nav-link mt-3">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <h2 class="mb-4">Gestion des Départements</h2>
            
            <?php if (!empty($_SESSION['alert'])): ?>
                <div class="alert alert-<?= $_SESSION['alert']['type'] ?> alert-dismissible fade show">
                    <?= $_SESSION['alert']['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['alert']); ?>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="add_department.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i> Ajouter un département
                </a>
                
                <form method="GET" class="d-flex search-box">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Médecins</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($departments)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">Aucun département trouvé</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($departments as $dept): ?>
                                <tr>
                                    <td><?= htmlspecialchars($dept['id']) ?></td>
                                    <td><strong><?= htmlspecialchars($dept['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($dept['description'] ?? 'Aucune description') ?></td>
                                    <td>
                                        <span class="badge-count"><?= htmlspecialchars($dept['doctor_count']) ?></span>
                                        <?php if (!empty($dept['doctors'])): ?>
                                            <div class="doctor-list">
                                                <?= implode('<br>', array_map('htmlspecialchars', $dept['doctors'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="edit_department.php?id=<?= $dept['id'] ?>" 
                                           class="btn btn-sm btn-warning"
                                           title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_department.php?id=<?= $dept['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                                           class="btn btn-sm btn-danger"
                                           title="Supprimer"
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce département ?');">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                                    &laquo; Précédent
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
                                    Suivant &raquo;
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activer les tooltips Bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
</body>
</html>