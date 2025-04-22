<?php
define('HMS_ACCESS', true);
require __DIR__ . '/../include/security.php';
require __DIR__ . '/../include/connection.php';

// Vérification admin
checkAuth('admin');

// Initialisation PDO
$pdo = Database::getInstance();

// Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_department'])) {
    // Vérification CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die("Erreur de sécurité: Token CSRF invalide");
    }

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    if (empty($name) || empty($description)) {
        $error = "Tous les champs sont obligatoires";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO departement (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            
            $_SESSION['message'] = "Département ajouté avec succès";
            header('Location: manage_departments.php');
            exit();
        } catch (PDOException $e) {
            error_log("Erreur d'ajout département: " . $e->getMessage());
            $error = "Erreur lors de l'ajout du département. Veuillez réessayer.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter Département</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 800px; }
        .form-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container py-4">
        <h2 class="mb-4">Ajouter Département</h2>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <div class="form-container">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                
                <div class="mb-3">
                    <label class="form-label">Nom</label>
                    <input type="text" name="name" class="form-control" required maxlength="100">
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="4" required maxlength="500"></textarea>
                </div>

                <button type="submit" name="add_department" class="btn btn-primary">
                    Ajouter
                </button>
                <a href="manage_departments.php" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</body>
</html>