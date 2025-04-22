<?php
define('HMS_ACCESS', true);
require __DIR__ . '/../include/security.php';
require __DIR__ . '/../include/connection.php';

checkAuth('admin');

$pdo = Database::getInstance();

// Pagination
$patientsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $patientsPerPage;

// Recherche
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Requête SQL
$sql = "SELECT * FROM patients";
$countSql = "SELECT COUNT(*) FROM patients";

if (!empty($search)) {
    $searchTerm = "%$search%";
    $sql .= " WHERE last_name LIKE :search1 OR first_name LIKE :search2 OR email LIKE :search3 OR phone LIKE :search4";
    $countSql .= " WHERE last_name LIKE :search1 OR first_name LIKE :search2 OR email LIKE :search3 OR phone LIKE :search4";
}

// Nombre total de patients
try {
    $stmt = $pdo->prepare($countSql);
    if (!empty($search)) {
        $stmt->bindValue(':search1', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':search2', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':search3', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':search4', $searchTerm, PDO::PARAM_STR);
    }
    $stmt->execute();
    $totalPatients = $stmt->fetchColumn();
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// Liste des patients
try {
    $sql .= " ORDER BY last_name, first_name LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    if (!empty($search)) {
        $stmt->bindValue(':search1', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':search2', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':search3', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':search4', $searchTerm, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $patientsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

$alert = $_SESSION['alert'] ?? null;
unset($_SESSION['alert']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Patients | Kibris Aydin Hospital</title>
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
        
        /* Styles spécifiques à la liste des patients */
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
        
        .patient-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }
        
        .patient-table th {
            background-color: var(--primary);
            color: white;
            padding: 0.75rem;
            text-align: left;
        }
        
        .patient-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #eee;
        }
        
        .patient-table tr:hover {
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
                <a class="nav-link active" href="list_patients.php">
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
                
                <button class="btn btn-outline-light mt-3 w-100" onclick="toggleProfileForm()">
                    <i class="fas fa-user-edit"></i> Modifier profil
                </button>
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
                    <small class="text-muted">Liste des patients</small>
                </div>
            </div>
            
            <!-- Messages flash -->
            <?php if ($alert) : ?>
                <div class="alert alert-<?= $alert['type'] ?>">
                    <i class="fas fa-check-circle me-2"></i> <?= $alert['message'] ?>
                </div>
            <?php endif; ?>
            
            <!-- Barre de recherche -->
            <form method="GET" action="list_patients.php" class="search-container">
                <input type="text" name="search" class="search-input" 
                       placeholder="Rechercher un patient..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Rechercher
                </button>
            </form>
            
            <!-- Tableau des patients -->
           <table class="patient-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Sexe</th>
            <th>Email</th>
            <th>Téléphone</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($patients)) : ?>
            <tr>
                <td colspan="6" class="no-data">Aucun patient trouvé</td>
            </tr>
        <?php else : ?>
            <?php foreach ($patients as $patient) : ?>
                <tr>
                    <td><?= htmlspecialchars($patient['id']) ?></td>
                    <td><?= htmlspecialchars($patient['last_name']) ?></td>
                    <td><?= htmlspecialchars($patient['first_name']) ?></td>
                    <td><?= htmlspecialchars($patient['sex']) ?></td> <!-- Affiche directement M ou F -->
                    <td><?= htmlspecialchars($patient['email']) ?></td>
                    <td><?= htmlspecialchars($patient['phone']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($totalPatients > $patientsPerPage) : ?>
                <div class="pagination">
                    <?php
                    $totalPages = ceil($totalPatients / $patientsPerPage);
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