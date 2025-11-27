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
    const parsedUrl = url.parse(req.url, true);
    const queryString = parsedUrl.query ? new URLSearchParams(parsedUrl.query).toString() : '';
    
    // Récupérer les cookies de la requête
    const cookies = req.headers.cookie || '';
    
    // Récupérer les variables d'environnement pour PHP
    const env = {
        ...process.env,
        REQUEST_METHOD: req.method,
        REQUEST_URI: req.url,
        QUERY_STRING: queryString,
        HTTP_HOST: req.headers.host || 'localhost',
        SERVER_NAME: req.headers.host ? req.headers.host.split(':')[0] : 'localhost',
        SERVER_PORT: PORT.toString(),
        SCRIPT_NAME: parsedUrl.pathname,
        SCRIPT_FILENAME: filePath,
        PATH_INFO: parsedUrl.pathname,
        PATH_TRANSLATED: filePath,
        DOCUMENT_ROOT: __dirname,
        SERVER_PROTOCOL: 'HTTP/1.1',
        GATEWAY_INTERFACE: 'CGI/1.1',
        CONTENT_TYPE: req.headers['content-type'] || '',
        CONTENT_LENGTH: req.headers['content-length'] || '0',
        HTTP_COOKIE: cookies,
        HTTP_USER_AGENT: req.headers['user-agent'] || '',
        HTTP_ACCEPT: req.headers.accept || '*/*',
        HTTP_ACCEPT_LANGUAGE: req.headers['accept-language'] || '',
        HTTP_ACCEPT_ENCODING: req.headers['accept-encoding'] || '',
        HTTP_CONNECTION: req.headers.connection || 'keep-alive',
        HTTP_REFERER: req.headers.referer || '',
    };

    // Ajouter tous les autres headers HTTP comme variables d'environnement
    Object.keys(req.headers).forEach(key => {
        if (!env['HTTP_' + key.toUpperCase().replace(/-/g, '_')]) {
            const envKey = 'HTTP_' + key.toUpperCase().replace(/-/g, '_');
            env[envKey] = req.headers[key];
        }
    });

    // Pour les requêtes POST/PUT, récupérer le body
    let body = '';
    req.on('data', chunk => {
        body += chunk.toString();
    });

    req.on('end', () => {
        // Exécuter PHP avec les bonnes options
        const phpProcess = spawn(PHP_PATH, ['-f', filePath], {
            env: env,
            cwd: __dirname,
            stdio: ['pipe', 'pipe', 'pipe']
        });

        let output = '';
        let errorOutput = '';

        phpProcess.stdout.on('data', (data) => {
            output += data.toString();
        });

        phpProcess.stderr.on('data', (data) => {
            errorOutput += data.toString();
        });

        // Gérer l'erreur si PHP n'est pas installé
        phpProcess.on('error', (err) => {
            if (err.code === 'ENOENT') {
                console.error('❌ PHP n\'est pas installé !');
                console.error('💡 Installez PHP dans votre conteneur Coolify.');
                console.error('   Option 1: Utilisez le Dockerfile fourni');
                console.error('   Option 2: Ajoutez dans Build Command: apt-get update && apt-get install -y php php-cli php-mysql');
                res.writeHead(503, { 'Content-Type': 'text/html; charset=utf-8' });
                res.end(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>PHP non installé</title>
                        <meta charset="utf-8">
                        <style>
                            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
                            code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
                        </style>
                    </head>
                    <body>
                        <h1>❌ PHP n'est pas installé</h1>
                        <p>PHP doit être installé dans votre conteneur Coolify pour exécuter les fichiers PHP.</p>
                        <h2>Solutions :</h2>
                        <h3>Option 1 : Utiliser le Dockerfile (Recommandé)</h3>
                        <p>Dans Coolify, configurez votre service pour utiliser le <code>Dockerfile</code> fourni.</p>
                        <h3>Option 2 : Installer PHP via Build Command</h3>
                        <p>Dans Coolify, ajoutez dans <strong>Build Command</strong> :</p>
                        <pre><code>apt-get update && apt-get install -y php php-cli php-mysql php-mbstring php-xml php-curl && npm install</code></pre>
                        <h3>Option 3 : Utiliser une image Docker avec PHP</h3>
                        <p>Utilisez une image de base qui contient déjà PHP et Node.js.</p>
                    </body>
                    </html>
                `);
                return;
            }
            console.error('Erreur lors de l\'exécution de PHP:', err);
            res.writeHead(500, { 'Content-Type': 'text/html; charset=utf-8' });
            res.end(`<h1>Erreur serveur</h1><p>${err.message}</p>`);
        });

        phpProcess.on('close', (code) => {
            if (code !== 0) {
                console.error('Erreur PHP:', errorOutput);
                res.writeHead(500, { 'Content-Type': 'text/html; charset=utf-8' });
                res.end(`<h1>Erreur PHP</h1><pre>${errorOutput}</pre>`);
                return;
            }

            // Parser la sortie PHP pour extraire les headers et le body
            // PHP peut envoyer des headers avec \r\n\r\n ou \n\n
            let headersPart = '';
            let bodyPart = output;
            
            const doubleNewlineIndex = output.indexOf('\r\n\r\n');
            if (doubleNewlineIndex !== -1) {
                headersPart = output.substring(0, doubleNewlineIndex);
                bodyPart = output.substring(doubleNewlineIndex + 4);
            } else {
                const doubleNewlineIndex2 = output.indexOf('\n\n');
                if (doubleNewlineIndex2 !== -1) {
                    headersPart = output.substring(0, doubleNewlineIndex2);
                    bodyPart = output.substring(doubleNewlineIndex2 + 2);
                }
            }
            
            const headers = { 'Content-Type': 'text/html; charset=utf-8' };
            
            if (headersPart) {
                headersPart.split(/\r?\n/).forEach(line => {
                    const colonIndex = line.indexOf(':');
                    if (colonIndex > 0) {
                        const key = line.substring(0, colonIndex).trim();
                        const value = line.substring(colonIndex + 1).trim();
                        if (key.toLowerCase() === 'content-type') {
                            headers['Content-Type'] = value;
                        } else if (key.toLowerCase() === 'location') {
                            headers['Location'] = value;
                            res.writeHead(302, headers);
                            res.end();
                            return;
                        } else if (key.toLowerCase().startsWith('set-cookie')) {
                            // Gérer les cookies
                            if (!res.getHeader('Set-Cookie')) {
                                res.setHeader('Set-Cookie', []);
                            }
                            const cookies = res.getHeader('Set-Cookie') || [];
                            cookies.push(value);
                            res.setHeader('Set-Cookie', cookies);
                        } else {
                            headers[key] = value;
                        }
                    }
                });
            }

            res.writeHead(200, headers);
            res.end(bodyPart);
        });

        // Envoyer le body POST/PUT à PHP via stdin si nécessaire
        if (body && (req.method === 'POST' || req.method === 'PUT')) {
            phpProcess.stdin.write(body);
            phpProcess.stdin.end();
        } else {
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
