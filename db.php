<?php
// Configuration SQLite - Base de données locale
$dbPath = __DIR__ . '/database.db';

// Vérifier si SQLite est disponible
if (!extension_loaded('pdo_sqlite')) {
    // Essayer d'initialiser la base de données si elle n'existe pas
    if (!file_exists($dbPath)) {
        // Rediriger vers init-db.php pour initialiser
        if (file_exists(__DIR__ . '/init-db.php')) {
            require __DIR__ . '/init-db.php';
            // Après l'initialisation, réessayer la connexion
        } else {
            die('❌ Erreur: L\'extension SQLite (pdo_sqlite) n\'est pas installée dans PHP.<br>'
                . 'Installez php-sqlite3 dans votre conteneur.<br>'
                . 'Pour Coolify, ajoutez dans Build Command: apt-get update && apt-get install -y php-sqlite3');
        }
    } else {
        die('❌ Erreur: L\'extension SQLite (pdo_sqlite) n\'est pas installée dans PHP.<br>'
            . 'Installez php-sqlite3 dans votre conteneur.');
    }
}

try {
    // Créer la base de données si elle n'existe pas
    if (!file_exists($dbPath)) {
        // Initialiser la base de données automatiquement
        $db = new PDO("sqlite:$dbPath", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        $db->exec('PRAGMA foreign_keys = ON');
        
        // Créer les tables
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
        
        $db->exec("
            CREATE TABLE IF NOT EXISTS game_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                game_id INTEGER DEFAULT NULL,
                exported_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                json_snapshot TEXT,
                FOREIGN KEY (game_id) REFERENCES game_session(id) ON DELETE SET NULL
            )
        ");
    } else {
        $db = new PDO("sqlite:$dbPath", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        // Activer les clés étrangères pour SQLite
        $db->exec('PRAGMA foreign_keys = ON');
    }
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données: ' . $e->getMessage());
}
