# LocalO'drive

Plateforme de livraison de produits locaux en Auvergne-Rhône-Alpes.

## Prérequis

- PHP 7.4 ou supérieur
- Composer
- Node.js et npm
- Serveur web (Apache, Nginx, etc.)
- Base de données MySQL

## Installation

1. **Cloner le repository**
```bash
git clone https://git.freewebworld.fr/dimitri.f/projet_annuel_b2_localodrive.git
cd projet_annuel_b2_localodrive
```

2. **Installer les dépendances PHP avec Composer**
```bash
composer install
```

3. **Installer les dépendances JavaScript avec npm**
```bash
npm install
```

4. **Construire les assets**
```bash
npm run build
```

5. **Configuration de l'environnement**
- Copier le fichier `.env.example` en `.env`
- Configurer les variables d'environnement :
  - `API_KEY_SIRENE` : Clé API pour l'API Sirene
  - Autres variables selon vos besoins

## Structure du projet

```
├── assets/           # Assets compilés (CSS, JS, fonts)
├── css/             # Fichiers CSS source
├── includes/        # Fichiers PHP inclus
├── js/              # Fichiers JavaScript source
├── public/          # Point d'entrée public
├── vendor/          # Dépendances PHP
└── node_modules/    # Dépendances JavaScript
```

## Dépendances principales

### PHP
- `vlucas/phpdotenv` : Gestion des variables d'environnement

### JavaScript
- `bootstrap` : Framework CSS
- `@fortawesome/fontawesome-free` : Icônes
- `@fontsource/poppins` : Police Poppins
- `proj4` : Conversion de coordonnées géographiques
- `leaflet` : Bibliothèque de cartographie
- `leaflet.markercluster` : Clustering de marqueurs pour Leaflet

## Développement

1. **Lancer le serveur de développement**
```bash
php -S localhost:8000 -t public
```

2. **Reconstruire les assets après modification**
```bash
npm run build
```

## Production

1. **Optimiser les assets**
```bash
npm run build
```

2. **Configurer le serveur web**
- Pointer le document root vers le dossier `public/`
- Configurer les règles de réécriture pour le routage

## API utilisées

- API Sirene : Données des entreprises
- API Adresse : Géocodage et recherche d'adresses
- API Geo : Informations géographiques

## Licence

Ce projet est sous licence [MIT](LICENSE). 