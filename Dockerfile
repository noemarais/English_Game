FROM node:18

# Installer PHP et les extensions nécessaires (SQLite au lieu de MySQL)
RUN apt-get update && \
    apt-get install -y \
    php \
    php-cli \
    php-sqlite3 \
    php-mbstring \
    php-xml \
    php-curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copier les fichiers de dépendances
COPY package*.json ./

# Installer les dépendances Node.js
RUN npm install

# Copier tout le code
COPY . .

# Exposer le port
EXPOSE 3025

# Démarrer le serveur
CMD ["npm", "start"]

