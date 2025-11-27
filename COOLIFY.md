# Configuration Coolify - English Game

## ✅ Solution tout-en-un automatique

Le serveur gère **automatiquement** :
- ✅ Fichiers PHP (exécutés via PHP CLI)
- ✅ Fichiers statiques (CSS, JS, images)
- ✅ WebSockets sur `/ws`

**Un seul service Node.js suffit !**

---

## Configuration dans Coolify

### Service Node.js (tout-en-un)

#### Installation (Install Command)
```bash
npm install
```

#### Build (Build Command)
```bash
npm install && npm run init-db
```

**Note** : L'initialisation de la base de données se fait automatiquement lors du build.

#### Run (Start Command)
```bash
npm start
```

#### Variables d'environnement
- `PORT`: Port pour le serveur (Coolify définit automatiquement cette variable, généralement 3025)
- `PHP_PATH`: (Optionnel) Chemin vers PHP si ce n'est pas dans le PATH. Par défaut: `php`

**Note** : Assurez-vous que PHP est installé dans le conteneur Coolify. Si ce n'est pas le cas, ajoutez PHP dans les dépendances ou utilisez une image Docker avec PHP.

---

## Installation de PHP dans Coolify

### ⚠️ IMPORTANT : PHP doit être installé

Si vous voyez l'erreur `spawn php ENOENT`, PHP n'est pas installé dans votre conteneur.

### Option 1 : Utiliser le Dockerfile (RECOMMANDÉ)

Un `Dockerfile` est fourni qui installe automatiquement PHP et Node.js.

Dans Coolify :
1. Allez dans les paramètres de votre service
2. **Type de build** : Sélectionnez "Dockerfile"
3. Le Dockerfile sera détecté automatiquement
4. Redéployez votre service

### Option 2 : Installer PHP via Build Command

Si vous n'utilisez pas le Dockerfile, dans Coolify :

1. Allez dans les paramètres de votre service
2. Dans **Build Command**, remplacez par :
```bash
apt-get update && apt-get install -y php php-cli php-mysql php-mbstring php-xml php-curl && npm install
```

**Important** : Assurez-vous que votre image de base supporte `apt-get` (images basées sur Debian/Ubuntu).

### Option 3 : Utiliser une image Docker personnalisée

Dans Coolify, vous pouvez spécifier une image Docker personnalisée qui contient déjà PHP et Node.js, par exemple :
- `php:8.1-cli` + installation de Node.js
- Ou une image personnalisée que vous créez

---

## Initialisation de la base de données

### Première utilisation

Avant de démarrer l'application, initialisez la base de données SQLite :

```bash
npm run init-db
```

Ou dans Coolify, ajoutez dans **Build Command** :
```bash
npm install && npm run init-db
```

La base de données sera créée dans `database.db` (fichier local).

## Fonctionnalités

- ✅ **Fichiers PHP** : Exécutés automatiquement via PHP CLI
- ✅ **Fichiers statiques** : CSS, JS, images servis directement
- ✅ **WebSockets** : Disponibles sur `/ws`
- ✅ **Base de données SQLite** : Fichier local `database.db`, pas besoin de MySQL !
- ✅ **Tout automatique** : Un seul service, tout fonctionne !

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
