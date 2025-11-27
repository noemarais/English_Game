# Configuration Coolify - English Game

## ⚠️ PROBLÈME RÉSOLU : "Upgrade Required"

Le problème "Upgrade Required" vient du fait que Coolify essaie de servir les fichiers PHP via le serveur WebSocket. 

**Solution** : Utilisez le nouveau fichier `server.js` qui gère à la fois HTTP et WebSocket.

---

## Configuration dans Coolify

### Installation (Install Command)
```bash
npm install
```

### Build (Build Command)
```bash
# Laissez vide
```

### Run (Start Command)
```bash
npm start
```

## Variables d'environnement

- `PORT`: Port pour le serveur (Coolify définit automatiquement cette variable, généralement 3025)

## Configuration du reverse proxy

Dans Coolify, vous devez configurer le reverse proxy pour :

1. **Routes `/ws`** → Service Node.js (pour les WebSockets)
2. **Routes `*.php`** → Service PHP (si vous avez un service PHP séparé)
3. **Autres fichiers statiques** → Service Node.js

### Option recommandée : Service PHP séparé

Pour une meilleure performance, créez **DEUX services** dans Coolify :

#### Service 1 : Node.js (WebSocket + fichiers statiques)
- **Install**: `npm install`
- **Start**: `npm start`
- Gère les WebSockets sur `/ws` et les fichiers statiques (CSS, JS, images)

#### Service 2 : PHP (fichiers PHP)
- **Type**: PHP
- **Version**: PHP 8.1+
- Gère tous les fichiers `.php`

Configurez le reverse proxy pour router :
- `/ws` → Service Node.js
- `*.php` → Service PHP
- Autres → Service Node.js

---

## Notes importantes

- Le serveur WebSocket écoute sur `/ws` (pas besoin de spécifier le port dans l'URL)
- Les fichiers PHP doivent être servis par un serveur PHP (Apache/Nginx avec PHP-FPM)
- La base de données MySQL doit être configurée dans `db.php`
- Le nouveau `server.js` remplace `ws-server.js` et gère à la fois HTTP et WebSocket
