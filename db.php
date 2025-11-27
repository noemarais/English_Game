<?php
// Configuration SQLite - Base de données locale
$dbPath = __DIR__ . '/database.db';

try {
    $db = new PDO(
        "sqlite:$dbPath",
        null,
        null,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    
    // Activer les clés étrangères pour SQLite
    $db->exec('PRAGMA foreign_keys = ON');
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données: ' . $e->getMessage());
}
