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
# Laissez vide
```

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

Si PHP n'est pas disponible dans votre conteneur Node.js, vous pouvez :

### Option 1 : Utiliser une image Docker personnalisée

Créez un `Dockerfile` :
```dockerfile
FROM node:18

# Installer PHP
RUN apt-get update && apt-get install -y php php-cli && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .

CMD ["npm", "start"]
```

### Option 2 : Installer PHP via les commandes de build

Dans Coolify, ajoutez dans **Build Command** :
```bash
apt-get update && apt-get install -y php php-cli && npm install
```

---

## Fonctionnalités

- ✅ **Fichiers PHP** : Exécutés automatiquement via PHP CLI
- ✅ **Fichiers statiques** : CSS, JS, images servis directement
- ✅ **WebSockets** : Disponibles sur `/ws`
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
