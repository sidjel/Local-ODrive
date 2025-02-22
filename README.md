# LocalO'Drive

**Auteurs :**  
- Silvère MARTIN  
- Morgan GRANDY  

**Dépôt Git :** [https://git.freewebworld.fr/dimitri.f/projet_annuel_b2_localodrive](https://git.freewebworld.fr/dimitri.f/projet_annuel_b2_localodrive)

LocalO'Drive est un site web développé dans le cadre d’un projet annuel B2 et du TP API. Il exploite plusieurs APIs de data.gouv.fr ainsi que la géolocalisation HTML5 pour afficher, sur une carte interactive OpenStreetMap, des informations sur les entreprises locales du secteur alimentaire. Ces données sont croisées afin d’offrir une vue complète des établissements présents dans une zone donnée, avec notamment un cercle bleu transparent représentant le rayon de recherche.

---

## Table des matières

- [Résumé d'installation](#résumé-dinstallation)
- [Procédure détaillée](#procédure-détaillée)
    - [1. Contexte](#1-contexte)
        - [1.1 Sujet](#11-sujet)
        - [1.2 Livrable](#12-livrable)
    - [2. Prérequis](#2-prérequis)
    - [3. Installation](#3-installation)
    - [4. Tests et Dépannage](#4-tests-et-dépannage)
    - [5. Informations complémentaires](#5-informations-complémentaires)

---

## Résumé d'installation

Pour cloner et exécuter LocalO'Drive en local :

1. **Cloner le dépôt**  
     Ouvrez un terminal et exécutez :
     ```bash
     git clone https://git.freewebworld.fr/dimitri.f/projet_annuel_b2_localodrive.git
     cd localodrive
     ```

2. **Installer les dépendances PHP**  
     ```bash
     composer install
     ```

3. **Installer les dépendances front-end (Bootstrap)**  
     ```bash
     npm install
     ```

4. **Configurer l’environnement**  
    Ouvrez le fichier .env et coller la clé API fournie par les auteurs : API_KEY_SIRENE= VOTRE_CLE_API

5. **Configurer un serveur local (ex. : XAMPP)**  
     - Copiez le projet dans `C:\xampp\htdocs\projet_annuel_b2_localodrive`.
     - Démarrez Apache via le panneau de contrôle de XAMPP.

6. **Accéder au site**  
     - **TP API :** [http://localhost/projet_annuel_b2_localodrive/public/TP_API-Silvere-Morgan-LocaloDrive.php](http://localhost/projet_annuel_b2_localodrive/public/TP_API-Silvere-Morgan-LocaloDrive.php)
     - **Projet Annuel :** [http://localhost/projet_annuel_b2_localodrive/public/index.php](http://localhost/projet_annuel_b2_localodrive/public/index.php)

---

## Procédure détaillée

### 1. Contexte

#### 1.1 Sujet

LocalO'Drive répond aux exigences du TP API en intégrant plusieurs services :

- **API Géolocalisation HTML5**  
    Obtention des coordonnées de l’utilisateur pour centrer la carte et afficher un marqueur.
- **API Adresse (BAN)**  
    Géocodage (recherche et reverse) pour récupérer les coordonnées et adresses.  
    [En savoir plus](https://www.data.gouv.fr/fr/dataservices/api-adresse-base-adresse-nationale-ban/)
- **API IP (api64.ipify.org)**  
    Récupération de l’adresse IP publique de l’utilisateur.  
    [Accéder à l’API](https://api64.ipify.org/)
- **GeoZones**  
    Informations sur les régions, départements, et centres-villes.  
    [Voir la documentation](https://www.data.gouv.fr/en/datasets/geozones/)
- **API Sirene (Open Data)**  
    Recherche d’entreprises locales filtrées par secteur et rayon.  
    [Plus d’informations](https://www.data.gouv.fr/fr/dataservices/api-sirene-open-data/)

Ces données sont croisées pour afficher les entreprises sur une carte OpenStreetMap avec des marqueurs interactifs et un cercle bleu transparent correspondant au rayon sélectionné (100 m à 10 km).

#### 1.2 Livrable

Ce dépôt contient :

- `public/TP_API-Silvere-Morgan-LocaloDrive.php` : Page principale du TP API et partie intégrante du projet annuel.
- `public/index.php` : Page supplémentaire spécifique au projet annuel.
- Les dépendances (`.env`, `vendor/`, `node_modules/`) doivent être générées localement.  
    L’URL du dépot GIT a été communiquée au formateur via Discord privé (ArrobeHugues).

---

### 2. Prérequis

Assurez-vous d’avoir installé :

- **Git** : Pour cloner le dépôt.  
    [Télécharger Git](https://git-scm.com/)
- **PHP** : Version ≥ 7.4 avec les extensions *curl*, *mbstring*, *openssl*.  
    [Télécharger PHP](https://www.php.net/)
- **Composer** : Pour gérer les dépendances PHP.  
    [Télécharger Composer](https://getcomposer.org/)
- **Node.js/npm** : Pour Bootstrap et autres packages front-end.  
    [Télécharger Node.js](https://nodejs.org/)
- **XAMPP** : Serveur local recommandé (ou alternative compatible PHP).  
    [Télécharger XAMPP](https://www.apachefriends.org/index.html)

---

### 3. Installation

#### 3.1 Clonage du dépôt

Exécutez la commande suivante dans un terminal :
```bash
git clone https://git.freewebworld.fr/dimitri.f/projet_annuel_b2_localodrive.git
cd projet_annuel_b2_localodrive
```

#### 3.2 Installation des dépendances PHP

```bash
composer install
```
Cette commande génère le dossier `vendor/` avec [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv).

#### 3.3 Installation des dépendances front-end

```bash
npm install
```
Cela installe Bootstrap et génère le dossier `node_modules/`.

#### 3.4 Configuration de l’environnement

Copiez le fichier `.env.example` en `.env` et éditez-le pour ajouter la clé API Sirene :
```bash
cp .env.example .env
```
Par exemple, ajoutez :
```
API_KEY_SIRENE=clé_fournie
```

#### 3.5 Configuration du serveur local

- **Avec XAMPP (recommandé) :**  
    - Copiez le dossier `projet_annuel_b2_localodrive` dans `C:\xampp\htdocs\`.
    - Démarrez Apache via le panneau de contrôle XAMPP.
    - Accédez aux URL :
        - **TP API :** [http://localhost/projet_annuel_b2_localodrive/public/TP_API-Silvere-Morgan-LocaloDrive.php](http://localhost/projet_annuel_b2_localodrive/public/TP_API-Silvere-Morgan-LocaloDrive.php)
        - **Projet Annuel :** [http://localhost/projet_annuel_b2_localodrive/public/index.php](http://localhost/projet_annuel_b2_localodrive/public/index.php)


### 4. Tests et Dépannage

#### 4.1 Tests

- Ouvrez `TP_API-Silvere-Morgan-LocaloDrive.php` dans votre navigateur.
- Autorisez la géolocalisation pour permettre l’affichage de votre position.
- Effectuez une recherche (par exemple : "Grenoble", "Commerce alimentaire", "100 m") :
    - La carte affiche votre position avec un marqueur.
    - Un cercle bleu transparent correspondant au rayon sélectionné (100 m) doit apparaître après la recherche.
    - La liste des entreprises locales s'affiche à gauche et est synchronisée avec les marqueurs de la carte.

#### 4.2 Dépannage

- **Composer non installé :**  
    Téléchargez Composer et relancez `composer install`.
- **Assets non chargés :**  
    Vérifiez la présence du dossier `node_modules/` et envisagez d’utiliser XAMPP pour éviter les problèmes de chemins relatifs.
- **Erreur API Sirene :**  
    Vérifiez que la clé dans le fichier `.env` est correcte.
- **Carte non affichée :**  
    Assurez-vous que vous êtes connecté à Internet, car Leaflet nécessite un accès en ligne.

---

### 5. Informations complémentaires

#### 5.1 Fonctionnalités principales

- Géolocalisation initiale avec popup d'inforamtion.
- Recherche d’entreprises par ville, secteur, sous-secteur (Code APE/NAF) et rayon (100 m à 10 km).
- Affichage des régions et départements.
- Carte interactive intégrant un cercle bleu pour le rayon de recherche.

#### 5.2 Technologies utilisées

- **Frontend :**  
    HTML5, CSS (Bootstrap), JavaScript, Leaflet, Proj4js.
- **Backend :**  
    PHP.
- **APIs intégrées :**
    - API Adresse (BAN)
    - GeoZones
    - API Sirene (Open Data)
    - API IP (api64.ipify.org)
    - Géolocalisation HTML5.
- **Dépendances :**
    - PHP : [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) via Composer.
    - JavaScript : Bootstrap via npm.

#### 5.3 Flux des appels aux APIs

Voici l’ordre chronologique des appels dans `TP_API-Silvere-Morgan-LocaloDrive.php` :

1. **API Géolocalisation HTML5**  
     - Appel au chargement initial via `watchPosition` pour récupérer les coordonnées (latitude, longitude).
2. **API Adresse (BAN) - Reverse**  
     - Appelé après géolocalisation (via `Promise.all`) pour récupérer l’adresse (ville, rue, numéro).
3. **API IP (api64.ipify.org)**  
     - Appelé simultanément à l’API Adresse pour récupérer l’adresse IP publique.
4. **GeoZones**  
     - Appelé après récupération de l’adresse ou lors d’une recherche (`recupererZone`) pour obtenir la région, le département et les coordonnées du centre-ville.
5. **API Adresse (BAN) - Search**  
     - Appelé lors d’une recherche manuelle (`rechercherAdresse`) pour obtenir coordonnées et code postal.
6. **API Sirene (Open Data)**  
     - Appelé après la recherche (`recupererEntreprises`) pour obtenir les informations des entreprises (nom, adresse, SIREN, etc.).

#### 5.4 Remarques

- Le dépôt ne versionne pas les fichiers/dossiers générés localement : `.env`, `vendor/`, `node_modules/`.
- L'utilisation de HTTPS est recommandée pour une géolocalisation optimale.
- Testé sur PHP 8.2 avec XAMPP sous Windows.
