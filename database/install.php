<?php
require_once '../includes/config.php';

try {
    // Lire le fichier schema.sql
    $sql = file_get_contents(__DIR__ . '/schema.sql');

    // Exécuter les requêtes SQL
    $pdo->exec($sql);

    echo "Base de données créée avec succès !";
} catch (PDOException $e) {
    die("Erreur lors de la création de la base de données : " . $e->getMessage());
}
