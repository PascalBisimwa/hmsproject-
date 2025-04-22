<?php
if (!defined('HMS_ACCESS')) {
    define('HMS_ACCESS', true);
}
require_once __DIR__ . '/../include/security.php';
require_once __DIR__ . '/../include/connection.php';
require_once __DIR__ . '/../include/functions.php';

// Initialisation
$pdo = Database::getInstance();
initSession();

class InventoryManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function validateItemData($data) {
        $errors = [];
        
        if (empty($data['item_name'])) {
            $errors[] = "Le nom de l'article est requis";
        }
        
        if (!isset($data['quantity']) || !is_numeric($data['quantity'])) {
            $errors[] = "La quantité doit être un nombre valide";
        }
        
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode("\n", $errors));
        }
    }

    public function addItem($data) {
        $this->validateItemData($data);
        
        $sql = "INSERT INTO inventory 
                (item_name, category_id, quantity, min_quantity, unit, departement_id, 
                 supplier, unit_price, location, batch_number, expiry_date, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['item_name'],
                $data['category_id'] ?? null,
                $data['quantity'],
                $data['min_quantity'] ?? null,
                $data['unit'] ?? null,
                $data['departement_id'] ?? null,
                $data['supplier'] ?? null,
                $data['unit_price'] ?? null,
                $data['location'] ?? null,
                $data['batch_number'] ?? null,
                $data['expiry_date'] ?? null,
                $data['notes'] ?? null
            ]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Erreur d'ajout d'article: " . $e->getMessage());
            throw new Exception("Erreur lors de l'ajout de l'article");
        }
    }

    public function getItemById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM inventory WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur de récupération d'article: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération de l'article");
        }
    }

    public function updateItem($id, $data) {
        $this->validateItemData($data);
        
        $sql = "UPDATE inventory SET 
                item_name = ?, 
                category_id = ?, 
                quantity = ?, 
                min_quantity = ?, 
                unit = ?, 
                departement_id = ?, 
                supplier = ?, 
                unit_price = ?, 
                location = ?, 
                batch_number = ?, 
                expiry_date = ?, 
                notes = ?,
                last_updated = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['item_name'],
                $data['category_id'] ?? null,
                $data['quantity'],
                $data['min_quantity'] ?? null,
                $data['unit'] ?? null,
                $data['departement_id'] ?? null,
                $data['supplier'] ?? null,
                $data['unit_price'] ?? null,
                $data['location'] ?? null,
                $data['batch_number'] ?? null,
                $data['expiry_date'] ?? null,
                $data['notes'] ?? null,
                $id
            ]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Erreur de mise à jour: " . $e->getMessage());
            throw new Exception("Erreur lors de la mise à jour de l'article");
        }
    }

    public function deleteItem($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM inventory WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Erreur de suppression: " . $e->getMessage());
            throw new Exception("Erreur lors de la suppression de l'article");
        }
    }

    public function listItems($page = 1, $perPage = 20, $departmentFilter = null, $search = '') {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $conditions = [];
        
        $sql = "SELECT i.*, d.name as department_name 
                FROM inventory i
                LEFT JOIN departement d ON i.departement_id = d.id";
        
        if ($departmentFilter !== null) {
            $conditions[] = "i.departement_id = ?";
            $params[] = $departmentFilter;
        }
        
        if (!empty($search)) {
            $conditions[] = "(i.item_name LIKE ? OR i.batch_number LIKE ? OR i.supplier LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY i.last_updated DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur de liste: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération des articles");
        }
    }

    public function getInventoryCount($departmentFilter = null, $search = '') {
        try {
            $sql = "SELECT COUNT(*) FROM inventory";
            $params = [];
            $conditions = [];
            
            if ($departmentFilter !== null) {
                $conditions[] = "departement_id = ?";
                $params[] = $departmentFilter;
            }
            
            if (!empty($search)) {
                $conditions[] = "(item_name LIKE ? OR batch_number LIKE ? OR supplier LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur de comptage: " . $e->getMessage());
            throw new Exception("Erreur lors du comptage des articles");
        }
    }

    public function getStockAlerts() {
        try {
            $stmt = $this->pdo->query(
                "SELECT i.*, d.name as department_name 
                 FROM inventory i
                 LEFT JOIN departement d ON i.departement_id = d.id
                 WHERE quantity < min_quantity 
                 ORDER BY (min_quantity - quantity) DESC"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur d'alertes: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération des alertes");
        }
    }

    public function getDepartments() {
        try {
            $stmt = $this->pdo->query("SELECT id, name FROM departement ORDER BY name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur de départements: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération des départements");
        }
    }

    public function getCategories() {
        return [
            'medicament' => 'Médicament',
            'equipement' => 'Équipement',
            'fourniture' => 'Fourniture'
        ];
    }
}

// Initialisation
$inventoryManager = new InventoryManager($pdo);
$csrfToken = generateCsrfToken();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            throw new Exception("Token CSRF invalide");
        }

        if ($_POST['action'] === 'add') {
            $_POST['unit_price'] = !empty($_POST['unit_price']) ? (float)$_POST['unit_price'] : null;
            $_POST['quantity'] = (int)$_POST['quantity'];
            $_POST['min_quantity'] = !empty($_POST['min_quantity']) ? (int)$_POST['min_quantity'] : null;
            
            $itemId = $inventoryManager->addItem($_POST);
            $_SESSION['message'] = "Article ajouté avec succès";
            header("Location: manage_inventory.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: manage_inventory.php");
        exit();
    }
}

// Paramètres de pagination et filtres
$page = max(1, (int)($_GET['page'] ?? 1));
$departmentFilter = isset($_GET['department_id']) ? (int)$_GET['department_id'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Récupération des données
try {
    $inventoryData = [
        'items' => $inventoryManager->listItems($page, 20, $departmentFilter, $search),
        'total' => $inventoryManager->getInventoryCount($departmentFilter, $search),
        'pages' => ceil($inventoryManager->getInventoryCount($departmentFilter, $search) / 20)
    ];
    $alerts = $inventoryManager->getStockAlerts();
    $departements = $inventoryManager->getDepartments();
    $categories = $inventoryManager->getCategories();
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Inventaire | Kibris Aydin Hospital</title>
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
            margin: 0;
        }
        
        .dashboard-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }
        
        /* Sidebar styles */
        .sidebar {
            background: var(--primary);
            color: white;
            padding: 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: sticky;
            top: 0;
        }
        
        .profile-section {
            text-align: center;
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1rem;
        }
        
        .profile-icon {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            color: rgba(255,255,255,0.8);
        }
        
        .user-name {
            color: white;
            font-size: 1rem;
            margin-bottom: 0.2rem;
        }
        
        .user-role {
            color: rgba(255,255,255,0.6);
            font-size: 0.8rem;
        }
        
        .nav-item {
            margin-bottom: 0.2rem;
        }
        
        .nav-link {
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.8);
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border-left: 3px solid var(--secondary);
            text-decoration: none;
        }
        
        .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        
        .main-content {
            padding: 2rem;
            background-color: white;
            position: relative;
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
        
        /* Search form styles */
        .search-form {
            margin-bottom: 2rem;
            display: flex;
            max-width: 600px;
            gap: 10px;
        }
        
        .search-form input {
            padding: 0.5rem 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.95rem;
        }
        
        .search-form select {
            padding: 0.5rem 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.95rem;
        }
        
        .search-form button {
            padding: 0 1.25rem;
            background-color: var(--secondary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Table styles */
        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }
        
        .inventory-table th {
            background-color: var(--primary);
            color: white;
            padding: 0.75rem;
            text-align: left;
            font-weight: 500;
        }
        
        .inventory-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        .inventory-table tr:not(:last-child) td {
            border-bottom: 1px solid #eee;
        }
        
        .inventory-table tr:hover {
            background-color: rgba(0,0,0,0.02);
        }
        
        /* Progress bar styles */
        .progress-container {
            width: 100%;
            min-width: 150px;
        }
        
        .progress {
            height: 24px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-bar {
            font-size: 12px;
            line-height: 24px;
            padding: 0 8px;
            text-align: left;
            white-space: nowrap;
        }
        
        .progress-bar.bg-success {
            background-color: var(--success) !important;
        }
        
        .progress-bar.bg-warning {
            background-color: var(--warning) !important;
            color: #000;
        }
        
        /* Action buttons */
        .actions-cell {
            white-space: nowrap;
        }
        
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
        
        /* Messages and alerts */
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
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-color: #ffeeba;
        }
        
        /* No data message */
        .no-data {
            text-align: center;
            padding: 2rem;
            color: #666;
            font-style: italic;
            background-color: #f9f9f9;
            border-radius: 4px;
        }
        
        /* Stats cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--secondary);
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
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
                flex-direction: column;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="profile-section">
                <div class="profile-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h5 class="user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></h5>
                <span class="user-role">Administrateur</span>
            </div>

            <ul class="nav flex-column flex-grow-1">
                <li class="nav-item">
                    <a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : '' ?>" href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'list_doctors.php' ? 'active' : '' ?>" href="list_doctors.php">
                        <i class="fas fa-user-md me-2"></i>Docteurs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'list_patients.php' ? 'active' : '' ?>" href="list_patients.php">
                        <i class="fas fa-procedures me-2"></i>Patients
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) == 'list_appointments.php' ? 'active' : '' ?>" href="list_appointments.php">
                        <i class="fas fa-calendar-check me-2"></i>Rendez-vous
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white active" href="manage_inventory.php">
                        <i class="fas fa-boxes me-2"></i>Inventaire
                    </a>
                </li>
            </ul>

            <div class="p-3">
                <a class="btn btn-danger w-100" href="/Hms/logout.php?redirect=graceful" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?');">
                    <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                </a>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
            <div class="hospital-header">
                <img src="/Hms/img/logo.png" alt="Logo Kibris Aydin Hospital" class="hospital-logo">
                <div>
                    <h1 class="hospital-name mb-0">Kibris Aydin Hospital</h1>
                    <small class="text-muted">Gestion de l'inventaire</small>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['message']) ?>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <!-- Alertes de stock -->
            <?php if (!empty($alerts)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong><?= count($alerts) ?> alerte(s) de stock faible</strong>
                </div>
            <?php endif; ?>
            
            <!-- Bouton d'ajout -->
            <button class="add-btn" data-bs-toggle="modal" data-bs-target="#addItemModal">
                <i class="fas fa-plus-circle"></i> Ajouter un article
            </button>
            
            <!-- Statistiques -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-boxes"></i></div>
                    <div class="stat-value"><?= htmlspecialchars($inventoryData['total']) ?></div>
                    <div class="stat-label">Articles en stock</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-value"><?= count($alerts) ?></div>
                    <div class="stat-label">Stocks faibles</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-building"></i></div>
                    <div class="stat-value"><?= count($departements) ?></div>
                    <div class="stat-label">Départements</div>
                </div>
            </div>
            
            <!-- Formulaire de recherche -->
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Rechercher..." 
                       value="<?= htmlspecialchars($search) ?>" class="form-control">
                <select name="department_id" class="form-select">
                    <option value="">Tous départements</option>
                    <?php foreach ($departements as $dept): ?>
                        <option value="<?= htmlspecialchars($dept['id']) ?>" 
                            <?= $dept['id'] == $departmentFilter ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Rechercher
                </button>
            </form>
            
            <!-- Tableau des articles -->
            <div class="table-responsive">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Article</th>
                            <th>Catégorie</th>
                            <th>Quantité</th>
                            <th>Fournisseur</th>
                            <th>Prix unitaire</th>
                            <th>Département</th>
                            <th>Dernière mise à jour</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($inventoryData['items'])): ?>
                            <tr>
                                <td colspan="9" class="no-data">Aucun article trouvé</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inventoryData['items'] as $item): ?>
                                <tr class="<?= $item['quantity'] < $item['min_quantity'] ? 'table-warning' : '' ?>">
                                    <td><?= htmlspecialchars($item['id']) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($item['item_name']) ?></strong>
                                        <?php if (!empty($item['batch_number'])): ?>
                                            <br><small>Lot: <?= htmlspecialchars($item['batch_number']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($categories[$item['category_id']] ?? $item['category_id']) ?></td>
                                    <td>
                                        <div class="progress-container">
                                            <div class="progress">
                                                <?php 
                                                $percent = ($item['min_quantity'] > 0) 
                                                    ? min(100, ($item['quantity'] / $item['min_quantity']) * 100)
                                                    : ($item['quantity'] > 0 ? 100 : 0);
                                                ?>
                                                <div class="progress-bar <?= $percent < 100 ? 'bg-warning' : 'bg-success' ?>" 
                                                     style="width: <?= $percent ?>%">
                                                    <?= htmlspecialchars($item['quantity']) ?>
                                                    <?= !empty($item['unit']) ? htmlspecialchars($item['unit']) : '' ?>
                                                </div>
                                            </div>
                                            <?php if ($item['min_quantity'] > 0): ?>
                                                <small>Seuil: <?= htmlspecialchars($item['min_quantity']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($item['supplier'] ?? 'N/A') ?></td>
                                    <td><?= !empty($item['unit_price']) ? number_format($item['unit_price'], 2) . ' €' : 'N/A' ?></td>
                                    <td><?= htmlspecialchars($item['department_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($item['last_updated']) ?></td>
                                    <td class="actions-cell">
                                        <a href="edit_item.php?id=<?= htmlspecialchars($item['id']) ?>" 
                                           class="action-btn edit-btn" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_item.php?id=<?= htmlspecialchars($item['id']) ?>" 
                                           class="action-btn delete-btn" title="Supprimer"
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet article ?');">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($inventoryData['pages'] > 1): ?>
                <nav class="pagination-container">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" 
                                   href="?page=<?= $page-1 ?>&department_id=<?= $departmentFilter ?>&search=<?= urlencode($search) ?>">
                                    &laquo;
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $inventoryData['pages']; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" 
                                   href="?page=<?= $i ?>&department_id=<?= $departmentFilter ?>&search=<?= urlencode($search) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $inventoryData['pages']): ?>
                            <li class="page-item">
                                <a class="page-link" 
                                   href="?page=<?= $page+1 ?>&department_id=<?= $departmentFilter ?>&search=<?= urlencode($search) ?>">
                                    &raquo;
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal d'ajout -->
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" action="manage_inventory.php">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Ajouter un article</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nom*</label>
                                <input type="text" name="item_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Catégorie*</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Sélectionner...</option>
                                    <?php foreach ($categories as $id => $name): ?>
                                        <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Quantité*</label>
                                <input type="number" name="quantity" class="form-control" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Stock minimum</label>
                                <input type="number" name="min_quantity" class="form-control" min="0">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Unité</label>
                                <input type="text" name="unit" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Prix unitaire (€)</label>
                                <input type="number" step="0.01" name="unit_price" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Département</label>
                                <select name="departement_id" class="form-select">
                                    <option value="">Aucun</option>
                                    <?php foreach ($departements as $dept): ?>
                                        <option value="<?= htmlspecialchars($dept['id']) ?>">
                                            <?= htmlspecialchars($dept['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fournisseur</label>
                                <input type="text" name="supplier" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Numéro de lot</label>
                                <input type="text" name="batch_number" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date d'expiration</label>
                                <input type="date" name="expiry_date" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Emplacement</label>
                                <input type="text" name="location" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Confirmation avant suppression
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (!confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>