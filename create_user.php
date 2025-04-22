<?php
session_start();
require __DIR__ . '/include/connection.php';

// Vérifier si l'utilisateur est un super administrateur
if ($_SESSION['role'] !== 'super_admin') {
    die("Accès refusé.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role']; // admin, doctor, reception

    // Validation des données
    if (empty($username) || empty($email) || empty($password)) {
        $error = "Tous les champs sont obligatoires.";
    } else {
        // Vérifier si l'email ou le nom d'utilisateur existe déjà
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        $existing_user = $stmt->fetch();

        if ($existing_user) {
            $error = "Cet email ou nom d'utilisateur est déjà utilisé.";
        } else {
            // Hacher le mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insérer l'utilisateur dans la base de données
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password, $role]);

                $success = "Utilisateur créé avec succès !";
            } catch (PDOException $e) {
                $error = "Erreur lors de la création de l'utilisateur : " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Créer un utilisateur</title>
</head>
<body>
    <h1>Créer un utilisateur</h1>
    <?php if (isset($error)): ?>
        <div style="color: red;"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <div style="color: green;"><?php echo $success; ?></div>
    <?php endif; ?>
    <form action="" method="POST">
        <input type="text" name="username" placeholder="Nom d'utilisateur" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <select name="role" required>
            <option value="admin">Admin</option>
            <option value="doctor">Doctor</option>
            <option value="reception">Reception</option>
        </select>
        <button type="submit">Créer</button>
    </form>
</body>
</html>