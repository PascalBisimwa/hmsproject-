<?php
require_once __DIR__ . '/../include/connection.php';
require_once __DIR__ . '/../include/security.php';

session_start();
checkAuth('doctor');

// Constantes pour les statuts
define('PRIORITY_HIGH', 'high');
define('PRIORITY_MEDIUM', 'medium');
define('PRIORITY_LOW', 'low');

$priority_levels = [
    PRIORITY_HIGH => 'Haute',
    PRIORITY_MEDIUM => 'Moyenne',
    PRIORITY_LOW => 'Basse'
];

$confidentiality_levels = [
    'secret' => 'Secret',
    'confidential' => 'Confidentiel',
    'normal' => 'Normal'
];

try {
    // Récupération des données
    $stmt = $pdo->prepare("SELECT d.*, u.full_name, 
                          COALESCE(dep.name, 'Non spécifié') as department_name, 
                          COALESCE(s.name, 'Non spécifié') as service_name
                          FROM doctors d
                          JOIN users u ON d.user_id = u.id
                          LEFT JOIN departements dep ON u.departement_id = dep.id
                          LEFT JOIN services s ON u.service_id = s.id
                          WHERE d.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $doctor = $stmt->fetch();

    if (!$doctor) throw new Exception("Profil docteur introuvable");

    // Récupération des rapports
    $stmt = $pdo->prepare("SELECT r.*, 
                          CONCAT(p.first_name, ' ', p.last_name) AS patient_name
                          FROM reports r
                          JOIN patients p ON r.patient_id = p.user_id
                          WHERE r.doctor_id = ? 
                          ORDER BY r.created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $reports = $stmt->fetchAll();

} catch (Exception $e) {
    die("Erreur: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Rapports | Kibris Aydin Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --light-gray: #f8f9fa;
        }
        
        body {
            background: #f4f7fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, var(--primary-color), #1a5276);
            color: white;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .profile-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: white;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 2rem;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .user-role {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.8);
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
            text-decoration: none;
        }
        
        .nav-link i {
            width: 25px;
            text-align: center;
            margin-right: 10px;
        }
        
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        
        .hospital-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .hospital-logo {
            height: 50px;
            margin-right: 15px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border-left: 4px solid;
            transition: transform 0.3s;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.appointments {
            border-color: #4caf50;
        }
        
        .stat-card.patients {
            border-color: #2196f3;
        }
        
        .stat-card.messages {
            border-color: #ff9800;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 15px 0;
        }
        
        .report-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .badge-file {
            background-color: #6c757d;
        }
        
        .action-btns .btn {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="text-center mb-4">
                <div class="profile-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="user-name"><?= htmlspecialchars($doctor['full_name']) ?></div>
                <div class="user-role"><?= htmlspecialchars($doctor['speciality'] ?? 'Médecin') ?></div>
            </div>
            
            <nav class="nav flex-column">
                <a class="nav-link" href="doctors_dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Tableau de bord
                </a>
                <a class="nav-link" href="doctor_appointments.php">
                    <i class="fas fa-calendar-check"></i> Rendez-vous
                    <?php if ($today_appointments > 0): ?>
                        <span class="badge bg-danger float-end"><?= $today_appointments ?></span>
                    <?php endif; ?>
                </a>
                <a class="nav-link" href="doctor_patients.php">
                    <i class="fas fa-procedures"></i> Patients
                </a>
                <a class="nav-link" href="doctor_messages.php">
                    <i class="fas fa-comments"></i> Messagerie
                    <?php if ($unread_messages > 0): ?>
                        <span class="badge bg-danger float-end"><?= $unread_messages ?></span>
                    <?php endif; ?>
                </a>
                <a class="nav-link active" href="doctor_reports.php">
                    <i class="fas fa-file-medical"></i> Rapports
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="hospital-header">
                <h1><i class="fas fa-file-medical me-2"></i> Gestion des Rapports</h1>
            </div>
            
            <!-- Cartes de statistiques -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-card appointments">
                        <h5><i class="fas fa-calendar-day me-2"></i> RDV aujourd'hui</h5>
                        <div class="stat-value"><?= $today_appointments ?></div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="stat-card patients">
                        <h5><i class="fas fa-user-injured me-2"></i> Patients</h5>
                        <div class="stat-value"><?= $total_patients ?></div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="stat-card messages">
                        <h5><i class="fas fa-envelope me-2"></i> Messages</h5>
                        <div class="stat-value"><?= $unread_messages ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Section Rapports -->
            <div class="card border-0 shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i> Liste des Rapports
                    </h5>
                    <a href="create_report.php" class="btn btn-sm btn-light">
                        <i class="fas fa-plus me-1"></i> Nouveau Rapport
                    </a>
                </div>
                
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Type</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reports)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">Aucun rapport disponible</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reports as $report): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($report['created_at'])) ?></td>
                                        <td><?= htmlspecialchars($report['patient_name']) ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?= htmlspecialchars($report['report_type']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($report['is_draft']): ?>
                                                <span class="badge bg-warning">Brouillon</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Finalisé</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="action-btns">
                                            <a href="view_report.php?id=<?= $report['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_report.php?id=<?= $report['id'] ?>" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>