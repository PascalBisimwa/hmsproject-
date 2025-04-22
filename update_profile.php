<?php
// 1. Protection contre l'accès direct
define('HMS_ACCESS', true);

// 2. Inclusion des fichiers nécessaires
require __DIR__ . '/../include/security.php';
require __DIR__ . '/../include/connection.php';

// 3. Initialisation session
initSession();

// 4. Vérification que la méthode est bien POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Si on arrive ici par GET, on redirige vers le dashboard avec un message
    $_SESSION['error'] = "Cette page ne peut être accédée directement";
    header("Location: doctors_dashboard.php");
    exit();
}

// 5. Vérification de l'authentification et du rôle
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    $_SESSION['error'] = "Accès non autorisé";
    header("Location: /Hms/login.php");
    exit();
}

// 6. Validation des données
$requiredFields = [
    'full_name' => 'Nom complet',
    'email' => 'Email',
    'sex' => 'Sexe',
    'speciality' => 'Spécialité',
    'license_number' => 'Numéro de licence'
];

$errors = [];
foreach ($requiredFields as $field => $label) {
    if (empty($_POST[$field])) {
        $errors[] = "Le champ $label est requis";
    }
}

// Validation supplémentaire pour l'email
if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Format d'email invalide";
}

// Si erreurs, on redirige
if (!empty($errors)) {
    $_SESSION['error'] = implode("<br>", $errors);
    $_SESSION['form_data'] = $_POST; // Pour pré-remplir le formulaire
    header("Location: doctors_dashboard.php");
    exit();
}

// 7. Traitement de la mise à jour
try {
    $pdo = Database::getInstance();
    $pdo->beginTransaction();

    // a. Mise à jour de la table users
    $stmt = $pdo->prepare("
        UPDATE users 
        SET full_name = ?, 
            email = ?, 
            phone = ?, 
            address = ?, 
            sex = ?,
            departement_id = ?, 
            service_id = ?, 
            updated_at = NOW() 
        WHERE id = ?
    ");
    
    $stmt->execute([
        htmlspecialchars($_POST['full_name']),
        htmlspecialchars($_POST['email']),
        htmlspecialchars($_POST['phone'] ?? null),
        htmlspecialchars($_POST['address'] ?? null),
        htmlspecialchars($_POST['sex']),
        !empty($_POST['departement_id']) ? (int)$_POST['departement_id'] : null,
        !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null,
        $_SESSION['user_id']
    ]);

    // b. Mise à jour de la table doctors
    $stmt = $pdo->prepare("
        UPDATE doctors 
        SET speciality = ?, 
            license_number = ?, 
            updated_at = NOW() 
        WHERE user_id = ?
    ");
    
    $stmt->execute([
        htmlspecialchars($_POST['speciality']),
        htmlspecialchars($_POST['license_number']),
        $_SESSION['user_id']
    ]);

    $pdo->commit();

    // 8. Mise à jour des données de session
    $_SESSION['full_name'] = htmlspecialchars($_POST['full_name']);
    $_SESSION['email'] = htmlspecialchars($_POST['email']);
    $_SESSION['success'] = "Profil mis à jour avec succès";

} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour";
    error_log("Erreur mise à jour profil: " . $e->getMessage());
}

// 9. Redirection vers le dashboard
header("Location: doctors_dashboard.php");
exit();
?>