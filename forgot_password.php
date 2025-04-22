<?php
// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure le fichier de connexion à la base de données
require __DIR__ . '/include/connection.php';

// Inclure PHPMailer via Composer (recommandé)
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Variables pour les messages
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL); // Nettoyer l'e-mail

    if (empty($email)) {
        $error_message = "Veuillez entrer une adresse e-mail valide.";
    } else {
        // Vérifier si l'email existe dans la base de données
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Générer un token de réinitialisation
            $token = bin2hex(random_bytes(50)); // Token unique
            $expires = date("Y-m-d H:i:s", strtotime("+1 hour")); // Expiration dans 1 heure

            // Stocker le token dans la base de données
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $user['id']]);

            // Envoyer un e-mail avec le lien de réinitialisation
            $reset_link = "http://votre-site.com/reset_password.php?token=$token";

            // Configuration de PHPMailer
            $mail = new PHPMailer(true);

            try {
                // Paramètres du serveur SMTP
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; // Serveur SMTP de Gmail
                $mail->SMTPAuth = true;
                $mail->Username = 'votre-email@gmail.com'; // Votre adresse Gmail
                $mail->Password = 'votre-mot-de-passe'; // Votre mot de passe Gmail
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Chiffrement TLS
                $mail->Port = 587; // Port SMTP pour Gmail

                // Destinataire et expéditeur
                $mail->setFrom('no-reply@votre-site.com', 'Votre Site');
                $mail->addAddress($email); // Adresse e-mail de l'utilisateur

                // Contenu de l'e-mail
                $mail->isHTML(true);
                $mail->Subject = 'Réinitialisation de votre mot de passe';
                $mail->Body = "Pour réinitialiser votre mot de passe, cliquez sur le lien suivant : <a href='$reset_link'>$reset_link</a>";

                // Envoyer l'e-mail
                $mail->send();
                $success_message = "Un e-mail de réinitialisation a été envoyé à votre adresse e-mail.";
            } catch (Exception $e) {
                $error_message = "Erreur lors de l'envoi de l'e-mail : " . $mail->ErrorInfo;
            }
        } else {
            $error_message = "Aucun utilisateur trouvé avec cette adresse e-mail.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mot de passe oublié</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="form-container">
        <h2>Mot de passe oublié</h2>
        <?php if (isset($success_message)): ?>
            <p class="success-message"><?php echo $success_message; ?></p>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form action="forgot_password.php" method="POST">
            <input type="email" name="email" placeholder="Votre adresse e-mail" required>
            <button type="submit">Envoyer</button>
        </form>
       <p><a href="login.php" class="toggle-form">Retour à la connexion</a></p>
    </div>
</body>
</html>