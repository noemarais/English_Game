# Guide de configuration Coolify - English Game

## 📋 Étape par étape

### Étape 1 : Créer le service PHP (Point d'entrée principal)

1. Dans Coolify, créez un **nouveau service** de type **PHP**
2. Configurez le service PHP :
   - **Type** : PHP
   - **Version PHP** : 8.1 ou supérieur
   - **Source** : Votre dépôt Git ou dossier local
   - **Install Command** : (laissez vide)
   - **Build Command** : (laissez vide)
   - **Start Command** : (laissez vide - Coolify gère automatiquement PHP-FPM)
   - **Port** : Laissez Coolify gérer automatiquement (généralement 80/443)

3. Ce service servira :
   - ✅ Tous les fichiers `.php`
   - ✅ Tous les fichiers statiques (CSS, JS, images)
   - ✅ La page d'accueil (`/`)

---

### Étape 2 : Créer le service Node.js (WebSocket uniquement)

1. Dans Coolify, créez un **nouveau service** de type **Node.js**
2. Configurez le service Node.js :
   - **Type** : Node.js
   - **Source** : Même dépôt Git ou dossier local
   - **Install Command** : `npm install`
   - **Build Command** : (laissez vide)
   - **Start Command** : `npm start`
   - **Port** : Notez le port assigné par Coolify (ex: 3025)

3. Ce service servira :
   - ✅ Uniquement les WebSockets sur `/ws`

---

### Étape 3 : Configurer le reverse proxy

Dans les paramètres de votre **application principale** (service PHP) :

1. Allez dans **Settings** → **Reverse Proxy** ou **Routing**
2. Configurez les routes :

#### Option A : Configuration via interface Coolify

Si Coolify a une interface de routing :
- **Route par défaut** : Service PHP (toutes les routes sauf `/ws`)
- **Route `/ws`** : Service Node.js (port 3025 ou celui assigné)

#### Option B : Configuration via Nginx (si disponible)

Si vous pouvez éditer la configuration Nginx, ajoutez :

```nginx
# Route WebSocket vers Node.js
location /ws {
    proxy_pass http://nodejs-service:3025;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}

# Toutes les autres routes vers PHP
location / {
    try_files $uri $uri/ /index.php?$query_string;
    # ... configuration PHP standard de Coolify
}
```

---

### Étape 4 : Variables d'environnement

#### Service PHP
Configurez les variables de base de données dans `db.php` ou via les variables d'environnement de Coolify :
- `DB_HOST` : Adresse de votre base de données MySQL
- `DB_NAME` : Nom de la base de données
- `DB_USER` : Utilisateur de la base de données
- `DB_PASS` : Mot de passe de la base de données

#### Service Node.js
- `PORT` : Coolify définit automatiquement cette variable

---

### Étape 5 : Vérification

1. Accédez à votre URL principale → Devrait afficher `home.php` (page de connexion)
2. Vérifiez les WebSockets → Les connexions WebSocket vers `/ws` devraient fonctionner
3. Vérifiez la console du service Node.js → Devrait afficher "Serveur HTTP/WebSocket démarré sur le port X"

---

## 🔧 Alternative : Un seul service PHP avec proxy WebSocket

Si Coolify ne permet pas facilement deux services, vous pouvez :

1. Utiliser **uniquement le service PHP** comme point d'entrée
2. Dans le service PHP, configurez un reverse proxy pour `/ws` vers le service Node.js
3. Le service Node.js tourne en arrière-plan et gère uniquement les WebSockets

---

## ❓ Problèmes courants

### Le site affiche toujours "Service non disponible"
→ Le reverse proxy n'est pas configuré. Vérifiez que les routes PHP pointent vers le service PHP.

### Les WebSockets ne fonctionnent pas
→ Vérifiez que la route `/ws` est bien configurée pour pointer vers le service Node.js.

### Erreur 502 Bad Gateway
→ Vérifiez que le service Node.js est bien démarré et écoute sur le bon port.

---

## 📝 Résumé de la configuration

```
┌─────────────────────────────────────┐
│         Coolify Reverse Proxy        │
├─────────────────────────────────────┤
│  Route: /ws  →  Service Node.js     │
│  Route: /*   →  Service PHP         │
└─────────────────────────────────────┘
         │                    │
         ▼                    ▼
┌──────────────┐    ┌──────────────┐
│ Service PHP  │    │ Service      │
│ (PHP-FPM)    │    │ Node.js      │
│              │    │ (WebSocket)  │
│ - *.php      │    │ - /ws        │
│ - CSS/JS     │    │              │
│ - Images     │    │              │
└──────────────┘    └──────────────┘
```

