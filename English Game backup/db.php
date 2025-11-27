<?php
$host = 'localhost';
$dbname = 'cnxtpydq_english-game';
$user = 'cnxtpydq_english-game';
$pass = 'English-game2025';

try {
    $db = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données');
}
