<?php
session_start();

// Configuration de base
define('BASE_URL', '/Hms/');
define('LOGIN_URL', BASE_URL . 'login.php');

// Roles disponibles
define('ROLE_ADMIN', 'admin');
define('ROLE_DOCTOR', 'doctor');
define('ROLE_RECEPTIONISTE', 'receptioniste');

// Vérifie si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Initialise la session
function initSession() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// Vérifie le token CSRF
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Redirige vers la page de login si non connecté
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: ' . LOGIN_URL . '?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
}

// Vérifie les permissions de l'utilisateur
function hasPermission($requiredRole) {
    if (!isLoggedIn()) return false;
    
    // L'admin a tous les droits
    if ($_SESSION['role'] === ROLE_ADMIN) {
        return true;
    }
    
    // Tableau des permissions (peut être adapté selon vos besoins)
    $permissions = [
        ROLE_ADMIN => ['admin', 'doctor', 'receptioniste'], // Admin peut tout faire
        ROLE_DOCTOR => ['doctor', 'receptioniste'],         // Doctor a ses droits + receptioniste
        ROLE_RECEPTIONISTE => ['receptioniste']             // Receptioniste seulement
    ];
    
    // Vérifie si le rôle demandé est dans les permissions de l'utilisateur
    return in_array($requiredRole, $permissions[$_SESSION['role']] ?? []);
}

// Fonction pour déconnecter l'utilisateur
function logout() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    header('Location: ' . LOGIN_URL);
    exit();
}

// Protection CSRF pour les formulaires
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

// Vérifie les permissions et redirige si nécessaire
function checkPermission($requiredRole, $redirectUrl = null) {
    if (!hasPermission($requiredRole)) {
        $_SESSION['error'] = "Accès refusé : permissions insuffisantes";
        header('Location: ' . ($redirectUrl ?? LOGIN_URL));
        exit();
    }
}

// Obtient l'ID de l'utilisateur connecté
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Obtient le rôle de l'utilisateur connecté
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

// Vérifie si l'utilisateur est admin
function isAdmin() {
    return getCurrentUserRole() === ROLE_ADMIN;
}

// Vérifie si l'utilisateur est doctor
function isDoctor() {
    return getCurrentUserRole() === ROLE_DOCTOR;
}

// Vérifie si l'utilisateur est receptioniste
function isReceptioniste() {
    return getCurrentUserRole() === ROLE_RECEPTIONISTE;
}