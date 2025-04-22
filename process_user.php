<?php
session_start();
require __DIR__ . '/../include/connection.php';

// Vérifier si l'utilisateur est un administrateur
if ($_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Récupérer les données du formulaire
$username = $_POST['username'];
$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$email = $_POST['email'];
$address = $_POST['address'];
$phone = $_POST['phone'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hasher le mot de passe
$role = $_POST['role'];

// Insérer l'utilisateur dans la base de données
$sql = "INSERT INTO users (username, first_name, last_name, email, address, phone, password, role) 
        VALUES (:username, :first_name, :last_name, :email, :address, :phone, :password, :role)";
$stmt = $pdo->prepare($sql);

$stmt->bindValue(':username', $username, PDO::PARAM_STR);
$stmt->bindValue(':first_name', $first_name, PDO::PARAM_STR);
$stmt->bindValue(':last_name', $last_name, PDO::PARAM_STR);
$stmt->bindValue(':email', $email, PDO::PARAM_STR);
$stmt->bindValue(':address', $address, PDO::PARAM_STR);
$stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
$stmt->bindValue(':password', $password, PDO::PARAM_STR);
$stmt->bindValue(':role', $role, PDO::PARAM_STR);

if ($stmt->execute()) {
    // Rediriger vers la page de gestion des utilisateurs avec un message de succès
    header('Location: manage_users.php?success=1');
    exit();
} else {
    // Rediriger avec un message d'erreur
    header('Location: manage_users.php?error=1');
    exit();
}