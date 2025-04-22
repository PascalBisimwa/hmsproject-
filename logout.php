<?php
define('HMS_ACCESS', true);
session_name('HMS_SESS');
session_start();

if (isset($_SESSION['user_id'])) {
    error_log("Déconnexion - User:".$_SESSION['user_id']." Rôle:".($_SESSION['role'] ?? 'inconnu'));
}

// Destruction complète
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    setcookie(session_name(), '', time()-3600, '/');
}
session_destroy();

// Redirection intelligente
$from = $_GET['from'] ?? '';
$redirect = match($from) {
    'admin' => '/Hms/admin/login.php',
    'doctor' => '/Hms/doctors/login.php',
    'reception' => '/Hms/reception/login.php',
    'patient' => '/Hms/patients/login.php',
    default => '/Hms/login.php'
};

header("Location: $redirect?logout=1");
exit();
?>