<?php
// Réactiver les contraintes de clés étrangères - Version simple

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Connexion directe à la base de données
    $mysqli = new mysqli('localhost', 'root', '', 'ies');
    
    if ($mysqli->connect_error) {
        die("Erreur de connexion: " . $mysqli->connect_error);
    }
    
    echo "=== RÉACTIVATION DES CONTRAINTES ===\n";
    
    // Réactiver les contraintes de clés étrangères
    if ($mysqli->query('SET FOREIGN_KEY_CHECKS = 1')) {
        echo "✓ Commande exécutée: SET FOREIGN_KEY_CHECKS = 1\n";
    } else {
        echo "❌ Erreur lors de l'exécution: " . $mysqli->error . "\n";
    }
    
    // Vérifier l'état
    $result = $mysqli->query('SELECT @@foreign_key_checks as status');
    if ($result) {
        $row = $result->fetch_assoc();
        $status = $row['status'];
        
        echo "\n=== VÉRIFICATION ===\n";
        echo "État FOREIGN_KEY_CHECKS: " . ($status ? "ACTIVÉ (1)" : "DÉSACTIVÉ (0)") . "\n";
        
        if ($status) {
            echo "✅ Les contraintes de clés étrangères sont maintenant actives\n";
        } else {
            echo "❌ Les contraintes sont toujours désactivées\n";
        }
    }
    
    $mysqli->close();
    
} catch(Exception $exp) {
    echo "ERROR: " . $exp->getMessage();
}
?>
