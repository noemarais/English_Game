// server.js - Serveur HTTP avec support WebSocket
const http = require('http');
const https = require('https');
const fs = require('fs');
const path = require('path');
const url = require('url');
const WebSocket = require('ws');

const PORT = process.env.PORT || 3025;
const IS_HTTPS = process.env.HTTPS === 'true';

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

// Création du serveur HTTP
const server = http.createServer((req, res) => {
    const parsedUrl = url.parse(req.url, true);
    const pathname = parsedUrl.pathname;

    // Route WebSocket - retourne 426 Upgrade Required pour forcer l'upgrade
    if (pathname === '/ws') {
        res.writeHead(426, {
            'Upgrade': 'websocket',
            'Connection': 'Upgrade'
        });
        res.end('WebSocket upgrade required');
        return;
    }

    // Pour les fichiers PHP, on retourne une erreur car ils doivent être servis par PHP-FPM
    if (pathname.endsWith('.php')) {
        res.writeHead(503, { 'Content-Type': 'text/plain' });
        res.end('PHP files must be served by a PHP server (Apache/Nginx with PHP-FPM)');
        return;
    }

    // Servir les fichiers statiques
    let filePath = path.join(__dirname, pathname === '/' ? 'home.php' : pathname);
    
    // Si c'est la racine, essayer home.php puis index.html
    if (pathname === '/') {
        filePath = path.join(__dirname, 'home.php');
        if (!fs.existsSync(filePath)) {
            filePath = path.join(__dirname, 'index.html');
        }
    }

    // Vérifier si le fichier existe
    fs.stat(filePath, (err, stats) => {
        if (err || !stats.isFile()) {
            res.writeHead(404, { 'Content-Type': 'text/plain' });
            res.end('404 Not Found');
            return;
        }

        serveStaticFile(req, res, filePath);
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
    console.log(`Serveur HTTP/WebSocket démarré sur le port ${PORT}`);
    console.log(`WebSocket disponible sur ws://localhost:${PORT}/ws`);
});

