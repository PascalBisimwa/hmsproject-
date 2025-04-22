<?php
define('HMS_ACCESS', true);
require __DIR__ . '/../include/security.php';
require __DIR__ . '/../include/connection.php';

// Debug initial
error_log("=== DEBUT TRAITEMENT save_service.php ===");
error_log("Session: " . print_r($_SESSION, true));
error_log("POST: " . print_r($_POST, true));

checkAuth('admin');
$pdo = Database::getInstance();

// Vérification CSRF
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    error_log("CSRF token invalide");
    $_SESSION['alert'] = ['type' => 'danger', 'message' => 'Erreur de sécurité'];
    header('Location: add_service.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. Récupération des données
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $departement_id = (int)($_POST['departement_id'] ?? 0);
        $manager_id = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_night_service = isset($_POST['is_night_service']) ? 1 : 0;

        error_log("Données nettoyées:");
        error_log("- Name: $name");
        error_log("- Departement ID: $departement_id");
        error_log("- Manager ID: " . ($manager_id ?? 'NULL'));

        // 2. Validation
        if (empty($name) || strlen($name) > 100) {
            throw new Exception("Le nom doit contenir entre 1 et 100 caractères");
        }

        if (empty($departement_id)) {
            throw new Exception("Département non sélectionné");
        }

        // 3. Vérification existence département
        $stmt = $pdo->prepare("SELECT id FROM departement WHERE id = ?");
        $stmt->execute([$departement_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Département #$departement_id introuvable");
        }

        // 4. Vérification manager (si spécifié)
        if ($manager_id) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role IN ('manager','admin')");
            $stmt->execute([$manager_id]);
            if (!$stmt->fetch()) {
                throw new Exception("Responsable #$manager_id invalide ou non autorisé");
            }
        }

        // 5. Préparation requête
        $sql = "INSERT INTO services (
                name, 
                description, 
                departement_id, 
                manager_id, 
                is_active, 
                is_night_service
            ) VALUES (?, ?, ?, ?, ?, ?)";
        
        error_log("Requête SQL: $sql");

        // 6. Exécution
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            $name,
            $description,
            $departement_id,
            $manager_id,
            $is_active,
            $is_night_service
        ]);

        if (!$success) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Erreur SQL: " . $errorInfo[2]);
        }

        $newId = $pdo->lastInsertId();
        error_log("Service créé avec ID: $newId");

        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => "Service #$newId créé avec succès"
        ];
        header('Location: manage_services.php');
        exit();

    } catch (PDOException $e) {
        error_log("PDOException: " . $e->getMessage());
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Erreur base de données: ' . $e->getMessage()
        ];
        header('Location: add_service.php');
        exit();
    } catch (Exception $e) {
        error_log("Exception: " . $e->getMessage());
        $_SESSION['alert'] = [
            'type' => 'warning',
            'message' => $e->getMessage()
        ];
        header('Location: add_service.php');
        exit();
    }
} else {
    error_log("Méthode non POST");
    header('Location: add_service.php');
    exit();
}