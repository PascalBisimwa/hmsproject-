<?php
defined('HMS_ACCESS') or die('Accès direct non autorisé');

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $config = [
            'host' => 'localhost',
            'dbname' => 'hmsbd',
            'username' => 'root',
            'password' => '',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        ];

        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            
            // Test immédiat de la connexion
            $this->pdo->query("SELECT 1");
        } catch (PDOException $e) {
            // Journalisation détaillée
            error_log("[" . date('Y-m-d H:i:s') . "] Erreur DB: " . $e->getMessage());
            
            // Message convivial en production
            die("Erreur de connexion à la base de données. Veuillez réessayer plus tard.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->pdo;
    }
}