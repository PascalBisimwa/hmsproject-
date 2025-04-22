<?php
if (!defined('HMS_ACCESS')) {
    define('HMS_ACCESS', true);
}
require_once __DIR__ . '/../include/security.php';
require_once __DIR__ . '/../include/connection.php';
require_once __DIR__ . '/../include/functions.php';

// Initialisation
$pdo = Database::getInstance();
initSession();

// Vérification renforcée de l'ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id <= 0) {
    $_SESSION['error'] = "ID d'article invalide";
    header("Location: manage_inventory.php");
    exit();
}

class InventoryManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getItemById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM inventory WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$item) {
                throw new Exception("Article non trouvé");
            }
            
            return $item;
        } catch (PDOException $e) {
            error_log("Erreur DB: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération de l'article");
        }
    }

    public function updateItem($id, $data) {
        // Validation des données
        $errors = $this->validateItemData($data);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode("\n", $errors));
        }
        
        // Formatage des données
        $data = $this->formatItemData($data);
        
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE inventory SET 
                item_name = :item_name, 
                category_id = :category_id, 
                quantity = :quantity, 
                min_quantity = :min_quantity, 
                unit = :unit, 
                departement_id = :departement_id, 
                supplier = :supplier, 
                unit_price = :unit_price, 
                location = :location, 
                batch_number = :batch_number, 
                expiry_date = :expiry_date, 
                notes = :notes,
                last_updated = CURRENT_TIMESTAMP
                WHERE id = :id"
            );
            
            $params = [
                ':item_name' => $data['item_name'],
                ':category_id' => $data['category_id'],
                ':quantity' => $data['quantity'],
                ':min_quantity' => $data['min_quantity'],
                ':unit' => $data['unit'],
                ':departement_id' => $data['departement_id'],
                ':supplier' => $data['supplier'],
                ':unit_price' => $data['unit_price'],
                ':location' => $data['location'],
                ':batch_number' => $data['batch_number'],
                ':expiry_date' => $data['expiry_date'],
                ':notes' => $data['notes'],
                ':id' => $id
            ];
            
            $stmt->execute($params);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Erreur DB: " . $e->getMessage());
            throw new Exception("Erreur lors de la mise à jour de l'article");
        }
    }

    private function validateItemData($data) {
        $errors = [];
        
        if (empty(trim($data['item_name']))) {
            $errors[] = "Le nom de l'article est requis";
        }
        
        if (!isset($data['quantity']) || !is_numeric($data['quantity']) || $data['quantity'] < 0) {
            $errors[] = "La quantité doit être un nombre positif";
        }
        
        if (isset($data['min_quantity']) && $data['min_quantity'] !== '' && (!is_numeric($data['min_quantity']) || $data['min_quantity'] < 0)) {
            $errors[] = "Le stock minimum doit être un nombre positif";
        }
        
        return $errors;
    }

    private function formatItemData($data) {
        return [
            'item_name' => trim($data['item_name']),
            'category_id' => !empty($data['category_id']) ? $data['category_id'] : null,
            'quantity' => (int)$data['quantity'],
            'min_quantity' => !empty($data['min_quantity']) ? (int)$data['min_quantity'] : null,
            'unit' => !empty($data['unit']) ? trim($data['unit']) : null,
            'departement_id' => !empty($data['departement_id']) ? (int)$data['departement_id'] : null,
            'supplier' => !empty($data['supplier']) ? trim($data['supplier']) : null,
            'unit_price' => !empty($data['unit_price']) ? (float)$data['unit_price'] : null,
            'location' => !empty($data['location']) ? trim($data['location']) : null,
            'batch_number' => !empty($data['batch_number']) ? trim($data['batch_number']) : null,
            'expiry_date' => !empty($data['expiry_date']) ? $data['expiry_date'] : null,
            'notes' => !empty($data['notes']) ? trim($data['notes']) : null
        ];
    }

    public function getDepartments() {
        try {
            $stmt = $this->pdo->query("SELECT id, name FROM departement ORDER BY name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur DB: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération des départements");
        }
    }

    public function getCategories() {
        return [
            'medicament' => 'Médicament',
            'equipement' => 'Équipement',
            'fourniture' => 'Fourniture'
        ];
    }
}

// Initialisation
$inventoryManager = new InventoryManager($pdo);
$csrfToken = generateCsrfToken();

// Récupérer l'article à modifier
try {
    $item = $inventoryManager->getItemById($id);
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: manage_inventory.php");
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            throw new Exception("Token CSRF invalide");
        }

        if ($_POST['action'] === 'update') {
            $updated = $inventoryManager->updateItem($id, $_POST);
            
            if ($updated) {
                $_SESSION['message'] = "Article mis à jour avec succès";
                header("Location: manage_inventory.php");
                exit();
            } else {
                $_SESSION['error'] = "Aucune modification effectuée";
                header("Location: edit_item.php?id=" . $id);
                exit();
            }
        }
    } catch (InvalidArgumentException $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: edit_item.php?id=" . $id);
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Une erreur est survenue: " . $e->getMessage();
        header("Location: edit_item.php?id=" . $id);
        exit();
    }
}

// Récupérer les données pour les listes déroulantes
try {
    $departements = $inventoryManager->getDepartments();
    $categories = $inventoryManager->getCategories();
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: manage_inventory.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Article | Kibris Aydin Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            padding: 2rem;
        }
        
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .hospital-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            gap: 15px;
        }
        
        .hospital-logo {
            height: 50px;
            object-fit: contain;
        }
        
        .required-field::after {
            content: " *";
            color: var(--danger);
        }
        
        .form-control:disabled, .form-select:disabled {
            background-color: #e9ecef;
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="hospital-header">
            <img src="/Hms/img/logo.png" alt="Logo Kibris Aydin Hospital" class="hospital-logo">
            <div>
                <h1 class="hospital-name mb-0">Kibris Aydin Hospital</h1>
                <small class="text-muted">Modification d'article (ID: <?= htmlspecialchars($id) ?>)</small>
            </div>
        </div>

        <div class="form-header">
            <h2><i class="fas fa-edit me-2"></i>Modifier l'article</h2>
            <a href="manage_inventory.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Retour
            </a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form method="post" action="edit_item.php?id=<?= htmlspecialchars($id) ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
            
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label required-field">Nom</label>
                    <input type="text" name="item_name" class="form-control" 
                           value="<?= htmlspecialchars($item['item_name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label required-field">Catégorie</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Sélectionner...</option>
                        <?php foreach ($categories as $catId => $catName): ?>
                            <option value="<?= htmlspecialchars($catId) ?>" 
                                <?= $catId == $item['category_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($catName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label required-field">Quantité</label>
                    <input type="number" name="quantity" class="form-control" 
                           value="<?= htmlspecialchars($item['quantity']) ?>" min="0" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Stock minimum</label>
                    <input type="number" name="min_quantity" class="form-control" 
                           value="<?= htmlspecialchars($item['min_quantity']) ?>" min="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Unité</label>
                    <input type="text" name="unit" class="form-control" 
                           value="<?= htmlspecialchars($item['unit']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Prix unitaire (€)</label>
                    <input type="number" step="0.01" name="unit_price" class="form-control" 
                           value="<?= htmlspecialchars($item['unit_price']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Département</label>
                    <select name="departement_id" class="form-select">
                        <option value="">Aucun</option>
                        <?php foreach ($departements as $dept): ?>
                            <option value="<?= htmlspecialchars($dept['id']) ?>" 
                                <?= $dept['id'] == $item['departement_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fournisseur</label>
                    <input type="text" name="supplier" class="form-control" 
                           value="<?= htmlspecialchars($item['supplier']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Numéro de lot</label>
                    <input type="text" name="batch_number" class="form-control" 
                           value="<?= htmlspecialchars($item['batch_number']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date d'expiration</label>
                    <input type="date" name="expiry_date" class="form-control" 
                           value="<?= htmlspecialchars($item['expiry_date']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Emplacement</label>
                    <input type="text" name="location" class="form-control" 
                           value="<?= htmlspecialchars($item['location']) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($item['notes']) ?></textarea>
                </div>
            </div>
            
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Enregistrer
                </button>
                <a href="manage_inventory.php" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation côté client
        document.querySelector('form').addEventListener('submit', function(e) {
            const itemName = document.querySelector('[name="item_name"]').value.trim();
            const quantity = document.querySelector('[name="quantity"]').value;
            const category = document.querySelector('[name="category_id"]').value;
            
            if (!itemName || isNaN(quantity) || quantity < 0 || !category) {
                e.preventDefault();
                alert('Veuillez remplir correctement les champs obligatoires');
                return false;
            }
            return true;
        });
    </script>
</body>
</html>