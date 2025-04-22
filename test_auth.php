<?php
// test_auth.php - Fichier de test autonome

// 1. Configuration de base
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

// 2. Chemin relatif sécurisé
$authPath = __DIR__ . '/../include/auth.php';
echo "Chemin testé : $authPath\n\n";

// 3. Vérification physique du fichier
if (!file_exists($authPath)) {
    die("❌ ERREUR : auth.php introuvable");
}

// 4. Inclusion du fichier
require_once $authPath;
echo "✅ auth.php chargé avec succès\n\n";

// 5. Liste des fonctions à vérifier
$requiredFunctions = [
    'initSecureSession',
    'hasPermission',
    'verifyCsrfToken'
];

// 6. Test d'existence
foreach ($requiredFunctions as $fn) {
    echo "$fn : ";
    echo function_exists($fn) ? "✅ Existe" : "❌ Manquante";
    echo "\n";
}

// 7. Test d'exécution (optionnel)
if (function_exists('initSecureSession')) {
    echo "\nTest d'exécution :\n";
    try {
        initSecureSession();
        $_SESSION['test'] = 'ok';
        echo "Session : ";
        print_r($_SESSION);
    } catch (Exception $e) {
        echo "Erreur : " . $e->getMessage();
    }
}