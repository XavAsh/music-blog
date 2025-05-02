# Blog Musical avec Intégration Spotify

Application web permettant de gérer des articles musicaux et d'interagir avec l'API Spotify.

## Installation

```bash
# Cloner le projet
git clone https://github.com/XavAsh/music-blog.git
cd music-blog

# Installer les dépendances
composer install
npm install

# Configurer la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Charger les fixtures (données de test)
php bin/console doctrine:fixtures:load
```

## Comptes de Test

### Administrateur

- Email: admin@blog.com
- Mot de passe: adminpass

### Utilisateur

- Email: user@blog.com
- Mot de passe: userpass

## Routes Disponibles

### Routes Publiques

- `/` - Page d'accueil
- `/login` - Connexion
- `/articles` - Liste des articles
- `/articles/{id}` - Voir un article spécifique

### Routes Utilisateur (nécessite ROLE_USER)

- `/articles/new` - Créer un nouvel article
- `/articles/{id}/edit` - Modifier son propre article
- `/articles/{id}/pdf` - Générer un PDF d'un article
- `/spotify` - Recherche d'artistes Spotify
- `/spotify/artists/{id}` - Voir le profil d'un artiste
- `/spotify/artists/{id}/pdf` - Générer un PDF du profil d'artiste

### Routes Admin (nécessite ROLE_ADMIN)

- `/articles/{id}/delete` - Supprimer un article
- Peut modifier/supprimer tous les articles

## Fonctionnalités

- Gestion des articles (CRUD)
- Intégration Spotify
- Génération de PDF
- Système d'authentification
- Interface responsive (Bootstrap)

## API Routes

### Routes Publiques

- `GET /api/articles` - Liste des articles
- `GET /api/articles/{id}` - Détails d'un article

### Routes Protégées (JWT Auth requis)

- `POST /api/articles` - Créer un article
- `PUT /api/articles/{id}` - Modifier un article
- `DELETE /api/articles/{id}` - Supprimer un article
- `POST /api/spotify/artists/{id}/pdf` - Générer PDF d'artiste

## Variables d'Environnement Requises

```env
DATABASE_URL=
SPOTIFY_CLIENT_ID=
SPOTIFY_CLIENT_SECRET=
JWT_SECRET_KEY=
JWT_PUBLIC_KEY=
JWT_PASSPHRASE=
```
