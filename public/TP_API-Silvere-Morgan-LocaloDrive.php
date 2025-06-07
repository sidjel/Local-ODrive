<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>Localo'Map - Recherche et Carte</title>
  <link rel="stylesheet" href="../node_modules/bootstrap/dist/css/bootstrap.min.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../css/style.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

  <?php
  require_once 'init.php';
  ?>
  <style>
    .marker-cluster {
      background-clip: padding-box;
      border-radius: 20px;
      transition: all 0.3s ease;
    }
    .marker-cluster-small {
      background-color: rgba(76, 175, 80, 0.4);
    }
    .marker-cluster-small div {
      background-color: rgba(76, 175, 80, 0.7);
    }
    .marker-cluster-medium {
      background-color: rgba(255, 193, 7, 0.4);
    }
    .marker-cluster-medium div {
      background-color: rgba(255, 193, 7, 0.7);
    }
    .marker-cluster-large {
      background-color: rgba(244, 67, 54, 0.4);
    }
    .marker-cluster-large div {
      background-color: rgba(244, 67, 54, 0.7);
    }
    .marker-cluster div {
      width: 30px;
      height: 30px;
      margin-left: 5px;
      margin-top: 5px;
      text-align: center;
      border-radius: 15px;
      font: 12px "Helvetica Neue", Arial, Helvetica, sans-serif;
      color: #fff;
      font-weight: bold;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .custom-div-icon {
      background: none;
      border: none;
    }
    .custom-marker {
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
      transition: all 0.3s ease;
    }
    .custom-marker:hover {
      transform: scale(1.1);
      box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    }
    .marker-cluster-animating {
      transition: all 0.3s ease-out;
    }
    .cluster-anim-forward {
      opacity: 0;
      transform: scale(0.5);
    }
    .cluster-anim-backward {
      opacity: 0;
      transform: scale(1.5);
    }
    .entreprise-card {
      transition: all 0.3s ease;
    }
    .entreprise-card:hover {
      background-color: #f8f9fa;
      transform: translateX(5px);
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .entreprise-card.active {
      border-left: 4px solid #3388ff;
      background-color: #f0f7ff;
    }
    .user-location-marker {
      z-index: 300 !important;
    }
    .leaflet-marker-icon:not(.user-location-marker) {
      z-index: 400 !important;
    }
    .marker-cluster {
      z-index: 450 !important;
    }
    .leaflet-popup {
      z-index: 500 !important;
    }
    
    /* Styles pour la spiderfication */
    .marker-cluster-spider {
      background-color: rgba(51, 136, 255, 0.6);
      border-radius: 20px;
      transform: scale(1.2);
      transition: all 0.3s ease;
    }
    
    .marker-spider-leg {
      background-color: rgba(51, 136, 255, 0.6);
      position: absolute;
      pointer-events: none;
      transition: all 0.3s ease;
    }
    
    .marker-cluster-spider-animated {
      animation: spider-in 0.3s ease-out;
    }
    
    @keyframes spider-in {
      0% {
        opacity: 0;
        transform: scale(0.3);
      }
      100% {
        opacity: 1;
        transform: scale(1);
      }
    }
    
    /* Amélioration de la visibilité des marqueurs */
    .leaflet-marker-icon {
      transition: all 0.3s ease;
    }
    
    .leaflet-marker-icon:hover {
      transform: scale(1.2);
      z-index: 1000 !important;
    }

    /* Styles pour l'autocomplétion */
    .autocomplete-container {
      position: relative;
    }

    .autocomplete-list {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      background: white;
      border: 1px solid #ddd;
      border-top: none;
      border-radius: 0 0 4px 4px;
      max-height: 200px;
      overflow-y: auto;
      z-index: 1000;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .autocomplete-item {
      padding: 8px 12px;
      cursor: pointer;
      border-bottom: 1px solid #f0f0f0;
    }

    .autocomplete-item:hover {
      background-color: #f8f9fa;
    }

    .autocomplete-item.selected {
      background-color: #e9ecef;
    }
  </style>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.7.5/proj4.js"></script>
  <!-- J'inclus Proj4js pour convertir les coordonnées Lambert93 (utilisées par l'API Sirene) en WGS84 (pour la carte). -->
  <script>
    // Je définis la projection Lambert93 pour que Proj4js sache comment convertir les coordonnées.
    proj4.defs("EPSG:2154", "+proj=lcc +lat_1=44 +lat_2=49 +lat_0=46.5 +lon_0=3 +x_0=700000 +y_0=6600000 +ellps=GRS80 +units=m +no_defs");

    // Fonction pour l'autocomplétion des villes
    function setupCityAutocomplete() {
      const champVille = document.getElementById('champ-ville');
      let autocompleteList = null;
      let selectedIndex = -1;
      let debounceTimer;

      // Créer le conteneur d'autocomplétion
      const container = document.createElement('div');
      container.className = 'autocomplete-container';
      champVille.parentNode.insertBefore(container, champVille);
      container.appendChild(champVille);

      // Fonction pour créer la liste d'autocomplétion
      function createAutocompleteList() {
        if (!autocompleteList) {
          autocompleteList = document.createElement('div');
          autocompleteList.className = 'autocomplete-list';
          container.appendChild(autocompleteList);
        }
        return autocompleteList;
      }

      // Fonction pour mettre à jour la liste d'autocomplétion
      async function updateAutocompleteList(query) {
        if (query.length < 2) {
          hideAutocompleteList();
          return;
        }

        try {
          const response = await fetch(`https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(query)}&type=municipality&limit=5`);
          const data = await response.json();
          
          if (data.features && data.features.length > 0) {
            const list = createAutocompleteList();
            list.innerHTML = '';
            
            data.features.forEach((feature, index) => {
              const item = document.createElement('div');
              item.className = 'autocomplete-item';
              item.textContent = feature.properties.city;
              
              item.addEventListener('click', () => {
                champVille.value = feature.properties.city;
                hideAutocompleteList();
              });

              item.addEventListener('mouseover', () => {
                selectedIndex = index;
                updateSelection();
              });

              list.appendChild(item);
            });

            list.style.display = 'block';
          } else {
            hideAutocompleteList();
          }
        } catch (error) {
          console.error('Erreur lors de la récupération des suggestions:', error);
          hideAutocompleteList();
        }
      }

      // Fonction pour cacher la liste d'autocomplétion
      function hideAutocompleteList() {
        if (autocompleteList) {
          autocompleteList.style.display = 'none';
        }
        selectedIndex = -1;
      }

      // Fonction pour mettre à jour la sélection
      function updateSelection() {
        const items = autocompleteList?.querySelectorAll('.autocomplete-item') || [];
        items.forEach((item, index) => {
          item.classList.toggle('selected', index === selectedIndex);
        });
      }

      // Gestionnaire d'événements pour l'input
      champVille.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
          updateAutocompleteList(e.target.value);
        }, 300);
      });

      // Gestionnaire pour les touches du clavier
      champVille.addEventListener('keydown', (e) => {
        const items = autocompleteList?.querySelectorAll('.autocomplete-item') || [];
        
        switch(e.key) {
          case 'ArrowDown':
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
            updateSelection();
            break;
          case 'ArrowUp':
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, -1);
            updateSelection();
            break;
          case 'Enter':
            e.preventDefault();
            if (selectedIndex >= 0 && items[selectedIndex]) {
              champVille.value = items[selectedIndex].textContent;
              hideAutocompleteList();
            }
            break;
          case 'Escape':
            hideAutocompleteList();
            break;
        }
      });

      // Cacher la liste quand on clique ailleurs
      document.addEventListener('click', (e) => {
        if (!container.contains(e.target)) {
          hideAutocompleteList();
        }
      });
    }

    // Initialiser l'autocomplétion au chargement du document
    document.addEventListener("DOMContentLoaded", function() {
      setupCityAutocomplete();
      // ... existing code ...
    });
  </script>
</head>

<body>
<?php include '../includes/header.php'; ?>

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
          Local<span class="text-vert-pomme">O'</span>Map
          <!-- Le titre avec une partie en vert définie dans mon CSS -->
        </h1>
        <p class="card-text text-secondary">
          Faciliter l'accès aux produits locaux en connectant producteurs et consommateurs
          <!-- Une petite phrase pour expliquer l'objectif du site -->
        </p>
      </div>
    </div>
<!-- Une ligne Bootstrap avec une colonne gauche réduite et une carte agrandie -->
<div class="row">
    <!-- Colonne gauche réduite pour le formulaire et les résultats -->
    <div class="col-md-3" id="colonne-resultats">
        <!-- Mon formulaire de recherche, stylé avec Bootstrap -->
        <form id="formulaire-adresse" class="formulaire-gauche mb-4">
            <input type="text" id="champ-ville" class="form-control mb-2" placeholder="Ville">
            <!-- Champ pour entrer la ville, obligatoire pour la recherche -->
            <input type="text" id="champ-adresse" class="form-control mb-2" placeholder="Adresse (facultatif)" style="display: none;">
            <!-- Champ facultatif pour préciser une adresse -->
            <input type="text" id="champ-nom-entreprise" class="form-control mb-2" placeholder="Mot clé ou Nom de l'entreprise">
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
                <option value="Cultures et productions végétales">🌾 Cultures et productions végétales</option>
                <option value="Élevage et productions animales">🐄 Élevage et productions animales</option>
                <option value="Pêche et aquaculture">🐟 Pêche et aquaculture</option>
                <option value="Boulangerie-Pâtisserie">🥐 Boulangerie-Pâtisserie</option>
                <option value="Viandes et Charcuterie">🍖 Viandes et Charcuterie</option>
                <option value="Produits laitiers">🧀 Produits laitiers</option>
                <option value="Boissons">🍹 Boissons</option>
                <option value="Épicerie spécialisée">🛒 Épicerie spécialisée</option>
                <option value="Restauration">🍽️ Restauration</option>
                <option value="Autres transformations alimentaires">🍲 Autres transformations alimentaires</option>
            </select>
            <!-- Menu déroulant pour choisir le secteur d'activité des entreprises -->
            <select id="Sous-Secteur" class="form-select mb-2">
                <option value="">-- Sous-Secteur --</option>
            </select>
            <!-- Menu déroulant pour les sous-secteurs, rempli dynamiquement selon le secteur choisi -->
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="filtre-actifs" checked>
                <label class="form-check-label" for="filtre-actifs">Filtrer uniquement sur les établissements en activité</label>
            </div>

            <!-- Boutons avec retour à la ligne après "Rechercher" pour un agencement vertical -->
            <div class="d-flex flex-column gap-2">
                <!-- Bouton pour lancer la recherche avec le style Bootstrap -->
                <button type="submit" class="btn btn-rechercher w-100">Rechercher</button>
                <!-- Bouton pour réinitialiser le formulaire et la carte (supprime les marqueurs et remet les champs à zéro) -->
                <button type="button" class="btn btn-effacer w-100" id="effacer-recherche">Effacer</button>
            </div>
        </form>
        <div id="resultats-api"></div>
        <!-- Div où les résultats de la recherche seront affichés -->
    </div>
    <!-- Colonne droite agrandie pour la carte interactive -->
    <div class="col-md-9" id="colonne-carte">
        <div id="geo-messages" class="mb-1"></div>
        <!-- Zone pour afficher les messages liés à la géolocalisation -->
        <div id="map" style="height:500px;"></div>
        <!-- Conteneur pour la carte Leaflet avec une hauteur fixe -->
    </div>
</div>

  <!-- Inclusion des scripts JavaScript nécessaires -->
  <script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Script Bootstrap pour les fonctionnalités interactives comme les dropdowns -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
  <!-- Script Leaflet pour gérer la carte interactive -->
  <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
  <!-- Script Leaflet.markercluster pour gérer les clusters de marqueurs -->
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // J'attends que le DOM soit chargé avant d'exécuter mon code JavaScript.

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
          },
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
        let categorie = this.value;
        sousCategorieSelect.innerHTML = '<option value="">-- Sous-Secteur --</option>';
    
    if (categorie && mappingAlimentation[categorie] && mappingAlimentation[categorie].length > 0) {
          mappingAlimentation[categorie].forEach(function(item) {
            let option = document.createElement('option');
            option.value = item.code;
            option.textContent = item.label;
            sousCategorieSelect.appendChild(option);
          });
        }
      });

// Ne pas déclencher l'événement au chargement initial
// categoriePrincipaleSelect.dispatchEvent(new Event('change'));

      /* ----- Initialisation de la carte ----- */
      const map = L.map('map', {
        wheelDebounceTime: 100,        // Réactivité améliorée
        wheelPxPerZoomLevel: 400,      // Sensibilité réduite
        zoomSnap: 0.25,               // Niveaux de zoom plus fins
        zoomDelta: 0.25,              // Transitions plus douces
        smoothWheelZoom: true,        // Activation du zoom progressif
        smoothSensitivity: 2,         // Contrôle de la vitesse de zoom
        maxZoom: 19,                  // Limite maximale standard OSM
        minZoom: 3,                   // Limite minimale standard OSM
        bounceAtZoomLimits: false     // Pas de rebond aux limites
      }).setView([46.603354, 1.888334], 6);

      // Variables de contrôle du zoom
      let isUserZooming = false;
      let lastZoomTime = Date.now();
      const ZOOM_COOLDOWN = 100; // Réduit à 100ms pour plus de réactivité

      // Gestion des événements de zoom
      map.on('zoomstart', function(e) {
        if (Date.now() - lastZoomTime < ZOOM_COOLDOWN) {
          return false;
        }
        isUserZooming = true;
        lastZoomTime = Date.now();
      });

      map.on('zoomend', function() {
        setTimeout(() => {
          isUserZooming = false;
        }, ZOOM_COOLDOWN);
      });

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors'
      }).addTo(map);

      // Styles CSS pour les animations des clusters
      const style = document.createElement('style');
      style.textContent = `
        .marker-cluster-animating {
          transition: all 0.3s ease-out;
        }
        .cluster-anim-forward {
          opacity: 0;
          transform: scale(0.5);
        }
        .cluster-anim-backward {
          opacity: 0;
          transform: scale(1.5);
        }
      `;
      document.head.appendChild(style);

      // Configuration des clusters avec zoom automatique désactivé
      const markerClusterOptions = {
        maxClusterRadius: 60,            // Augmenté pour réduire le nombre de clusters
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,      // Désactivé pour améliorer les performances
        zoomToBoundsOnClick: false,
        spiderfyDistanceMultiplier: 2,
        animate: true,
        animateAddingMarkers: false,     // Désactivé pour améliorer les performances
        disableClusteringAtZoom: 17,     // Réduit pour commencer à décluster plus tôt
        chunkedLoading: true,
        chunkInterval: 100,              // Augmenté pour réduire la charge
        chunkDelay: 50,
        maxClusters: 300,               // Limite le nombre maximum de clusters
        removeOutsideVisibleBounds: true, // Supprime les marqueurs hors écran
        spiderLegPolylineOptions: {
          weight: 1.5,
          color: '#222',
          opacity: 0.5
        }
      };

      // Créer le groupe de clusters avec les nouvelles options
      window.markersLayer = L.markerClusterGroup(markerClusterOptions).addTo(map);

      // 📍 Créer le groupe de clusters pour les points de collecte (ne sera jamais vidé)
      window.collecteLayer = L.markerClusterGroup(markerClusterOptions).addTo(map);

      // Ajouter des gestionnaires d'événements pour une meilleure gestion des animations
      window.markersLayer.on('animationend', function(e) {
          e.target.refreshClusters();
      });

      window.markersLayer.on('spiderfied', function(e) {
          e.cluster.refreshIconOptions();
      });

      // Fonction utilitaire pour trouver le cluster parent
      function findParentCluster(marker) {
          let parentCluster = null;
          window.markersLayer.eachLayer((layer) => {
              if (layer instanceof L.MarkerCluster && layer.getAllChildMarkers().includes(marker)) {
                  if (!parentCluster || layer._zoom > parentCluster._zoom) {
                      parentCluster = layer;
                  }
              }
          });
          return parentCluster;
      }

      // Gérer les clics sur les clusters
      window.markersLayer.on('clusterclick', function(e) {
          if (isUserZooming) {
              e.preventDefault();
              return false;
          }

          const cluster = e.layer;
          const markers = cluster.getAllChildMarkers();
          
          if (markers.length > 10) {
              // Pour les grands clusters, zoom progressif
              e.layer.zoomToBounds({
                  animate: true,
                  duration: 0.5
              });
          } else {
              // Pour les petits clusters, déployer directement
              e.layer.spiderfy();
          }
      });

      // Fonctions utilitaires pour le clustering
      function construireAdresse(etablissement) {
        const adresseObj = etablissement.adresseEtablissement || {};
        return `${adresseObj.numeroVoieEtablissement || ''} ${adresseObj.typeVoieEtablissement || ''} ${adresseObj.libelleVoieEtablissement || ''}, ${adresseObj.codePostalEtablissement || ''} ${adresseObj.libelleCommuneEtablissement || ''}`.trim();
      }

      function getMarkerHtml(etablissement) {
        const ul = etablissement.uniteLegale || {};
        const statutCode = ul.etatAdministratifUniteLegale;
        const isActif = statutCode === "A";
        const color = isActif ? "#4CAF50" : "#F44336";
        
        return `
          <div class="custom-marker" style="background-color: ${color}; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
            <span>${isActif ? "A" : "F"}</span>
          </div>
        `;
      }

      function creerContenuPopup(etablissement, ul) {
        const adresseObj = etablissement.adresseEtablissement || {};
        const adresse = construireAdresse(etablissement);
        const nom = ul.denominationUniteLegale || ul.nomUniteLegale || "Nom inconnu";
        const statutCode = ul.etatAdministratifUniteLegale;
        const statut = statutCode === "A" ? "Actif" : "Fermé";
        const statutClass = statutCode === "A" ? "statut-actif" : "statut-ferme";

        return `
          <div class="popup-content">
            <h5>${nom}</h5>
            <p><strong>Statut:</strong> <span class="${statutClass}">${statut}</span></p>
            <p><strong>Adresse:</strong> ${adresse}</p>
            <button class="btn btn-sm btn-primary plus-details-btn" data-siret="${etablissement.siret}">Plus de détails</button>
          </div>
        `;
      }

      /* ----- Fonction de reverse géocodage pour récupérer la ville et l'adresse à partir des coordonnées ----- */
      function reverseGeocode(lon, lat, callback) {
        // Cette fonction récupère une adresse à partir de coordonnées GPS via l'API Adresse.
        var url = `https://api-adresse.data.gouv.fr/reverse/?lon=${lon}&lat=${lat}`;
        fetch(url)
          .then(response => response.json())
          .then(data => {
            console.log("Réponse reverse geocode :", data);
            // Je logue la réponse pour vérifier ce que l'API renvoie.
            if (data.features && data.features.length > 0) {
              let prop = data.features[0].properties;
              let city = prop.city || prop.label || "Ville inconnue";
              let address = prop.housenumber ? `${prop.housenumber} ${prop.street || ''}`.trim() : prop.street || "Adresse inconnue";
              // J'extrais la ville et l'adresse, avec des valeurs par défaut si elles manquent.
              callback(city, address);
            } else {
              callback("Ville inconnue", "Adresse inconnue");
              // Si rien n'est trouvé, je renvoie des valeurs par défaut.
            }
          })
          .catch(error => {
            console.error("Erreur lors du reverse géocodage :", error);
            callback("Ville inconnue", "Adresse inconnue");
            // En cas d'erreur, je logue et renvoie des valeurs par défaut.
          });
      }

      /* ----- Fonction pour récupérer l'adresse IP de l'utilisateur ----- */
      function getUserIP(callback) {
        // Cette fonction récupère l'IP publique via une API externe.
        fetch("https://api64.ipify.org?format=json")
          .then(response => response.json())
          .then(data => callback(data.ip))
          // Je renvoie l'IP récupérée via le callback.
          .catch(error => {
            console.error("Erreur lors de la récupération de l'adresse IP :", error);
            callback("IP inconnue");
            // En cas d'erreur, je logue et renvoie une valeur par défaut.
          });
      }

      /* ----- Fonction pour récupérer les informations du navigateur ----- */
      function getBrowserInfo() {
        // Je détecte le navigateur et sa version à partir de l'user-agent.
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

      /* ----- Définition de l'icône personnalisée pour la position de l'utilisateur ----- */
      const userIcon = L.divIcon({
        className: 'user-location-marker',
        html: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 36" width="24" height="36">
                <path d="M12 0C5.373 0 0 5.373 0 12c0 10 12 24 12 24s12-14 12-24c0-6.627-5.373-12-12-12z" 
                      fill="#ff0000" 
                      stroke="#ffffff" 
                      stroke-width="1.5"/>
                <circle cx="12" cy="12" r="4" fill="#ffffff"/>
              </svg>`,
        iconSize: [24, 36],
        iconAnchor: [12, 36],
        popupAnchor: [0, -36]
      });

      // Ajout des styles pour gérer les z-index
      const zIndexStyles = document.createElement('style');
      zIndexStyles.textContent = `
        .user-location-marker {
          z-index: 300 !important;
        }
        .leaflet-marker-icon:not(.user-location-marker) {
          z-index: 400 !important;
        }
        .marker-cluster {
          z-index: 450 !important;
        }
        .leaflet-popup {
          z-index: 500 !important;
        }
      `;
      document.head.appendChild(zIndexStyles);

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
            // Si c'est la première mise à jour, je complète la popup avec plus d'infos.
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

        // Je tente d'abord une localisation rapide avec getCurrentPosition
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
            maximumAge: 60000 // Accepte une position mise en cache jusqu'à 1 minute
          }
        );
      }

      /* ----- Gestion de la soumission du formulaire de recherche ----- */
      document.getElementById('formulaire-adresse').addEventListener('submit', function(e) {
        // Quand l'utilisateur clique sur "Rechercher", je lance cette fonction.
        e.preventDefault();
        // J'empêche le rechargement de la page par défaut du formulaire.
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
        // Je vérifie que la ville et le secteur sont remplis, sinon j'arrête.

        let query = (adresseRecherche === "" || adresseRecherche === "Non renseigné") ? villeRecherche : adresseRecherche + " " + villeRecherche;
        // Je construis la requête : ville seule si pas d'adresse, sinon adresse + ville.
        rechercherAdresse(query, villeRecherche);
        // Je lance la recherche avec ces paramètres.
      });

      // Code pour le bouton effacer-recherche
      document.getElementById('effacer-recherche').addEventListener('click', function() {
        champVille.value = "";
        champAdresse.value = "";
        // Vérifie si champNomEntreprise existe avant de le modifier
        if (document.getElementById('champ-nom-entreprise')) {
          document.getElementById('champ-nom-entreprise').value = "";
        }
        rayonSelect.selectedIndex = 0;
        categoriePrincipaleSelect.selectedIndex = 0;
        sousCategorieSelect.innerHTML = '<option value="">-- Sous-Secteur --</option>';
        if (window.markersLayer) {
          window.markersLayer.clearLayers(); // Supprime tous les marqueurs
        }
        document.getElementById('resultats-api').innerHTML = '';
        if (searchCircle) {
          map.removeLayer(searchCircle);
        }
      });
      /* ----- Fonction d'affichage des résultats d'adresse et lancement de la recherche d'entreprises ----- */
      function afficherResultats(data, ville) {
        // Cette fonction affiche les résultats de l'API Adresse et lance la recherche d'entreprises.
        var conteneur = document.getElementById('resultats-api');
        conteneur.innerHTML = '';
        // Je vide la zone des résultats avant d'ajouter du nouveau contenu.
        window.markersLayer.clearLayers();
        // Je supprime tous les marqueurs précédents de la carte.
        let features = data.features;
        if ((champAdresse.value.trim() === "" || champAdresse.value.trim() === "Non renseigné") && ville !== "") {
          features = [features[0]];
        }
        // Si pas d'adresse précisée, je prends juste le premier résultat.

        if (features && features.length > 0) {
          features.forEach(async function(feature) {
            let propriete = feature.properties;
            let lat = feature.geometry.coordinates[1];
            let lng = feature.geometry.coordinates[0];
            let citycode = propriete.citycode;
            let postcode = propriete.postcode;
            // J'extrais les infos utiles de chaque résultat (coords, code postal, etc.).

            const zoneData = await recupererZone(propriete.city, conteneur);
            // J'attends les infos de région et département pour cette ville.

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
            // Je crée une div pour chaque résultat et l'ajoute au conteneur.

            recupererEntreprises(postcode, divResultat, ville);
            // Je cherche les entreprises dans ce code postal.
          });
        } else {
          conteneur.innerHTML = '<p>Aucun résultat trouvé.</p>';
          // Si pas de résultats, j'affiche un message.
        }
      }

      /* ----- Fonction de recherche via l'API Base Adresse ----- */
      function rechercherAdresse(query, ville) {
        console.log("Recherche Base Adresse pour : ", ville);
        var url = 'https://api-adresse.data.gouv.fr/search/?q=' + encodeURIComponent(ville) + '&type=municipality';

        fetch(url)
            .then(response => response.json())
            .then(data => {
                console.log("Résultats Base Adresse : ", data);
                afficherResultats(data, ville);

                if (userPosition && rayonSelect.value) {
                    if (searchCircle) {
                        map.removeLayer(searchCircle);
                    }
                    const rayonEnKm = parseFloat(rayonSelect.value);
                    searchCircle = L.circle([userPosition.lat, userPosition.lon], {
                        radius: rayonEnKm * 1000,
                        color: 'blue',
                        fillColor: 'blue',
                        fillOpacity: 0.1,
                        weight: 2
                    }).addTo(map);
                } else if (searchCircle) {
                    map.removeLayer(searchCircle);
                    searchCircle = null;
                }
            })
            .catch(error => {
                console.error("Erreur lors de la récupération des données :", error);
            });
      }

      /* ----- Fonction pour récupérer les informations de zone via l'API Geo ----- */
      function recupererZone(ville, conteneur) {
        // Cette fonction récupère les infos de région et département via l'API Geo.
        var urlGeo = `https://geo.api.gouv.fr/communes?nom=${encodeURIComponent(ville)}&fields=nom,centre,departement,region&format=json`;
        return fetch(urlGeo)
          .then(response => response.json())
          .then(data => {
            if (data && data.length > 0) {
              let departement = data[0].departement ? data[0].departement.nom : "Non renseigné";
              let region = data[0].region ? data[0].region.nom : "Non renseigné";
              afficherZone(data[0], conteneur);
              // J'affiche les infos dans le conteneur.
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
              // Si rien n'est trouvé, je renvoie des valeurs par défaut.
            }
          })
          .catch(error => {
            console.error("Erreur lors de la récupération des données de la zone :", error);
            return {
              departement: "Non renseigné",
              region: "Non renseigné"
            };
            // En cas d'erreur, je logue et renvoie des valeurs par défaut.
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
        // Si un emplacement pour la zone existe, je l'utilise (mais ici, je n'en ai pas).

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
        // Je supprime l'ancien marqueur du centre-ville s'il existe.

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
          // J'ajoute un marqueur pour le centre-ville avec une icône personnalisée.
        }
      }

      /* Fonction pour récupérer les entreprises via l'API Sirene */
      function recupererEntreprises(postcode, conteneur, ville) {
        let themeDetail = sousCategorieSelect.value;
        let categoriePrincipale = categoriePrincipaleSelect.value;
        let nomEntreprise = document.getElementById('champ-nom-entreprise').value.trim();
        let q = "";

        // Construction de la requête de base avec le code postal
        if (ville.toUpperCase() === "GRENOBLE") {
            q = '(codePostalEtablissement:"38000" OR codePostalEtablissement:"38100")';
        } else {
            q = 'codePostalEtablissement:"' + postcode + '"';
        }

        // Ajout du filtre sur la commune
        if (ville && ville.trim() !== '') {
            // Traitement spécial pour les villes avec arrondissements (Paris, Lyon, Marseille)
            if (ville.toUpperCase().includes("PARIS")) {
                q += ' AND libelleCommuneEtablissement:"PARIS"';
            } else if (ville.toUpperCase().includes("LYON")) {
                q += ' AND libelleCommuneEtablissement:"LYON"';
            } else if (ville.toUpperCase().includes("MARSEILLE")) {
                q += ' AND libelleCommuneEtablissement:"MARSEILLE"';
            } else {
                q += ' AND libelleCommuneEtablissement:"' + ville.toUpperCase() + '"';
            }
        }

        // Ajout du filtre sur le nom de l'entreprise si spécifié
        if (nomEntreprise !== '') {
            q += ' AND (denominationUniteLegale:"*' + nomEntreprise.toUpperCase() + '*" OR nomUniteLegale:"*' + nomEntreprise.toUpperCase() + '*")';
        }

        // Ajout des filtres de secteur d'activité
        if (themeDetail) {
            q += ' AND activitePrincipaleUniteLegale:"' + themeDetail + '"';
        } else if (categoriePrincipale !== "") {
            let codes = mappingAlimentation[categoriePrincipale].map(item => item.code);
            if (codes.length === 0) {
                console.warn("Aucun code NAF/APE trouvé pour le secteur:", categoriePrincipale);
                return;
            }
            q += ' AND (' + codes.map(code => 'activitePrincipaleUniteLegale:"' + code + '"').join(' OR ') + ')';
        }

        console.log("Filtre Sirene:", q);
        let urlSirene = 'https://api.insee.fr/api-sirene/3.11/siret?q=' + encodeURIComponent(q) + '&nombre=200'; // Augmenté à 200 résultats
        fetch(urlSirene, {
            headers: {
                'X-INSEE-Api-Key-Integration': API_KEY_SIRENE,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error("Réponse non valide de l'API Sirene: " + response.status);
            }
            return response.json();
        })
        .then(data => {
            // Vérifie si data est défini et a une structure attendue
            if (!data || typeof data !== 'object') {
                console.error("Réponse invalide de l'API Sirene:", data);
                afficherEntreprises({ etablissements: [] }, conteneur);
                return;
            }
            let etablissements = data.etablissements || [];
            if (!Array.isArray(etablissements)) {
                console.warn("Les établissements ne sont pas un tableau, traitement comme vide:", etablissements);
                etablissements = [];
            }

            if (filtreActifs.checked) {
                etablissements = etablissements.filter(function(etablissement) {
                    let statut = etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0 ?
                        etablissement.periodesEtablissement[0].etatAdministratifEtablissement :
                        "";
                    return statut === "A";
                });
            }

            if (userPosition && rayonSelect.value) {
                let rayon = parseFloat(rayonSelect.value);
                etablissements = etablissements.filter(function(etablissement) {
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

            console.log("Résultats Sirene:", etablissements);
            afficherEntreprises({ etablissements: etablissements }, conteneur);
            ajouterMarqueursEntreprises({ etablissements: etablissements });
        })
        .catch(error => {
            console.error("Erreur lors de la récupération des données Sirene :", error);
            afficherEntreprises({ etablissements: [] }, conteneur);
        });
    }

      /* Fonction pour afficher les entreprises dans le bloc résultats */
      function afficherEntreprises(data, conteneur) {
          let divEntreprises = conteneur.querySelector('.entreprises');
          if (!divEntreprises) {
              divEntreprises = document.createElement('div');
              divEntreprises.className = 'entreprises mt-3 p-3 border-top';
              conteneur.appendChild(divEntreprises);
          }

          let etablissements = data.etablissements || [];
          if (!Array.isArray(etablissements)) {
              console.warn("Les établissements ne sont pas un tableau, traitement comme vide:", etablissements);
              etablissements = [];
          }

          if (etablissements.length > 0) {
              let html = '<p><strong>Entreprises locales :</strong></p>';
              let themeGeneralText = (categoriePrincipaleSelect.selectedIndex > 0) ?
                  categoriePrincipaleSelect.selectedOptions[0].text :
                  "Non précisé";
              let themeDetailText = (sousCategorieSelect.value !== "") ?
                  sousCategorieSelect.selectedOptions[0].text :
                  "Non précisé";

              etablissements.forEach(function(etablissement) {
                  let ul = etablissement.uniteLegale || {};
                  let commune = (etablissement.adresseEtablissement && etablissement.adresseEtablissement.libelleCommuneEtablissement) || "Non renseigné";
                  let adresseObj = etablissement.adresseEtablissement || {};
                  
                  // Conversion des coordonnées Lambert93 en WGS84
                  let latitude = null;
                  let longitude = null;
                  if (adresseObj.coordonneeLambertAbscisseEtablissement && adresseObj.coordonneeLambertOrdonneeEtablissement) {
                      const x = parseFloat(adresseObj.coordonneeLambertAbscisseEtablissement);
                      const y = parseFloat(adresseObj.coordonneeLambertOrdonneeEtablissement);
                      const result = proj4("EPSG:2154", "EPSG:4326", [x, y]);
                      longitude = result[0];
                      latitude = result[1];
                  }

                  let numero = adresseObj.numeroVoieEtablissement || '';
                  let typeVoie = adresseObj.typeVoieEtablissement || '';
                  let libelleVoie = adresseObj.libelleVoieEtablissement || '';
                  let codePostal = adresseObj.codePostalEtablissement || '';
                  let adresseComplete = (numero || typeVoie || libelleVoie) ?
                      ((numero + " " + typeVoie + " " + libelleVoie).trim() + ", " + codePostal + " " + commune) :
                      "Non renseigné";

                  let periode = (etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0) ?
                      etablissement.periodesEtablissement[0] : {};
                  let dateDebut = periode.dateDebut || "Non renseigné";
                  let dateFin = periode.dateFin || "...";
                  let statutCode = (etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0) ?
                      etablissement.periodesEtablissement[0].etatAdministratifEtablissement : '';

                  let statutClass = "";
                  let statutText = "Non précisé";
                  if (statutCode === 'A') {
                      statutClass = "statut-actif";
                      statutText = "En Activité";
                  } else if (statutCode === 'F') {
                      statutClass = "statut-ferme";
                      statutText = "Fermé";
                  }

                  let siren = etablissement.siren || 'N/A';
                  let siret = etablissement.siret || 'N/A';
                  let dateCreationUniteLegale = ul.dateCreationUniteLegale || "Non renseigné";

                  // Ajout des attributs data-lat et data-lon pour le clic
                  html += `<div class="card mb-2 entreprise-card" style="cursor: pointer;" 
                               data-siret="${siret}" 
                               data-lat="${latitude}" 
                               data-lon="${longitude}">
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
              });

              divEntreprises.innerHTML = html;

              // Ajout des gestionnaires d'événements pour les clics
              document.querySelectorAll('.entreprise-card').forEach(card => {
                  card.addEventListener('click', async function() {
                      // Retirer la classe active des autres cartes
                      document.querySelectorAll('.entreprise-card.active').forEach(c => c.classList.remove('active'));
                      
                      // Ajouter la classe active à la carte cliquée
                      this.classList.add('active');
                      
                      const lat = parseFloat(this.dataset.lat);
                      const lon = parseFloat(this.dataset.lon);
                      
                      let targetMarker = null;
                      let parentCluster = null;

                      // Trouver le marqueur correspondant
                      window.markersLayer.eachLayer(function(layer) {
                          if (layer.getLatLng && 
                              layer.getLatLng().lat === lat && 
                              layer.getLatLng().lng === lon) {
                              targetMarker = layer;
                          }
                      });

                      if (targetMarker) {
                          // Trouver le cluster parent si le marqueur est clustérisé
                          parentCluster = findParentCluster(targetMarker);
                          
                          if (parentCluster) {
                              // Zoom progressif sur le cluster
                              const bounds = parentCluster.getBounds();
                              await new Promise(resolve => {
                                  map.once('moveend', resolve);
                                  map.fitBounds(bounds, {
                                      maxZoom: map.getZoom(),
                                      animate: true,
                                      duration: 0.5
                                  });
                              });

                              // Attendre que le cluster soit déployé
                              await new Promise(resolve => {
                                  if (parentCluster.spiderfy) {
                                      parentCluster.once('spiderfied', resolve);
                                      parentCluster.spiderfy();
                                  } else {
                                      resolve();
                                  }
                              });
                          }

                          // Zoom final sur le marqueur
                          map.setView(targetMarker.getLatLng(), 16, {
                              animate: true,
                              duration: 0.5
                          });

                          // Ouvrir la popup après un court délai
                          setTimeout(() => {
                              targetMarker.openPopup();
                          }, 500);
                      }
                  });
              });

              setTimeout(() => {
                  document.querySelectorAll(".statut-actif").forEach(el => el.style.color = "green");
                  document.querySelectorAll(".statut-ferme").forEach(el => el.style.color = "red");
              }, 500);
          } else {
              divEntreprises.innerHTML = '<p>Aucune entreprise locale trouvée pour ce secteur ou cette localisation.</p>';
          }
      }

      /* Fonction pour ajouter les marqueurs des entreprises */
      function ajouterMarqueursEntreprises(data) {
        const etablissements = data.etablissements || [];
        const maxMarkers = 200; // Augmenté à 200 marqueurs
        
        // Nettoyer les marqueurs existants
        window.markersLayer.clearLayers();
        
        // Trier les établissements par statut (actifs en premier)
        const etablissementsTries = etablissements
          .sort((a, b) => {
            const statutA = a.periodesEtablissement?.[0]?.etatAdministratifEtablissement === 'A' ? 1 : 0;
            const statutB = b.periodesEtablissement?.[0]?.etatAdministratifEtablissement === 'A' ? 1 : 0;
            return statutB - statutA;
          })
          .slice(0, maxMarkers); // Limite le nombre de marqueurs

        etablissementsTries.forEach(function(etablissement) {
          const ul = etablissement.uniteLegale || {};
          let latitude = null;
          let longitude = null;

          // Conversion des coordonnées Lambert93 en WGS84 si disponibles
          if (etablissement.adresseEtablissement && 
              etablissement.adresseEtablissement.coordonneeLambertAbscisseEtablissement && 
              etablissement.adresseEtablissement.coordonneeLambertOrdonneeEtablissement) {
            const x = parseFloat(etablissement.adresseEtablissement.coordonneeLambertAbscisseEtablissement);
            const y = parseFloat(etablissement.adresseEtablissement.coordonneeLambertOrdonneeEtablissement);
            const result = proj4("EPSG:2154", "EPSG:4326", [x, y]);
            longitude = result[0];
            latitude = result[1];
          }

          // Si pas de coordonnées Lambert93, on utilise l'adresse pour géocoder
          if (!latitude || !longitude) {
            const adresse = construireAdresse(etablissement);
            obtenirCoordonneesParAdresse(adresse, function(lat, lon) {
                          if (lat && lon) {
                ajouterMarqueur(lat, lon, etablissement, ul);
              }
            });
          } else {
            ajouterMarqueur(latitude, longitude, etablissement, ul);
          }
        });

        // Ajuster la vue avec un délai pour permettre le chargement des clusters
        setTimeout(() => {
          if (window.markersLayer.getBounds().isValid()) {
            map.fitBounds(window.markersLayer.getBounds(), {
              padding: [50, 50],
              maxZoom: 13
            });
          }
        }, 100);
      }

      function ajouterMarqueur(latitude, longitude, etablissement, ul) {
        // Réduire le décalage aléatoire
        latitude += (Math.random() - 0.5) * 0.00005;
        longitude += (Math.random() - 0.5) * 0.00005;

        const marker = L.marker([latitude, longitude], {
          icon: L.divIcon({
            className: 'custom-div-icon',
            html: getMarkerHtml(etablissement),
            iconSize: [30, 42],
            iconAnchor: [15, 42]
          })
        });

        // Optimisation des popups : création à la demande
        let popup = null;
        marker.on('click', function() {
          if (!popup) {
            popup = L.popup({
              maxWidth: 250,
              minWidth: 200,
              className: 'popup-entreprise',
              autoPan: true,
              autoPanPadding: [20, 20]
            }).setContent(creerContenuPopup(etablissement, ul));
          }
          marker.bindPopup(popup).openPopup();
        });

        // Supprimer les événements de survol pour réduire la charge
        marker.off('mouseover');
        marker.off('mouseout');

        window.collecteLayer.addLayer(marker);
      }

      /* ----- Fonction pour géocoder une adresse via l'API Adresse ----- */
      function obtenirCoordonneesParAdresse(adresse, callback) {
        // Cette fonction récupère les coords GPS d'une adresse quand Lambert93 manque.
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
              // Si rien n'est trouvé, je renvoie null.
            }
          })
          .catch(error => {
            console.error("Erreur API Adresse :", error);
            callback(null, null);
            // En cas d'erreur, je logue et renvoie null.
          });
      }

      /* Fonction pour ajouter un marqueur sur la carte */
      function ajouterMarqueur(lat, lon, etablissement) {
          // Cette fonction crée un marqueur avec une popup allégée au survol, sans déplacer la carte, avec un délai de fermeture de 2 secondes, et une popup détaillée au clic sur "Plus de détails", centrée sur la popup.
          let ul = etablissement.uniteLegale || {};
          let activitePrincipale = ul.activitePrincipaleUniteLegale || "Non renseigné";
          let categorieEntreprise = ul.categorieEntreprise || "Non renseigné";
          let dateCreationUniteLegale = ul.dateCreationUniteLegale || "Non renseigné";
          let periode = etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0
                        ? etablissement.periodesEtablissement[0]
                        : {};
          let dateDebut = periode.dateDebut || "Non renseigné";
          let dateFin = periode.dateFin || "...";
          let siren = etablissement.siren || 'N/A';
          let siret = etablissement.siret || 'N/A';
          let commune = etablissement.adresseEtablissement.libelleCommuneEtablissement || 'N/A';
          let numero = etablissement.adresseEtablissement.numeroVoieEtablissement || '';
          let typeVoie = etablissement.adresseEtablissement.typeVoieEtablissement || '';
          let libelleVoie = etablissement.adresseEtablissement.libelleVoieEtablissement || '';
          let codePostal = etablissement.adresseEtablissement.codePostalEtablissement || '';
          let adresseComplete = numero || typeVoie || libelleVoie
              ? (numero + " " + typeVoie + " " + libelleVoie).trim() + ", " + codePostal + " " + commune
              : "Non renseigné";

          let statutCode = etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0
                           ? etablissement.periodesEtablissement[0].etatAdministratifEtablissement
                           : '';
          let statutClass = "";
          let statutText = "Non précisé";
          if (statutCode === 'A') {
              statutClass = "statut-actif";
              statutText = "En Activité";
          } else if (statutCode === 'F') {
              statutClass = "statut-ferme";
              statutText = "Fermé";
          }

          let themeGeneralText = categoriePrincipaleSelect.selectedIndex > 0
              ? categoriePrincipaleSelect.selectedOptions[0].text
              : "Non précisé";
          let themeDetailText = sousCategorieSelect.value !== ""
              ? sousCategorieSelect.selectedOptions[0].text
              : "Non précis";

          // Contenu allégé pour la popup au survol
          let popupContentAllgee = `
              <div style="font-weight:bold; font-size:1.1em; max-width: 200px; overflow-wrap: break-word;">
                  ${ul.denominationUniteLegale || ul.nomUniteLegale || 'Nom non disponible'}
              </div>
              <strong>Commune :</strong> ${commune || "Non renseigné"}<br>
              <strong>Adresse :</strong> ${adresseComplete}<br>
              <strong>Secteurs :</strong> ${themeGeneralText}<br>`;
          if (userPosition) {
              let distance = haversineDistance(userPosition.lat, userPosition.lon, lat, lon);
              popupContentAllgee += `<strong>Distance :</strong> ${distance.toFixed(2)} km<br>`;
          }
          
          // Encodage sécurisé des données de l'établissement pour l'attribut data
          const etablissementData = JSON.stringify(etablissement).replace(/'/g, "&#39;").replace(/"/g, "&quot;");
          popupContentAllgee += `<button class="btn btn-primary btn-sm mt-2 plus-details-btn" data-lat="${lat}" data-lon="${lon}" data-etablissement="${etablissementData}">Plus de détails</button>`;

          // Contenu complet pour la popup détaillée
          let popupContentDetaillee = `
              <div style="font-weight:bold; font-size:1.2em;">
                  ${ul.denominationUniteLegale || ul.nomUniteLegale || 'Nom non disponible'}
              </div>
              <strong>Commune :</strong> ${commune || "Non renseigné"}<br>
              <strong>Adresse :</strong><br> ${adresseComplete}<br>
              <strong>Secteurs :</strong> ${themeGeneralText}<br>
              <strong>Sous-Secteur :</strong> ${themeDetailText}<br>`;
          if (userPosition) {
              let distance = haversineDistance(userPosition.lat, userPosition.lon, lat, lon);
              popupContentDetaillee += `<strong style="color:blue;">Distance :</strong> ${distance.toFixed(2)} km<br>`;
          }
          popupContentDetaillee += `<br>
                           <strong>Statut :</strong> <strong class="${statutClass}">${statutText}</strong><br>
                           <strong>Date de création :</strong> ${dateCreationUniteLegale}<br>
                           <strong>Date de validité des informations :</strong> ${dateDebut} à ${dateFin}<br>
                           <strong>SIREN :</strong> ${siren}<br>
                           <strong>SIRET :</strong> ${siret}<br>
                           <strong>Code NAF/APE :</strong> ${activitePrincipale}`;

          let marqueur = L.marker([lat, lon]).addTo(window.markersLayer);
          let popupAllgee = L.popup({
              autoPan: false, // Pas de déplacement de la carte au survol
              maxWidth: 250,
              minWidth: 200,
              className: 'popup-entreprise'
          }).setContent(popupContentAllgee);
          marqueur.bindPopup(popupAllgee);

          let timeoutId = null; // Pour gérer le délai de fermeture

          marqueur.on('mouseover', function() {
          // Ouvre la popup au survol après un délai de 0,5 seconde, sans déplacer ni centrer la carte.
          let timeoutId = null; // Pour gérer le délai d'ouverture
          timeoutId = setTimeout(() => {
              this.openPopup();
          }, 500); // Délai de 0,5 seconde

          // Annule le délai si la souris quitte avant l'ouverture
          marqueur.on('mouseout', function() {
              if (timeoutId) {
                  clearTimeout(timeoutId);
              }
          });
      });

          marqueur.on('mouseout', function() {
              // Ferme la popup après un délai de 2 secondes quand la souris quitte le marqueur.
              timeoutId = setTimeout(() => {
                  this.closePopup();
              }, 2000); // Délai de 2 secondes
          });

          // Ajout d'un écouteur d'événements pour le bouton "Plus de détails" avec gestion robuste
          document.addEventListener('click', function(e) {
          if (e.target.classList.contains('plus-details-btn')) {
              try {
                  // Efface le délai de fermeture éventuel
                  if (timeoutId) {
                      clearTimeout(timeoutId);
                  }
                  
                  // Récupère et décode les données depuis les attributs data
                  const latitude = parseFloat(e.target.dataset.lat);
                  const longitude = parseFloat(e.target.dataset.lon);
                  
                  // Décode les entités HTML puis parse le JSON
                  const etablissementStr = e.target.dataset.etablissement
                      .replace(/&quot;/g, '"')
                      .replace(/&#39;/g, "'");
                  const etablissement = JSON.parse(etablissementStr);

                  // Reconstitue le contenu détaillé de la popup à partir des données de l'établissement
                  const uniteLegale = etablissement.uniteLegale || {};
                  const activitePrincipale = uniteLegale.activitePrincipaleUniteLegale || "Non renseigné";
                  const dateCreation = uniteLegale.dateCreationUniteLegale || "Non renseigné";
                  const periode = (etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0)
                                  ? etablissement.periodesEtablissement[0]
                                  : {};
                  const dateDebut = periode.dateDebut || "Non renseigné";
                  const dateFin = periode.dateFin || "...";
                  const siren = etablissement.siren || 'N/A';
                  const siret = etablissement.siret || 'N/A';
                  const adresseObj = etablissement.adresseEtablissement || {};
                  const commune = adresseObj.libelleCommuneEtablissement || 'N/A';
                  const numero = adresseObj.numeroVoieEtablissement || '';
                  const typeVoie = adresseObj.typeVoieEtablissement || '';
                  const libelleVoie = adresseObj.libelleVoieEtablissement || '';
                  const codePostal = adresseObj.codePostalEtablissement || '';
                  const adresseComplete = (numero || typeVoie || libelleVoie)
                      ? ((numero + " " + typeVoie + " " + libelleVoie).trim() + ", " + codePostal + " " + commune)
                      : "Non renseigné";

                  let statutCode = (etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0)
                                  ? etablissement.periodesEtablissement[0].etatAdministratifEtablissement
                                  : '';
                  let classeStatut = "";
                  let texteStatut = "Non précisé";
                  if (statutCode === 'A') {
                      classeStatut = "statut-actif";
                      texteStatut = "En Activité";
                  } else if (statutCode === 'F') {
                      classeStatut = "statut-ferme";
                      texteStatut = "Fermé";
                  }

                  const themeGeneral = (categoriePrincipaleSelect.selectedIndex > 0)
                                      ? categoriePrincipaleSelect.selectedOptions[0].text
                                      : "Non précisé";
                  const themeDetail = (sousCategorieSelect.value !== "")
                                      ? sousCategorieSelect.selectedOptions[0].text
                                      : "Non précisé";

                  const distance = userPosition ? haversineDistance(userPosition.lat, userPosition.lon, latitude, longitude) : null;

                  const contenuPopupDetaillee = `
                      <div style="font-weight:bold; font-size:1.2em;">
                          ${uniteLegale.denominationUniteLegale || uniteLegale.nomUniteLegale || 'Nom non disponible'}
                      </div>
                      <strong>Commune :</strong> ${commune}<br>
                      <strong>Adresse :</strong> ${adresseComplete}<br>
                      <strong>Secteurs :</strong> ${themeGeneral}<br>
                      <strong>Sous-Secteur :</strong> ${themeDetail}<br>
                      ${distance ? `<strong style="color:blue;">Distance :</strong> ${distance.toFixed(2)} km<br>` : ''}
                      <br>
                      <strong>Statut :</strong> <strong class="${classeStatut}">${texteStatut}</strong><br>
                      <strong>Date de création :</strong> ${dateCreation}<br>
                      <strong>Date de validité :</strong> ${dateDebut} à ${dateFin}<br>
                      <strong>SIREN :</strong> ${siren}<br>
                      <strong>SIRET :</strong> ${siret}<br>
                      <strong>Code NAF/APE :</strong> ${activitePrincipale}
                  `;

                  // Crée une popup détaillée avec autoPan activé pour centrer la carte
                  const popupDetaillee = L.popup({
                      autoPan: true,
                      autoPanPadding: [20, 20],
                      maxWidth: 250,
                      minWidth: 200,
                      className: 'popup-entreprise'
                  }).setContent(contenuPopupDetaillee);

                  // Ferme toutes les popups actuellement ouvertes
                  map.closePopup();

                  // Crée un objet LatLng et centre la carte dessus
                  const coordPopup = L.latLng(latitude, longitude);
                  map.panTo(coordPopup, { animate: true, duration: 0.5 });

                  // Ouvre la popup détaillée sur la carte
                  popupDetaillee.setLatLng(coordPopup);
                  popupDetaillee.openOn(map);

              } catch (error) {
                  console.error("Erreur lors de la gestion du clic sur le bouton 'Plus de détails':", error);
              }
          }
      });

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

      // Ajouter un écouteur d'événements pour le champ ville
      champVille.addEventListener('input', function() {
          // Vider le champ adresse quand la ville change
          champAdresse.value = "";
      });

      // Modifier la gestion du formulaire
      document.getElementById('formulaire-adresse').addEventListener('submit', function(e) {
          e.preventDefault();
          if (userMarker && userMarker.getPopup()) {
              userMarker.closePopup();
          }
          
          // Vider explicitement le champ adresse
          champAdresse.value = "";
          
          let villeRecherche = champVille.value.trim();
          let categoriePrincipale = categoriePrincipaleSelect.value;

          if (villeRecherche === "") {
              alert("Veuillez entrer une ville");
              return;
          }
          if (categoriePrincipale === "") {
              alert("Veuillez sélectionner un Secteur");
              return;
          }

          // Utiliser uniquement la ville pour la recherche
          rechercherAdresse(villeRecherche, villeRecherche);
      });

      // Modifier le bouton effacer
      document.getElementById('effacer-recherche').addEventListener('click', function() {
          champVille.value = "";
          champAdresse.value = "";
          if (document.getElementById('champ-nom-entreprise')) {
              document.getElementById('champ-nom-entreprise').value = "";
          }
          rayonSelect.selectedIndex = 0;
          categoriePrincipaleSelect.selectedIndex = 0;
          sousCategorieSelect.innerHTML = '<option value="">-- Sous-Secteur --</option>';
          if (window.markersLayer) {
              window.markersLayer.clearLayers();
          }
          document.getElementById('resultats-api').innerHTML = '';
          if (searchCircle) {
              map.removeLayer(searchCircle);
          }
      });
    });
  </script>
<!-- 📍 NEW : points de collecte + réservation -->
<script>
document.addEventListener("DOMContentLoaded", () => {
  /* Grandes villes d'Isère (+ Lyon) */
  const villesCollecte = [
    { nom: "Grenoble",             lat: 45.188529, lon: 5.724524,  nb: 3 },
    { nom: "Échirolles",           lat: 45.149000, lon: 5.706200,  nb: 1 },
    { nom: "Saint-Martin-d'Hères", lat: 45.169500, lon: 5.763700,  nb: 1 },
    { nom: "Meylan",               lat: 45.209600, lon: 5.790830,  nb: 1 },
    { nom: "Bourgoin-Jallieu",     lat: 45.589189, lon: 5.280700,  nb: 2 },
    { nom: "Voiron",               lat: 45.364200, lon: 5.589000,  nb: 2 },
    { nom: "Vienne",               lat: 45.525700, lon: 4.874280,  nb: 2 },
    { nom: "Lyon",                 lat: 45.757813, lon: 4.832011,  nb: 3 }
  ];

  /* --------------------------------------------------------- *
   *  Icône, création des marqueurs, Flatpickr, validation      *
   * --------------------------------------------------------- */
  let idCompteur = 0;
  const iconeCollecte = L.divIcon({
    className : 'custom-div-icon',
    html      : `<div class="custom-marker"
                      style="background:#1E88E5;width:30px;height:30px;
                             border-radius:50%;display:flex;
                             align-items:center;justify-content:center;
                             color:#fff;font-size:18px;">📦</div>`,
    iconSize  : [30,42],
    iconAnchor: [15,42]
  });

  function creeMarqueurCollecte(ville, index) {
    const lat = ville.lat + (Math.random() - 0.5) * 0.02;
    const lon = ville.lon + (Math.random() - 0.5) * 0.03;
    const pointNom = `${ville.nom} – Point ${index + 1}`;
    const uid = `pc-${++idCompteur}`;

    const marker = L.marker([lat, lon], { icon: iconeCollecte });
    marker.bindPopup(`
      <div style="min-width:230px">
        <h6 class="mb-2">${pointNom}</h6>
        <div class="mb-2">
          <label class="form-label">Jour :</label>
          <input id="${uid}-date" class="form-control" placeholder="Choisir une date">
        </div>
        <div class="mb-2">
          <label class="form-label">Heure :</label>
          <input id="${uid}-time" class="form-control" placeholder="Choisir une heure">
        </div>
        <button class="btn btn-success w-100"
                onclick="validerCollecte('${pointNom}','${uid}')">
          Valider
        </button>
      </div>`);

    marker.on('popupopen', () => {
      flatpickr(`#${uid}-date`, {
        locale: "fr",
        dateFormat: "Y-m-d",
        disable: [d => d.getDay() === 0],          // pas de dimanche
        minDate: "today",
        maxDate: new Date().fp_incr(60)            // 60 jours maxi
      });
      flatpickr(`#${uid}-time`, {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true,
        minTime: "09:00",
        maxTime: "20:00"
      });
    });

    window.collecteLayer.addLayer(marker);
  }

  villesCollecte.forEach(v => {
    for (let i = 0; i < v.nb; i++) creeMarqueurCollecte(v, i);
  });

  window.validerCollecte = (nomPoint, uid) => {
    const jour  = document.getElementById(`${uid}-date`).value;
    const heure = document.getElementById(`${uid}-time`).value;
    if (!jour || !heure) {
      alert("Merci de choisir une date ET une heure entre 9 h et 20 h.");
      return;
    }
    alert(`✅ Créneau réservé :\n${nomPoint}\n${jour} à ${heure}`);
  };
});
</script>

<!-- Inclusion du footer avec lien git -->
<?php 
$gitUrl = "https://git.freewebworld.fr/dimitri.f/projet_annuel_b2_localodrive";
$mainSiteUrl = "https://localodrive.fr/";
include '../includes/footer.php';
?>
  </body>
</html>