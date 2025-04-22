<?php
// Vérification sécurité
if (!isset($doctor)) {
    throw new Exception("Données docteur non chargées");
}
?>
<div class="sidebar">
    <div class="text-center mb-4">
        <div class="profile-icon">
            <i class="fas fa-user-md"></i>
        </div>
        <div class="user-name"><?= htmlspecialchars($doctor['full_name']) ?></div>
        <div class="user-role"><?= htmlspecialchars($doctor['speciality'] ?? 'Médecin') ?></div>
    </div>
    
    <nav class="nav flex-column">
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'doctors_dashboard.php' ? 'active' : '' ?>" href="doctors_dashboard.php">
            <i class="fas fa-tachometer-alt"></i> Tableau de bord
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'doctor_appointments.php' ? 'active' : '' ?>" href="doctor_appointments.php">
            <i class="fas fa-calendar-check"></i> Rendez-vous
            <?php if (isset($today_appointments) && $today_appointments > 0): ?>
                <span class="badge bg-danger float-end"><?= $today_appointments ?></span>
            <?php endif; ?>
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'doctor_patients.php' ? 'active' : '' ?>" href="doctor_patients.php">
            <i class="fas fa-procedures"></i> Patients
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'doctor_messages.php' ? 'active' : '' ?>" href="doctor_messages.php">
            <i class="fas fa-comments"></i> Messagerie
            <?php if (isset($unread_messages) && $unread_messages > 0): ?>
                <span class="badge bg-danger float-end"><?= $unread_messages ?></span>
            <?php endif; ?>
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'doctor_reports.php' ? 'active' : '' ?>" href="doctor_reports.php">
            <i class="fas fa-file-medical"></i> Rapports
        </a>
    </nav>
</div>