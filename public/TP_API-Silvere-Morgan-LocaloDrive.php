<?php
/*
 * TP_API-Silvere-Morgan-LocaloDrive.php
 * Version 20.8 : Ajout rayon de recherche sur 2 klm et suppression du rayon 10 klm
 */

require_once __DIR__ . "/../vendor/autoload.php";
// Cette ligne charge automatiquement toutes les dépendances PHP installées via Composer, comme phpdotenv.

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
// Je crée une instance de Dotenv pour lire les variables d’environnement depuis le fichier .env situé à la racine.

$dotenv->load();
// Cette commande charge effectivement les variables du fichier .env dans l’environnement PHP.

$API_KEY_SIRENE = $_ENV['API_KEY_SIRENE'];
// Je récupère la clé API Sirene depuis les variables d’environnement pour l’utiliser plus tard dans les requêtes.
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <!-- J’indique que le document utilise l’encodage UTF-8 pour supporter les caractères spéciaux français. -->
  <title>Localo'Drive - Recherche et Carte</title>
  <!-- Le titre de la page qui apparaît dans l’onglet du navigateur. -->
  <link rel="stylesheet" href="../node_modules/bootstrap/dist/css/bootstrap.min.css">
  <!-- J’inclus le CSS de Bootstrap pour avoir un style moderne et responsive. -->
  <link rel="stylesheet" href="../css/style.css">
  <!-- Mon fichier CSS personnalisé pour ajuster le design à mes besoins. -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <!-- J’ajoute le CSS de Leaflet pour que la carte interactive soit bien stylisée. -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.7.5/proj4.js"></script>
  <!-- J’inclus Proj4js pour convertir les coordonnées Lambert93 (utilisées par l’API Sirene) en WGS84 (pour la carte). -->
  <script>
    // Je définis la projection Lambert93 pour que Proj4js sache comment convertir les coordonnées.
    proj4.defs("EPSG:2154", "+proj=lcc +lat_1=44 +lat_2=49 +lat_0=46.5 +lon_0=3 +x_0=700000 +y_0=6600000 +ellps=GRS80 +units=m +no_defs");
  </script>
</head>

<body>

  <script>
    // Je passe la clé API Sirene de PHP à JavaScript de manière sécurisée avec htmlspecialchars pour éviter les injections XSS.
    const API_KEY_SIRENE = "<?php echo htmlspecialchars($API_KEY_SIRENE, ENT_QUOTES, 'UTF-8'); ?>";
  </script>

  <!-- Conteneur principal de la page avec une marge en haut -->
  <div class="container mt-4">
    <div class="card text-center mb-4">
      <!-- Une carte Bootstrap pour afficher le titre et la description du projet -->
      <div class="card-body">
        <h1 class="card-title">
          Local<span class="text-vert-pomme">O'</span>Drive
          <!-- Le titre avec une partie en vert définie dans mon CSS -->
        </h1>
        <p class="card-text text-secondary">
          Faciliter l'accès aux produits locaux en connectant producteurs et consommateurs
          <!-- Une petite phrase pour expliquer l’objectif du site -->
        </p>
      </div>
    </div>
    <!-- Une ligne Bootstrap avec deux colonnes pour séparer le formulaire et la carte -->
    <div class="row">
      <!-- Colonne gauche pour le formulaire et les résultats -->
      <div class="col-md-4" id="colonne-resultats">
        <!-- Mon formulaire de recherche, stylé avec Bootstrap -->
        <form id="formulaire-adresse" class="formulaire-gauche mb-4">
          <input type="text" id="champ-ville" class="form-control mb-2" placeholder="Ville">
          <!-- Champ pour entrer la ville, obligatoire pour la recherche -->
          <input type="text" id="champ-adresse" class="form-control mb-2" placeholder="Adresse (facultatif)">
          <!-- Champ facultatif pour préciser une adresse -->
          <input type="text" id="champ-nom-entreprise" class="form-control mb-2" placeholder="Nom de l'entreprise (France entière)">
          <!-- Champ pour chercher une entreprise par nom dans toute la France -->
          <select id="rayon-select" class="form-select mb-2">
            <option value="">-- Rayon de recherche --</option>
            <option value="0.1">100 m</option>
            <option value="0.5">500 m</option>
            <option value="1">1 km</option>
            <option value="2">2 km</option>
            <option value="3">3 km</option>
            <option value="5">5 km</option>
          </select>
          <!-- Menu déroulant pour choisir le rayon de recherche autour de la position -->
          <select id="Secteur" class="form-select mb-2">
            <option value="">-- Secteur --</option>
            <option value="Cultures et productions végétales">Cultures et productions végétales</option>
            <option value="Élevage et productions animales">Élevage et productions animales</option>
            <option value="Pêche et aquaculture">Pêche et aquaculture</option>
            <option value="Boulangerie-Pâtisserie">Boulangerie-Pâtisserie</option>
            <option value="Viandes et Charcuterie">Viandes et Charcuterie</option>
            <option value="Produits laitiers">Produits laitiers</option>
            <option value="Boissons">Boissons</option>
            <option value="Épicerie spécialisée">Épicerie spécialisée</option>
            <option value="Restauration">Restauration</option>
            <option value="Autres transformations alimentaires">Autres transformations alimentaires</option>
          </select>
          <!-- Menu déroulant pour choisir le secteur d’activité des entreprises -->
          <select id="Sous-Secteur" class="form-select mb-2">
            <option value="">-- Sous-Secteur --</option>
          </select>
          <!-- Menu déroulant pour les sous-secteurs, rempli dynamiquement selon le secteur choisi -->
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="filtre-actifs">
            <label class="form-check-label" for="filtre-actifs">Filtrer uniquement sur les établissements en activité</label>
          </div>
          <!-- Case à cocher pour limiter les résultats aux entreprises actives -->
          <button type="submit" class="btn btn-success">Rechercher</button>
          <!-- Bouton pour lancer la recherche avec le style Bootstrap -->
        </form>
        <div id="resultats-api"></div>
        <!-- Div où les résultats de la recherche seront affichés -->
      </div>
      <!-- Colonne droite pour la carte interactive -->
      <div class="col-md-8" id="colonne-carte">
        <div id="geo-messages" class="mb-1"></div>
        <!-- Zone pour afficher les messages liés à la géolocalisation -->
        <div id="map" style="height:500px;"></div>
        <!-- Conteneur pour la carte Leaflet avec une hauteur fixe -->
      </div>
    </div>
  </div>

  <!-- Inclusion des scripts JavaScript nécessaires -->
  <script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Script Bootstrap pour les fonctionnalités interactives comme les dropdowns -->
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <!-- Script Leaflet pour gérer la carte interactive -->
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // J’attends que le DOM soit chargé avant d’exécuter mon code JavaScript.

      /* ----- Initialisation des variables globales et réinitialisation des champs ----- */
      // Variable pour stocker la position de l'utilisateur, utilisée pour le filtrage par rayon
      let userPosition = null;
      // Variable pour stocker le marqueur du centre-ville afin d'éviter les doublons
      let marqueurCentreVille = null;
      // Variable pour stocker le cercle dynamique du rayon sélectionné après recherche
      let searchCircle = null;
      // Récupération des éléments du DOM correspondant aux champs du formulaire dans la colonne de gauche
      const champVille = document.querySelector('#colonne-resultats #champ-ville');
      const champAdresse = document.querySelector('#colonne-resultats #champ-adresse');
      const rayonSelect = document.querySelector('#colonne-resultats #rayon-select');
      const categoriePrincipaleSelect = document.querySelector('#colonne-resultats #Secteur');
      const sousCategorieSelect = document.querySelector('#colonne-resultats #Sous-Secteur');
      const filtreActifs = document.querySelector('#colonne-resultats #filtre-actifs');

      // Réinitialisation des valeurs des champs lors du chargement de la page
      champVille.value = "";
      champAdresse.value = "";
      rayonSelect.selectedIndex = 0;
      categoriePrincipaleSelect.selectedIndex = 0;
      sousCategorieSelect.innerHTML = '<option value="">-- Sous-Secteur --</option>';
      // Je remets tous les champs à zéro pour éviter des valeurs résiduelles.

      /* ----- Définition du mapping pour le secteur d'alimentation avec les codes NAF/APE ----- */
      const mappingAlimentation = {
        "Cultures et productions végétales": [{
            code: "01.11Z",
            label: "Code NAF/APE : 01.11Z - Culture de céréales (sauf riz)"
          },
          {
            code: "01.12Z",
            label: "Code NAF/APE : 01.12Z - Culture du riz"
          },
          {
            code: "01.13Z",
            label: "Code NAF/APE : 01.13Z - Culture de légumes, melons, racines et tubercules"
          },
          {
            code: "01.19Z",
            label: "Code NAF/APE : 01.19Z - Autres cultures non permanentes"
          },
          {
            code: "01.21Z",
            label: "Code NAF/APE : 01.21Z - Culture de la vigne"
          },
          {
            code: "01.22Z",
            label: "Code NAF/APE : 01.22Z - Culture de fruits tropicaux et subtropicaux"
          },
          {
            code: "01.23Z",
            label: "Code NAF/APE : 01.23Z - Culture d'agrumes"
          },
          {
            code: "01.24Z",
            label: "Code NAF/APE : 01.24Z - Culture de fruits à pépins et à noyau"
          },
          {
            code: "01.25Z",
            label: "Code NAF/APE : 01.25Z - Culture d'autres fruits d'arbres ou d'arbustes et de fruits à coque"
          },
          {
            code: "01.26Z",
            label: "Code NAF/APE : 01.26Z - Culture de fruits oléagineux"
          },
          {
            code: "01.27Z",
            label: "Code NAF/APE : 01.27Z - Culture de plantes à boissons"
          },
          {
            code: "01.28Z",
            label: "Code NAF/APE : 01.28Z - Culture de plantes à épices, aromatiques, médicinales et pharmaceutiques"
          },
          {
            code: "01.29Z",
            label: "Code NAF/APE : 01.29Z - Autres cultures permanentes"
          },
          {
            code: "01.30Z",
            label: "Code NAF/APE : 01.30Z - Reproduction de plantes"
          },
          {
            code: "01.50Z",
            label: "Code NAF/APE : 01.50Z - Culture et élevage associés"
          }, // Partiellement ici
          {
            code: "01.61Z",
            label: "Code NAF/APE : 01.61Z - Activités de soutien aux cultures"
          },
          {
            code: "01.63Z",
            label: "Code NAF/APE : 01.63Z - Traitement primaire des récoltes"
          },
          {
            code: "01.64Z",
            label: "Code NAF/APE : 01.64Z - Traitement des semences"
          }
        ],
        "Élevage et productions animales": [{
            code: "01.41Z",
            label: "Code NAF/APE : 01.41Z - Élevage de vaches laitières"
          },
          {
            code: "01.42Z",
            label: "Code NAF/APE : 01.42Z - Élevage d'autres bovins et de buffles"
          },
          {
            code: "01.43Z",
            label: "Code NAF/APE : 01.43Z - Élevage de chevaux et d'autres équidés"
          },
          {
            code: "01.44Z",
            label: "Code NAF/APE : 01.44Z - Élevage de chameaux et d'autres camélidés"
          },
          {
            code: "01.45Z",
            label: "Code NAF/APE : 01.45Z - Élevage d'ovins et de caprins"
          },
          {
            code: "01.46Z",
            label: "Code NAF/APE : 01.46Z - Élevage de porcins"
          },
          {
            code: "01.47Z",
            label: "Code NAF/APE : 01.47Z - Élevage de volailles"
          },
          {
            code: "01.49Z",
            label: "Code NAF/APE : 01.49Z - Élevage d'autres animaux"
          },
          {
            code: "01.50Z",
            label: "Code NAF/APE : 01.50Z - Culture et élevage associés"
          }, // Partiellement ici aussi
          {
            code: "01.62Z",
            label: "Code NAF/APE : 01.62Z - Activités de soutien à la production animale"
          }
        ],
        "Pêche et aquaculture": [{
            code: "03.11Z",
            label: "Code NAF/APE : 03.11Z - Pêche en mer"
          },
          {
            code: "03.12Z",
            label: "Code NAF/APE : 03.12Z - Pêche en eau douce"
          },
          {
            code: "03.21Z",
            label: "Code NAF/APE : 03.21Z - Aquaculture en mer"
          },
          {
            code: "03.22Z",
            label: "Code NAF/APE : 03.22Z - Aquaculture en eau douce"
          }
        ],
        "Boulangerie-Pâtisserie": [{
            code: "10.71A",
            label: "Code NAF/APE : 10.71A - Fabrication industrielle de pain et de pâtisserie fraîche"
          },
          {
            code: "10.71B",
            label: "Code NAF/APE : 10.71B - Cuisson de produits de boulangerie"
          },
          {
            code: "10.71C",
            label: "Code NAF/APE : 10.71C - Boulangerie et boulangerie-pâtisserie"
          },
          {
            code: "10.71D",
            label: "Code NAF/APE : 10.71D - Pâtisserie"
          },
          {
            code: "10.72Z",
            label: "Code NAF/APE : 10.72Z - Fabrication de biscuits, biscottes et pâtisseries de conservation"
          },
          {
            code: "47.24Z",
            label: "Code NAF/APE : 47.24Z - Commerce de détail de pain, pâtisserie et confiserie en magasin spécialisé"
          }
        ],
        "Viandes et Charcuterie": [{
            code: "10.11Z",
            label: "Code NAF/APE : 10.11Z - Transformation et conservation de la viande de boucherie"
          },
          {
            code: "10.12Z",
            label: "Code NAF/APE : 10.12Z - Transformation et conservation de la viande de volaille"
          },
          {
            code: "10.13A",
            label: "Code NAF/APE : 10.13A - Préparation industrielle de produits à base de viande"
          },
          {
            code: "10.13B",
            label: "Code NAF/APE : 10.13B - Charcuterie"
          },
          {
            code: "46.32A",
            label: "Code NAF/APE : 46.32A - Commerce de gros de viandes de boucherie"
          },
          {
            code: "46.32B",
            label: "Code NAF/APE : 46.32B - Commerce de gros de produits à base de viande"
          },
          {
            code: "47.22Z",
            label: "Code NAF/APE : 47.22Z - Commerce de détail de viandes et de produits à base de viande en magasin spécialisé"
          }
        ],
        "Produits laitiers": [{
            code: "10.51A",
            label: "Code NAF/APE : 10.51A - Fabrication de lait liquide et de produits frais"
          },
          {
            code: "10.51B",
            label: "Code NAF/APE : 10.51B - Fabrication de beurre"
          },
          {
            code: "10.51C",
            label: "Code NAF/APE : 10.51C - Fabrication de fromage"
          },
          {
            code: "10.51D",
            label: "Code NAF/APE : 10.51D - Fabrication d'autres produits laitiers"
          },
          {
            code: "10.52Z",
            label: "Code NAF/APE : 10.52Z - Fabrication de glaces et sorbets"
          },
          {
            code: "46.33Z",
            label: "Code NAF/APE : 46.33Z - Commerce de gros de produits laitiers, œufs, huiles et matières grasses comestibles"
          }
        ],
        "Boissons": [{
            code: "11.01Z",
            label: "Code NAF/APE : 11.01Z - Production de boissons alcooliques distillées"
          },
          {
            code: "11.02A",
            label: "Code NAF/APE : 11.02A - Fabrication de vins effervescents"
          },
          {
            code: "11.02B",
            label: "Code NAF/APE : 11.02B - Vinification"
          },
          {
            code: "11.03Z",
            label: "Code NAF/APE : 11.03Z - Fabrication de cidre et de vins de fruits"
          },
          {
            code: "11.04Z",
            label: "Code NAF/APE : 11.04Z - Production d'autres boissons fermentées non distillées"
          },
          {
            code: "11.05Z",
            label: "Code NAF/APE : 11.05Z - Fabrication de bière"
          },
          {
            code: "11.06Z",
            label: "Code NAF/APE : 11.06Z - Production de malt"
          },
          {
            code: "11.07A",
            label: "Code NAF/APE : 11.07A - Industrie des eaux de table"
          },
          {
            code: "11.07B",
            label: "Code NAF/APE : 11.07B - Production de boissons rafraîchissantes"
          },
          {
            code: "46.34Z",
            label: "Code NAF/APE : 46.34Z - Commerce de gros de boissons"
          },
          {
            code: "47.25Z",
            label: "Code NAF/APE : 47.25Z - Commerce de détail de boissons en magasin spécialisé"
          }
        ],
        "Épicerie spécialisée": [{
            code: "46.31Z",
            label: "Code NAF/APE : 46.31Z - Commerce de gros de fruits et légumes"
          },
          {
            code: "46.33Z",
            label: "Code NAF/APE : 46.33Z - Commerce de gros de produits laitiers, œufs, huiles et matières grasses comestibles"
          }, // Partiellement ici
          {
            code: "46.36Z",
            label: "Code NAF/APE : 46.36Z - Commerce de gros de sucre, chocolat et confiserie"
          },
          {
            code: "46.37Z",
            label: "Code NAF/APE : 46.37Z - Commerce de gros de café, thé, cacao et épices"
          },
          {
            code: "46.38A",
            label: "Code NAF/APE : 46.38A - Commerce de gros de poissons, crustacés et mollusques"
          },
          {
            code: "46.38B",
            label: "Code NAF/APE : 46.38B - Commerce de gros alimentaire spécialisé divers"
          },
          {
            code: "46.39A",
            label: "Code NAF/APE : 46.39A - Commerce de gros de produits surgelés"
          },
          {
            code: "46.39B",
            label: "Code NAF/APE : 46.39B - Autre commerce de gros alimentaire"
          },
          {
            code: "47.11A",
            label: "Code NAF/APE : 47.11A - Commerce de détail de produits surgelés"
          },
          {
            code: "47.11B",
            label: "Code NAF/APE : 47.11B - Commerce d'alimentation générale"
          },
          {
            code: "47.11C",
            label: "Code NAF/APE : 47.11C - Supérettes"
          },
          {
            code: "47.11D",
            label: "Code NAF/APE : 47.11D - Supermarchés"
          },
          {
            code: "47.11E",
            label: "Code NAF/APE : 47.11E - Magasins multi-commerces"
          },
          {
            code: "47.11F",
            label: "Code NAF/APE : 47.11F - Hypermarchés"
          },
          {
            code: "47.19A",
            label: "Code NAF/APE : 47.19A - Grands magasins"
          },
          {
            code: "47.19B",
            label: "Code NAF/APE : 47.19B - Autres commerces de détail en magasin non spécialisé"
          },
          {
            code: "47.21Z",
            label: "Code NAF/APE : 47.21Z - Commerce de détail de fruits et légumes en magasin spécialisé"
          },
          {
            code: "47.23Z",
            label: "Code NAF/APE : 47.23Z - Commerce de détail de poissons, crustacés et mollusques en magasin spécialisé"
          },
          {
            code: "47.26Z",
            label: "Code NAF/APE : 47.26Z - Commerce de détail de produits à base de tabac en magasin spécialisé"
          },
          {
            code: "47.29Z",
            label: "Code NAF/APE : 47.29Z - Autres commerces de détail alimentaires en magasin spécialisé"
          },
          {
            code: "47.30Z",
            label: "Code NAF/APE : 47.30Z - Commerce de détail de carburants en magasin spécialisé"
          },
          {
            code: "47.81Z",
            label: "Code NAF/APE : 47.81Z - Commerce de détail alimentaire sur éventaires et marchés"
          }
        ],
        "Restauration": [{
            code: "56.10A",
            label: "Code NAF/APE : 56.10A - Restauration traditionnelle"
          },
          {
            code: "56.10B",
            label: "Code NAF/APE : 56.10B - Cafétérias et autres libres-services"
          },
          {
            code: "56.10C",
            label: "Code NAF/APE : 56.10C - Restauration de type rapide"
          },
          {
            code: "56.21Z",
            label: "Code NAF/APE : 56.21Z - Services des traiteurs"
          },
          {
            code: "56.29A",
            label: "Code NAF/APE : 56.29A - Restauration collective sous contrat"
          },
          {
            code: "56.29B",
            label: "Code NAF/APE : 56.29B - Autres services de restauration n.c.a."
          },
          {
            code: "56.30Z",
            label: "Code NAF/APE : 56.30Z - Débits de boissons"
          }
        ],
        "Autres transformations alimentaires": [{
            code: "10.20Z",
            label: "Code NAF/APE : 10.20Z - Transformation et conservation de poisson, crustacés et mollusques"
          },
          {
            code: "10.31Z",
            label: "Code NAF/APE : 10.31Z - Transformation et conservation de pommes de terre"
          },
          {
            code: "10.32Z",
            label: "Code NAF/APE : 10.32Z - Préparation de jus de fruits et légumes"
          },
          {
            code: "10.39A",
            label: "Code NAF/APE : 10.39A - Autre transformation et conservation de légumes"
          },
          {
            code: "10.39B",
            label: "Code NAF/APE : 10.39B - Transformation et conservation de fruits"
          },
          {
            code: "10.41A",
            label: "Code NAF/APE : 10.41A - Fabrication d'huiles et graisses brutes"
          },
          {
            code: "10.41B",
            label: "Code NAF/APE : 10.41B - Fabrication d'huiles et graisses raffinées"
          },
          {
            code: "10.42Z",
            label: "Code NAF/APE : 10.42Z - Fabrication de margarine et graisses comestibles similaires"
          },
          {
            code: "10.61A",
            label: "Code NAF/APE : 10.61A - Meunerie"
          },
          {
            code: "10.61B",
            label: "Code NAF/APE : 10.61B - Autres activités du travail des grains"
          },
          {
            code: "10.62Z",
            label: "Code NAF/APE : 10.62Z - Fabrication de produits amylacés"
          },
          {
            code: "10.73Z",
            label: "Code NAF/APE : 10.73Z - Fabrication de pâtes alimentaires"
          },
          {
            code: "10.81Z",
            label: "Code NAF/APE : 10.81Z - Fabrication de sucre"
          },
          {
            code: "10.82Z",
            label: "Code NAF/APE : 10.82Z - Fabrication de cacao, chocolat et de produits de confiserie"
          },
          {
            code: "10.83Z",
            label: "Code NAF/APE : 10.83Z - Transformation du thé et du café"
          },
          {
            code: "10.84Z",
            label: "Code NAF/APE : 10.84Z - Fabrication de condiments et assaisonnements"
          },
          {
            code: "10.85Z",
            label: "Code NAF/APE : 10.85Z - Fabrication de plats préparés"
          },
          {
            code: "10.86Z",
            label: "Code NAF/APE : 10.86Z - Fabrication d'aliments homogénéisés et diététiques"
          },
          {
            code: "10.89Z",
            label: "Code NAF/APE : 10.89Z - Fabrication d'autres produits alimentaires n.c.a."
          },
          {
            code: "10.91Z",
            label: "Code NAF/APE : 10.91Z - Fabrication d'aliments pour animaux de ferme"
          },
          {
            code: "10.92Z",
            label: "Code NAF/APE : 10.92Z - Fabrication d'aliments pour animaux de compagnie"
          }
        ]
      };

      /* ----- Mise à jour dynamique du menu des Sous-Secteur en fonction du Secteur sélectionné ----- */
      categoriePrincipaleSelect.addEventListener('change', function() {
        // Quand l’utilisateur choisit un secteur, je mets à jour les sous-secteurs.
        let categorie = this.value;
        sousCategorieSelect.innerHTML = '<option value="">-- Sous-Secteur --</option>';
        // Je vide d’abord le menu déroulant des sous-secteurs.
        if (mappingAlimentation[categorie] && mappingAlimentation[categorie].length > 0) {
          // Si la catégorie existe dans mon mapping et a des sous-secteurs...
          mappingAlimentation[categorie].forEach(function(item) {
            // Je parcours chaque sous-secteur pour l’ajouter au menu.
            let option = document.createElement('option');
            option.value = item.code;
            option.textContent = item.label;
            sousCategorieSelect.appendChild(option);
          });
        } else {
          console.warn("Aucun Sous-Secteur trouvée pour le Secteur:", categorie);
          // Si rien n’est trouvé, je logue un avertissement dans la console.
        }
      });

      categoriePrincipaleSelect.dispatchEvent(new Event('change'));
      // Je déclenche l’événement "change" au chargement pour remplir les sous-secteurs si un secteur est présélectionné.

      /* ----- Initialisation de la carte ----- */
      var map = L.map('map').setView([46.603354, 1.888334], 6);
      // Je crée la carte Leaflet centrée sur la France avec un zoom initial de 6.
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
      }).addTo(map);
      // J’ajoute les tuiles OpenStreetMap comme fond de carte avec une attribution obligatoire.
      window.markersLayer = L.layerGroup().addTo(map);
      // Je crée un groupe de marqueurs pour gérer facilement ceux ajoutés à la carte.

      /* ----- Fonction de reverse géocodage pour récupérer la ville et l'adresse à partir des coordonnées ----- */
      function reverseGeocode(lon, lat, callback) {
        // Cette fonction récupère une adresse à partir de coordonnées GPS via l’API Adresse.
        var url = `https://api-adresse.data.gouv.fr/reverse/?lon=${lon}&lat=${lat}`;
        fetch(url)
          .then(response => response.json())
          .then(data => {
            console.log("Réponse reverse geocode :", data);
            // Je logue la réponse pour vérifier ce que l’API renvoie.
            if (data.features && data.features.length > 0) {
              let prop = data.features[0].properties;
              let city = prop.city || prop.label || "Ville inconnue";
              let address = prop.housenumber ? `${prop.housenumber} ${prop.street || ''}`.trim() : prop.street || "Adresse inconnue";
              // J’extrais la ville et l’adresse, avec des valeurs par défaut si elles manquent.
              callback(city, address);
            } else {
              callback("Ville inconnue", "Adresse inconnue");
              // Si rien n’est trouvé, je renvoie des valeurs par défaut.
            }
          })
          .catch(error => {
            console.error("Erreur lors du reverse géocodage :", error);
            callback("Ville inconnue", "Adresse inconnue");
            // En cas d’erreur, je logue et renvoie des valeurs par défaut.
          });
      }

      /* ----- Fonction pour récupérer l'adresse IP de l'utilisateur ----- */
      function getUserIP(callback) {
        // Cette fonction récupère l’IP publique via une API externe.
        fetch("https://api64.ipify.org?format=json")
          .then(response => response.json())
          .then(data => callback(data.ip))
          // Je renvoie l’IP récupérée via le callback.
          .catch(error => {
            console.error("Erreur lors de la récupération de l'adresse IP :", error);
            callback("IP inconnue");
            // En cas d’erreur, je logue et renvoie une valeur par défaut.
          });
      }

      /* ----- Fonction pour récupérer les informations du navigateur ----- */
      function getBrowserInfo() {
        // Je détecte le navigateur et sa version à partir de l’user-agent.
        const ua = navigator.userAgent;
        let browserName = "Navigateur inconnu";
        let browserVersion = "Version inconnue";

        if (ua.includes("Chrome")) {
          browserName = "Google Chrome";
          browserVersion = ua.match(/Chrome\/([\d.]+)/)?.[1] || "Inconnue";
        } else if (ua.includes("Firefox")) {
          browserName = "Mozilla Firefox";
          browserVersion = ua.match(/Firefox\/([\d.]+)/)?.[1] || "Inconnue";
        } else if (ua.includes("Safari") && !ua.includes("Chrome")) {
          browserName = "Apple Safari";
          browserVersion = ua.match(/Version\/([\d.]+)/)?.[1] || "Inconnue";
        } else if (ua.includes("MSIE") || ua.includes("Trident")) {
          browserName = "Internet Explorer";
          browserVersion = ua.match(/(MSIE |rv:)([\d.]+)/)?.[2] || "Inconnue";
        } else if (ua.includes("Edge") || ua.includes("Edg")) {
          browserName = "Microsoft Edge";
          browserVersion = ua.match(/(Edge|Edg)\/([\d.]+)/)?.[2] || "Inconnue";
        } else {
          browserName = ua.split(" ")[0];
          browserVersion = "Inconnue";
        }
        return {
          browserName,
          browserVersion
        };
        // Je retourne un objet avec le nom et la version du navigateur.
      }

      /* ----- Définition de l'icône personnalisée pour la position de l'utilisateur (représentée par "Moi") ----- */
      const userIcon = L.divIcon({
        className: 'user-div-icon',
        html: `<div><span>Moi</span></div>`,
        iconSize: [30, 30],
        iconAnchor: [15, 15],
        popupAnchor: [0, -15]
      });
      // J’ai créé une icône ronde avec "Moi" pour marquer ma position sur la carte.

      // Variable globale pour stocker le marqueur de l'utilisateur sur la carte
      let userMarker = null;

      /* ----- Vérification de la disponibilité de la géolocalisation et récupération de la position de l'utilisateur ----- */
      if (navigator.geolocation) {
        // Si le navigateur supporte la géolocalisation, je vais chercher ma position.
        function mettreAJourMarqueurUtilisateur(lat, lon, contenuPopup = "Localisation en cours...") {
          // Cette fonction met à jour ou crée mon marqueur sur la carte.
          if (userMarker) {
            userMarker.setLatLng([lat, lon]);
            userMarker.setPopupContent(contenuPopup);
          } else {
            userMarker = L.marker([lat, lon], {
                icon: userIcon
              })
              .addTo(map)
              .bindPopup(contenuPopup, {
                autoClose: false
              })
              .openPopup();
          }
          map.setView([lat, lon], 13);

          if (contenuPopup === "Localisation en cours...") {
            // Si c’est la première mise à jour, je complète la popup avec plus d’infos.
            Promise.all([
              fetch(`https://api-adresse.data.gouv.fr/reverse/?lon=${lon}&lat=${lat}`).then(response => response.json()),
              fetch("https://api64.ipify.org?format=json").then(response => response.json())
            ]).then(([geoData, ipData]) => {
              let ville = geoData.features?.[0]?.properties.city || "Ville inconnue";
              let adresse = geoData.features?.[0]?.properties.housenumber ? `${geoData.features[0].properties.housenumber} ${geoData.features[0].properties.street || ''}`.trim() : geoData.features?.[0]?.properties.street || "Adresse inconnue";
              const ip = ipData.ip || "IP inconnue";
              const {
                browserName,
                browserVersion
              } = getBrowserInfo();

              const popupContent = `
                    <b>Vous êtes ici</b><br>
                    <br>
                    🗺️ <b>Adresse :</b> ${adresse}, ${ville}<br>
                    🌐 <b>Navigateur :</b> ${browserName} ${browserVersion}<br>
                    🖥️ <b>Adresse IP :</b> ${ip}<br>
                    📍<b>Latitude :</b> ${lat.toFixed(4)}<br>
                    📍<b>Longitude :</b> ${lon.toFixed(4)}
                `;
              userMarker.setPopupContent(popupContent);

              if (champVille.value.trim() === "") champVille.value = ville;
              if (champAdresse.value.trim() === "") champAdresse.value = adresse;

              if (isChrome) {
                geoMessages.innerHTML = `<p>Chrome : Localisation via IP et Wi-Fi (Google Location Services) en ${tempsReponse.toFixed(2)}s</p>`;
              } else if (isFirefox) {
                geoMessages.innerHTML = `<p>Firefox : Localisation via ${sourceLocalisation} en ${tempsReponse.toFixed(2)}s</p>`;
              } else if (isEdge) {
                geoMessages.innerHTML = `<p>Edge : Localisation via IP et Wi-Fi (Google Location Services) en ${tempsReponse.toFixed(2)}s</p>`;
              } else if (isSafari) {
                geoMessages.innerHTML = `<p>Safari : Localisation via GPS (Apple Location Services) en ${tempsReponse.toFixed(2)}s</p>`;
              } else {
                geoMessages.innerHTML = `<p>Localisation via services navigateur en ${tempsReponse.toFixed(2)}s</p>`;
              }

              recupererZone(ville, document.getElementById('resultats-api'));
            }).catch(error => {
              console.error("Erreur lors de la mise à jour de la popup :", error);
              const {
                browserName,
                browserVersion
              } = getBrowserInfo();
              const popupContent = `
                    <b>Vous êtes ici</b><br>
                    🗺️ <b>Adresse :</b> Données indisponibles<br>
                    🌐 <b>Navigateur :</b> ${browserName} ${browserVersion}<br>
                    🖥️ <b>Adresse IP :</b> Non disponible<br>
                    📍 <b>Latitude :</b> ${lat.toFixed(4)}<br>
                    📍 <b>Longitude :</b> ${lon.toFixed(4)}
                `;
              userMarker.setPopupContent(popupContent);

              geoMessages.innerHTML = `<p>Localisation trouvée, mais détails indisponibles (${tempsReponse.toFixed(2)}s)</p>`;
            });
          }
        }

        let geoMessages = document.getElementById('geo-messages');
        if (!geoMessages) {
          console.warn("Élément #geo-messages non trouvé, création dynamique...");
          geoMessages = document.createElement('div');
          geoMessages.id = 'geo-messages';
          geoMessages.className = 'mb-1';
          document.getElementById('colonne-carte').insertBefore(geoMessages, document.getElementById('map'));
        }
        geoMessages.innerHTML = "<p>Recherche de votre position...</p>";

        const userAgent = navigator.userAgent.toLowerCase();
        const isChrome = userAgent.includes("chrome");
        const isFirefox = userAgent.includes("firefox");
        const isEdge = userAgent.includes("edg");
        const isSafari = userAgent.includes("safari") && !isChrome;

        let debutRecherche = performance.now(); // Début du chronomètre
        let tempsReponse = 0; // Temps en secondes
        let sourceLocalisation = "IP/Wi-Fi"; // Par défaut pour Firefox en local sans HTTPS

        // Je tente d’abord une localisation rapide avec getCurrentPosition
        navigator.geolocation.getCurrentPosition(
          function(position) {
            tempsReponse = (performance.now() - debutRecherche) / 1000; // Temps écoulé en secondes
            sourceLocalisation = "IP/Wi-Fi"; // Firefox en local sans HTTPS
            userPosition = {
              lat: position.coords.latitude,
              lon: position.coords.longitude
            };
            mettreAJourMarqueurUtilisateur(userPosition.lat, userPosition.lon);
          },
          function(error) {
            // Si la géolocalisation échoue ou est trop lente, je passe par une API IP
            console.error("Erreur de géolocalisation : " + error.message);
            fetch("http://ip-api.com/json")
              .then(response => response.json())
              .then(data => {
                if (data.status === "success") {
                  tempsReponse = (performance.now() - debutRecherche) / 1000;
                  sourceLocalisation = "API IP (ip-api.com)";
                  userPosition = {
                    lat: data.lat,
                    lon: data.lon
                  };
                  mettreAJourMarqueurUtilisateur(data.lat, data.lon);
                } else {
                  geoMessages.innerHTML = "<p>Échec de la localisation, position approximative indisponible</p>";
                }
              })
              .catch(() => {
                geoMessages.innerHTML = "<p>Échec de la localisation, vérifiez votre connexion</p>";
              });
          }, {
            enableHighAccuracy: false, // Désactivé en local pour Firefox, car HTTPS est absent
            timeout: 10000, // Timeout à 10s pour donner une chance
            maximumAge: 60000 // Accepte une position mise en cache jusqu’à 1 minute
          }
        );
      }

      /* ----- Gestion de la soumission du formulaire de recherche ----- */
      document.getElementById('formulaire-adresse').addEventListener('submit', function(e) {
        // Quand l’utilisateur clique sur "Rechercher", je lance cette fonction.
        e.preventDefault();
        // J’empêche le rechargement de la page par défaut du formulaire.
        if (userMarker && userMarker.getPopup()) {
          userMarker.closePopup();
        }
        // Je ferme la popup du marqueur utilisateur si elle existe et est ouverte.
        let villeRecherche = champVille.value.trim();
        let adresseRecherche = champAdresse.value.trim();
        let categoriePrincipale = categoriePrincipaleSelect.value;

        if (villeRecherche === "") {
          alert("Veuillez entrer une ville");
          return;
        }
        if (categoriePrincipale === "") {
          alert("Veuillez sélectionner un Secteur");
          return;
        }
        // Je vérifie que la ville et le secteur sont remplis, sinon j’arrête.

        let query = (adresseRecherche === "" || adresseRecherche === "Non renseigné") ? villeRecherche : adresseRecherche + " " + villeRecherche;
        // Je construis la requête : ville seule si pas d’adresse, sinon adresse + ville.
        rechercherAdresse(query, villeRecherche);
        // Je lance la recherche avec ces paramètres.
      });
      /* ----- Fonction d'affichage des résultats d'adresse et lancement de la recherche d'entreprises ----- */
      function afficherResultats(data, ville) {
        // Cette fonction affiche les résultats de l’API Adresse et lance la recherche d’entreprises.
        var conteneur = document.getElementById('resultats-api');
        conteneur.innerHTML = '';
        // Je vide la zone des résultats avant d’ajouter du nouveau contenu.
        window.markersLayer.clearLayers();
        // Je supprime tous les marqueurs précédents de la carte.
        let features = data.features;
        if ((champAdresse.value.trim() === "" || champAdresse.value.trim() === "Non renseigné") && ville !== "") {
          features = [features[0]];
        }
        // Si pas d’adresse précisée, je prends juste le premier résultat.

        if (features && features.length > 0) {
          features.forEach(async function(feature) {
            let propriete = feature.properties;
            let lat = feature.geometry.coordinates[1];
            let lng = feature.geometry.coordinates[0];
            let citycode = propriete.citycode;
            let postcode = propriete.postcode;
            // J’extrais les infos utiles de chaque résultat (coords, code postal, etc.).

            const zoneData = await recupererZone(propriete.city, conteneur);
            // J’attends les infos de région et département pour cette ville.

            let blocB = `
          <div class="bloc-b">
            <p><strong>Région :</strong> ${zoneData.region}</p>
            <p><strong>Département :</strong> ${zoneData.departement}</p>
          </div> 
        `;
            // Je construis le "bloc B" avec la région et le département.

            let divResultat = document.createElement('div');
            divResultat.className = 'resultat p-3 mb-3 border rounded';
            divResultat.dataset.adresse = propriete.label;
            divResultat.innerHTML = blocB;
            conteneur.appendChild(divResultat);
            // Je crée une div pour chaque résultat et l’ajoute au conteneur.

            recupererEntreprises(postcode, divResultat, ville);
            // Je cherche les entreprises dans ce code postal.
          });
        } else {
          conteneur.innerHTML = '<p>Aucun résultat trouvé.</p>';
          // Si pas de résultats, j’affiche un message.
        }
      }

      /* ----- Fonction de recherche via l'API Base Adresse ----- */
      function rechercherAdresse(query, ville) {
        // Cette fonction appelle l’API Adresse pour géocoder la recherche.
        console.log("Recherche Base Adresse pour : ", query);
        var url = 'https://api-adresse.data.gouv.fr/search/?q=' + encodeURIComponent(query);

        fetch(url)
          .then(response => response.json())
          .then(data => {
            console.log("Résultats Base Adresse : ", data);
            afficherResultats(data, ville);
            // J’affiche les résultats et lance la recherche d’entreprises.

            if (userPosition && rayonSelect.value) {
              if (searchCircle) {
                map.removeLayer(searchCircle);
              }
              // Je supprime l’ancien cercle si il existe.
              const rayonEnKm = parseFloat(rayonSelect.value);
              searchCircle = L.circle([userPosition.lat, userPosition.lon], {
                radius: rayonEnKm * 1000,
                color: 'blue',
                fillColor: 'blue',
                fillOpacity: 0.1,
                weight: 2
              }).addTo(map);
              // J’ajoute un nouveau cercle bleu autour de ma position avec le rayon choisi.
            } else if (searchCircle) {
              map.removeLayer(searchCircle);
              searchCircle = null;
              // Si pas de rayon sélectionné, je supprime le cercle.
            }
          })
          .catch(error => {
            console.error("Erreur lors de la récupération des données :", error);
            // Je logue une erreur si l’appel à l’API échoue.
          });
      }

      /* ----- Fonction pour récupérer les informations de zone via l'API Geo ----- */
      function recupererZone(ville, conteneur) {
        // Cette fonction récupère les infos de région et département via l’API Geo.
        var urlGeo = `https://geo.api.gouv.fr/communes?nom=${encodeURIComponent(ville)}&fields=nom,centre,departement,region&format=json`;
        return fetch(urlGeo)
          .then(response => response.json())
          .then(data => {
            if (data && data.length > 0) {
              let departement = data[0].departement ? data[0].departement.nom : "Non renseigné";
              let region = data[0].region ? data[0].region.nom : "Non renseigné";
              afficherZone(data[0], conteneur);
              // J’affiche les infos dans le conteneur.
              return {
                departement,
                region
              };
              // Je retourne ces données pour les utiliser ailleurs.
            } else {
              console.warn("Aucune donnée trouvée pour la ville :", ville);
              return {
                departement: "Non renseigné",
                region: "Non renseigné"
              };
              // Si rien n’est trouvé, je renvoie des valeurs par défaut.
            }
          })
          .catch(error => {
            console.error("Erreur lors de la récupération des données de la zone :", error);
            return {
              departement: "Non renseigné",
              region: "Non renseigné"
            };
            // En cas d’erreur, je logue et renvoie des valeurs par défaut.
          });
      }

      /* ----- Fonction d'affichage des informations de zone dans les éléments prévus ----- */
      function afficherZone(donnees, conteneur) {
        // Cette fonction affiche les infos de zone (région, département, centre-ville) dans le "bloc B".
        let placeholderZone = conteneur.querySelector('.zone-info-placeholder');
        let placeholderCentreVille = conteneur.querySelector('.centre-ville-placeholder');

        let departement = donnees.departement ? donnees.departement.nom : "Non renseigné";
        let region = donnees.region ? donnees.region.nom : "Non renseigné";
        let latitudeCentre = donnees.centre ? donnees.centre.coordinates[1] : "Non renseigné";
        let longitudeCentre = donnees.centre ? donnees.centre.coordinates[0] : "Non renseigné";

        if (placeholderZone) {
          placeholderZone.innerHTML = `
        <p><strong>Département :</strong> ${departement}</p>
        <p><strong>Région :</strong> ${region}</p>
      `;
        }
        // Si un emplacement pour la zone existe, je l’utilise (mais ici, je n’en ai pas).

        if (placeholderCentreVille) {
          placeholderCentreVille.innerHTML = `
        <p><strong>Géolocalisation Centre-ville :</strong></p>
        <p><strong>Latitude :</strong> ${latitudeCentre}</p>
        <p><strong>Longitude :</strong> ${longitudeCentre}</p>
      `;
        }
        // Pareil pour le centre-ville, pas utilisé ici mais prévu.

        if (marqueurCentreVille) {
          map.removeLayer(marqueurCentreVille);
        }
        // Je supprime l’ancien marqueur du centre-ville s’il existe.

        if (latitudeCentre !== "Non renseigné" && longitudeCentre !== "Non renseigné") {
          var centreVilleIcon = L.icon({
            iconUrl: '../img/icone_centre_ville.png',
            iconSize: [30, 30],
            iconAnchor: [15, 15],
            popupAnchor: [0, -15]
          });
          marqueurCentreVille = L.marker([latitudeCentre, longitudeCentre], {
              icon: centreVilleIcon
            })
            .addTo(map)
            .bindPopup(`<b>Centre-ville de ${donnees.nom}</b><br>📍 Latitude : ${latitudeCentre}<br>📍 Longitude : ${longitudeCentre}`);
          // J’ajoute un marqueur pour le centre-ville avec une icône personnalisée.
        }
      }

      /* ----- Fonction pour récupérer les entreprises via l'API Sirene ----- */
      function recupererEntreprises(postcode, conteneur, ville) {
        // Cette fonction appelle l’API Sirene pour trouver les entreprises locales.
        let themeDetail = sousCategorieSelect.value;
        let categoriePrincipale = categoriePrincipaleSelect.value;
        let q = "";
        if (ville.toUpperCase() === "GRENOBLE") {
          q = '(codePostalEtablissement:"38000" OR codePostalEtablissement:"38100")';
        } else {
          q = 'codePostalEtablissement:"' + postcode + '"';
        }
        // Je gère un cas spécial pour Grenoble avec deux codes postaux.

        if (ville && ville.trim() !== '') {
          q += ' AND libelleCommuneEtablissement:"' + ville.toUpperCase() + '"';
        }
        // J’ajoute un filtre sur la commune pour affiner les résultats.

        if (themeDetail) {
          q += ' AND activitePrincipaleUniteLegale:"' + themeDetail + '"';
        } else if (categoriePrincipale !== "") {
          let codes = mappingAlimentation[categoriePrincipale].map(item => item.code);
          q += ' AND (' + codes.map(code => 'activitePrincipaleUniteLegale:"' + code + '"').join(' OR ') + ')';
        }
        // Je construis le filtre selon le sous-secteur ou le secteur choisi.

        console.log("Filtre Sirene:", q);
        let urlSirene = 'https://api.insee.fr/api-sirene/3.11/siret?q=' + encodeURIComponent(q) + '&nombre=300';
        fetch(urlSirene, {
            headers: {
              'X-INSEE-Api-Key-Integration': API_KEY_SIRENE,
              'Accept': 'application/json'
            }
          })
          .then(response => response.json())
          .then(data => {
            if (filtreActifs.checked) {
              data.etablissements = data.etablissements.filter(function(etablissement) {
                let statut = etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0 ?
                  etablissement.periodesEtablissement[0].etatAdministratifEtablissement :
                  "";
                return statut === "A";
              });
            }
            // Si la case "actifs" est cochée, je filtre pour garder seulement les entreprises actives.

            if (userPosition && rayonSelect.value) {
              let rayon = parseFloat(rayonSelect.value);
              data.etablissements = data.etablissements.filter(function(etablissement) {
                let adresseObj = etablissement.adresseEtablissement;
                if (adresseObj && adresseObj.coordonneeLambertAbscisseEtablissement && adresseObj.coordonneeLambertOrdonneeEtablissement) {
                  let x = parseFloat(adresseObj.coordonneeLambertAbscisseEtablissement);
                  let y = parseFloat(adresseObj.coordonneeLambertOrdonneeEtablissement);
                  let coords = proj4("EPSG:2154", "EPSG:4326", [x, y]);
                  let d = haversineDistance(userPosition.lat, userPosition.lon, coords[1], coords[0]);
                  return d <= rayon;
                }
                return false;
              });
            }
            // Je filtre les entreprises dans le rayon choisi autour de ma position.

            console.log("Résultats Sirene:", data);
            afficherEntreprises(data, conteneur);
            ajouterMarqueursEntreprises(data);
            // J’affiche les entreprises dans le "bloc B" et sur la carte.
          })
          .catch(error => {
            console.error("Erreur lors de la récupération des données Sirene :", error);
            // Je logue une erreur si l’API Sirene échoue.
          });
      }

      /* ----- Fonction pour afficher les entreprises dans le bloc résultats ----- */
      function afficherEntreprises(data, conteneur) {
        // Cette fonction affiche les entreprises dans la colonne de gauche.
        let divEntreprises = conteneur.querySelector('.entreprises');
        if (!divEntreprises) {
          divEntreprises = document.createElement('div');
          divEntreprises.className = 'entreprises mt-3 p-3 border-top';
          conteneur.appendChild(divEntreprises);
        }
        // Je crée la div pour les entreprises si elle n’existe pas encore.

        if (data && data.etablissements && data.etablissements.length > 0) {
          let html = '<p><strong>Entreprises locales :</strong></p>';
          let themeGeneralText = (categoriePrincipaleSelect.selectedIndex > 0) ?
            categoriePrincipaleSelect.selectedOptions[0].text :
            "Non précisé";
          let themeDetailText = (sousCategorieSelect.value !== "") ?
            sousCategorieSelect.selectedOptions[0].text :
            "Non précisé";
          // Je prépare le texte pour le secteur et sous-secteur affichés.

          data.etablissements.forEach(function(etablissement) {
            let ul = etablissement.uniteLegale || {};
            let commune = (etablissement.adresseEtablissement && etablissement.adresseEtablissement.libelleCommuneEtablissement) || "Non renseigné";
            let adresseObj = etablissement.adresseEtablissement || {};
            let numero = adresseObj.numeroVoieEtablissement || '';
            let typeVoie = adresseObj.typeVoieEtablissement || '';
            let libelleVoie = adresseObj.libelleVoieEtablissement || '';
            let codePostal = adresseObj.codePostalEtablissement || '';
            let adresseComplete = (numero || typeVoie || libelleVoie) ?
              ((numero + " " + typeVoie + " " + libelleVoie).trim() + ", " + codePostal + " " + commune) :
              "Non renseigné";
            // Je construis l’adresse complète avec les infos disponibles.

            let periode = (etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0) ?
              etablissement.periodesEtablissement[0] :
              {};
            let dateDebut = periode.dateDebut || "Non renseigné";
            let dateFin = periode.dateFin || "...";
            let statutCode = (etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0) ?
              etablissement.periodesEtablissement[0].etatAdministratifEtablissement :
              '';

            console.log("Entreprise:", ul.denominationUniteLegale || ul.nomUniteLegale || "Nom inconnu", "StatutCode:", statutCode);
            // Je logue le statut pour vérifier ce que l’API renvoie.

            let statutClass = "";
            let statutText = "Non précisé";
            if (statutCode === 'A') {
              statutClass = "statut-actif";
              statutText = "En Activité";
              console.log("Statut 'A' détecté pour", ul.denominationUniteLegale || "Nom inconnu");
            } else if (statutCode === 'F') {
              statutClass = "statut-ferme";
              statutText = "Fermé";
              console.log("Statut 'F' détecté pour", ul.denominationUniteLegale || "Nom inconnu");
            } else {
              console.log("Statut non reconnu (ni 'A' ni 'F') pour", ul.denominationUniteLegale || "Nom inconnu");
            }
            console.log("Entreprise :", ul.denominationUniteLegale || "Nom inconnu", "StatutCode:", statutCode, "Classe CSS appliquée :", statutClass);
            // Je définis la classe CSS et le texte selon le statut.

            let siren = etablissement.siren || 'N/A';
            let siret = etablissement.siret || 'N/A';
            let dateCreationUniteLegale = ul.dateCreationUniteLegale || "Non renseigné";

            html += `<div class="card mb-2">
                    <div class="card-body">
                        <h5 class="card-title text-primary" style="font-weight:bold;">🏢${ul.denominationUniteLegale || ul.nomUniteLegale || 'Nom non disponible'}</h5>
                        <p class="card-text">
                            <strong>Commune :</strong> ${commune}<br>
                            <strong>Adresse :</strong> ${adresseComplete}<br>
                            <strong>Secteurs :</strong> ${themeGeneralText}<br>
                            <strong>Sous-Secteur :</strong> ${themeDetailText}<br>
                            <br>
                            <strong>Statut :</strong> <strong class="${statutClass}">${statutText}</strong><br>
                            <strong>Date de création :</strong> ${dateCreationUniteLegale}<br>
                            <strong>Intervalle de validité des informations :</strong> ${dateDebut} à ${dateFin}<br>
                            <strong>SIREN :</strong> ${siren}<br>
                            <strong>SIRET :</strong> ${siret}<br>
                            <strong>Code NAF/APE :</strong> ${ul.activitePrincipaleUniteLegale || "Non renseigné"}<br>
                        </p>
                    </div>
                </div>`;
            // Je construis une carte Bootstrap pour chaque entreprise avec toutes ses infos.
          });

          console.log("HTML généré pour bloc B:", html);
          divEntreprises.innerHTML = html;
          // J’injecte le HTML dans la div des entreprises.

          setTimeout(() => {
            document.querySelectorAll(".statut-actif").forEach(el => el.style.color = "green");
            document.querySelectorAll(".statut-ferme").forEach(el => el.style.color = "red");
          }, 500);
          // Petit délai pour s’assurer que les styles CSS s’appliquent bien au statut.
        } else {
          divEntreprises.innerHTML = '<p>Aucune entreprise locale trouvée.</p>';
          // Si pas d’entreprises, j’affiche un message simple.
        }
      }

      /* ----- Fonction pour ajouter les marqueurs des entreprises sur la carte ----- */
      function ajouterMarqueursEntreprises(data) {
        // Cette fonction ajoute les marqueurs des entreprises sur la carte.
        if (data && data.etablissements && data.etablissements.length > 0) {
          data.etablissements.forEach(function(etablissement) {
            let adresseObj = etablissement.adresseEtablissement;

            if (adresseObj && adresseObj.coordonneeLambertAbscisseEtablissement && adresseObj.coordonneeLambertOrdonneeEtablissement) {
              let x = parseFloat(adresseObj.coordonneeLambertAbscisseEtablissement);
              let y = parseFloat(adresseObj.coordonneeLambertOrdonneeEtablissement);
              let coords = proj4("EPSG:2154", "EPSG:4326", [x, y]);
              // Je convertis les coordonnées Lambert93 en WGS84 pour la carte.

              coords[1] += (Math.random() - 0.5) * 0.0005;
              coords[0] += (Math.random() - 0.5) * 0.0005;
              // J’ajoute un petit décalage aléatoire pour éviter que les marqueurs se superposent.

              console.log(`Conversion Lambert93 -> WGS84 : ${x}, ${y} → ${coords[1]}, ${coords[0]}`);
              ajouterMarqueur(coords[1], coords[0], etablissement);
              // J’ajoute le marqueur avec les coords converties.
            } else {
              const adresseComplete = `${adresseObj.numeroVoieEtablissement || ''} ${adresseObj.typeVoieEtablissement || ''} ${adresseObj.libelleVoieEtablissement || ''}, ${adresseObj.codePostalEtablissement || ''} ${adresseObj.libelleCommuneEtablissement || ''}`.trim();
              if (adresseComplete !== ",") {
                obtenirCoordonneesParAdresse(adresseComplete, (lat, lon) => {
                  if (lat && lon) {
                    console.log(`Ajout du marqueur via API Adresse : ${lat}, ${lon}`);
                    ajouterMarqueur(lat, lon, etablissement);
                    // Si pas de coords Lambert, je géocode l’adresse et ajoute le marqueur.
                  } else {
                    console.warn(`Impossible d'afficher l'entreprise : ${adresseComplete} (aucune coordonnée trouvée)`);
                  }
                });
              } else {
                console.warn("Impossible d'afficher l'entreprise : adresse incomplète");
              }
            }
          });
        }
      }

      /* ----- Fonction pour géocoder une adresse via l’API Adresse ----- */
      function obtenirCoordonneesParAdresse(adresse, callback) {
        // Cette fonction récupère les coords GPS d’une adresse quand Lambert93 manque.
        const url = `https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(adresse)}&limit=1`;

        fetch(url)
          .then(response => response.json())
          .then(data => {
            if (data.features.length > 0) {
              const lon = data.features[0].geometry.coordinates[0];
              const lat = data.features[0].geometry.coordinates[1];
              console.log(`Coordonnées récupérées pour ${adresse} : ${lat}, ${lon}`);
              callback(lat, lon);
              // Je renvoie les coords trouvées via le callback.
            } else {
              console.warn(`Aucune coordonnée trouvée pour : ${adresse}`);
              callback(null, null);
              // Si rien n’est trouvé, je renvoie null.
            }
          })
          .catch(error => {
            console.error("Erreur API Adresse :", error);
            callback(null, null);
            // En cas d’erreur, je logue et renvoie null.
          });
      }

      /* ----- Fonction pour ajouter un marqueur sur la carte ----- */
      function ajouterMarqueur(lat, lon, etablissement) {
        // Cette fonction crée un marqueur avec une popup pour chaque entreprise.
        let ul = etablissement.uniteLegale || {};
        let activitePrincipale = ul.activitePrincipaleUniteLegale || "Non renseigné";
        let categorieEntreprise = ul.categorieEntreprise || "Non renseigné";
        let dateCreationUniteLegale = ul.dateCreationUniteLegale || "Non renseigné";
        let periode = (etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0) ?
          etablissement.periodesEtablissement[0] :
          {};
        let dateDebut = periode.dateDebut || "Non renseigné";
        let dateFin = periode.dateFin || "...";
        let siren = etablissement.siren || 'N/A';
        let siret = etablissement.siret || 'N/A';
        let commune = etablissement.adresseEtablissement.libelleCommuneEtablissement || 'N/A';
        let numero = etablissement.adresseEtablissement.numeroVoieEtablissement || '';
        let typeVoie = etablissement.adresseEtablissement.typeVoieEtablissement || '';
        let libelleVoie = etablissement.adresseEtablissement.libelleVoieEtablissement || '';
        let codePostal = etablissement.adresseEtablissement.codePostalEtablissement || '';
        let adresseComplete = (numero || typeVoie || libelleVoie) ?
          ((numero + " " + typeVoie + " " + libelleVoie).trim() + ", " + codePostal + " " + commune) :
          "Non renseigné";

        let statutCode = (etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0) ?
          etablissement.periodesEtablissement[0].etatAdministratifEtablissement :
          '';
        let statutClass = "";
        let statutText = "Non précisé";
        if (statutCode === 'A') {
          statutClass = "statut-actif";
          statutText = "En Activité";
        } else if (statutCode === 'F') {
          statutClass = "statut-ferme";
          statutText = "Fermé";
        }
        // Je définis la classe CSS et le texte pour le statut dans la popup.

        let themeGeneralText = (categoriePrincipaleSelect.selectedIndex > 0) ?
          categoriePrincipaleSelect.selectedOptions[0].text :
          "Non précisé";
        let themeDetailText = (sousCategorieSelect.value !== "") ?
          sousCategorieSelect.selectedOptions[0].text :
          "Non précisé";

        let popupContent = `<div style="font-weight:bold; font-size:1.2em;">
                            ${ul.denominationUniteLegale || ul.nomUniteLegale || 'Nom non disponible'}
                        </div>
                        <strong>Commune :</strong> ${commune || "Non renseigné"}<br>
                        <strong>Adresse :</strong><br> ${adresseComplete}<br>
                        <strong>Secteurs :</strong><br> ${themeGeneralText}<br>
                        <strong>Sous-Secteur :</strong> ${themeDetailText}<br>`;
        // Je commence à construire le contenu de la popup avec les infos de base.

        if (userPosition) {
          let d = haversineDistance(userPosition.lat, userPosition.lon, lat, lon);
          popupContent += `<strong style="color:blue;">Distance :</strong> ${d.toFixed(2)} km<br>`;
        }
        // Si j’ai ma position, j’ajoute la distance à l’entreprise.

        popupContent += `<br>
                     <strong>Statut :</strong> <strong class="${statutClass}">${statutText}</strong><br>
                     <strong>Date de création :</strong> ${dateCreationUniteLegale}<br>
                     <strong>Date de validité des informations :</strong><br> ${dateDebut} à ${dateFin}<br>
                     <strong>SIREN :</strong> ${siren}<br>
                     <strong>SIRET :</strong> ${siret}<br>
                     <strong>Code NAF/APE :</strong> ${activitePrincipale}`;
        // Je termine la popup avec le statut, les dates, et les identifiants.

        let marker = L.marker([lat, lon]).addTo(window.markersLayer);
        marker.bindPopup(popupContent);
        // J’ajoute le marqueur à la carte avec sa popup.
      }

      /* ----- Fonction de calcul de la distance entre deux points (formule de Haversine) ----- */
      function haversineDistance(lat1, lon1, lat2, lon2) {
        // Cette fonction calcule la distance en km entre deux points GPS avec la formule de Haversine.
        const toRad = x => x * Math.PI / 180;
        const R = 6371; // Rayon de la Terre en km
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
          Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
          Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
        // Je retourne la distance calculée.
      }
    });
  </script>
</body>

</html>