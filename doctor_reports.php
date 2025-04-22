<?php
require_once __DIR__ . '/../include/connection.php';
require_once __DIR__ . '/../include/security.php';

initSession();
checkAuth('doctor');

try {
    $pdo = Database::getInstance();
    
    // Récupérer les rapports du docteur
    $stmt = $pdo->prepare("
        SELECT r.id, r.report_date, r.report_content,
               CONCAT(u.first_name, ' ', u.last_name) as patient_name
        FROM reports r
        JOIN users u ON r.patient_id = u.id
        WHERE r.doctor_id = ?
        ORDER BY r.report_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $reports = $stmt->fetchAll();

} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de chargement des rapports";
    header("Location: doctor_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports Médicaux | Kibris Aydin Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== STYLES GÉNÉRAUX ===== */
        :root {
            --sidebar-width: 250px;
            --primary-dark: #2c3e50;
            --primary-light: #3d566e;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --text-dark: #333;
            --text-light: #6c757d;
            --bg-light: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        /* ===== SIDEBAR STYLE ===== */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-light));
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100%;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .profile-section {
            text-align: center;
            padding: 0 15px;
            margin-bottom: 25px;
        }
        
        .profile-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background-color: white;
            color: var(--primary-dark);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 5px;
        }
        
        .user-role {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.8);
        }
        
        .nav-menu {
            padding: 0 10px;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 10px 15px;
            margin: 2px 0;
            border-radius: 5px;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: rgba(255,255,255,0.15);
            color: white;
            text-decoration: none;
        }
        
        .nav-link i {
            width: 25px;
            text-align: center;
            margin-right: 10px;
            font-size: 1rem;
        }
        
        .badge-notification {
            font-size: 0.7rem;
            padding: 3px 6px;
            margin-left: auto;
        }
        
        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
        }
        
        .header-container {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .hospital-logo {
            height: 40px;
            margin-right: 15px;
        }
        
        .header-title {
            font-size: 1.5rem;
            color: var(--primary-dark);
            margin-bottom: 5px;
        }
        
        .doctor-info {
            font-size: 0.9rem;
            color: var(--text-light);
        }
        
        /* ===== REPORT TABLE ===== */
        .card-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .card-header {
            background: var(--primary-dark);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 1.1rem;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .card-title i {
            margin-right: 10px;
        }
        
        .btn-new-report {
            background: white;
            color: var(--primary-dark);
            font-size: 0.85rem;
            padding: 5px 10px;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            background-color: #f1f5f9;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 12px 15px;
        }
        
        .table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
            font-size: 0.9rem;
        }
        
        .priority-high {
            background-color: rgba(220, 53, 69, 0.05);
            border-left: 3px solid #dc3545;
        }
        
        .priority-medium {
            background-color: rgba(255, 193, 7, 0.05);
            border-left: 3px solid #ffc107;
        }
        
        .badge-status {
            font-weight: 500;
            padding: 5px 8px;
            font-size: 0.8rem;
            min-width: 70px;
            display: inline-block;
            text-align: center;
        }
        
        .action-buttons {
            white-space: nowrap;
        }
        
        .btn-action {
            padding: 5px 8px;
            font-size: 0.8rem;
            margin-right: 5px;
        }
        
        .empty-table-message {
            padding: 30px;
            text-align: center;
            color: var(--text-light);
            font-style: italic;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            .sidebar {
                width: 220px;
            }
            
            .main-content {
                margin-left: 220px;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .header-container {
                flex-direction: column;
                text-align: center;
            }
            
            .hospital-logo {
                margin-right: 0;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar identique au dashboard -->
        <div class="sidebar">
            <div class="profile-section">
                <div class="profile-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="user-name"><?= htmlspecialchars($doctor['full_name']) ?></div>
                <div class="user-role">Médecin</div>
            </div>
            
            <div class="nav-menu">
                <a class="nav-link" href="doctors_dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Tableau de bord
                </a>
                <a class="nav-link" href="doctor_appointments.php">
                    <i class="fas fa-calendar-check"></i> Rendez-vous
                    <?php if ($today_appointments > 0): ?>
                        <span class="badge badge-notification bg-danger"><?= $today_appointments ?></span>
                    <?php endif; ?>
                </a>
                <a class="nav-link" href="doctor_patients.php">
                    <i class="fas fa-procedures"></i> Patients
                </a>
                <a class="nav-link" href="doctor_messages.php">
                    <i class="fas fa-comments"></i> Messagerie
                    <?php if ($unread_messages > 0): ?>
                        <span class="badge badge-notification bg-danger"><?= $unread_messages ?></span>
                    <?php endif; ?>
                </a>
                <a class="nav-link active" href="doctor_reports.php">
                    <i class="fas fa-file-medical"></i> Rapports
                </a>
            </div>
        </div>

        <div class="main-content">
            <div class="header-container">
                <img src="/Hms/img/logo.png" alt="Logo" class="hospital-logo">
                <div>
                    <h1 class="header-title">Gestion des Rapports</h1>
                    <div class="doctor-info">
                        Dr. <?= htmlspecialchars($doctor['full_name']) ?> | 
                        <?= htmlspecialchars($doctor['department_name']) ?> | 
                        <?= htmlspecialchars($doctor['service_name']) ?>
                    </div>
                </div>
            </div>

            <!-- Tableau des rapports -->
            <div class="card-container">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-file-medical"></i> Liste des Rapports
                    </h2>
                    <a href="create_report.php" class="btn btn-new-report">
                        <i class="fas fa-plus"></i> Nouveau
                    </a>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Type</th>
                                <th>Priorité</th>
                                <th>Confidentialité</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reports)): ?>
                                <tr>
                                    <td colspan="7" class="empty-table-message">Aucun rapport enregistré</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reports as $report): ?>
                                <tr class="<?= $report['priority'] === 'high' ? 'priority-high' : ($report['priority'] === 'medium' ? 'priority-medium' : '') ?>">
                                    <td><?= date('d/m/Y', strtotime($report['report_date'])) ?></td>
                                    <td><?= htmlspecialchars($report['patient_name']) ?></td>
                                    <td><?= htmlspecialchars($report['report_type']) ?></td>
                                    <td>
                                        <span class="badge badge-status bg-<?= $report['priority'] === 'high' ? 'danger' : ($report['priority'] === 'medium' ? 'warning' : 'success') ?>">
                                            <?= $priority_levels[$report['priority']] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-status bg-<?= $report['confidentiality_level'] === 'secret' ? 'danger' : ($report['confidentiality_level'] === 'confidential' ? 'warning' : 'secondary') ?>">
                                            <?= $confidentiality_levels[$report['confidentiality_level']] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($report['is_draft']): ?>
                                            <span class="badge badge-status bg-warning">Brouillon</span>
                                        <?php elseif ($report['signature_status']): ?>
                                            <span class="badge badge-status bg-success">Signé</span>
                                        <?php else: ?>
                                            <span class="badge badge-status bg-primary">Finalisé</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-buttons">
                                        <a href="view_report.php?id=<?= $report['id'] ?>" class="btn btn-action btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($report['is_draft'] && !$report['is_locked']): ?>
                                        <a href="edit_report.php?id=<?= $report['id'] ?>" class="btn btn-action btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>