<?php
function verifySession() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    if (empty($_SESSION['user_id'])) {
        header("Location: /Hms/login.php");
        exit();
    }
    
    // Vérifie l'inactivité (30 minutes)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        header("Location: /Hms/login.php?timeout=1");
        exit();
    }
    
    $_SESSION['last_activity'] = time();
}