<?php
if (!defined('HMS_ACCESS')) {
    define('HMS_ACCESS', true);
}
require_once __DIR__ . '/../include/security.php';
require_once __DIR__ . '/../include/connection.php';
require_once __DIR__ . '/../include/functions.php';

// Initialisation
$pdo = Database::getInstance();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
initSession();
checkAuth('admin');

class ReportManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getReports($filters = []) {
        try {
            $where = ["r.deleted_at IS NULL"];
            $params = [];
            
            // Gestion des filtres
            if (!empty($filters['status'])) {
                if ($filters['status'] === 'draft') {
                    $where[] = "r.is_draft = 1";
                } else {
                    $where[] = "r.status = ?";
                    $params[] = $filters['status'];
                }
            }
            
            if (!empty($filters['doctor_id'])) {
                $where[] = "r.doctor_id = ?";
                $params[] = (int)$filters['doctor_id'];
            }
            
            $sql = "SELECT 
                        r.id, 
                        r.report_date,
                        r.report_content as report_type,
                        r.patient_id,
                        r.doctor_id,
                        r.is_draft,
                        r.status,
                        r.priority,
                        r.created_at,
                        CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                        CONCAT(d.first_name, ' ', d.last_name) as doctor_name
                    FROM reports r
                    LEFT JOIN users p ON r.patient_id = p.id
                    LEFT JOIN users d ON r.doctor_id = d.id
                    WHERE " . implode(" AND ", $where) . "
                    ORDER BY r.created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Déterminer le statut affiché
            foreach ($reports as &$report) {
                $report['display_status'] = $report['is_draft'] ? 'draft' : ($report['status'] ?? 'final');
                $report['patient_name'] = $report['patient_name'] ?? 'N/A';
                $report['doctor_name'] = $report['doctor_name'] ?? 'N/A';
            }
            
            return $reports;
            
        } catch (PDOException $e) {
            error_log("Erreur SQL getReports: " . $e->getMessage());
            throw new Exception("Erreur technique lors du chargement des rapports");
        }
    }

    public function getDoctors() {
        try {
            $stmt = $this->pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) as name 
                                     FROM users 
                                     WHERE role = 'doctor' AND deleted_at IS NULL 
                                     ORDER BY name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur getDoctors: " . $e->getMessage());
            throw new Exception("Erreur lors du chargement des médecins");
        }
    }
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            throw new Exception("Token de sécurité invalide");
        }

        $reportId = (int)($_POST['report_id'] ?? 0);
        $reportManager = new ReportManager($pdo);
        
        switch ($_POST['action']) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE reports 
                                     SET status = 'approved', approved_at = NOW(), approved_by = ? 
                                     WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'], $reportId]);
                $_SESSION['success'] = "Rapport approuvé avec succès";
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("UPDATE reports 
                                     SET deleted_at = NOW(), deleted_by = ? 
                                     WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'], $reportId]);
                $_SESSION['success'] = "Rapport supprimé avec succès";
                break;
        }
        
        header("Location: manage_reports.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: manage_reports.php");
        exit();
    }
}

// Initialisation
$reportManager = new ReportManager($pdo);
$csrfToken = generateCsrfToken();
$error = null;
$reports = [];
$doctors = [];

try {
    $filters = [
        'status' => !empty($_GET['status']) ? sanitizeInput($_GET['status']) : null,
        'doctor_id' => !empty($_GET['doctor']) ? (int)$_GET['doctor'] : null
    ];
    
    $reports = $reportManager->getReports($filters);
    $doctors = $reportManager->getDoctors();
    
} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("Erreur manage_reports: " . $error);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Rapports | Kibris Aydin Hospital</title>
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
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }
        
        .sidebar {
            background: var(--primary);
            color: white;
            position: sticky;
            top: 0;
            height: 100vh;
        }
        
        .user-role {
            color: white !important;
            opacity: 0.8;
        }
        
        .table-responsive {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .badge {
            font-weight: 500;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            margin: 0 2px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar p-3">
            <div class="text-center mb-4">
                <i class="fas fa-user-shield fa-3x mb-3"></i>
                <h5><?= safeOutput($_SESSION['full_name'] ?? 'Admin') ?></h5>
                <div class="user-role">Administrateur</div>
            </div>
            
            <nav class="nav flex-column">
                <a class="nav-link text-white mb-2" href="admin_dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a class="nav-link text-white mb-2" href="list_doctors.php">
                    <i class="fas fa-user-md me-2"></i> Docteurs
                </a>
                <a class="nav-link text-white mb-2" href="list_patients.php">
                    <i class="fas fa-procedures me-2"></i> Patients
                </a>
                <a class="nav-link text-white mb-2" href="list_appointments.php">
                    <i class="fas fa-calendar-check me-2"></i> Rendez-vous
                </a>
                <a class="nav-link text-white mb-2" href="manage_inventory.php">
                    <i class="fas fa-boxes me-2"></i> Inventaire
                </a>
                <a class="nav-link text-white active mb-2" href="manage_reports.php">
                    <i class="fas fa-file-alt me-2"></i> Rapports
                </a>
            </nav>
            
            <div class="mt-auto pt-3">
                <a href="/Hms/logout.php" class="btn btn-outline-light w-100">
                    <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="fas fa-file-alt me-2"></i> Gestion des Rapports
                </h2>
                <a href="create_report.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i> Nouveau rapport
                </a>
            </div>
            
            <!-- Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= safeOutput($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= safeOutput($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= safeOutput($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Formulaire de recherche -->
            <form method="GET" class="bg-white p-3 rounded shadow-sm mb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Statut</label>
                        <select name="status" class="form-select">
                            <option value="">Tous statuts</option>
                            <option value="draft" <?= ($_GET['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                            <option value="submitted" <?= ($_GET['status'] ?? '') === 'submitted' ? 'selected' : '' ?>>Soumis</option>
                            <option value="approved" <?= ($_GET['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approuvé</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Médecin</label>
                        <select name="doctor" class="form-select">
                            <option value="">Tous médecins</option>
                            <?php foreach ($doctors as $doctor): ?>
                                <option value="<?= safeOutput($doctor['id']) ?>" 
                                    <?= ($_GET['doctor'] ?? '') == $doctor['id'] ? 'selected' : '' ?>>
                                    <?= safeOutput($doctor['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-filter me-2"></i> Filtrer
                        </button>
                        <a href="manage_reports.php" class="btn btn-outline-secondary">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </div>
            </form>
            
            <!-- Tableau des rapports -->
            <div class="table-responsive">
                <?php if (empty($reports)): ?>
                    <div class="alert alert-info text-center py-4">
                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                        <h4>Aucun rapport trouvé</h4>
                        <p class="mb-0">Essayez de modifier vos critères de recherche</p>
                    </div>
                <?php else: ?>
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Médecin</th>
                                <th>Type</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td><?= safeOutput($report['id']) ?></td>
                                    <td><?= formatDate($report['report_date']) ?></td>
                                    <td><?= safeOutput($report['patient_name']) ?></td>
                                    <td><?= safeOutput($report['doctor_name']) ?></td>
                                    <td><?= safeOutput($report['report_type']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            ($report['display_status'] ?? '') === 'approved' ? 'success' : 
                                            (($report['display_status'] ?? '') === 'submitted' ? 'warning' : 
                                            (($report['display_status'] ?? '') === 'draft' ? 'secondary' : 'primary'))
                                        ?>">
                                            <?= ucfirst($report['display_status'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a href="view_report.php?id=<?= safeOutput($report['id']) ?>" 
                                               class="action-btn btn btn-sm btn-info" title="Voir">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <?php if (($report['display_status'] ?? '') === 'draft'): ?>
                                                <a href="edit_report.php?id=<?= safeOutput($report['id']) ?>" 
                                                   class="action-btn btn btn-sm btn-warning" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= safeOutput($csrfToken) ?>">
                                                <input type="hidden" name="report_id" value="<?= safeOutput($report['id']) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="action-btn btn btn-sm btn-danger" title="Supprimer"
                                                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce rapport ?');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fermeture automatique des alertes après 5 secondes
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => {
                    new bootstrap.Alert(alert).close();
                });
            }, 5000);
            
            // Confirmation avant suppression
            document.querySelectorAll('form[action="delete"]').forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!confirm('Êtes-vous sûr de vouloir supprimer définitivement ce rapport ?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>