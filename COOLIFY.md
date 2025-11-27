# Configuration Coolify - English Game

## ⚠️ PROBLÈME : Téléchargement de fichiers au lieu d'affichage

Si vous voyez un fichier "téléchargement" se télécharger au lieu d'afficher votre site, c'est parce que **Coolify essaie de servir les fichiers PHP via le serveur Node.js**.

**Solution** : Vous devez créer **DEUX services** dans Coolify.

---

## Configuration dans Coolify

### Service 1 : Node.js (WebSocket UNIQUEMENT)

#### Installation (Install Command)
```bash
npm install
```

#### Build (Build Command)
```bash
# Laissez vide
```

#### Run (Start Command)
```bash
npm start
```

#### Variables d'environnement
- `PORT`: Port pour le serveur (Coolify définit automatiquement cette variable, généralement 3025)

**Ce service gère UNIQUEMENT les WebSockets sur `/ws`**

---

### Service 2 : PHP (fichiers PHP + fichiers statiques)

#### Type de service
- **Type**: PHP
- **Version PHP**: 8.1 ou supérieur

#### Installation (Install Command)
```bash
# Laissez vide
```

#### Build (Build Command)
```bash
# Laissez vide
```

#### Run (Start Command)
```bash
# Laissez vide (Coolify gère automatiquement PHP-FPM)
```

**Ce service gère TOUS les fichiers PHP et les fichiers statiques (CSS, JS, images)**

---

## Configuration du reverse proxy dans Coolify

Dans les paramètres de votre application Coolify, configurez le reverse proxy pour :

1. **Routes `/ws`** → Service Node.js (port 3025 ou celui défini)
2. **Toutes les autres routes** → Service PHP

### Configuration recommandée

- **Point d'entrée principal** : Service PHP
- **Route spéciale `/ws`** : Service Node.js

Cela garantit que :
- Les fichiers PHP sont exécutés par PHP-FPM
- Les fichiers statiques sont servis par Nginx/Apache
- Les WebSockets sont gérés par Node.js

---

## Notes importantes

- Le serveur WebSocket écoute sur `/ws` (pas besoin de spécifier le port dans l'URL)
- Les fichiers PHP doivent être servis par un serveur PHP (Apache/Nginx avec PHP-FPM)
- La base de données MySQL doit être configurée dans `db.php`
- Le nouveau `server.js` remplace `ws-server.js` et gère à la fois HTTP et WebSocket
