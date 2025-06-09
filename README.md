# LocalO'drive

LocalO'drive est une plateforme de vente en ligne de produits locaux, permettant aux producteurs de vendre leurs produits directement aux consommateurs.

## Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Composer
- Serveur web (Apache/Nginx)
- Extension PHP PDO
- Extension PHP OpenSSL

## Installation

1. Clonez le repository :
```bash
git clone [URL_DU_REPO]
cd localodrive
```

2. Installez les dépendances avec Composer :
```bash
composer install
```

3. Copiez le fichier `.env.exemple` en `.env` et configurez vos variables d'environnement :
```bash
cp .env.exemple .env
```

4. Configurez les variables dans le fichier `.env` :
```env
# Base de données
DB_HOST=localhost
DB_NAME=localodrive
DB_USER=root
DB_PASS=

# Configuration SMTP pour les emails
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=LocalO'drive

# Autres configurations
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/localodrive
```

5. Initialisez la base de données :
   - Accédez à `http://localhost/localodrive/database/install.php`
   - Une fois l'installation terminée, **supprimez le dossier `database`** pour des raisons de sécurité

## Structure du projet

```
localodrive/
├── assets/          # Fichiers statiques (CSS, JS, images)
├── includes/        # Fichiers PHP communs
├── public/          # Point d'entrée public
├── vendor/          # Dépendances Composer
├── .env            # Configuration
└── README.md       # Documentation
```

## Fonctionnalités actuelles

### Gestion des utilisateurs
- Inscription avec validation par email
- Connexion/Déconnexion
- Profils utilisateurs (client, producteur, admin)
- Gestion des informations personnelles

### Gestion des produits
- Catalogue de produits
- Catégorisation des produits
- Gestion des stocks
- Prix et unités de vente

### Panier d'achat
- Ajout/Suppression de produits
- Modification des quantités
- Calcul automatique des totaux

### Interface administrateur
- Tableau de bord
- Gestion des utilisateurs
- Gestion des produits
- Gestion des commandes

### Interface producteur
- Tableau de bord
- Gestion des produits
- Suivi des ventes
- Gestion des stocks

## Sécurité

- Protection contre les injections SQL
- Validation des entrées utilisateur
- Hachage des mots de passe
- Protection CSRF
- Sessions sécurisées
- Validation des emails

## Développement

Pour le développement local :
1. Assurez-vous que `APP_ENV=development` dans votre `.env`
2. Les erreurs seront affichées en mode développement
3. Utilisez XAMPP ou un serveur web local
4. Lancez les tests unitaires avec `composer test` (après avoir exécuté `composer install` pour installer PHPUnit)

## Production

Pour la mise en production :
1. Modifiez `APP_ENV=production` dans votre `.env`
2. Désactivez l'affichage des erreurs
3. Configurez correctement les paramètres SMTP
4. Assurez-vous que les permissions des fichiers sont correctes
5. Utilisez HTTPS

## Contribution

1. Fork le projet
2. Créez une branche pour votre fonctionnalité
3. Committez vos changements
4. Poussez vers la branche
5. Créez une Pull Request

## Licence

[Votre licence ici] 