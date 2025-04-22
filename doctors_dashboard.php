<?php
define('HMS_ACCESS', true);
require __DIR__ . '/../include/security.php';
require __DIR__ . '/../include/connection.php';

// Vérification du rôle
Security::checkAuth(['doctor']);

// Récupération des données du docteur
$pdo = Database::getInstance();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$doctor = $stmt->fetch();

if (!$doctor) {
    header("Location: /Hms/logout.php");
    exit();
}

// Récupération des statistiques (exemple)
$stats = [
    'appointments' => $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ?")
                         ->execute([$_SESSION['user_id']])->fetchColumn(),
    'patients' => $pdo->prepare("SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id = ?")
                     ->execute([$_SESSION['user_id']])->fetchColumn()
];


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Docteur | HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --light: #f8f9fa;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
        }
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, var(--primary), #1a5276);
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100%;
            transition: all 0.3s;
        }
        .profile-section {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .profile-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            margin-bottom: 15px;
        }
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 25px;
            margin: 5px 0;
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left: 3px solid var(--secondary);
        }
        .badge-notification {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
        }
        .main-content {
            flex: 1;
            padding: 30px;
            margin-left: 280px;
            transition: all 0.3s;
        }
        .stat-card {
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.3;
            position: absolute;
            right: 20px;
            top: 20px;
        }
        .appointment-badge {
            font-size: 0.75rem;
            padding: 3px 6px;
        }
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
                overflow: hidden;
            }
            .sidebar .nav-text {
                display: none;
            }
            .main-content {
                margin-left: 80px;
            }
            .profile-section {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="profile-section">
                <img src="/Hms/uploads/avatars/<?= $doctor['id'] ?>.jpg" 
                     onerror="this.src='/Hms/img/default-avatar.jpg'" 
                     class="profile-img" 
                     alt="Photo profil">
                <h5 class="mb-1"><?= htmlspecialchars($doctor['full_name']) ?></h5>
                <small class="text-white-50"><?= htmlspecialchars($doctor['speciality']) ?></small>
                <div class="mt-2">
                    <span class="badge bg-success"><?= htmlspecialchars($doctor['license_number']) ?></span>
                </div>
            </div>
            
            <nav class="nav flex-column mt-3">
                <a class="nav-link active" href="/Hms/doctors/doctors_dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    <span class="nav-text">Tableau de bord</span>
                </a>
                <a class="nav-link" href="/Hms/doctors/appointments.php">
                    <i class="fas fa-calendar-check me-2"></i>
                    <span class="nav-text">Rendez-vous</span>
                    <?php if ($stats['today_appointments'] > 0): ?>
                        <span class="badge bg-danger badge-notification"><?= $stats['today_appointments'] ?></span>
                    <?php endif; ?>
                </a>
                <a class="nav-link" href="/Hms/doctors/patients.php">
                    <i class="fas fa-procedures me-2"></i>
                    <span class="nav-text">Patients</span>
                    <span class="badge bg-primary badge-notification"><?= $stats['total_patients'] ?></span>
                </a>
                <a class="nav-link" href="/Hms/doctors/reports.php">
                    <i class="fas fa-file-medical me-2"></i>
                    <span class="nav-text">Rapports</span>
                    <?php if ($stats['pending_reports'] > 0): ?>
                        <span class="badge bg-danger badge-notification"><?= $stats['pending_reports'] ?></span>
                    <?php endif; ?>
                </a>
                <a class="nav-link" href="/Hms/doctors/prescriptions.php">
                    <i class="fas fa-prescription me-2"></i>
                    <span class="nav-text">Ordonnances</span>
                </a>
                <a class="nav-link" href="/Hms/doctors/schedule.php">
                    <i class="fas fa-clock me-2"></i>
                    <span class="nav-text">Disponibilités</span>
                </a>
                <div class="mt-auto">
                    <a href="/Hms/logout.php" class="nav-link text-white">
                        <i class="fas fa-sign-out-alt me-2"></i>
                        <span class="nav-text">Déconnexion</span>
                    </a>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">
                        <i class="fas fa-tachometer-alt me-2"></i> Tableau de bord
                    </h2>
                    <small class="text-muted">
                        <?= date('l, d F Y') ?> | 
                        Département: <?= htmlspecialchars($doctor['department_name'] ?? 'Non spécifié') ?>
                    </small>
                </div>
                <div class="d-flex align-items-center">
                    <div class="me-3 text-end">
                        <div class="fw-bold">Dr. <?= htmlspecialchars($doctor['full_name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($doctor['service_name'] ?? '') ?></small>
                    </div>
                    <img src="/Hms/img/logo-sm.png" alt="Logo" style="height: 40px;">
                </div>
            </div>

            <!-- Alertes -->
            <?php if (!empty($alerts)): ?>
                <div class="alert alert-warning alert-dismissible fade show mb-4">
                    <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i> Alertes</h5>
                    <ul class="mb-0">
                        <?php foreach ($alerts as $alert): ?>
                            <li><?= htmlspecialchars($alert['message']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Messages système -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <!-- Cartes statistiques -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stat-card bg-white shadow-sm">
                        <div class="card-body position-relative">
                            <i class="fas fa-calendar-day text-success stat-icon"></i>
                            <h5 class="text-success mb-3">RDV Aujourd'hui</h5>
                            <div class="d-flex justify-content-between align-items-end">
                                <div>
                                    <h2 class="text-success mb-0"><?= $stats['today_appointments'] ?></h2>
                                    <small class="text-muted">Patients à voir</small>
                                </div>
                                <a href="/Hms/doctors/appointments.php?filter=today" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-list"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card bg-white shadow-sm">
                        <div class="card-body position-relative">
                            <i class="fas fa-users text-primary stat-icon"></i>
                            <h5 class="text-primary mb-3">Patients</h5>
                            <div class="d-flex justify-content-between align-items-end">
                                <div>
                                    <h2 class="text-primary mb-0"><?= $stats['total_patients'] ?></h2>
                                    <small class="text-muted">Patients suivis</small>
                                </div>
                                <a href="/Hms/doctors/patients.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-list"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card bg-white shadow-sm">
                        <div class="card-body position-relative">
                            <i class="fas fa-file-medical text-danger stat-icon"></i>
                            <h5 class="text-danger mb-3">Rapports en attente</h5>
                            <div class="d-flex justify-content-between align-items-end">
                                <div>
                                    <h2 class="text-danger mb-0"><?= $stats['pending_reports'] ?></h2>
                                    <small class="text-muted">À compléter</small>
                                </div>
                                <a href="/Hms/doctors/reports.php?status=pending" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-list"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="stat-card bg-white shadow-sm">
                        <div class="card-body position-relative">
                            <i class="fas fa-calendar-alt text-info stat-icon"></i>
                            <h5 class="text-info mb-3">RDV ce mois</h5>
                            <div class="d-flex justify-content-between align-items-end">
                                <div>
                                    <h2 class="text-info mb-0"><?= $stats['monthly_appointments'] ?></h2>
                                    <small class="text-muted">Total consultations</small>
                                </div>
                                <a href="/Hms/doctors/appointments.php?filter=month" class="btn btn-sm btn-outline-info">
                                    <i class="fas fa-list"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Prochains RDV et Rapports -->
            <div class="row g-4 mb-4">
                <div class="col-md-8">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-calendar-alt me-2"></i> Prochains rendez-vous
                            </div>
                            <a href="/Hms/doctors/appointments.php" class="btn btn-sm btn-light">
                                <i class="fas fa-plus"></i> Nouveau
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date/Heure</th>
                                            <th>Patient</th>
                                            <th>Âge/Sexe</th>
                                            <th>Motif</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($appointments)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                                                    <p>Aucun rendez-vous à venir</p>
                                                    <a href="/Hms/doctors/appointments.php" class="btn btn-sm btn-primary">
                                                        Planifier un rendez-vous
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($appointments as $apt): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-bold"><?= date('d/m/Y', strtotime($apt['appointment_date'])) ?></div>
                                                        <small><?= date('H:i', strtotime($apt['appointment_date'])) ?></small>
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold"><?= htmlspecialchars($apt['patient_name']) ?></div>
                                                        <small class="text-muted"><?= htmlspecialchars($apt['patient_phone']) ?></small>
                                                    </td>
                                                    <td>
                                                        <?= $apt['patient_age'] ?> ans
                                                        <span class="badge bg-<?= $apt['patient_gender'] === 'M' ? 'primary' : 'danger' ?> appointment-badge">
                                                            <?= $apt['patient_gender'] === 'M' ? 'Homme' : 'Femme' ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($apt['reason'] ?? 'Consultation') ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="/Hms/doctors/appointment_details.php?id=<?= $apt['id'] ?>" 
                                                               class="btn btn-sm btn-outline-primary" 
                                                               title="Détails">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="/Hms/doctors/reports.php?appointment_id=<?= $apt['id'] ?>" 
                                                               class="btn btn-sm btn-outline-success" 
                                                               title="Créer rapport">
                                                                <i class="fas fa-file-medical"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-light">
                            <a href="/Hms/doctors/appointments.php" class="btn btn-sm btn-primary float-end">
                                Voir tous les rendez-vous <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-tasks me-2"></i> Actions rapides
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="/Hms/doctors/reports.php?action=new" class="btn btn-primary btn-sm text-start">
                                    <i class="fas fa-file-medical me-2"></i> Nouveau rapport médical
                                </a>
                                <a href="/Hms/doctors/prescriptions.php?action=new" class="btn btn-info btn-sm text-start">
                                    <i class="fas fa-prescription me-2"></i> Nouvelle ordonnance
                                </a>
                                <a href="/Hms/doctors/patients.php?action=new" class="btn btn-warning btn-sm text-start">
                                    <i class="fas fa-user-plus me-2"></i> Nouveau patient
                                </a>
                                <a href="/Hms/doctors/schedule.php" class="btn btn-secondary btn-sm text-start">
                                    <i class="fas fa-clock me-2"></i> Gérer mes disponibilités
                                </a>
                            </div>
                            
                            <hr>
                            
                            <h5 class="mt-3"><i class="fas fa-bell me-2"></i> Rappels</h5>
                            <ul class="list-group list-group-flush">
                                <?php if ($stats['pending_reports'] > 0): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Rapports à compléter
                                        <span class="badge bg-danger rounded-pill"><?= $stats['pending_reports'] ?></span>
                                    </li>
                                <?php endif; ?>
                                <?php if ($stats['today_appointments'] > 0): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        RDV aujourd'hui
                                        <span class="badge bg-primary rounded-pill"><?= $stats['today_appointments'] ?></span>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion des tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Auto-dismiss alerts after 5 seconds
            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => {
                    bootstrap.Alert.getInstance(alert)?.close();
                });
            }, 5000);
        });
    </script>
</body>
</html>