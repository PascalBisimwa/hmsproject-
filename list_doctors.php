<?php
// Protection contre l'accès direct
define('HMS_ACCESS', true);
require __DIR__ . '/../include/security.php';
require __DIR__ . '/../include/connection.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
checkAuth('admin');

// Initialisation PDO
$pdo = Database::getInstance();

// Pagination
$doctorsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $doctorsPerPage;

// Récupérer le terme de recherche
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Requête SQL de base
$baseSql = "SELECT 
            u.id, 
            u.first_name, 
            u.last_name,
            u.sex,
            CONCAT(u.first_name, ' ', u.last_name) AS full_name,
            u.email, 
            u.phone,
            d.speciality,
            s.name AS service_name,
            dep.name AS department_name
        FROM users u
        JOIN doctors d ON u.id = d.user_id
        LEFT JOIN services s ON u.service_id = s.id
        LEFT JOIN departement dep ON u.departement_id = dep.id";

$countSql = "SELECT COUNT(*) 
            FROM users u
            JOIN doctors d ON u.id = d.user_id
            LEFT JOIN departement dep ON u.departement_id = dep.id";

// Gestion de la recherche
if (!empty($search)) {
    $searchTerm = "%$search%";
    $where = " WHERE (u.first_name LIKE :search OR u.last_name LIKE :search 
              OR u.email LIKE :search OR u.phone LIKE :search
              OR d.speciality LIKE :search
              OR dep.name LIKE :search
              OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search)";
    
    $baseSql .= $where;
    $countSql .= $where;
}
// Compter le nombre total de médecins
try {
    $stmt = $pdo->prepare($countSql);
    
    if (!empty($search)) {
        $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $totalDoctors = $stmt->fetchColumn();
} catch (PDOException $e) {
    die("Erreur de comptage : " . $e->getMessage());
}

// Récupérer la liste des médecins
try {
    $sql = $baseSql . " ORDER BY u.last_name, u.first_name LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    
    if (!empty($search)) {
        $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
    }
    
    $stmt->bindValue(':limit', $doctorsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de requête : " . $e->getMessage());
}

// Messages flash
$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Médecins | Kibris Aydin Hospital</title>
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
        
        /* Styles spécifiques */
        .search-container {
            display: flex;
            margin-bottom: 1.5rem;
            gap: 10px;
        }
        
        .search-input {
            flex: 1;
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .search-btn {
            background-color: var(--secondary);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0.5rem 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .doctor-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }
        
        .doctor-table th {
            background-color: var(--primary);
            color: white;
            padding: 0.75rem;
            text-align: left;
        }
        
        .doctor-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #eee;
        }
        
        .doctor-table tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .gender-male {
            color: var(--info);
        }
        
        .gender-female {
            color: #e83e8c;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 1.5rem;
        }
        
        .page-link {
            padding: 0.5rem 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: var(--primary);
            text-decoration: none;
        }
        
        .page-link.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .no-data {
            text-align: center;
            padding: 2rem;
            color: #666;
            font-style: italic;
        }
        
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
        
        /* Responsive */
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
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="text-center mb-4">
                <div class="profile-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h5><?= htmlspecialchars($_SESSION['full_name']) ?></h5>
                <small class="text-white-50">Administrateur</small>
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
                <a class="nav-link" href="manage_services.php">
                    <i class="fas fa-concierge-bell"></i> Services
                </a>
                <a class="nav-link" href="list_patients.php">
                    <i class="fas fa-procedures"></i> Patients
                </a>
                <a class="nav-link active" href="list_doctors.php">
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
            <a href="logout.php" class="logout-btn btn btn-outline-danger">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
            
            <div class="hospital-header">
                <img src="/Hms/img/logo.png" alt="Logo Kibris Aydin Hospital" class="hospital-logo">
                <div>
                    <h1 class="hospital-name mb-0">Kibris Aydin Hospital</h1>
                    <small class="text-muted">Liste des médecins</small>
                </div>
            </div>
            
            <!-- Messages flash -->
            <?php if ($alert) : ?>
                <div class="alert alert-<?= $alert['type'] ?>">
                    <i class="fas fa-check-circle me-2"></i> <?= $alert['message'] ?>
                </div>
            <?php endif; ?>
            
            <!-- Barre de recherche -->
            <form method="GET" action="list_doctors.php" class="search-container">
                <input type="text" name="search" class="search-input" 
                       placeholder="Rechercher un médecin..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Rechercher
                </button>
            </form>
            
            <!-- Tableau des médecins -->
            <table class="doctor-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Sexe</th>
                        <th>Spécialité</th>
                        <th>Service</th>
                        <th>Contact</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($doctors)) : ?>
                        <tr>
                            <td colspan="7" class="no-data">
                                <?= empty($search) ? 'Aucun médecin enregistré' : 'Aucun résultat pour "' . htmlspecialchars($search) . '"' ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($doctors as $doctor) : ?>
                            <tr>
                                <td><?= htmlspecialchars($doctor['id']) ?></td>
                                <td><?= htmlspecialchars($doctor['last_name']) ?></td>
                                <td><?= htmlspecialchars($doctor['first_name']) ?></td>
                                <td>
                                    <?php 
                                    $gender = strtoupper($doctor['sex'] ?? '');
                                    if ($gender === 'M'): ?>
                                        <span class="gender-male">M</span>
                                    <?php elseif ($gender === 'F'): ?>
                                        <span class="gender-female">F</span>
                                    <?php else: ?>
                                        <span>-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($doctor['speciality'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($doctor['service_name'] ?? 'N/A') ?></td>
                                <td>
                                    <div><?= htmlspecialchars($doctor['email']) ?></div>
                                    <div><?= htmlspecialchars($doctor['phone']) ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($totalDoctors > $doctorsPerPage) : ?>
                <div class="pagination">
                    <?php
                    $totalPages = ceil($totalDoctors / $doctorsPerPage);
                    for ($i = 1; $i <= $totalPages; $i++) :
                        $active = ($i === $page) ? 'active' : '';
                    ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="page-link <?= $active ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleProfileForm() {
            const form = document.getElementById('profile-form');
            if (form) {
                form.style.display = form.style.display === 'none' ? 'block' : 'none';
            }
        }
    </script>
</body>
</html>