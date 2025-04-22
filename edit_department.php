<?php
define('HMS_ACCESS', true);
require __DIR__ . '/../include/security.php';
require __DIR__ . '/../include/connection.php';

// Vérification admin
checkAuth('admin');

// Initialisation PDO
$pdo = Database::getInstance();

// Vérification ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header('Location: manage_departments.php');
    exit();
}

$department_id = (int)$_GET['id'];

// Récupération département
try {
    $stmt = $pdo->prepare("SELECT * FROM departement WHERE id = ?");
    $stmt->execute([$department_id]);
    $department = $stmt->fetch();

    if (!$department) {
        header('Location: manage_departments.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Database error in edit_department: " . $e->getMessage());
    die("Erreur base de données");
}

// Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_department'])) {
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
            $stmt = $pdo->prepare("UPDATE departement SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $department_id]);
            
            $_SESSION['message'] = "Département mis à jour avec succès";
            header('Location: manage_departments.php');
            exit();
        } catch (PDOException $e) {
            error_log("Update department error: " . $e->getMessage());
            $error = "Erreur lors de la mise à jour. Veuillez réessayer.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Département</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { max-width: 800px; }
        .form-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container py-4">
        <h2 class="mb-4">Modifier Département</h2>
        
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
                    <input type="text" name="name" class="form-control" 
                           value="<?= htmlspecialchars($department['name']) ?>" required maxlength="100">
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="4" required maxlength="500"><?= 
                        htmlspecialchars($department['description']) ?></textarea>
                </div>

                <button type="submit" name="update_department" class="btn btn-primary">
                    Enregistrer
                </button>
                <a href="manage_departments.php" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</body>
</html>