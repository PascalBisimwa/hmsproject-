<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==============================================
// CONFIGURATION
// ==============================================
$use_real_database = false; // Mettez à true pour utiliser la vraie base de données

// ==============================================
// SYSTÈME DE CONNEXION
// ==============================================
if ($use_real_database) {
    // Utilisation de la vraie base de données
    require __DIR__ . '/include/connection.php';
    
    try {
        $pdo = Database::getInstance();
        $db_status = "Connecté à la base de données réelle";
    } catch (Exception $e) {
        $db_status = "Erreur DB: " . $e->getMessage();
    }
} else {
    // Simulation de base de données
    $users = [
        'admin' => [
            'id' => 1,
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'full_name' => 'Administrateur Système'
        ],
        'docteur' => [
            'id' => 2,
            'password' => password_hash('docteur123', PASSWORD_DEFAULT),
            'role' => 'doctor',
            'full_name' => 'Dr. Dupont'
        ]
    ];
    $db_status = "Utilisation de la base de données simulée";
}

// Traitement du formulaire
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($use_real_database) {
        // Connexion avec la vraie base de données
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION = [
                    'user_id' => $user['id'],
                    'role' => $user['role'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name']
                ];
                
                // Redirection
                $dashboardUrl = match($user['role']) {
                    'admin' => '/Hms/admin/admin_dashboard.php',
                    'doctor' => '/Hms/doctors/doctors_dashboard.php',
                    'receptionist' => '/Hms/reception/reception_dashboard.php',
                    'patient' => '/Hms/patient/patient_dashboard.php',
                    default => '/Hms/index.php'
                };
                header("Location: $dashboardUrl");
                exit();
            } else {
                $error = "Identifiants incorrects";
            }
        } catch (PDOException $e) {
            $error = "Erreur de base de données: " . $e->getMessage();
        }
    } else {
        // Connexion avec la simulation
        if (isset($users[$username])) {
            if (password_verify($password, $users[$username]['password'])) {
                $_SESSION = [
                    'user_id' => $users[$username]['id'],
                    'role' => $users[$username]['role'],
                    'username' => $username,
                    'full_name' => $users[$username]['full_name']
                ];
                
                // Redirection simulée
                echo "<script>alert('Connexion réussie (simulation)! Redirection désactivée en mode test.');</script>";
            } else {
                $error = "Mot de passe incorrect";
            }
        } else {
            $error = "Utilisateur non trouvé";
        }
    }
}

// Debug: Afficher les infos de session
$session_info = isset($_SESSION['user_id']) 
    ? "Connecté en tant que: " . $_SESSION['full_name'] . " (" . $_SESSION['role'] . ")"
    : "Non connecté";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Connexion | HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding-top: 50px; }
        .test-container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .debug-info { background: #f1f1f1; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .credentials { background: #e9f7ef; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-container">
            <h2 class="text-center mb-4">Test de Connexion HMS</h2>
            
            <div class="debug-info">
                <h5>Informations de débogage:</h5>
                <p><strong>Mode:</strong> <?= $use_real_database ? 'Base de données réelle' : 'Simulation' ?></p>
                <p><strong>Statut DB:</strong> <?= $db_status ?></p>
                <p><strong>Session:</strong> <?= $session_info ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="credentials">
                <h5>Identifiants de test:</h5>
                <ul>
                    <li><strong>Admin:</strong> admin / admin123</li>
                    <li><strong>Docteur:</strong> docteur / docteur123</li>
                </ul>
            </div>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Nom d'utilisateur</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Mot de passe</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" name="login" class="btn btn-primary w-100">Se connecter</button>
            </form>
            
            <div class="mt-4 text-center">
                <button class="btn btn-sm btn-outline-secondary" onclick="toggleDebug()">Afficher les infos de session</button>
                <div id="debugSession" style="display: none; margin-top: 15px;">
                    <pre><?php print_r($_SESSION) ?></pre>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleDebug() {
            const debugDiv = document.getElementById('debugSession');
            debugDiv.style.display = debugDiv.style.display === 'none' ? 'block' : 'none';
        }
        
        // Afficher une alerte si connexion simulée réussie
        <?php if (!$use_real_database && isset($_SESSION['user_id'])): ?>
            alert("Connexion simulée réussie!\n\n" +
                  "Utilisateur: <?= $_SESSION['username'] ?>\n" +
                  "Rôle: <?= $_SESSION['role'] ?>\n\n" +
                  "Redirection désactivée en mode test.");
        <?php endif; ?>
    </script>
</body>
</html>