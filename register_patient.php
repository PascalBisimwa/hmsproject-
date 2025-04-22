<?php
// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Le formulaire n'a pas été soumis.");
}

// Afficher les données POST pour le débogage
print_r($_POST);

// Vérifier que tous les champs requis sont présents
$required_fields = ['username', 'password', 'email', 'last_name', 'first_name', 'phone', 'address'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        die("Le champ $field est requis.");
    }
}

// Inclure la connexion à la base de données
require __DIR__ . '/include/connection.php';

// Récupérer les données du formulaire
$username = $_POST['username'];
$password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hacher le mot de passe
$role = 'patient';
$email = $_POST['email'];
$last_name = $_POST['last_name'];
$first_name = $_POST['first_name'];
$phone = $_POST['phone'];
$address = $_POST['address'];

try {
    // Commencer une transaction (pour garantir l'intégrité des données)
    $pdo->beginTransaction();

    // Étape 1 : Insérer dans la table `users`
    $stmt = $pdo->prepare("
        INSERT INTO users (username, password, role, email)
        VALUES (:username, :password, :role, :email)
    ");
    $stmt->execute([
        ':username' => $username,
        ':password' => $password,
        ':role' => $role,
        ':email' => $email,
    ]);

    // Étape 2 : Récupérer l'ID de l'utilisateur inséré
    $user_id = $pdo->lastInsertId();

    // Étape 3 : Insérer dans la table `patients`
    $stmt = $pdo->prepare("
        INSERT INTO patients (user_id, last_name, first_name, email, phone, address)
        VALUES (:user_id, :last_name, :first_name, :email, :phone, :address)
    ");
    $stmt->execute([
        ':user_id' => $user_id,
        ':last_name' => $last_name,
        ':first_name' => $first_name,
        ':email' => $email,
        ':phone' => $phone,
        ':address' => $address,
    ]);

    // Valider la transaction
    $pdo->commit();

    echo "Nouveau patient enregistré avec succès !";
} catch (PDOException $e) {
    // En cas d'erreur, annuler la transaction
    $pdo->rollBack();
    die("Erreur lors de l'enregistrement du patient : " . $e->getMessage());
}
?>