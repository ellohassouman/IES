<?php
/**
 * Configuration centralisée pour les scripts PHP
 * À inclure dans tous les scripts PHP nécessitant une connexion
 */

// Configuration de la base de données
$DB_CONFIG = [
    'host' => '127.0.0.1',
    'user' => 'root',
    'password' => '',
    'database' => 'ies',
    'charset' => 'utf8mb4'
];

// Fonction de connexion réutilisable
function connectToDatabase($config = null) {
    global $DB_CONFIG;
    $cfg = $config ?? $DB_CONFIG;
    
    $conn = new mysqli($cfg['host'], $cfg['user'], $cfg['password'], $cfg['database']);
    
    if ($conn->connect_error) {
        die("❌ Erreur de connexion: " . $conn->connect_error);
    }
    
    $conn->set_charset($cfg['charset']);
    return $conn;
}

// Fonction pour afficher un message de succès
function showSuccess($message) {
    echo "✅ " . $message . "\n";
}

// Fonction pour afficher un message d'erreur
function showError($message) {
    echo "❌ " . $message . "\n";
}

// Fonction pour afficher un message d'information
function showInfo($message) {
    echo "ℹ️  " . $message . "\n";
}

// Fonction pour afficher un titre
function showTitle($title) {
    $line = str_repeat("=", 70);
    echo "\n$line\n";
    echo "  " . $title . "\n";
    echo "$line\n\n";
}

?>
