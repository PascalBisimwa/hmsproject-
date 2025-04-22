<?php
// Inclure la connexion à la base de données
include("../include/connection.php");

// Script pour hacher un mot de passe
$password = 'admin123'; // Mot de passe en clair
$hashed_password = password_hash($password, PASSWORD_BCRYPT); // Hachage du mot de passe

echo "Mot de passe haché : " . $hashed_password . "<br>";

// Exemple de mise à jour du mot de passe dans la base de données pour l'utilisateur "admin01"
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
$stmt->execute([$hashed_password, 'admin01']);

echo "Mots de passe mis à jour avec succès.";
?>
