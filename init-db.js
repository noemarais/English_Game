// init-db.js - Script d'initialisation de la base de données SQLite
const sqlite3 = require('sqlite3').verbose();
const fs = require('fs');
const path = require('path');

const dbPath = path.join(__dirname, 'database.db');

// Supprimer la base de données existante si elle existe
if (fs.existsSync(dbPath)) {
    fs.unlinkSync(dbPath);
    console.log('✅ Base de données existante supprimée');
}

// Créer une nouvelle base de données
const db = new sqlite3.Database(dbPath, (err) => {
    if (err) {
        console.error('❌ Erreur lors de la création de la base de données:', err);
        process.exit(1);
    }
    console.log('✅ Base de données SQLite créée');
});

// Activer les clés étrangères
db.run('PRAGMA foreign_keys = ON');

// Fonction pour exécuter une requête SQL
function runSQL(sql) {
    return new Promise((resolve, reject) => {
        db.run(sql, (err) => {
            if (err) {
                reject(err);
            } else {
                resolve();
            }
        });
    });
}

// Fonction pour exécuter plusieurs requêtes
async function initDatabase() {
    try {
        console.log('📦 Création des tables...');

        // Table joueurs
        await runSQL(`
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
        `);

        // Table friends
        await runSQL(`
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
        `);

        // Table game_session
        await runSQL(`
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
        `);

        // Table game_players
        await runSQL(`
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
        `);

        // Table game_rounds
        await runSQL(`
            CREATE TABLE IF NOT EXISTS game_rounds (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                game_player_id INTEGER NOT NULL,
                round_number INTEGER NOT NULL,
                points INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(game_player_id, round_number),
                FOREIGN KEY (game_player_id) REFERENCES game_players(id) ON DELETE CASCADE
            )
        `);

        // Table game_invitations
        await runSQL(`
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
        `);

        // Table game_history
        await runSQL(`
            CREATE TABLE IF NOT EXISTS game_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                game_id INTEGER DEFAULT NULL,
                exported_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                json_snapshot TEXT,
                FOREIGN KEY (game_id) REFERENCES game_session(id) ON DELETE SET NULL
            )
        `);

        console.log('✅ Toutes les tables créées avec succès');
        
        console.log('✅ Base de données initialisée avec succès !');
        console.log('💡 Vous pouvez maintenant créer un compte via l\'interface web');
        
        db.close((err) => {
            if (err) {
                console.error('❌ Erreur lors de la fermeture:', err);
            } else {
                console.log('✅ Base de données fermée');
            }
            process.exit(0);
        });
    } catch (error) {
        console.error('❌ Erreur lors de l\'initialisation:', error);
        db.close();
        process.exit(1);
    }
}

initDatabase();

