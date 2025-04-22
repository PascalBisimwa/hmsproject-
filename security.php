<?php
defined('HMS_ACCESS') or die('Accès direct non autorisé');

class Security {
    public static function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'name' => 'HMS_SESS',
                'cookie_lifetime' => 86400,
                'cookie_httponly' => true,
                'cookie_secure' => isset($_SERVER['HTTPS']),
                'use_strict_mode' => true
            ]);
        }
    }

    public static function checkAuth($allowedRoles = []) {
        self::initSession();
        
        if (empty($_SESSION['user_id'])) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header("Location: /Hms/login.php");
            exit();
        }

        if (!empty($allowedRoles) && !in_array($_SESSION['role'], $allowedRoles)) {
            header("Location: /Hms/unauthorized.php");
            exit();
        }
    }
}