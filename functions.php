<?php
/**
 * Fonctions utilitaires HMS
 */

if (!defined('HMS_ACCESS')) {
    die('Accès direct interdit');
}

// Fonctions d'affichage sécurisé
if (!function_exists('safeOutput')) {
    function safeOutput($data) {
        return htmlspecialchars($data ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

// Formatage des dates
if (!function_exists('formatDate')) {
    function formatDate($date, $includeTime = false) {
        if (!$date || $date == '0000-00-00') return 'Non spécifié';
        return date($includeTime ? 'd/m/Y H:i' : 'd/m/Y', strtotime($date));
    }
}

// Nettoyage des entrées
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        if (is_array($input)) return array_map('sanitizeInput', $input);
        return htmlspecialchars(strip_tags(trim($input ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

// Journalisation des erreurs
if (!function_exists('logError')) {
    function logError($message, $context = []) {
        $logDir = __DIR__.'/../logs/';
        if (!file_exists($logDir)) mkdir($logDir, 0755, true);
        
        $logMessage = '['.date('Y-m-d H:i:s').'] '.$message;
        if (!empty($context)) {
            $logMessage .= ' | Context: '.json_encode($context);
        }
        
        file_put_contents(
            $logDir.'error_'.date('Y-m-d').'.log',
            $logMessage.PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}

// Redirection avec message
if (!function_exists('redirectWithMessage')) {
    function redirectWithMessage($url, $type, $message) {
        $_SESSION[$type] = $message;
        header("Location: $url");
        exit();
    }
}