# Configuration Coolify - English Game

## Commandes pour Coolify

### Installation (Install Command)
```bash
npm install
```

### Build (Build Command)
```bash
# Laissez vide ou ne définissez pas de commande build
# Ce projet n'a pas besoin de build
```

### Run (Start Command)
```bash
npm start
```
ou
```bash
node ws-server.js
```

## Configuration dans l'interface Coolify

Dans votre application Coolify, configurez :

1. **Install Command**: `npm install`
2. **Build Command**: (laissez vide)
3. **Start Command**: `npm start` ou `node ws-server.js`

## Variables d'environnement

Ajoutez dans Coolify :
- `PORT`: Port pour le serveur WebSocket (défaut: 3025)
  - Le serveur démarre sur le port 3025 par défaut
  - Vous pouvez définir `PORT=3025` dans Coolify pour être explicite

## Notes importantes

- Le serveur WebSocket écoute sur le port défini par la variable d'environnement `PORT` ou 3025 par défaut
- Les fichiers PHP nécessitent un serveur web séparé (Apache/Nginx) avec PHP-FPM
- La base de données MySQL doit être configurée dans `db.php` avec les bonnes variables d'environnement
- Assurez-vous que le dossier de travail (Working Directory) dans Coolify pointe vers le dossier contenant `package.json` et `ws-server.js`

