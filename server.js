// server.js - Serveur HTTP complet optimisé avec PHP, fichiers statiques et WebSocket
const http = require('http');
const fs = require('fs');
const path = require('path');
const url = require('url');
const { spawn } = require('child_process');
const WebSocket = require('ws');

const PORT = process.env.PORT || 3025;
const PHP_PATH = process.env.PHP_PATH || 'php';

// gameId -> Set<ws>
const games = new Map();
const users = new Map();

function addSocketToGame(gameId, ws) {
    if (!games.has(gameId)) games.set(gameId, new Set());
    games.get(gameId).add(ws);
}

function removeSocketFromGame(gameId, ws) {
    if (!games.has(gameId)) return;
    const set = games.get(gameId);
    set.delete(ws);
    if (set.size === 0) games.delete(gameId);
}

function addSocketToUser(userId, ws) {
    if (!users.has(userId)) users.set(userId, new Set());
    users.get(userId).add(ws);
}

function removeSocketFromUser(userId, ws) {
    if (!users.has(userId)) return;
    const set = users.get(userId);
    set.delete(ws);
    if (set.size === 0) users.delete(userId);
}

const BROADCAST_TYPES = new Set([
    'timer_tick', 'timer_done', 'player_round_points', 'round_changed',
    'game_ended', 'start_game', 'game_invite_created', 'player_joined', 'player_connected'
]);

// Types MIME
const MIME_TYPES = {
    '.html': 'text/html; charset=utf-8',
    '.css': 'text/css; charset=utf-8',
    '.js': 'application/javascript; charset=utf-8',
    '.json': 'application/json; charset=utf-8',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.gif': 'image/gif',
    '.svg': 'image/svg+xml',
    '.ico': 'image/x-icon',
    '.woff': 'font/woff',
    '.woff2': 'font/woff2',
    '.ttf': 'font/ttf'
};

// Servir un fichier statique
function serveStaticFile(req, res, filePath) {
    const ext = path.extname(filePath).toLowerCase();
    const contentType = MIME_TYPES[ext] || 'application/octet-stream';
    
    fs.readFile(filePath, (err, data) => {
        if (err) {
            console.error(`❌ Erreur lecture ${filePath}:`, err.message);
            res.writeHead(404, { 'Content-Type': 'text/plain' });
            res.end('404 Not Found');
            return;
        }
        
        res.writeHead(200, {
            'Content-Type': contentType,
            'Cache-Control': 'public, max-age=3600'
        });
        res.end(data);
    });
}

// Exécuter un fichier PHP
function executePHP(req, res, filePath) {
    const parsedUrl = url.parse(req.url, true);
    const queryString = parsedUrl.query ? new URLSearchParams(parsedUrl.query).toString() : '';
    const cookies = req.headers.cookie || '';
    
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
        HTTP_REFERER: req.headers.referer || ''
    };
    
    Object.keys(req.headers).forEach(key => {
        if (!env['HTTP_' + key.toUpperCase().replace(/-/g, '_')]) {
            env['HTTP_' + key.toUpperCase().replace(/-/g, '_')] = req.headers[key];
        }
    });
    
    let body = '';
    req.on('data', chunk => { body += chunk.toString(); });
    
    req.on('end', () => {
        const phpProcess = spawn(PHP_PATH, ['-f', filePath], {
            env: env,
            cwd: __dirname,
            stdio: ['pipe', 'pipe', 'pipe']
        });
        
        let output = '';
        let errorOutput = '';
        
        phpProcess.stdout.on('data', (data) => { output += data.toString(); });
        phpProcess.stderr.on('data', (data) => { errorOutput += data.toString(); });
        
        phpProcess.on('error', (err) => {
            if (err.code === 'ENOENT') {
                res.writeHead(503, { 'Content-Type': 'text/html; charset=utf-8' });
                res.end('<!DOCTYPE html><html><head><title>PHP non installé</title></head><body><h1>❌ PHP n\'est pas installé</h1><p>Installez PHP dans votre conteneur.</p></body></html>');
                return;
            }
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
            
            // Parser les headers et le body
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
        
        if (body && (req.method === 'POST' || req.method === 'PUT')) {
            phpProcess.stdin.write(body);
            phpProcess.stdin.end();
        } else {
            phpProcess.stdin.end();
        }
    });
}

// Serveur HTTP
const server = http.createServer((req, res) => {
    const parsedUrl = url.parse(req.url, true);
    const pathname = parsedUrl.pathname;
    
    // WebSocket
    if (pathname === '/ws') {
        return;
    }
    
    // Déterminer le chemin du fichier
    let filePath;
    if (pathname === '/') {
        filePath = path.join(__dirname, 'home.php');
    } else {
        const cleanPath = pathname.split('?')[0];
        filePath = path.join(__dirname, cleanPath);
        
        // Sécurité
        const resolvedPath = path.resolve(filePath);
        const basePath = path.resolve(__dirname);
        if (!resolvedPath.startsWith(basePath)) {
            res.writeHead(403, { 'Content-Type': 'text/plain' });
            res.end('403 Forbidden');
            return;
        }
    }
    
    // Vérifier si le fichier existe
    fs.stat(filePath, (err, stats) => {
        if (err) {
            console.error(`❌ Fichier non trouvé: ${pathname}`);
            res.writeHead(404, { 'Content-Type': 'text/plain' });
            res.end('404 Not Found: ' + pathname);
            return;
        }
        
        if (!stats.isFile()) {
            res.writeHead(404, { 'Content-Type': 'text/plain' });
            res.end('404 Not Found: Not a file');
            return;
        }
        
        const ext = path.extname(filePath).toLowerCase();
        
        // Servir les fichiers statiques (CSS, JS, images) AVANT les PHP
        if (ext !== '.php' && MIME_TYPES[ext]) {
            serveStaticFile(req, res, filePath);
        } else if (ext === '.php') {
            executePHP(req, res, filePath);
        } else {
            serveStaticFile(req, res, filePath);
        }
    });
});

// Serveur WebSocket
const wss = new WebSocket.Server({ server: server, path: '/ws' });

wss.on('connection', (ws, req) => {
    const parsedUrl = url.parse(req.url, true);
    const params = parsedUrl.query;
    
    const userId = params.user_id ? String(params.user_id) : null;
    const gameId = params.game_id ? String(params.game_id) : null;
    const name = params.name ? decodeURIComponent(params.name) : 'Unknown';
    
    ws._userId = userId;
    ws._gameId = gameId;
    ws._name = name;
    
    console.log('🔌 Client WebSocket connecté:', { userId, gameId, name });
    
    if (gameId) addSocketToGame(gameId, ws);
    if (userId) addSocketToUser(userId, ws);
    
    ws.on('message', (message) => {
        let data;
        try {
            data = JSON.parse(message.toString());
        } catch (e) {
            console.warn('Message non JSON:', message.toString());
            return;
        }
        
        const type = data.type;
        if (!data.gameId && gameId) data.gameId = gameId;
        const gId = String(data.gameId || gameId || '');
        
        if (type === 'join_lobby' && gId && games.has(gId)) {
            const payload = JSON.stringify({
                type: 'player_joined',
                gameId: gId,
                userId: data.userId || userId,
                name: data.name || name
            });
            
            games.get(gId).forEach(client => {
                if (client.readyState === WebSocket.OPEN) {
                    client.send(payload);
                }
            });
            return;
        }
        
        if (BROADCAST_TYPES.has(type) && gId && games.has(gId)) {
            const payload = JSON.stringify(data);
            games.get(gId).forEach(client => {
                if (client.readyState === WebSocket.OPEN) {
                    client.send(payload);
                }
            });
        }
    });
    
    ws.on('close', () => {
        console.log('🔌 Client WebSocket déconnecté:', { userId: ws._userId, gameId: ws._gameId });
        if (ws._gameId) removeSocketFromGame(ws._gameId, ws);
        if (ws._userId) removeSocketFromUser(ws._userId, ws);
    });
    
    ws.on('error', (err) => {
        console.error('❌ Erreur WebSocket:', err);
    });
});

// Vérifier la base de données
const dbPath = path.join(__dirname, 'database.db');
if (!fs.existsSync(dbPath)) {
    console.log('⚠️  Base de données non trouvée. Elle sera créée automatiquement au premier accès PHP.');
}

server.listen(PORT, () => {
    console.log(`✅ Serveur démarré sur le port ${PORT}`);
    console.log(`📄 PHP: http://localhost:${PORT}/`);
    console.log(`🔌 WebSocket: ws://localhost:${PORT}/ws`);
    console.log(`💾 Base de données: ${dbPath}`);
});
