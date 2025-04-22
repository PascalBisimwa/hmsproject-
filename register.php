<?php
session_start();
require __DIR__ . '/include/connection.php';

// Traitement du formulaire de connexion
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Récupérer l'utilisateur depuis la base de données
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Vérifier le mot de passe
    if ($user && password_verify($password, $user['password'])) {
        // Connexion réussie
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $user['username'];

        // Rediriger en fonction du rôle
        switch ($user['role']) {
            case 'admin':
                header('Location: admin_dashboard.php');
                break;
            case 'doctor':
                header('Location: doctor_dashboard.php');
                break;
            case 'reception':
                header('Location: reception_dashboard.php');
                break;
            case 'patient':
                header('Location: patient_dashboard.php');
                break;
            default:
                header('Location: index.php');
                break;
        }
        exit();
    } else {
        // Identifiants incorrects
        $login_error = "Nom d'utilisateur ou mot de passe incorrect.";
    }
}

// Traitement du formulaire d'inscription
if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $tel = $_POST['tel'];
    $address = $_POST['address'] ?? ''; // Optionnel pour les patients

    // Hacher le mot de passe
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insérer l'utilisateur dans la table `users`
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute([$username, $hashed_password, $role]);

    // Récupérer l'ID de l'utilisateur nouvellement créé
    $user_id = $pdo->lastInsertId();

    // Enregistrer dans la table spécifique en fonction du rôle
    switch ($role) {
        case 'doctor':
            $stmt = $pdo->prepare("INSERT INTO doctors (user_id, first_name, last_name, specialty, service, department, email, tel) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $first_name, $last_name, 'Spécialité', 'Service', 'Département', $email, $tel]);
            break;

        case 'patient':
            $stmt = $pdo->prepare("INSERT INTO patients (user_id, first_name, last_name, email, tel, address) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $first_name, $last_name, $email, $tel, $address]);
            break;

        case 'reception':
            // Utilisation de la table `receptionists` au lieu de `reception`
            $stmt = $pdo->prepare("INSERT INTO receptionists (user_id, first_name, last_name, email, phone_number) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $first_name, $last_name, $email, $tel]);
            break;
    }

    $register_success = "Utilisateur créé avec succès !";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login et Inscription</title>
    <style>
        .form-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-container input, .form-container select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .form-container button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .form-container button:hover {
            background-color: #0056b3;
        }
        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }
        .success-message {
            color: green;
            text-align: center;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <!-- Formulaire de connexion -->
        <h2>Connexion</h2>
        <?php if (isset($login_error)): ?>
            <div class="error-message"><?php echo $login_error; ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <input type="text" name="username" placeholder="Nom d'utilisateur" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit" name="login">Se connecter</button>
        </form>

        <!-- Formulaire d'inscription -->
        <h2>Inscription</h2>
        <?php if (isset($register_success)): ?>
            <div class="success-message"><?php echo $register_success; ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <input type="text" name="username" placeholder="Nom d'utilisateur" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <select name="role" required>
                <option value="admin">Admin</option>
                <option value="doctor">Doctor</option>
                <option value="reception">Reception</option>
                <option value="patient">Patient</option>
            </select>
            <input type="text" name="first_name" placeholder="Prénom" required>
            <input type="text" name="last_name" placeholder="Nom" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="tel" name="tel" placeholder="Téléphone" required>
            <input type="text" name="address" placeholder="Adresse (pour les patients)">
            <button type="submit" name="register">S'inscrire</button>
        </form>
    </div>
</body>
</html>