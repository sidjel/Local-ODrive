<?php
/*
 * TP_API-Silvere-Morgan-LocaloDrive.php
 * Version 18.5 : Ajout img pour le marqueur du centre-ville
 */

require_once __DIR__ . "/../vendor/autoload.php";
// Chargement des variables d'environnement depuis le fichier .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
// Récupération de la clé API Sirene depuis les variables d'environnement
$API_KEY_SIRENE = $_ENV['API_KEY_SIRENE'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Localo'Drive - Recherche et Carte</title>
  <!-- Inclusion de Bootstrap et du CSS personnalisé pour le style de la page -->
  <link rel="stylesheet" href="../node_modules/bootstrap/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <!-- Inclusion de Leaflet CSS pour l'affichage de la carte -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <!-- Inclusion de Proj4js pour la conversion de coordonnées entre systèmes de projection -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.7.5/proj4.js"></script>
  <script>
    // Définition de la projection Lambert93 (EPSG:2154) utilisée pour la conversion des coordonnées
    proj4.defs("EPSG:2154", "+proj=lcc +lat_1=44 +lat_2=49 +lat_0=46.5 +lon_0=3 +x_0=700000 +y_0=6600000 +ellps=GRS80 +units=m +no_defs");
  </script>
</head>
<body>

<script>
  // Passage sécurisé de la clé API depuis PHP vers JavaScript sans l'afficher directement
  const API_KEY_SIRENE = "<?php echo htmlspecialchars($API_KEY_SIRENE, ENT_QUOTES, 'UTF-8'); ?>";
</script>

<!-- Conteneur principal de la page -->
<div class="container mt-4">
  <div class="card text-center mb-4">
    <div class="card-body">
      <h1 class="card-title">
        Local<span class="text-vert-pomme">O'</span>Drive
      </h1>
      <p class="card-text text-secondary">
        Faciliter l'accès aux produits locaux en connectant producteurs et consommateurs
      </p>
    </div>
  </div>

  <!-- Formulaire de recherche avec champs pour ville, adresse, nom d'entreprise, rayon et filtres -->
  <form id="formulaire-adresse" class="d-flex flex-wrap justify-content-center mb-4">
    <!-- Champ de saisie pour la ville -->
    <input type="text" id="champ-ville" class="form-control me-2 mb-2" placeholder="Ville">
    <!-- Champ de saisie pour l'adresse (facultatif) -->
    <input type="text" id="champ-adresse" class="form-control me-2 mb-2" placeholder="Adresse (facultatif)">
    <!-- Champ de saisie pour le nom d'entreprise sur l'ensemble du territoire français -->
    <input type="text" id="champ-nom-entreprise" class="form-control me-2 mb-2" placeholder="Nom de l'entreprise (France entière)">
    <!-- Sélecteur pour définir le rayon de recherche autour de l'utilisateur -->
    <select id="rayon-select" class="form-select me-2 mb-2">
      <option value="">-- Rayon de recherche --</option>
      <option value="1">1 km</option>
      <option value="3">3 km</option>
      <option value="5">5 km</option>
      <option value="10">10 km</option>
    </select>
    <!-- Sélecteur pour choisir le secteur principal -->
    <select id="Secteur" class="form-select me-2 mb-2">
      <option value="">-- Secteur --</option>
      <option value="Production primaire">Production primaire</option>
      <option value="Transformation et fabrication de produits alimentaires">Transformation et fabrication de produits alimentaires</option>
      <option value="Fabrication de boissons">Fabrication de boissons</option>
      <option value="Commerce alimentaire">Commerce alimentaire</option>
      <option value="Restauration et services liés à l’alimentation">Restauration et services liés à l’alimentation</option>
    </select>
    <!-- Sélecteur dynamique pour le sous-secteur, mis à jour en fonction du secteur principal sélectionné -->
    <select id="Sous-Secteur" class="form-select me-2 mb-2">
      <option value="">-- Sous-Secteur --</option>
    </select>
    <!-- Case à cocher pour filtrer uniquement sur les établissements actuellement en activité -->
    <div class="form-check me-2 mb-2">
      <input class="form-check-input" type="checkbox" id="filtre-actifs">
      <label class="form-check-label" for="filtre-actifs">Filtrer uniquement sur les établissements en activité</label>
    </div>
    <!-- Bouton de soumission du formulaire de recherche -->
    <button type="submit" class="btn btn-success mb-2">Rechercher</button>
  </form>

  <!-- Conteneur pour afficher les résultats de recherche et la carte -->
  <div class="row">
    <!-- Colonne pour les résultats textuels (adresse, infos de zone, entreprises) -->
    <div class="col-md-4" id="colonne-resultats">
      <div id="resultats-api"></div>
    </div>
    <!-- Colonne pour la carte interactive -->
    <div class="col-md-8" id="colonne-carte">
      <div id="map" style="height:500px;"></div>
    </div>
  </div>
</div>

<!-- Inclusion des scripts JavaScript nécessaires -->
<script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {

  /* ----- Initialisation des variables globales et réinitialisation des champs ----- */
  // Variable pour stocker la position de l'utilisateur, utilisée pour le filtrage par rayon
  let userPosition = null;
  // Variable pour stocker le marqueur du centre-ville afin d'éviter les doublons
  let marqueurCentreVille = null;
  // Récupération des éléments du DOM correspondant aux champs du formulaire
  const champVille = document.getElementById('champ-ville');
  const champAdresse = document.getElementById('champ-adresse');
  const rayonSelect = document.getElementById('rayon-select');
  const categoriePrincipaleSelect = document.getElementById('Secteur');
  const sousCategorieSelect = document.getElementById('Sous-Secteur');
  const filtreActifs = document.getElementById('filtre-actifs');

  // Réinitialisation des valeurs des champs lors du chargement de la page
  champVille.value = "";
  champAdresse.value = "";
  rayonSelect.selectedIndex = 0;
  categoriePrincipaleSelect.selectedIndex = 0;
  sousCategorieSelect.innerHTML = '<option value="">-- Sous-Secteur --</option>';

  /* ----- Définition du mapping pour le secteur d'alimentation avec les codes NAF/APE ----- */
  const mappingAlimentation = {
    "Production primaire": [
      { code: "01.11Z", label: "Code NAF/APE : 01.11Z - Culture de céréales (sauf riz)" },
      { code: "01.12Z", label: "Code NAF/APE : 01.12Z - Culture du riz" },
      { code: "01.13Z", label: "Code NAF/APE : 01.13Z - Culture de légumes, melons, racines et tubercules" },
      { code: "01.19Z", label: "Code NAF/APE : 01.19Z - Autres cultures non permanentes" },
      { code: "01.21Z", label: "Code NAF/APE : 01.21Z - Culture de la vigne" },
      { code: "01.22Z", label: "Code NAF/APE : 01.22Z - Culture de fruits tropicaux et subtropicaux" },
      { code: "01.23Z", label: "Code NAF/APE : 01.23Z - Culture d'agrumes" },
      { code: "01.24Z", label: "Code NAF/APE : 01.24Z - Culture de fruits à pépins et à noyau" },
      { code: "01.25Z", label: "Code NAF/APE : 01.25Z - Culture d'autres fruits d'arbres ou d'arbustes et de fruits à coque" },
      { code: "01.26Z", label: "Code NAF/APE : 01.26Z - Culture de fruits oléagineux" },
      { code: "01.27Z", label: "Code NAF/APE : 01.27Z - Culture de plantes à boissons" },
      { code: "01.28Z", label: "Code NAF/APE : 01.28Z - Culture de plantes à épices, aromatiques, médicinales et pharmaceutiques" },
      { code: "01.29Z", label: "Code NAF/APE : 01.29Z - Autres cultures permanentes" },
      { code: "01.30Z", label: "Code NAF/APE : 01.30Z - Reproduction de plantes" },
      { code: "01.41Z", label: "Code NAF/APE : 01.41Z - Élevage de vaches laitières" },
      { code: "01.42Z", label: "Code NAF/APE : 01.42Z - Élevage d'autres bovins et de buffles" },
      { code: "01.43Z", label: "Code NAF/APE : 01.43Z - Élevage de chevaux et d'autres équidés" },
      { code: "01.44Z", label: "Code NAF/APE : 01.44Z - Élevage de chameaux et d'autres camélidés" },
      { code: "01.45Z", label: "Code NAF/APE : 01.45Z - Élevage d'ovins et de caprins" },
      { code: "01.46Z", label: "Code NAF/APE : 01.46Z - Élevage de porcins" },
      { code: "01.47Z", label: "Code NAF/APE : 01.47Z - Élevage de volailles" },
      { code: "01.49Z", label: "Code NAF/APE : 01.49Z - Élevage d'autres animaux" },
      { code: "01.50Z", label: "Code NAF/APE : 01.50Z - Culture et élevage associés" },
      { code: "01.61Z", label: "Code NAF/APE : 01.61Z - Activités de soutien aux cultures" },
      { code: "01.62Z", label: "Code NAF/APE : 01.62Z - Activités de soutien à la production animale" },
      { code: "01.63Z", label: "Code NAF/APE : 01.63Z - Traitement primaire des récoltes" },
      { code: "01.64Z", label: "Code NAF/APE : 01.64Z - Traitement des semences" },
      { code: "03.11Z", label: "Code NAF/APE : 03.11Z - Pêche en mer" },
      { code: "03.12Z", label: "Code NAF/APE : 03.12Z - Pêche en eau douce" },
      { code: "03.21Z", label: "Code NAF/APE : 03.21Z - Aquaculture en mer" },
      { code: "03.22Z", label: "Code NAF/APE : 03.22Z - Aquaculture en eau douce" }
    ],
    "Transformation et fabrication de produits alimentaires": [
      { code: "10.11Z", label: "Code NAF/APE : 10.11Z - Transformation et conservation de la viande de boucherie" },
      { code: "10.12Z", label: "Code NAF/APE : 10.12Z - Transformation et conservation de la viande de volaille" },
      { code: "10.13A", label: "Code NAF/APE : 10.13A - Préparation industrielle de produits à base de viande" },
      { code: "10.13B", label: "Code NAF/APE : 10.13B - Charcuterie" },
      { code: "10.20Z", label: "Code NAF/APE : 10.20Z - Transformation et conservation de poisson, crustacés et mollusques" },
      { code: "10.31Z", label: "Code NAF/APE : 10.31Z - Transformation et conservation de pommes de terre" },
      { code: "10.32Z", label: "Code NAF/APE : 10.32Z - Préparation de jus de fruits et légumes" },
      { code: "10.39A", label: "Code NAF/APE : 10.39A - Autre transformation et conservation de légumes" },
      { code: "10.39B", label: "Code NAF/APE : 10.39B - Transformation et conservation de fruits" },
      { code: "10.41A", label: "Code NAF/APE : 10.41A - Fabrication d'huiles et graisses brutes" },
      { code: "10.41B", label: "Code NAF/APE : 10.41B - Fabrication d'huiles et graisses raffinées" },
      { code: "10.42Z", label: "Code NAF/APE : 10.42Z - Fabrication de margarine et graisses comestibles similaires" },
      { code: "10.51A", label: "Code NAF/APE : 10.51A - Fabrication de lait liquide et de produits frais" },
      { code: "10.51B", label: "Code NAF/APE : 10.51B - Fabrication de beurre" },
      { code: "10.51C", label: "Code NAF/APE : 10.51C - Fabrication de fromage" },
      { code: "10.51D", label: "Code NAF/APE : 10.51D - Fabrication d'autres produits laitiers" },
      { code: "10.52Z", label: "Code NAF/APE : 10.52Z - Fabrication de glaces et sorbets" },
      { code: "10.61A", label: "Code NAF/APE : 10.61A - Meunerie" },
      { code: "10.61B", label: "Code NAF/APE : 10.61B - Autres activités du travail des grains" },
      { code: "10.62Z", label: "Code NAF/APE : 10.62Z - Fabrication de produits amylacés" },
      { code: "10.71A", label: "Code NAF/APE : 10.71A - Fabrication industrielle de pain et de pâtisserie fraîche" },
      { code: "10.71B", label: "Code NAF/APE : 10.71B - Cuisson de produits de boulangerie" },
      { code: "10.71C", label: "Code NAF/APE : 10.71C - Boulangerie et boulangerie-pâtisserie" },
      { code: "10.71D", label: "Code NAF/APE : 10.71D - Pâtisserie" },
      { code: "10.72Z", label: "Code NAF/APE : 10.72Z - Fabrication de biscuits, biscottes et pâtisseries de conservation" },
      { code: "10.73Z", label: "Code NAF/APE : 10.73Z - Fabrication de pâtes alimentaires" },
      { code: "10.81Z", label: "Code NAF/APE : 10.81Z - Fabrication de sucre" },
      { code: "10.82Z", label: "Code NAF/APE : 10.82Z - Fabrication de cacao, chocolat et de produits de confiserie" },
      { code: "10.83Z", label: "Code NAF/APE : 10.83Z - Transformation du thé et du café" },
      { code: "10.84Z", label: "Code NAF/APE : 10.84Z - Fabrication de condiments et assaisonnements" },
      { code: "10.85Z", label: "Code NAF/APE : 10.85Z - Fabrication de plats préparés" },
      { code: "10.86Z", label: "Code NAF/APE : 10.86Z - Fabrication d'aliments homogénéisés et diététiques" },
      { code: "10.89Z", label: "Code NAF/APE : 10.89Z - Fabrication d'autres produits alimentaires n.c.a." },
      { code: "10.91Z", label: "Code NAF/APE : 10.91Z - Fabrication d'aliments pour animaux de ferme" },
      { code: "10.92Z", label: "Code NAF/APE : 10.92Z - Fabrication d'aliments pour animaux de compagnie" }
    ],
    "Fabrication de boissons": [
      { code: "11.01Z", label: "Code NAF/APE : 11.01Z - Production de boissons alcooliques distillées" },
      { code: "11.02A", label: "Code NAF/APE : 11.02A - Fabrication de vins effervescents" },
      { code: "11.02B", label: "Code NAF/APE : 11.02B - Vinification" },
      { code: "11.03Z", label: "Code NAF/APE : 11.03Z - Fabrication de cidre et de vins de fruits" },
      { code: "11.04Z", label: "Code NAF/APE : 11.04Z - Production d'autres boissons fermentées non distillées" },
      { code: "11.05Z", label: "Code NAF/APE : 11.05Z - Fabrication de bière" },
      { code: "11.06Z", label: "Code NAF/APE : 11.06Z - Production de malt" },
      { code: "11.07A", label: "Code NAF/APE : 11.07A - Industrie des eaux de table" },
      { code: "11.07B", label: "Code NAF/APE : 11.07B - Production de boissons rafraîchissantes" }
    ],
    "Commerce alimentaire": [
      { code: "46.31Z", label: "Code NAF/APE : 46.31Z - Commerce de gros de fruits et légumes" },
      { code: "46.32A", label: "Code NAF/APE : 46.32A - Commerce de gros de viandes de boucherie" },
      { code: "46.32B", label: "Code NAF/APE : 46.32B - Commerce de gros de produits à base de viande" },
      { code: "46.33Z", label: "Code NAF/APE : 46.33Z - Commerce de gros de produits laitiers, œufs, huiles et matières grasses comestibles" },
      { code: "46.34Z", label: "Code NAF/APE : 46.34Z - Commerce de gros de boissons" },
      { code: "46.36Z", label: "Code NAF/APE : 46.36Z - Commerce de gros de sucre, chocolat et confiserie" },
      { code: "46.37Z", label: "Code NAF/APE : 46.37Z - Commerce de gros de café, thé, cacao et épices" },
      { code: "46.38A", label: "Code NAF/APE : 46.38A - Commerce de gros de poissons, crustacés et mollusques" },
      { code: "46.38B", label: "Code NAF/APE : 46.38B - Commerce de gros alimentaire spécialisé divers" },
      { code: "46.39A", label: "Code NAF/APE : 46.39A - Commerce de gros de produits surgelés" },
      { code: "46.39B", label: "Code NAF/APE : 46.39B - Autre commerce de gros alimentaire" },
      { code: "47.11A", label: "Code NAF/APE : 47.11A - Commerce de détail de produits surgelés" },
      { code: "47.11B", label: "Code NAF/APE : 47.11B - Commerce d'alimentation générale" },
      { code: "47.11C", label: "Code NAF/APE : 47.11C - Supérettes" },
      { code: "47.11D", label: "Code NAF/APE : 47.11D - Supermarchés" },
      { code: "47.11E", label: "Code NAF/APE : 47.11E - Magasins multi-commerces" },
      { code: "47.11F", label: "Code NAF/APE : 47.11F - Hypermarchés" },
      { code: "47.19A", label: "Code NAF/APE : 47.19A - Grands magasins" },
      { code: "47.19B", label: "Code NAF/APE : 47.19B - Autres commerces de détail en magasin non spécialisé" },
      { code: "47.21Z", label: "Code NAF/APE : 47.21Z - Commerce de détail de fruits et légumes en magasin spécialisé" },
      { code: "47.22Z", label: "Code NAF/APE : 47.22Z - Commerce de détail de viandes et de produits à base de viande en magasin spécialisé" },
      { code: "47.23Z", label: "Code NAF/APE : 47.23Z - Commerce de détail de poissons, crustacés et mollusques en magasin spécialisé" },
      { code: "47.24Z", label: "Code NAF/APE : 47.24Z - Commerce de détail de pain, pâtisserie et confiserie en magasin spécialisé" },
      { code: "47.25Z", label: "Code NAF/APE : 47.25Z - Commerce de détail de boissons en magasin spécialisé" },
      { code: "47.26Z", label: "Code NAF/APE : 47.26Z - Commerce de détail de produits à base de tabac en magasin spécialisé" },
      { code: "47.29Z", label: "Code NAF/APE : 47.29Z - Autres commerces de détail alimentaires en magasin spécialisé" },
      { code: "47.30Z", label: "Code NAF/APE : 47.30Z - Commerce de détail de carburants en magasin spécialisé" },
      { code: "47.81Z", label: "Code NAF/APE : 47.81Z - Commerce de détail alimentaire sur éventaires et marchés" }
    ],
    "Restauration et services liés à l’alimentation": [
      { code: "56.10A", label: "Code NAF/APE : 56.10A - Restauration traditionnelle" },
      { code: "56.10B", label: "Code NAF/APE : 56.10B - Cafétérias et autres libres-services" },
      { code: "56.10C", label: "Code NAF/APE : 56.10C - Restauration de type rapide" },
      { code: "56.21Z", label: "Code NAF/APE : 56.21Z - Services des traiteurs" },
      { code: "56.29A", label: "Code NAF/APE : 56.29A - Restauration collective sous contrat" },
      { code: "56.29B", label: "Code NAF/APE : 56.29B - Autres services de restauration n.c.a." },
      { code: "56.30Z", label: "Code NAF/APE : 56.30Z - Débits de boissons" }
    ]
  };

  /* ----- Mise à jour dynamique du menu des Sous-Secteur en fonction du Secteur sélectionné ----- */
  categoriePrincipaleSelect.addEventListener('change', function() {
    // Récupération de la valeur sélectionnée pour le secteur
    let categorie = this.value;
    // Réinitialisation du menu des sous-secteurs
    sousCategorieSelect.innerHTML = '<option value="">-- Sous-Secteur --</option>';
    // Si des sous-secteurs existent pour ce secteur, on les ajoute dans le menu
    if (mappingAlimentation[categorie] && mappingAlimentation[categorie].length > 0) {
      mappingAlimentation[categorie].forEach(function(item) {
        let option = document.createElement('option');
        option.value = item.code;
        option.textContent = item.label;
        sousCategorieSelect.appendChild(option);
      });
    } else {
      console.warn("Aucun Sous-Secteur trouvée pour le Secteur:", categorie);
    }
  });

  // Déclenchement manuel de l'événement "change" pour réinitialiser le menu des sous-secteurs au chargement
  categoriePrincipaleSelect.dispatchEvent(new Event('change'));

  /* ----- Initialisation de la carte ----- */
  // Création de la carte centrée sur une position par défaut (coordonnées de la France)
  var map = L.map('map').setView([46.603354, 1.888334], 6);
  // Ajout de la couche de tuiles OpenStreetMap
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);
  // Création d'un groupe de couches pour stocker les marqueurs et faciliter leur gestion
  window.markersLayer = L.layerGroup().addTo(map);

  /* ----- Fonction de reverse géocodage pour récupérer la ville et l'adresse à partir des coordonnées ----- */
  function reverseGeocode(lon, lat, callback) {
    var url = `https://api-adresse.data.gouv.fr/reverse/?lon=${lon}&lat=${lat}`;
    fetch(url)
      .then(response => response.json())
      .then(data => {
        console.log("Réponse reverse geocode :", data);
        if (data.features && data.features.length > 0) {
          // Récupération des propriétés du premier résultat
          let prop = data.features[0].properties;
          // Détermination de la ville à partir des informations disponibles
          let city = prop.city || prop.label || "Ville inconnue";
          // Construction de l'adresse selon la présence d'un numéro et d'une rue
          let address = prop.housenumber ? `${prop.housenumber} ${prop.street || ''}`.trim() : prop.street || "Adresse inconnue";
          callback(city, address);
        } else {
          callback("Ville inconnue", "Adresse inconnue");
        }
      })
      .catch(error => {
        console.error("Erreur lors du reverse géocodage :", error);
        callback("Ville inconnue", "Adresse inconnue");
      });
  }

  /* ----- Fonction pour récupérer l'adresse IP de l'utilisateur ----- */
  function getUserIP(callback) {
    fetch("https://api64.ipify.org?format=json")
      .then(response => response.json())
      .then(data => callback(data.ip))
      .catch(error => {
        console.error("Erreur lors de la récupération de l'adresse IP :", error);
        callback("IP inconnue");
      });
  }

  /* ----- Fonction pour récupérer les informations du navigateur ----- */
  function getBrowserInfo() {
    const ua = navigator.userAgent;
    let browserName = "Navigateur inconnu";
    let browserVersion = "Version inconnue";

    // Détection simplifiée du navigateur en fonction du userAgent
    if (ua.includes("Chrome")) {
      browserName = "Google Chrome";
      browserVersion = ua.match(/Chrome\/([\d.]+)/)[1];
    } else if (ua.includes("Firefox")) {
      browserName = "Mozilla Firefox";
      browserVersion = ua.match(/Firefox\/([\d.]+)/)[1];
    } else if (ua.includes("Safari") && !ua.includes("Chrome")) {
      browserName = "Apple Safari";
      browserVersion = ua.match(/Version\/([\d.]+)/)[1];
    } else if (ua.includes("MSIE") || ua.includes("Trident")) {
      browserName = "Internet Explorer";
      browserVersion = ua.match(/(MSIE |rv:)([\d.]+)/)[2];
    } else if (ua.includes("Edge")) {
      browserName = "Microsoft Edge";
      browserVersion = ua.match(/Edge\/([\d.]+)/)[1];
    } else {
      browserName = ua.split(" ")[0];
      browserVersion = "Inconnue";
    }
    return { browserName, browserVersion };
  }

  /* ----- Définition de l'icône personnalisée pour la position de l'utilisateur (représentée par "Moi") ----- */
  const userIcon = L.divIcon({
    className: 'user-div-icon',
    html: `<div><span>Moi</span></div>`,
    iconSize: [30, 30],
    iconAnchor: [15, 15],
    popupAnchor: [0, -15]
  });

  // Variable globale pour stocker le marqueur de l'utilisateur sur la carte
  let userMarker = null;

  /* ----- Vérification de la disponibilité de la géolocalisation et récupération de la position de l'utilisateur ----- */
  if (navigator.geolocation) {
    // DÉBUT DU BLOC : Récupération de la position et mise à jour pour le filtrage par rayon
    navigator.geolocation.getCurrentPosition(function(position) {
      // Création d'un objet contenant la latitude et la longitude de l'utilisateur
      let positionUtilisateur = {
        lat: position.coords.latitude,
        lon: position.coords.longitude
      };
      // Affectation de cette position à la variable globale userPosition pour le filtrage
      userPosition = positionUtilisateur;
      
      // Centrage de la carte sur la position de l'utilisateur avec un niveau de zoom approprié
      map.setView([positionUtilisateur.lat, positionUtilisateur.lon], 13);

      // Utilisation du reverse géocodage pour récupérer la ville et l'adresse à partir des coordonnées
      reverseGeocode(positionUtilisateur.lon, positionUtilisateur.lat, (ville, adresse) => {
        // Mise à jour des champs du formulaire si ceux-ci sont vides
        if (champVille.value.trim() === "") {
          champVille.value = ville;
        }
        if (champAdresse.value.trim() === "") {
          champAdresse.value = adresse;
        }
        // Mise à jour ou création du marqueur de l'utilisateur sur la carte
        if (userMarker) {
          userMarker.setLatLng([positionUtilisateur.lat, positionUtilisateur.lon]);
        } else {
          userMarker = L.marker([positionUtilisateur.lat, positionUtilisateur.lon], { icon: userIcon })
            .addTo(map)
            .bindPopup("Chargement des informations...");
        }
        // Récupération de l'adresse IP et des infos du navigateur pour compléter la popup du marqueur
        getUserIP((ip) => {
          const { browserName, browserVersion } = getBrowserInfo();
          userMarker.setPopupContent(`
                <b>Vous êtes ici</b><br>
                📍 <b>Adresse :</b> ${adresse}, ${ville} <br>
                🌐 <b>Navigateur :</b> ${browserName} ${browserVersion} <br>
                🖥️ <b>Adresse IP :</b> ${ip}
          `);
          // Lancement de la récupération des informations de zone pour la ville
          recupererZone(ville, document.getElementById('resultats-api'));
        });
      });
    }, function(error) {
      console.error("Erreur de géolocalisation : " + error.message);
    }, { enableHighAccuracy: true });
    // FIN DU BLOC : Récupération de la position et mise à jour pour le filtrage par rayon
  }

  /* ----- Écoute des modifications sur les champs Ville et Adresse pour déclencher la recherche de zone ----- */
  champVille.addEventListener('change', function() {
    let ville = this.value.trim();
    if (ville !== "") {
      recupererZone(ville, document.getElementById('resultats-api'));
    }
  });
  champAdresse.addEventListener('change', function() {
    let ville = champVille.value.trim();
    let adresse = this.value.trim();
    if (ville !== "") {
      // Construction de la requête en fonction de la présence ou non d'une adresse renseignée
      let query = (adresse === "" || adresse === "Non renseigné") ? ville : adresse + " " + ville;
      rechercherAdresse(query, ville);
    }
  });

  /* ----- Gestion de la soumission du formulaire de recherche ----- */
  document.getElementById('formulaire-adresse').addEventListener('submit', function(e) {
    e.preventDefault();
    let villeRecherche = champVille.value.trim();
    let adresseRecherche = champAdresse.value.trim();
    let categoriePrincipale = categoriePrincipaleSelect.value;
    // Vérification que la ville et le secteur ont été renseignés
    if (villeRecherche === "") {
      alert("Veuillez entrer une ville");
      return;
    }
    if (categoriePrincipale === "") {
      alert("Veuillez sélectionner un Secteur");
      return;
    }
    // Construction de la requête de recherche
    let query = (adresseRecherche === "" || adresseRecherche === "Non renseigné") ? villeRecherche : adresseRecherche + " " + villeRecherche;
    rechercherAdresse(query, villeRecherche);
  });

  /* ----- Fonction de recherche via l'API Base Adresse ----- */
  function rechercherAdresse(query, ville) {
    console.log("Recherche Base Adresse pour : ", query);
    var url = 'https://api-adresse.data.gouv.fr/search/?q=' + encodeURIComponent(query);
    fetch(url)
      .then(response => response.json())
      .then(data => {
        console.log("Résultats Base Adresse : ", data);
        // Affichage des résultats et lancement de la recherche d'entreprises associées
        afficherResultats(data, ville);
      })
      .catch(error => {
        console.error("Erreur lors de la récupération des données :", error);
      });
  }

  /* ----- Fonction d'affichage des résultats d'adresse et lancement de la recherche d'entreprises ----- */
  function afficherResultats(data, ville) {
    var conteneur = document.getElementById('resultats-api');
    // Réinitialisation de la zone d'affichage des résultats
    conteneur.innerHTML = '';
    // Suppression des marqueurs précédents de la carte
    window.markersLayer.clearLayers();
    let features = data.features;
    // Si aucune adresse précise n'est renseignée et que la ville est connue, ne prendre que le premier résultat
    if ((champAdresse.value.trim() === "" || champAdresse.value.trim() === "Non renseigné") && ville !== "") {
      features = [features[0]];
    }
    if (features && features.length > 0) {
      // Pour chaque résultat, création d'un bloc d'informations et lancement de la recherche d'entreprises
      features.forEach(function(feature) {
        let propriete = feature.properties;
        // Récupération des coordonnées
        let lat = feature.geometry.coordinates[1];
        let lng = feature.geometry.coordinates[0];
        let citycode = propriete.citycode;
        let postcode = propriete.postcode;
        let adresseAffichee = (champAdresse.value.trim() === "") ? "" : champAdresse.value;
        
        // Création du bloc d'information pour l'adresse
        let blocA = `
          <div class="bloc-a">
          
            <p><strong>Commune :</strong> ${propriete.city || "Non renseigné"}</p>
            <p><strong>Adresse :</strong> ${adresseAffichee}</p>
            <p><strong>Code postal :</strong> ${postcode}</p>
            <div class="zone-info-placeholder"></div>
          </div>
        `;
        // Création du bloc d'information pour la géolocalisation
        let blocB = `
          <div class="bloc-b">
            <p><strong>Géolocalisation de l'adresse :</strong></p>
            <p><strong>Latitude :</strong> ${lat}</p>
            <p><strong>Longitude :</strong> ${lng}</p>
            <div class="centre-ville-placeholder"></div>
          </div>
        `;
        
        // Création de l'élément de résultat complet et affectation des classes pour le style
        let divResultat = document.createElement('div');
        divResultat.className = 'resultat p-3 mb-3 border rounded';
        // Stockage de l'adresse dans un data-attribute
        divResultat.dataset.adresse = propriete.label;
        // Assemblage des deux blocs d'information
        divResultat.innerHTML = blocA + blocB;
        // Récupération des informations de zone pour compléter le bloc
        recupererZone(propriete.city, divResultat);
        // Ajout du bloc dans le conteneur des résultats
        conteneur.appendChild(divResultat);
        
        // Lancement de la recherche d'entreprises associées à ce résultat
        recupererEntreprises(postcode, divResultat, ville);
      });
    } else {
      // Message à afficher si aucun résultat n'est trouvé
      conteneur.innerHTML = '<p>Aucun résultat trouvé.</p>';
    }
  }

  /* ----- Fonction pour récupérer les informations de zone via l'API Geo ----- */
  function recupererZone(ville, conteneur) {
    var urlGeo = `https://geo.api.gouv.fr/communes?nom=${encodeURIComponent(ville)}&fields=nom,centre,departement,region&format=json`;
    fetch(urlGeo)
      .then(response => response.json())
      .then(data => {
        if (data && data.length > 0) {
          // Affichage des informations de zone dans le conteneur fourni
          afficherZone(data[0], conteneur);
        } else {
          console.warn("Aucune donnée trouvée pour la ville :", ville);
        }
      })
      .catch(error => {
        console.error("Erreur lors de la récupération des données de la zone :", error);
      });
  }

  /* ----- Fonction d'affichage des informations de zone dans les éléments prévus ----- */
  function afficherZone(donnees, conteneur) {
    let placeholderZone = conteneur.querySelector('.zone-info-placeholder');
    let placeholderCentreVille = conteneur.querySelector('.centre-ville-placeholder');

    // Extraction des informations de zone (département, région, centre-ville)
    let departement = donnees.departement ? donnees.departement.nom : "Non renseigné";
    let region = donnees.region ? donnees.region.nom : "Non renseigné";
    let latitudeCentre = donnees.centre ? donnees.centre.coordinates[1] : "Non renseigné";
    let longitudeCentre = donnees.centre ? donnees.centre.coordinates[0] : "Non renseigné";

    // Mise à jour du bloc de zone avec le département et la région
    if (placeholderZone) {
      placeholderZone.innerHTML = `
          <p><strong>Département :</strong> ${departement}</p>
          <p><strong>Région :</strong> ${region}</p>
      `;
    }

    // Mise à jour du bloc du centre-ville avec les coordonnées
    if (placeholderCentreVille) {
      placeholderCentreVille.innerHTML = `
          <p><strong>Géolocalisation du Centre-ville :</strong></p>
          <p><strong>Latitude :</strong> ${latitudeCentre}</p>
          <p><strong>Longitude :</strong> ${longitudeCentre}</p>
      `;
    }

    // Suppression de l'ancien marqueur du centre-ville s'il existe
    if (marqueurCentreVille) {
      map.removeLayer(marqueurCentreVille);
    }

 // Ajout d'un marqueur pour le centre-ville si les coordonnées sont valides
if (latitudeCentre !== "Non renseigné" && longitudeCentre !== "Non renseigné") {
  // Définition de l'icône personnalisée avec une image
  var centreVilleIcon = L.icon({  
    iconUrl: '/img/icone_centre_ville.png',
    iconSize: [30, 30],      // Dimensions de l'image
    iconAnchor: [15, 15],    // Point de l'icône correspondant à la position du marqueur
    popupAnchor: [0, -15]    // Décalage du popup par rapport à l'icône
  });
  
  // Création du marqueur avec l'icône personnalisée
  marqueurCentreVille = L.marker([latitudeCentre, longitudeCentre], { icon: centreVilleIcon })
    .addTo(map);
  
  // Création de la popup pour le centre-ville avec ses coordonnées
  marqueurCentreVille.bindPopup(`<b>Centre-ville de ${donnees.nom}</b><br>📍 Latitude: ${latitudeCentre}<br>📍 Longitude: ${longitudeCentre}`).openPopup();
}
  }

  /* ----- Fonction pour récupérer les entreprises via l'API Sirene ----- */
  function recupererEntreprises(postcode, conteneur, ville) {
    let themeDetail = sousCategorieSelect.value;
    let categoriePrincipale = categoriePrincipaleSelect.value;
    let q = "";
    // Cas particulier pour Grenoble
    if (ville.toUpperCase() === "GRENOBLE") {
      q = '(codePostalEtablissement:"38000" OR codePostalEtablissement:"38100")';
    } else {
      q = 'codePostalEtablissement:"' + postcode + '"';
    }
    // Ajout du filtre sur le nom de la commune
    if (ville && ville.trim() !== '') {
      q += ' AND libelleCommuneEtablissement:"' + ville.toUpperCase() + '"';
    }
    // Si un sous-secteur est sélectionné, ajout du filtre correspondant
    if (themeDetail) {
      q += ' AND activitePrincipaleUniteLegale:"' + themeDetail + '"';
    } else if (categoriePrincipale !== "") {
      // Si seul le secteur est défini, on filtre sur l'ensemble des codes correspondants
      let codes = mappingAlimentation[categoriePrincipale].map(item => item.code);
      q += ' AND (' + codes.map(code => 'activitePrincipaleUniteLegale:"' + code + '"').join(' OR ') + ')';
    }
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
      // Filtrage supplémentaire pour n'afficher que les établissements en activité si la case est cochée
      if (filtreActifs.checked) {
        data.etablissements = data.etablissements.filter(function(etablissement) {
          let statut = etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0
            ? etablissement.periodesEtablissement[0].etatAdministratifEtablissement
            : "";
          return statut === "A";
        });
      }
      // Application du filtre par rayon autour de la position de l'utilisateur
      if (userPosition && rayonSelect.value) {
        let rayon = parseFloat(rayonSelect.value);
        data.etablissements = data.etablissements.filter(function(etablissement) {
          let adresseObj = etablissement.adresseEtablissement;
          if (adresseObj && adresseObj.coordonneeLambertAbscisseEtablissement && adresseObj.coordonneeLambertOrdonneeEtablissement) {
            let x = parseFloat(adresseObj.coordonneeLambertAbscisseEtablissement);
            let y = parseFloat(adresseObj.coordonneeLambertOrdonneeEtablissement);
            // Conversion des coordonnées Lambert93 en coordonnées GPS (WGS84)
            let coords = proj4("EPSG:2154", "EPSG:4326", [x, y]);
            // Calcul de la distance entre la position de l'utilisateur et l'établissement
            let d = haversineDistance(userPosition.lat, userPosition.lon, coords[1], coords[0]);
            return d <= rayon;
          }
          return false;
        });
      }
      console.log("Résultats Sirene:", data);
      // Affichage des entreprises dans le bloc de résultats
      afficherEntreprises(data, conteneur);
      // Ajout des marqueurs correspondants aux entreprises sur la carte
      ajouterMarqueursEntreprises(data);
    })
    .catch(error => {
      console.error("Erreur lors de la récupération des données Sirene :", error);
    });
  }

  /* ----- Fonction pour afficher les entreprises dans le bloc résultats ----- */
  function afficherEntreprises(data, conteneur) {
    let divEntreprises = conteneur.querySelector('.entreprises');
    if (!divEntreprises) {
      divEntreprises = document.createElement('div');
      divEntreprises.className = 'entreprises mt-3 p-3 border-top';
      conteneur.appendChild(divEntreprises);
    }
    if (data && data.etablissements && data.etablissements.length > 0) {
      let html = '<p><strong>Entreprises locales :</strong></p>';
      // Récupération du libellé du secteur et du sous-secteur pour affichage
      let themeGeneralText = (categoriePrincipaleSelect.selectedIndex > 0)
        ? categoriePrincipaleSelect.selectedOptions[0].text
        : "Non précisé";
      let themeDetailText = (sousCategorieSelect.value !== "")
        ? sousCategorieSelect.selectedOptions[0].text
        : "Non précisé";

      data.etablissements.forEach(function(etablissement) {
        let ul = etablissement.uniteLegale || {};
        let commune = (etablissement.adresseEtablissement && etablissement.adresseEtablissement.libelleCommuneEtablissement) || "Non renseigné";
        let adresseObj = etablissement.adresseEtablissement || {};
        let numero = adresseObj.numeroVoieEtablissement || '';
        let typeVoie = adresseObj.typeVoieEtablissement || '';
        let libelleVoie = adresseObj.libelleVoieEtablissement || '';
        let codePostal = adresseObj.codePostalEtablissement || '';
        // Construction de l'adresse complète
        let adresseComplete = (numero || typeVoie || libelleVoie)
            ? ((numero + " " + typeVoie + " " + libelleVoie).trim() + ", " + codePostal + " " + commune)
            : "Non renseigné";

        // Extraction des informations de période pour l'établissement
        let periode = (etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0)
                          ? etablissement.periodesEtablissement[0]
                          : {};
        let dateDebut = periode.dateDebut || "Non renseigné";
        let dateFin = periode.dateFin || "...";
        let statutCode = (etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0)
                           ? etablissement.periodesEtablissement[0].etatAdministratifEtablissement
                           : '';
        let statut = (statutCode === 'A') ? "En Activité" : ((statutCode === 'F') ? "Fermé" : "Non précisé");

        let siren = etablissement.siren || 'N/A';
        let siret = etablissement.siret || 'N/A';
        let dateCreationUniteLegale = ul.dateCreationUniteLegale || "Non renseigné";

        // Construction du bloc HTML pour l'affichage des informations de l'entreprise
        html += '<div class="card mb-2">';
        html += '  <div class="card-body">';
// Ajout de l'icône 🏢 au début du titre
html += '    <h5 class="card-title text-primary" style="font-weight:bold; font-size:1.5em;">🏢' +
        (ul.denominationUniteLegale || ul.nomUniteLegale || 'Nom non disponible') +
        '</h5>';
        html += '    <p class="card-text">';
        html += '      <strong>Commune :</strong> ' + (commune || "Non renseigné") + '<br>';
        html += '      <strong>Adresse :</strong> ' + adresseComplete + '<br>';
        html += '      <strong>Secteurs :</strong> ' + themeGeneralText + '<br>';
        html += '      <strong>Sous-Secteur :</strong> ' + themeDetailText + '<br>';
        // Espace réservé pour la distance (ajouté si userPosition est défini)
        html += '      <br>';
        if (statutCode === 'A') {
          html += '      <strong>Statut Statut</strong> : <strong style="color:green;">En Activité</strong><br>';
        } else if (statutCode === 'F') {
          html += '      <strong>Statut Statut</strong> : <strong style="color:red;">Fermé</strong><br>';
        } else {
          html += '      <strong> :</strong> Non précisé<br>';
        }
        html += '      <strong>Date de création :</strong> ' + dateCreationUniteLegale + '<br>';
        html += '      <strong>Intervalle de validité des informations :</strong> ' + dateDebut + ' à ' + dateFin + '<br>';
        html += '      <strong>SIREN :</strong> ' + siren + '<br>';
        html += '      <strong>SIRET :</strong> ' + siret + '<br>';
        html += '      <strong>Code NAF/APE :</strong> ' + (ul.activitePrincipaleUniteLegale || "Non renseigné") + '<br>';
        html += '    </p>';
        html += '  </div>';
        html += '</div>';
      });
      // Injection du HTML construit dans l'élément de la page dédié aux entreprises
      divEntreprises.innerHTML = html;
    } else {
      divEntreprises.innerHTML = '<p>Aucune entreprise locale trouvée.</p>';
    }
  }

  /* ----- Fonction pour ajouter les marqueurs des entreprises sur la carte ----- */
  function ajouterMarqueursEntreprises(data) {
    if (data && data.etablissements && data.etablissements.length > 0) {
      data.etablissements.forEach(function(etablissement) {
        let adresseObj = etablissement.adresseEtablissement;
        // Vérification que les coordonnées de l'établissement sont disponibles
        if (adresseObj && adresseObj.coordonneeLambertAbscisseEtablissement && adresseObj.coordonneeLambertOrdonneeEtablissement) {
          let x = parseFloat(adresseObj.coordonneeLambertAbscisseEtablissement);
          let y = parseFloat(adresseObj.coordonneeLambertOrdonneeEtablissement);
          // Conversion des coordonnées du système Lambert93 vers le système GPS (WGS84)
          let coords = proj4("EPSG:2154", "EPSG:4326", [x, y]);
          let ul = etablissement.uniteLegale || {};
          let activitePrincipale = ul.activitePrincipaleUniteLegale || "Non renseigné";
          let categorieEntreprise = ul.categorieEntreprise || "Non renseigné";
          let dateCreationUniteLegale = ul.dateCreationUniteLegale || "Non renseigné";
          let periode = (etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0)
                          ? etablissement.periodesEtablissement[0]
                          : {};
          let dateDebut = periode.dateDebut || "Non renseigné";
          let dateFin = periode.dateFin || "...";
          let siren = etablissement.siren || 'N/A';
          let siret = etablissement.siret || 'N/A';
          let commune = adresseObj.libelleCommuneEtablissement || 'N/A';
          let numero = adresseObj.numeroVoieEtablissement || '';
          let typeVoie = adresseObj.typeVoieEtablissement || '';
          let libelleVoie = adresseObj.libelleVoieEtablissement || '';
          let codePostal = adresseObj.codePostalEtablissement || '';
          let adresseComplete = (numero || typeVoie || libelleVoie)
                                ? ((numero + " " + typeVoie + " " + libelleVoie).trim() + ", " + codePostal + " " + commune)
                                : "Non renseigné";
          let statutCode = (etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0)
                           ? etablissement.periodesEtablissement[0].etatAdministratifEtablissement
                           : '';
          let statut = (statutCode === 'A') ? "En Activité" : ((statutCode === 'F') ? "Fermé" : "Non précisé");

          // Récupération des libellés du secteur et du sous-secteur
          let themeGeneralText = (categoriePrincipaleSelect.selectedIndex > 0)
            ? categoriePrincipaleSelect.selectedOptions[0].text
            : "Non précisé";
          let themeDetailText = (sousCategorieSelect.value !== "")
            ? sousCategorieSelect.selectedOptions[0].text
            : "Non précisé";

          // Construction du contenu de la popup pour l'affichage des informations de l'entreprise
          let popupContent = '<div style="font-weight:bold; font-size:1.2em;">' +
                             (ul.denominationUniteLegale || ul.nomUniteLegale || 'Nom non disponible') +
                             '</div>' +
                             '<strong>Commune :</strong> ' + (commune || "Non renseigné") + '<br>' +
                             '<strong>Adresse :</strong><br> ' + adresseComplete + '<br>' +
                             '<strong>Secteurs :</strong><br> ' + themeGeneralText + '<br>' +
                             '<strong>Sous-Secteur :</strong> ' + themeDetailText + '<br>';
          if (userPosition) {
            let d = haversineDistance(userPosition.lat, userPosition.lon, coords[1], coords[0]);
            popupContent += '<strong style="color:blue;">Distance :</strong> ' + d.toFixed(2) + ' km<br>';
          }
          popupContent += '<br>';
          if (statutCode === 'A') {
            popupContent += '<strong>Statut</strong> : <strong style="color:green;">En Activité</strong><br>';
          } else if (statutCode === 'F') {
            popupContent += '<strong>Statut</strong> : <strong style="color:red;">Fermé</strong><br>';
          } else {
            popupContent += '<strong>Statut :</strong> Non précisé<br>';
          }
          popupContent += '<strong>Date de création :</strong> ' + dateCreationUniteLegale + '<br>' +
                          '<strong>Date de validité des informations :</strong><br>' + dateDebut + ' à ' + dateFin + '<br>' +
                          '<strong>SIREN :</strong> ' + siren + '<br>' +
                          '<strong>SIRET :</strong> ' + siret + '<br>' +
                          '<strong>Code NAF/APE :</strong> ' + activitePrincipale;
          
          // Ajout d'un marqueur sur la carte pour cette entreprise et association de la popup
          let marker = L.marker([coords[1], coords[0]]).addTo(window.markersLayer);
          marker.bindPopup(popupContent);
        }
      });
    }
  }

  /* ----- Fonction de calcul de la distance entre deux points (formule de Haversine) ----- */
  function haversineDistance(lat1, lon1, lat2, lon2) {
    // Conversion des degrés en radians
    const toRad = x => x * Math.PI / 180;
    // Rayon de la Terre en kilomètres
    const R = 6371;
    // Calcul des différences de latitude et de longitude en radians
    const dLat = toRad(lat2 - lat1);
    const dLon = toRad(lon2 - lon1);
    // Application de la formule de Haversine
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    // Retour de la distance en kilomètres
    return R * c;
  }
});
</script>
</body>
</html>
