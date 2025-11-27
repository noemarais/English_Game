# Configuration Coolify - English Game

## ✅ Solution tout-en-un automatique

Le serveur gère **automatiquement** :
- ✅ Fichiers PHP (exécutés via PHP CLI)
- ✅ Fichiers statiques (CSS, JS, images) - **PRIORITÉ**
- ✅ WebSockets sur `/ws`
- ✅ Base de données SQLite (fichier local `database.db`)

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
apt-get update && apt-get install -y php php-cli php-sqlite3 php-pdo-sqlite php-mbstring php-xml php-curl && npm install
```

#### Run (Start Command)
```bash
npm start
```

#### Variables d'environnement
- `PORT`: Port pour le serveur (Coolify définit automatiquement, généralement 3025)
- `PHP_PATH`: (Optionnel) Chemin vers PHP si ce n'est pas dans le PATH. Par défaut: `php`

---

## Installation de PHP dans Coolify

### Option 1 : Utiliser le Dockerfile (RECOMMANDÉ)

Un `Dockerfile` est fourni qui installe automatiquement PHP et Node.js.

Dans Coolify :
- **Type de build** : Dockerfile
- Le Dockerfile sera utilisé automatiquement

### Option 2 : Installer PHP via Build Command

Si vous n'utilisez pas le Dockerfile, utilisez la commande Build Command ci-dessus.

**Important** : Assurez-vous que votre image de base supporte `apt-get` (images basées sur Debian/Ubuntu).

---

## Initialisation de la base de données

La base de données SQLite sera **créée automatiquement** au premier accès à une page PHP.

Si vous voulez l'initialiser manuellement :
```bash
npm run init-db
```

Ou accédez à `http://votre-url/init-db.php` dans votre navigateur.

---

## Fonctionnalités

- ✅ **Fichiers PHP** : Exécutés automatiquement via PHP CLI
- ✅ **Fichiers statiques** : CSS, JS, images servis en PRIORITÉ (avant les PHP)
- ✅ **WebSockets** : Disponibles sur `/ws`
- ✅ **Base de données SQLite** : Fichier local `database.db`, pas besoin de MySQL !
- ✅ **Tout automatique** : Un seul service, tout fonctionne !

---

## Dépannage

### Le CSS ne charge pas
- Vérifiez que les fichiers CSS existent dans le répertoire
- Vérifiez les logs du serveur pour voir les requêtes
- Les fichiers CSS sont servis en priorité avant les PHP

### Erreur "could not find driver"
- Assurez-vous que `php-sqlite3` et `php-pdo-sqlite` sont installés
- Vérifiez la commande Build Command

### Erreur "PHP n'est pas installé"
- Vérifiez que PHP est installé dans le conteneur
- Utilisez le Dockerfile ou la commande Build Command complète
