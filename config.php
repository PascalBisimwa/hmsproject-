<?php
// config.php - Fichier central de configuration
declare(strict_types=1);

// Vérification avant définition des constantes
defined('BASE_PATH') || define('BASE_PATH', '/Hms/');
defined('INCLUDE_PATH') || define('INCLUDE_PATH', __DIR__ . DIRECTORY_SEPARATOR);
defined('LOGIN_ATTEMPTS_LIMIT') || define('LOGIN_ATTEMPTS_LIMIT', 5);
defined('LOGIN_BLOCK_TIME') || define('LOGIN_BLOCK_TIME', 300); // 5 minutes en secondes

// Configuration de la session (à appeler avant session_start())
function configureSession(): void {
    $sessionParams = [
        'name' => 'HMS_SESSID',
        'cookie_lifetime' => 86400,
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
        'gc_maxlifetime' => 1800
    ];
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start($sessionParams);
    }
}