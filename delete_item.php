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

class InventoryManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function deleteItem($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM inventory WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Erreur de suppression: " . $e->getMessage());
            throw new Exception("Erreur lors de la suppression de l'article");
        }
    }
}

// Initialisation
$inventoryManager = new InventoryManager($pdo);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID d'article invalide";
    header("Location: manage_inventory.php");
    exit();
}

try {
    $deleted = $inventoryManager->deleteItem($_GET['id']);
    if ($deleted) {
        $_SESSION['message'] = "Article supprimé avec succès";
    } else {
        $_SESSION['error'] = "Article non trouvé";
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header("Location: manage_inventory.php");
exit();
?>