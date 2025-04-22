<?php
class InventoryManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function validateItemData($data) {
        $errors = [];
        
        if (empty($data['item_name'])) {
            $errors[] = "Le nom de l'article est requis";
        }
        
        if (!isset($data['quantity']) || !is_numeric($data['quantity'])) {
            $errors[] = "La quantité doit être un nombre valide";
        }
        
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode("\n", $errors));
        }
    }

    public function addItem($data) {
        $this->validateItemData($data);
        
        $sql = "INSERT INTO inventory 
                (item_name, category_id, quantity, min_quantity, unit, departement_id, 
                 supplier, unit_price, location, batch_number, expiry_date, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['item_name'],
                $data['category_id'] ?? null,
                $data['quantity'],
                $data['min_quantity'] ?? null,
                $data['unit'] ?? null,
                $data['departement_id'] ?? null,
                $data['supplier'] ?? null,
                $data['unit_price'] ?? null,
                $data['location'] ?? null,
                $data['batch_number'] ?? null,
                $data['expiry_date'] ?? null,
                $data['notes'] ?? null
            ]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Erreur d'ajout d'article: " . $e->getMessage());
            throw new Exception("Erreur lors de l'ajout de l'article");
        }
    }

    public function getItemById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM inventory WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur de récupération d'article: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération de l'article");
        }
    }

    public function updateItem($id, $data) {
        $this->validateItemData($data);
        
        $sql = "UPDATE inventory SET 
                item_name = ?, 
                category_id = ?, 
                quantity = ?, 
                min_quantity = ?, 
                unit = ?, 
                departement_id = ?, 
                supplier = ?, 
                unit_price = ?, 
                location = ?, 
                batch_number = ?, 
                expiry_date = ?, 
                notes = ?,
                last_updated = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['item_name'],
                $data['category_id'] ?? null,
                $data['quantity'],
                $data['min_quantity'] ?? null,
                $data['unit'] ?? null,
                $data['departement_id'] ?? null,
                $data['supplier'] ?? null,
                $data['unit_price'] ?? null,
                $data['location'] ?? null,
                $data['batch_number'] ?? null,
                $data['expiry_date'] ?? null,
                $data['notes'] ?? null,
                $id
            ]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Erreur de mise à jour: " . $e->getMessage());
            throw new Exception("Erreur lors de la mise à jour de l'article");
        }
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

    public function listItems($page = 1, $perPage = 20, $departmentFilter = null, $search = '') {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $conditions = [];
        
        $sql = "SELECT i.*, d.name as department_name 
                FROM inventory i
                LEFT JOIN departement d ON i.departement_id = d.id";
        
        if ($departmentFilter !== null) {
            $conditions[] = "i.departement_id = ?";
            $params[] = $departmentFilter;
        }
        
        if (!empty($search)) {
            $conditions[] = "(i.item_name LIKE ? OR i.batch_number LIKE ? OR i.supplier LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY i.last_updated DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur de liste: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération des articles");
        }
    }

    public function getInventoryCount($departmentFilter = null, $search = '') {
        try {
            $sql = "SELECT COUNT(*) FROM inventory";
            $params = [];
            $conditions = [];
            
            if ($departmentFilter !== null) {
                $conditions[] = "departement_id = ?";
                $params[] = $departmentFilter;
            }
            
            if (!empty($search)) {
                $conditions[] = "(item_name LIKE ? OR batch_number LIKE ? OR supplier LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur de comptage: " . $e->getMessage());
            throw new Exception("Erreur lors du comptage des articles");
        }
    }

    public function getStockAlerts() {
        try {
            $stmt = $this->pdo->query(
                "SELECT i.*, d.name as department_name 
                 FROM inventory i
                 LEFT JOIN departement d ON i.departement_id = d.id
                 WHERE quantity < min_quantity 
                 ORDER BY (min_quantity - quantity) DESC"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur d'alertes: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération des alertes");
        }
    }

    public function getDepartments() {
        try {
            $stmt = $this->pdo->query("SELECT id, name FROM departement ORDER BY name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur de départements: " . $e->getMessage());
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