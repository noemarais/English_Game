// server.js - Serveur HTTP complet avec PHP, fichiers statiques et WebSocket
const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');
const { spawn } = require('child_process');
const WebSocket = require('ws');

const PORT = process.env.PORT || 3025;
const PHP_PATH = process.env.PHP_PATH || 'php'; // Chemin vers PHP (par défaut 'php' dans le PATH)

// gameId -> Set<ws>
const games = new Map();
// userId -> Set<ws> (pour la home / invites si besoin)
const users = new Map();

function addSocketToGame(gameId, ws) {
    if (!games.has(gameId)) {
        games.set(gameId, new Set());
    }
    games.get(gameId).add(ws);
}

function removeSocketFromGame(gameId, ws) {
    if (!games.has(gameId)) return;
    const set = games.get(gameId);
    set.delete(ws);
    if (set.size === 0) {
        games.delete(gameId);
    }
}

function addSocketToUser(userId, ws) {
    if (!users.has(userId)) {
        users.set(userId, new Set());
    }
    users.get(userId).add(ws);
}

function removeSocketFromUser(userId, ws) {
    if (!users.has(userId)) return;
    const set = users.get(userId);
    set.delete(ws);
    if (set.size === 0) {
        users.delete(userId);
    }
}

// Types qu'on rebroadcast "tel quels" à tous les joueurs de la game
const BROADCAST_TYPES = new Set([
    'timer_tick',
    'timer_done',
    'player_round_points',
    'round_changed',
    'game_ended',
    'start_game',
    'game_invite_created',
    'player_joined',
    'player_connected'
]);

// Fonction pour servir les fichiers statiques
function serveStaticFile(req, res, filePath) {
    const ext = path.extname(filePath).toLowerCase();
    const contentTypes = {
        '.html': 'text/html',
        '.css': 'text/css',
        '.js': 'application/javascript',
        '.json': 'application/json',
        '.png': 'image/png',
        '.jpg': 'image/jpeg',
        '.jpeg': 'image/jpeg',
        '.gif': 'image/gif',
        '.svg': 'image/svg+xml',
        '.ico': 'image/x-icon'
    };

    fs.readFile(filePath, (err, data) => {
        if (err) {
            res.writeHead(404, { 'Content-Type': 'text/plain' });
            res.end('404 Not Found');
            return;
        }

        const contentType = contentTypes[ext] || 'application/octet-stream';
        res.writeHead(200, { 'Content-Type': contentType });
        res.end(data);
    });
}

// Fonction pour exécuter un fichier PHP
function executePHP(req, res, filePath) {
    // Récupérer les variables d'environnement pour PHP
    const env = {
        ...process.env,
        REQUEST_METHOD: req.method,
        REQUEST_URI: req.url,
        QUERY_STRING: url.parse(req.url, true).query ? 
            new URLSearchParams(url.parse(req.url, true).query).toString() : '',
        HTTP_HOST: req.headers.host || 'localhost',
        SERVER_NAME: req.headers.host || 'localhost',
        SERVER_PORT: PORT,
        SCRIPT_NAME: req.url,
        SCRIPT_FILENAME: filePath,
        PATH_INFO: url.parse(req.url).pathname,
    };

    // Ajouter les headers HTTP comme variables d'environnement
    Object.keys(req.headers).forEach(key => {
        const envKey = 'HTTP_' + key.toUpperCase().replace(/-/g, '_');
        env[envKey] = req.headers[key];
    });

    // Pour les requêtes POST, récupérer le body
    let body = '';
    req.on('data', chunk => {
        body += chunk.toString();
    });

    req.on('end', () => {
        // Ajouter le body comme stdin pour PHP
        const phpProcess = spawn(PHP_PATH, ['-f', filePath], {
            env: env,
            cwd: path.dirname(filePath)
        });

        let output = '';
        let errorOutput = '';

        phpProcess.stdout.on('data', (data) => {
            output += data.toString();
        });

        phpProcess.stderr.on('data', (data) => {
            errorOutput += data.toString();
        });

        phpProcess.on('close', (code) => {
            if (code !== 0) {
                console.error('Erreur PHP:', errorOutput);
                res.writeHead(500, { 'Content-Type': 'text/html; charset=utf-8' });
                res.end(`<h1>Erreur PHP</h1><pre>${errorOutput}</pre>`);
                return;
            }

            // Parser la sortie PHP pour extraire les headers et le body
            const parts = output.split('\r\n\r\n');
            if (parts.length >= 2) {
                const headersPart = parts[0];
                const bodyPart = parts.slice(1).join('\r\n\r\n');
                
                const headers = {};
                headersPart.split('\r\n').forEach(line => {
                    const colonIndex = line.indexOf(':');
                    if (colonIndex > 0) {
                        const key = line.substring(0, colonIndex).trim();
                        const value = line.substring(colonIndex + 1).trim();
                        headers[key] = value;
                    }
                });

                res.writeHead(200, headers);
                res.end(bodyPart);
            } else {
                // Pas de headers, juste le body
                res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
                res.end(output);
            }
        });

        // Envoyer le body POST à PHP via stdin si nécessaire
        if (body && req.method === 'POST') {
            phpProcess.stdin.write(body);
            phpProcess.stdin.end();
        }
    });
}

// Création du serveur HTTP
const server = http.createServer((req, res) => {
    const parsedUrl = url.parse(req.url, true);
    const pathname = parsedUrl.pathname;

    // Route WebSocket - sera gérée par le serveur WebSocket
    if (pathname === '/ws') {
        // Le serveur WebSocket gérera cette route
        return;
    }

    // Déterminer le chemin du fichier
    let filePath;
    if (pathname === '/') {
        filePath = path.join(__dirname, 'home.php');
    } else {
        filePath = path.join(__dirname, pathname);
    }

    // Vérifier si le fichier existe
    fs.stat(filePath, (err, stats) => {
        if (err || !stats.isFile()) {
            res.writeHead(404, { 'Content-Type': 'text/plain' });
            res.end('404 Not Found');
            return;
        }

        // Si c'est un fichier PHP, l'exécuter
        if (filePath.endsWith('.php')) {
            executePHP(req, res, filePath);
        } else {
            // Sinon, servir comme fichier statique
            serveStaticFile(req, res, filePath);
        }
    });
});

// Création du serveur WebSocket attaché au serveur HTTP
const wss = new WebSocket.Server({ 
    server: server,
    path: '/ws'
});

wss.on('connection', (ws, req) => {
    const parsedUrl = url.parse(req.url, true);
    const params = parsedUrl.query;

    const userId = params.user_id ? String(params.user_id) : null;
    const gameId = params.game_id ? String(params.game_id) : null;
    const name   = params.name ? decodeURIComponent(params.name) : 'Unknown';

    ws._userId = userId;
    ws._gameId = gameId;
    ws._name   = name;

    console.log('Client connecté:', { userId, gameId, name });

    if (gameId) {
        addSocketToGame(gameId, ws);
    }
    if (userId) {
        addSocketToUser(userId, ws);
    }

    ws.on('message', (message) => {
        let data;
        try {
            data = JSON.parse(message.toString());
        } catch (e) {
            console.warn('Message non JSON:', message.toString());
            return;
        }

        const type = data.type;
        // On s'assure d'avoir un gameId dans les payload liés à une partie
        if (!data.gameId && gameId) {
            data.gameId = gameId;
        }
        const gId = String(data.gameId || gameId || '');

        // 1) Cas particulier: join_lobby -> on rebroadcast en player_joined
        if (type === 'join_lobby' && gId && games.has(gId)) {
            const payload = JSON.stringify({
                type: 'player_joined',
                gameId: gId,
                userId: data.userId || userId,
                name: data.name || name
            });

            console.log('Broadcast player_joined pour game', gId, 'user', data.userId);

            games.get(gId).forEach(client => {
                if (client.readyState === WebSocket.OPEN) {
                    client.send(payload);
                }
            });
            return;
        }

        // 2) Tous les autres types "classiques" qu'on relaie tels quels
        if (BROADCAST_TYPES.has(type) && gId && games.has(gId)) {
            const payload = JSON.stringify(data);
            console.log('Broadcast', type, 'pour game', gId);

            games.get(gId).forEach(client => {
                if (client.readyState === WebSocket.OPEN) {
                    client.send(payload);
                }
            });
        }

        // Si plus tard tu veux des messages ciblés par utilisateur,
        // tu pourras gérer ça ici avec users.get(userId)
    });

    ws.on('close', () => {
        console.log('Client déconnecté:', {
            userId: ws._userId,
            gameId: ws._gameId,
            name: ws._name
        });

        if (ws._gameId) {
            removeSocketFromGame(ws._gameId, ws);
        }
        if (ws._userId) {
            removeSocketFromUser(ws._userId, ws);
        }
    });

    ws.on('error', (err) => {
        console.error('Erreur WebSocket:', err);
    });
});

server.listen(PORT, () => {
    console.log(`✅ Serveur complet démarré sur le port ${PORT}`);
    console.log(`📄 Fichiers PHP: http://localhost:${PORT}/`);
    console.log(`🔌 WebSocket: ws://localhost:${PORT}/ws`);
    console.log(`📦 Fichiers statiques: http://localhost:${PORT}/[fichier]`);
});
