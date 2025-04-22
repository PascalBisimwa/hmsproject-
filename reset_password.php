<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session
session_start();

// Inclure le fichier de connexion à la base de données
require __DIR__ . '/include/connection.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $token = $_GET['token'] ?? '';
    if (empty($token)) {
        header('Location: login.php');
        exit();
    }

    // Vérifier si le token est valide et non expiré
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $error_message = "Le lien de réinitialisation est invalide ou a expiré.";
    }

    // Générer un token CSRF
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Vérifier le token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Erreur de sécurité. Veuillez réessayer.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 8) {
        $error_message = "Le mot de passe doit contenir au moins 8 caractères.";
    } else {
        // Vérifier le token et son expiration
        $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            // Hacher le nouveau mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Mettre à jour le mot de passe et effacer le token
            $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
            $stmt->execute([$hashed_password, $user['id']]);

            $success_message = "Votre mot de passe a été réinitialisé avec succès.";
            header('Refresh: 3; URL=login.php'); // Rediriger après 3 secondes
        } else {
            $error_message = "Le lien de réinitialisation est invalide ou a expiré.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Réinitialisation du mot de passe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="form-container">
        <h2>Réinitialisation du mot de passe</h2>
        <?php if (isset($success_message)): ?>
            <p class="success-message"><?php echo $success_message; ?></p>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form action="reset_password.php" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="password-container">
                <input type="password" name="password" placeholder="Nouveau mot de passe" required>
                <span class="toggle-password">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
            <div class="password-container">
                <input type="password" name="confirm_password" placeholder="Confirmez le mot de passe" required>
                <span class="toggle-password">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
            <button type="submit">Réinitialiser le mot de passe</button>
        </form>
        <p><a href="login.php" class="toggle-form">Retour à la connexion</a></p>
    </div>
    <script>
        document.querySelectorAll('.toggle-password').forEach(function(icon) {
            icon.addEventListener('click', function() {
                const input = this.previousElementSibling;
                if (input.type === 'password') {
                    input.type = 'text';
                    this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                } else {
                    input.type = 'password';
                    this.innerHTML = '<i class="fas fa-eye"></i>';
                }
            });
        });
    </script>
</body>
</html>