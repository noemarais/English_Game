<?php
// init-db.php - Script PHP pour initialiser la base de données SQLite
// Utilisez ce script si Node.js n'est pas disponible ou si vous préférez PHP

// Vérifier si SQLite est disponible
if (!extension_loaded('sqlite3') && !extension_loaded('pdo_sqlite')) {
    die('❌ Erreur: L\'extension SQLite n\'est pas installée dans PHP.<br>'
        . 'Installez php-sqlite3 dans votre conteneur.<br>'
        . 'Pour Coolify, ajoutez dans Build Command: apt-get update && apt-get install -y php-sqlite3');
}

$dbPath = __DIR__ . '/database.db';

// Supprimer la base de données existante si elle existe
if (file_exists($dbPath)) {
    unlink($dbPath);
    echo "✅ Base de données existante supprimée<br>";
}

try {
    $db = new PDO("sqlite:$dbPath", null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    $db->exec('PRAGMA foreign_keys = ON');
    
    echo "✅ Base de données SQLite créée<br>";
    echo "📦 Création des tables...<br>";
    
    // Table joueurs
    $db->exec("
        CREATE TABLE IF NOT EXISTS joueurs (
            user_id INTEGER PRIMARY KEY AUTOINCREMENT,
            nom VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            mot_de_passe VARCHAR(255) NOT NULL,
            points_totaux INTEGER DEFAULT 0,
            victoires_totales INTEGER DEFAULT 0,
            date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
            avatar_path VARCHAR(255) DEFAULT NULL
        )
    ");
    
    // Table friends
    $db->exec("
        CREATE TABLE IF NOT EXISTS friends (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            requester_id INTEGER NOT NULL,
            requested_id INTEGER NOT NULL,
            status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'accepted', 'rejected', 'blocked')),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(requester_id, requested_id),
            FOREIGN KEY (requester_id) REFERENCES joueurs(user_id) ON DELETE CASCADE,
            FOREIGN KEY (requested_id) REFERENCES joueurs(user_id) ON DELETE CASCADE
        )
    ");
    
    // Table game_session
    $db->exec("
        CREATE TABLE IF NOT EXISTS game_session (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            host_id INTEGER NOT NULL,
            code CHAR(6) NOT NULL UNIQUE,
            nom VARCHAR(100) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            finished_at DATETIME DEFAULT NULL,
            duration_seconds INTEGER DEFAULT NULL,
            status TEXT DEFAULT 'open' CHECK(status IN ('open', 'in_progress', 'finished')),
            FOREIGN KEY (host_id) REFERENCES joueurs(user_id) ON DELETE CASCADE
        )
    ");
    
    // Table game_players
    $db->exec("
        CREATE TABLE IF NOT EXISTS game_players (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            game_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            total_points INTEGER DEFAULT 0,
            manches_gagnees INTEGER DEFAULT 0,
            position_finale INTEGER DEFAULT NULL,
            added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(game_id, user_id),
            FOREIGN KEY (game_id) REFERENCES game_session(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES joueurs(user_id) ON DELETE CASCADE
        )
    ");
    
    // Table game_rounds
    $db->exec("
        CREATE TABLE IF NOT EXISTS game_rounds (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            game_player_id INTEGER NOT NULL,
            round_number INTEGER NOT NULL,
            points INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(game_player_id, round_number),
            FOREIGN KEY (game_player_id) REFERENCES game_players(id) ON DELETE CASCADE
        )
    ");
    
    // Table game_invitations
    $db->exec("
        CREATE TABLE IF NOT EXISTS game_invitations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            game_id INTEGER NOT NULL,
            inviter_id INTEGER NOT NULL,
            invited_id INTEGER NOT NULL,
            status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'accepted', 'declined')),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (game_id) REFERENCES game_session(id) ON DELETE CASCADE,
            FOREIGN KEY (inviter_id) REFERENCES joueurs(user_id) ON DELETE CASCADE,
            FOREIGN KEY (invited_id) REFERENCES joueurs(user_id) ON DELETE CASCADE
        )
    ");
    
    // Table game_history
    $db->exec("
        CREATE TABLE IF NOT EXISTS game_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            game_id INTEGER DEFAULT NULL,
            exported_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            json_snapshot TEXT,
            FOREIGN KEY (game_id) REFERENCES game_session(id) ON DELETE SET NULL
        )
    ");
    
    echo "✅ Toutes les tables créées avec succès<br>";
    echo "✅ Base de données initialisée !<br>";
    echo "💡 Vous pouvez maintenant créer un compte via l'interface web<br>";
    
} catch (PDOException $e) {
    die("❌ Erreur lors de l'initialisation: " . $e->getMessage());
}

