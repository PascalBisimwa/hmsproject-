<?php
define('HMS_ACCESS', true);
require __DIR__ . '/../include/security.php';
require __DIR__ . '/../include/connection.php';

checkAuth('admin');
$pdo = Database::getInstance();

// Vérification ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'ID invalide'];
    header("Location: manage_services.php");
    exit();
}

$service_id = (int)$_GET['id'];

// Récupérer le service
try {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch();

    if (!$service) {
        throw new Exception("Service introuvable");
    }
} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Erreur base de données'];
    header("Location: manage_services.php");
    exit();
}

// Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Token invalide'];
        header("Location: edit_service.php?id=$service_id");
        exit();
    }

    try {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $departement_id = (int)$_POST['departement_id'];

        if (empty($name) || empty($departement_id)) {
            throw new Exception("Nom et département obligatoires");
        }

        $stmt = $pdo->prepare("UPDATE services SET 
                              name = ?, 
                              description = ?, 
                              departement_id = ? 
                              WHERE id = ?");
        $stmt->execute([$name, $description, $departement_id, $service_id]);

        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Service mis à jour'];
        header("Location: manage_services.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['alert'] = ['type' => 'danger', 'message' => $e->getMessage()];
        header("Location: edit_service.php?id=$service_id");
        exit();
    }
}

// Récupérer les départements
$departements = $pdo->query("SELECT * FROM departement ORDER BY name")->fetchAll();
$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Service</title>
    <!-- [CSS identique à votre version actuelle] -->
</head>
<body>
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
            max-width: 800px;
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
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-bottom: 8px;
            font-weight: bold;
        }
        input, select, textarea {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        button {
            padding: 12px 20px;
            background: #003366;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: #0055a4;
        }
        .alert {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 6px;
            text-align: center;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
    <div class="container">
        <h1>Modifier Service</h1>

        <?php if (isset($_SESSION['alert'])) : ?>
            <div class="alert alert-<?= $_SESSION['alert']['type'] ?>">
                <?= $_SESSION['alert']['message'] ?>
            </div>
            <?php unset($_SESSION['alert']); ?>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
            <div class="form-group">
                <label>Nom</label>
                <input type="text" name="name" value="<?= htmlspecialchars($service['name']) ?>" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" required><?= htmlspecialchars($service['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label>Département</label>
                <select name="departement_id" required>
                    <?php foreach ($departements as $d) : ?>
                        <option value="<?= $d['id'] ?>" <?= $d['id'] == $service['departement_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" name="update_service">Enregistrer</button>
        </form>
    </div>
</body>
</html>