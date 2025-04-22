<?php
// Protection contre l'accès direct
define('HMS_ACCESS', true);
require __DIR__ . '/../include/security.php';
require __DIR__ . '/../include/connection.php';

// Vérifier si l'utilisateur est admin
checkAuth('admin');

// Initialisation PDO
$pdo = Database::getInstance();

// Récupérer la liste des départements
try {
    $stmt = $pdo->query("SELECT id, name FROM departement ORDER BY name");
    $departements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error in add_service: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'Erreur lors du chargement des départements'
    ];
    header('Location: manage_services.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Service | Kibris Aydin Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            width: 90%;
            max-width: 600px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #003366;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .submit-button {
            display: block;
            width: 100%;
            padding: 12px;
            background: #003366;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        .submit-button:hover {
            background: #0055a4;
        }
        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: #003366;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-plus-circle"></i> Ajouter un Service</h1>

        <!-- Formulaire d'ajout de service -->
        <form action="save_service.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="form-group">
                <label for="name" class="required-field">Nom du Service</label>
                <input type="text" id="name" name="name" required maxlength="100">
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" maxlength="500"></textarea>
            </div>
            
            <div class="form-group">
                <label for="departement_id" class="required-field">Département</label>
                <select id="departement_id" name="departement_id" required>
                    <option value="">Sélectionnez un département</option>
                    <?php foreach ($departements as $departement) : ?>
                        <option value="<?= htmlspecialchars($departement['id']) ?>">
                            <?= htmlspecialchars($departement['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" value="1" checked>
                    Service actif
                </label>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_night_service" value="1">
                    Service de nuit
                </label>
            </div>
            
            <button type="submit" class="submit-button">
                <i class="fas fa-save"></i> Enregistrer
            </button>
            
            <a href="manage_services.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>