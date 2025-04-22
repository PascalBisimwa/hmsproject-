<?php
session_start();

// Activer les erreurs PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure la connexion à la base de données
require __DIR__ . '/../include/connection.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Traitement du formulaire de mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mettre à jour les informations de profil
    $full_name = $_POST['full_name'] ?? '';
    $sex = $_POST['sex'] ?? '';
    $address = $_POST['address'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $grade = $_POST['grade'] ?? '';
    $bio = $_POST['bio'] ?? '';

    // Mettre à jour les informations dans la base de données
    try {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, sex = ?, address = ?, phone = ?, grade = ?, bio = ? WHERE id = ?");
        $stmt->execute([$full_name, $sex, $address, $phone, $grade, $bio, $_SESSION['user_id']]);

        // Mettre à jour les informations dans la session
        $_SESSION['full_name'] = $full_name;
        $_SESSION['sex'] = $sex;
        $_SESSION['address'] = $address;
        $_SESSION['phone'] = $phone;
        $_SESSION['grade'] = $grade;
        $_SESSION['bio'] = $bio;

       if ($_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) { // Pas de point-virgule ici
    $upload_dir = __DIR__ . '/../uploads/';
    if (!is_dir($upload_dir)) { // Erreur ici : parenthèse en trop
        mkdir($upload_dir, 0755, true);
    }

    $file_name = 'a3.jpg'; // Nom du fichier
    $file_path = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $file_path)) {
        $profile_picture = 'uploads/' . $file_name; // Chemin relatif
        $_SESSION['profile_picture'] = $profile_picture; // Stocke le chemin dans la session
    } else {
        $_SESSION['error'] = "Erreur lors du téléchargement de la photo de profil.";
        header('Location: admin_dashboard.php');
        exit();
    }
} // Pas de point-virgule ici
        $_SESSION['success'] = "Profil mis à jour avec succès.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    }

    // Rediriger vers le tableau de bord
    header('Location: admin_dashboard.php');
    exit();
}
?>