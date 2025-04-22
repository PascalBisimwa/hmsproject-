<?php
define('HMS_ACCESS', true);
require __DIR__ . '/../include/security.php';
require __DIR__ . '/../include/connection.php';

checkAuth('admin');
$pdo = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Vérification CSRF
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Erreur de sécurité: Token invalide');
        }

        // Validation ID
        if (!isset($_POST['id']) || !filter_var($_POST['id'], FILTER_VALIDATE_INT)) {
            throw new Exception('ID de service invalide');
        }
        $service_id = (int)$_POST['id'];

        // Vérification existence service
        $stmt = $pdo->prepare("SELECT id FROM services WHERE id = ?");
        $stmt->execute([$service_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Service introuvable');
        }

        // Désactiver temporairement les contraintes FK
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        // Suppression
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
        $stmt->execute([$service_id]);

        // Réactiver les contraintes
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        if ($stmt->rowCount() > 0) {
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Service supprimé avec succès'
            ];
        } else {
            throw new Exception('Aucun service supprimé');
        }

    } catch (PDOException $e) {
        error_log('Erreur suppression service: ' . $e->getMessage());
        
        $message = 'Erreur lors de la suppression';
        if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
            $message = 'Impossible de supprimer - des éléments sont liés à ce service';
        }

        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => $message
        ];
    } catch (Exception $e) {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'message' => $e->getMessage()
        ];
    }
}

header('Location: manage_services.php');
exit();