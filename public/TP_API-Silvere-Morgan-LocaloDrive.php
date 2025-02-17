<?php
// V 15.2 : Affichage amélioré des résultats - Affichage de "Non renseigné" pour l'adresse si vide - Encadrement de chaque entreprise dans une card Bootstrap avec le nom en bleu
// TP_API-Silvere-Morgan-LocaloDrive.php

require_once __DIR__ . '/../vendor/autoload.php'; // J'inclus l'autoloader de Composer
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../'); // Chargement des variables d'environnement depuis la racine du projet
$dotenv->load();
$API_KEY_SIRENE = $_ENV['API_KEY_SIRENE'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Localo'Drive - Recherche et Carte</title>
  <!-- J'inclus Bootstrap et mon CSS personnalisé -->
  <link rel="stylesheet" href="../node_modules/bootstrap/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="styles.css">
  <!-- CSS de Leaflet pour la carte -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <!-- Je charge proj4js pour la conversion de coordonnées -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.7.5/proj4.js"></script>
  <script>
    // Je définis la projection EPSG:2154 pour la France métropolitaine
    proj4.defs("EPSG:2154", "+proj=lcc +lat_1=44 +lat_2=49 +lat_0=46.5 +lon_0=3 +x_0=700000 +y_0=6600000 +ellps=GRS80 +units=m +no_defs");
  </script>
</head>
<body>

<script>
  // Je passe ma clé API de PHP à JavaScript en toute sécurité
  const API_KEY_SIRENE = "<?php echo htmlspecialchars($API_KEY_SIRENE, ENT_QUOTES, 'UTF-8'); ?>";
</script>

<div class="container mt-4">
  <h1 class="text-center">Localo'Drive - Recherche et Carte</h1>
  <p class="text-center">Faciliter l'accès aux produits locaux en connectant producteurs et consommateurs</p>

  <!-- Formulaire de recherche -->
  <form id="formulaire-adresse" class="d-flex flex-wrap justify-content-center mb-4">
    <input type="text" id="champ-ville" class="form-control me-2 mb-2" placeholder="Ville" style="max-width:300px;">
    <input type="text" id="champ-adresse" class="form-control me-2 mb-2" placeholder="Adresse (facultatif)" style="max-width:300px;">
    <select id="rayon-select" class="form-select me-2 mb-2" style="max-width:200px;">
      <option value="">-- Rayon de recherche --</option>
      <option value="3">3 km</option>
      <option value="5">5 km</option>
      <option value="10">10 km</option>
    </select>
    <select id="categorie-principale" class="form-select me-2 mb-2" style="max-width:300px;">
      <option value="">-- Catégorie principale --</option>
      <option value="Production primaire">Production primaire</option>
      <option value="Transformation et fabrication de produits alimentaires">Transformation et fabrication de produits alimentaires</option>
      <option value="Fabrication de boissons">Fabrication de boissons</option>
      <option value="Commerce alimentaire">Commerce alimentaire</option>
      <option value="Restauration et services liés à l’alimentation">Restauration et services liés à l’alimentation</option>
    </select>
    <select id="sous-categorie" class="form-select me-2 mb-2" style="max-width:300px;">
      <option value="">-- Sous-catégorie --</option>
    </select>
    <!-- Case à cocher pour filtrer uniquement sur les établissements en activité -->
    <div class="form-check me-2 mb-2">
      <input class="form-check-input" type="checkbox" id="filtre-actifs">
      <label class="form-check-label" for="filtre-actifs">Filtrer uniquement sur les établissements en activité</label>
    </div>
    <button type="submit" class="btn btn-success mb-2">Rechercher</button>
  </form>

  <!-- Zone d'affichage des résultats et de la carte -->
  <div class="row">
    <div class="col-md-4" id="colonne-resultats">
      <div id="resultats-api"></div>
    </div>
    <div class="col-md-8" id="colonne-carte">
      <div id="map" style="height:500px;"></div>
    </div>
  </div>
</div>

<!-- Inclusion des scripts -->
<script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    // Je déclare la variable pour stocker la position de l'utilisateur
    let userPosition = null;
    // Je récupère les références aux éléments du formulaire
    const champVille = document.getElementById('champ-ville');
    const champAdresse = document.getElementById('champ-adresse');
    const rayonSelect = document.getElementById('rayon-select');
    const categoriePrincipaleSelect = document.getElementById('categorie-principale');
    const sousCategorieSelect = document.getElementById('sous-categorie');
    const filtreActifs = document.getElementById('filtre-actifs');

    // Je définis le mapping des catégories et codes NAF associés
    const mappingAlimentation = {
      "Production primaire": [
        { code: "01.11Z", label: "Culture de céréales (à l'exception du riz), de légumineuses et de graines oléagineuses" },
        { code: "01.12Z", label: "Culture du riz" },
        { code: "01.13Z", label: "Culture de légumes, de melons, de racines et tubercules" },
        { code: "01.19Z", label: "Autres cultures non permanentes" },
        { code: "01.21Z", label: "Culture de la vigne" },
        { code: "01.22Z", label: "Culture de fruits tropicaux et subtropicaux" },
        { code: "01.23Z", label: "Culture d'agrumes" },
        { code: "01.24Z", label: "Culture de fruits à pépins et à noyau" },
        { code: "01.25Z", label: "Culture d'autres fruits d'arbres ou d'arbustes et de fruits à coque" },
        { code: "01.26Z", label: "Culture de fruits oléagineux" },
        { code: "01.27Z", label: "Culture de plantes à boissons" },
        { code: "01.28Z", label: "Culture de plantes à épices, aromatiques, médicinales et pharmaceutiques" },
        { code: "01.29Z", label: "Autres cultures permanentes" },
        { code: "01.30Z", label: "Reproduction de plantes" },
        { code: "01.41Z", label: "Élevage de vaches laitières" },
        { code: "01.42Z", label: "Élevage d'autres bovins et de buffles" },
        { code: "01.43Z", label: "Élevage de chevaux et d'autres équidés" },
        { code: "01.44Z", label: "Élevage de chameaux et d'autres camélidés" },
        { code: "01.45Z", label: "Élevage d'ovins et de caprins" },
        { code: "01.46Z", label: "Élevage de porcins" },
        { code: "01.47Z", label: "Élevage de volailles" },
        { code: "01.49Z", label: "Élevage d'autres animaux" },
        { code: "01.50Z", label: "Culture et élevage associés" },
        { code: "01.61Z", label: "Activités de soutien aux cultures" },
        { code: "01.62Z", label: "Activités de soutien à la production animale" },
        { code: "01.63Z", label: "Traitement primaire des récoltes" },
        { code: "01.64Z", label: "Traitement des semences" },
        { code: "03.11Z", label: "Pêche en mer" },
        { code: "03.12Z", label: "Pêche en eau douce" },
        { code: "03.21Z", label: "Aquaculture en mer" },
        { code: "03.22Z", label: "Aquaculture en eau douce" }
      ],
      "Transformation et fabrication de produits alimentaires": [
        { code: "10.11Z", label: "Transformation et conservation de la viande de boucherie" },
        { code: "10.12Z", label: "Transformation et conservation de la viande de volaille" },
        { code: "10.13A", label: "Préparation industrielle de produits à base de viande" },
        { code: "10.13B", label: "Charcuterie" },
        { code: "10.20Z", label: "Transformation et conservation de poisson, de crustacés et de mollusques" },
        { code: "10.31Z", label: "Transformation et conservation de pommes de terre" },
        { code: "10.32Z", label: "Préparation de jus de fruits et légumes" },
        { code: "10.39A", label: "Autre transformation et conservation de légumes" },
        { code: "10.39B", label: "Transformation et conservation de fruits" },
        { code: "10.41A", label: "Fabrication d'huiles et graisses brutes" },
        { code: "10.41B", label: "Fabrication d'huiles et graisses raffinées" },
        { code: "10.42Z", label: "Fabrication de margarine et graisses comestibles similaires" },
        { code: "10.51A", label: "Fabrication de lait liquide et de produits frais" },
        { code: "10.51B", label: "Fabrication de beurre" },
        { code: "10.51C", label: "Fabrication de fromage" },
        { code: "10.51D", label: "Fabrication d'autres produits laitiers" },
        { code: "10.52Z", label: "Fabrication de glaces et sorbets" },
        { code: "10.61A", label: "Meunerie" },
        { code: "10.61B", label: "Autres activités du travail des grains" },
        { code: "10.62Z", label: "Fabrication de produits amylacés" },
        { code: "10.71A", label: "Fabrication industrielle de pain et de pâtisserie fraîche" },
        { code: "10.71B", label: "Cuisson de produits de boulangerie" },
        { code: "10.71C", label: "Boulangerie et boulangerie-pâtisserie" },
        { code: "10.71D", label: "Pâtisserie" },
        { code: "10.72Z", label: "Fabrication de biscuits, biscottes et pâtisseries de conservation" },
        { code: "10.73Z", label: "Fabrication de pâtes alimentaires" },
        { code: "10.81Z", label: "Fabrication de sucre" },
        { code: "10.82Z", label: "Fabrication de cacao, chocolat et de produits de confiserie" },
        { code: "10.83Z", label: "Transformation du thé et du café" },
        { code: "10.84Z", label: "Fabrication de condiments et assaisonnements" },
        { code: "10.85Z", label: "Fabrication de plats préparés" },
        { code: "10.86Z", label: "Fabrication d'aliments homogénéisés et diététiques" },
        { code: "10.89Z", label: "Fabrication d'autres produits alimentaires n.c.a." },
        { code: "10.91Z", label: "Fabrication d'aliments pour animaux de ferme" },
        { code: "10.92Z", label: "Fabrication d'aliments pour animaux de compagnie" }
      ],
      "Fabrication de boissons": [
        { code: "11.01Z", label: "Production de boissons alcooliques distillées" },
        { code: "11.02A", label: "Fabrication de vins effervescents" },
        { code: "11.02B", label: "Vinification" },
        { code: "11.03Z", label: "Fabrication de cidre et de vins de fruits" },
        { code: "11.04Z", label: "Production d'autres boissons fermentées non distillées" },
        { code: "11.05Z", label: "Fabrication de bière" },
        { code: "11.06Z", label: "Production de malt" },
        { code: "11.07A", label: "Industrie des eaux de table" },
        { code: "11.07B", label: "Production de boissons rafraîchissantes" }
      ],
      "Commerce alimentaire": [
        { code: "46.31Z", label: "Commerce de gros de fruits et légumes" },
        { code: "46.32A", label: "Commerce de gros de viandes de boucherie" },
        { code: "46.32B", label: "Commerce de gros de produits à base de viande" },
        { code: "46.33Z", label: "Commerce de gros de produits laitiers, œufs, huiles et matières grasses comestibles" },
        { code: "46.34Z", label: "Commerce de gros de boissons" },
        { code: "46.36Z", label: "Commerce de gros de sucre, chocolat et confiserie" },
        { code: "46.37Z", label: "Commerce de gros de café, thé, cacao et épices" },
        { code: "46.38A", label: "Commerce de gros de poissons, crustacés et mollusques" },
        { code: "46.38B", label: "Commerce de gros alimentaire spécialisé divers" },
        { code: "46.39A", label: "Commerce de gros de produits surgelés" },
        { code: "46.39B", label: "Autre commerce de gros alimentaire" },
        { code: "47.11A", label: "Commerce de détail de produits surgelés" },
        { code: "47.11B", label: "Commerce d'alimentation générale" },
        { code: "47.11C", label: "Supérettes" },
        { code: "47.11D", label: "Supermarchés" },
        { code: "47.11E", label: "Magasins multi-commerces" },
        { code: "47.11F", label: "Hypermarchés" },
        { code: "47.19A", label: "Grands magasins" },
        { code: "47.19B", label: "Autres commerces de détail en magasin non spécialisé" },
        { code: "47.21Z", label: "Commerce de détail de fruits et légumes en magasin spécialisé" },
        { code: "47.22Z", label: "Commerce de détail de viandes et de produits à base de viande en magasin spécialisé" },
        { code: "47.23Z", label: "Commerce de détail de poissons, crustacés et mollusques en magasin spécialisé" },
        { code: "47.24Z", label: "Commerce de détail de pain, pâtisserie et confiserie en magasin spécialisé" },
        { code: "47.25Z", label: "Commerce de détail de boissons en magasin spécialisé" },
        { code: "47.26Z", label: "Commerce de détail de produits à base de tabac en magasin spécialisé" },
        { code: "47.29Z", label: "Autres commerces de détail alimentaires en magasin spécialisé" },
        { code: "47.30Z", label: "Commerce de détail de carburants en magasin spécialisé" },
        { code: "47.81Z", label: "Commerce de détail alimentaire sur éventaires et marchés" }
      ],
      "Restauration et services liés à l’alimentation": [
        { code: "56.10A", label: "Restauration traditionnelle" },
        { code: "56.10B", label: "Cafétérias et autres libres-services" },
        { code: "56.10C", label: "Restauration de type rapide" },
        { code: "56.21Z", label: "Services des traiteurs" },
        { code: "56.29A", label: "Restauration collective sous contrat" },
        { code: "56.29B", label: "Autres services de restauration n.c.a." },
        { code: "56.30Z", label: "Débits de boissons" }
      ]
    };

    // Mise à jour du menu déroulant des sous-catégories selon la catégorie principale sélectionnée
    categoriePrincipaleSelect.addEventListener('change', function() {
      let categorie = this.value;
      sousCategorieSelect.innerHTML = '<option value="">-- Sous-catégorie --</option>';
      if (mappingAlimentation[categorie]) {
        mappingAlimentation[categorie].forEach(function(item) {
          let option = document.createElement('option');
          option.value = item.code;
          option.textContent = item.label;
          sousCategorieSelect.appendChild(option);
        });
      }
    });

    // Je crée la carte avec une vue par défaut centrée sur la France
    var map = L.map('map').setView([46.603354, 1.888334], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    window.markersLayer = L.layerGroup().addTo(map);

    // Je récupère la géolocalisation de l'utilisateur pour centrer la carte et préremplir ville et adresse
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function(position) {
        userPosition = {
          lat: position.coords.latitude,
          lon: position.coords.longitude
        };
        map.setView([userPosition.lat, userPosition.lon], 13);
        L.marker([userPosition.lat, userPosition.lon]).addTo(map)
          .bindPopup("Vous êtes ici").openPopup();
        reverseGeocode(userPosition.lon, userPosition.lat);
      }, function(error) {
        console.error("Erreur de géolocalisation : " + error.message);
      }, { enableHighAccuracy: true });
    } else {
      console.error("La géolocalisation n'est pas supportée par ce navigateur.");
    }

    // Fonction de reverse géocodage pour obtenir ville et adresse à partir des coordonnées
    function reverseGeocode(lon, lat) {
      var url = `https://api-adresse.data.gouv.fr/reverse/?lon=${lon}&lat=${lat}`;
      fetch(url)
        .then(response => response.json())
        .then(data => {
          if (data.features && data.features.length > 0) {
            let prop = data.features[0].properties;
            champVille.value = prop.city || prop.label || "";
            let adresseAuto = "";
            if (prop.housenumber) adresseAuto += prop.housenumber + " ";
            if (prop.street) adresseAuto += prop.street;
            champAdresse.value = (adresseAuto.trim() !== "") ? adresseAuto : "Non renseigné";
          }
        })
        .catch(error => {
          console.error("Erreur lors du reverse géocodage :", error);
        });
    }

    // Lorsque l'utilisateur modifie la ville ou l'adresse, je lance une recherche
    champVille.addEventListener('change', function() {
      let ville = this.value.trim();
      let adresse = champAdresse.value.trim();
      if (ville !== "") {
        let query = (adresse === "" || adresse === "Non renseigné") ? ville : adresse + " " + ville;
        rechercherAdresse(query, ville);
      }
    });
    champAdresse.addEventListener('change', function() {
      let ville = champVille.value.trim();
      let adresse = this.value.trim();
      if (ville !== "") {
        let query = (adresse === "" || adresse === "Non renseigné") ? ville : adresse + " " + ville;
        rechercherAdresse(query, ville);
      }
    });

    // Gestion de la soumission du formulaire
    document.getElementById('formulaire-adresse').addEventListener('submit', function(e) {
      e.preventDefault();
      let villeRecherche = champVille.value.trim();
      let adresseRecherche = champAdresse.value.trim();
      let categoriePrincipale = categoriePrincipaleSelect.value;
      if (villeRecherche === "") {
        alert("Veuillez entrer une ville");
        return;
      }
      if (categoriePrincipale === "") {
        alert("Veuillez sélectionner une catégorie principale");
        return;
      }
      let query = (adresseRecherche === "" || adresseRecherche === "Non renseigné") ? villeRecherche : adresseRecherche + " " + villeRecherche;
      rechercherAdresse(query, villeRecherche);
    });

    // Fonction pour rechercher une adresse via l'API Base Adresse
    function rechercherAdresse(query, ville) {
      console.log("Recherche Base Adresse pour : ", query);
      var url = 'https://api-adresse.data.gouv.fr/search/?q=' + encodeURIComponent(query);
      fetch(url)
        .then(response => response.json())
        .then(data => {
          console.log("Résultats Base Adresse : ", data);
          afficherResultats(data, ville);
        })
        .catch(error => {
          console.error("Erreur lors de la récupération des données :", error);
        });
    }

    // Fonction pour afficher les résultats de l'adresse et lancer la recherche d'entreprises
    function afficherResultats(data, ville) {
      var conteneur = document.getElementById('resultats-api');
      conteneur.innerHTML = '';
      window.markersLayer.clearLayers();
      let features = data.features;
      // Si l'utilisateur n'a renseigné qu'une ville, je n'affiche que le premier résultat
      if ((champAdresse.value.trim() === "" || champAdresse.value.trim() === "Non renseigné") && ville !== "") {
        features = [features[0]];
      }
      if (features && features.length > 0) {
        features.forEach(function(feature) {
          let propriete = feature.properties;
          let lat = feature.geometry.coordinates[1];
          let lng = feature.geometry.coordinates[0];
          let citycode = propriete.citycode;
          let postcode = propriete.postcode;
          // Je crée un bloc (div) pour le résultat de l'adresse (zone et adresse)
          let divResultat = document.createElement('div');
          divResultat.className = 'resultat p-3 mb-3 border rounded';
          // Bloc Zone : informations issues du reverse géocodage
          divResultat.innerHTML = '<p><strong>Nom de la commune :</strong> ' + (propriete.city || "Non renseigné") + '</p>';
          // Bloc Adresse : adresse complète, latitude, longitude et code postal
          divResultat.innerHTML += '<p><strong>Adresse :</strong> ' + propriete.label + '</p>' +
                                    '<p><strong>Latitude :</strong> ' + lat + '</p>' +
                                    '<p><strong>Longitude :</strong> ' + lng + '</p>' +
                                    '<p><strong>Code postal :</strong> ' + postcode + '</p>';
          // J'appelle la fonction pour récupérer les infos de la zone via l'API Geo
          recupererZone(citycode, divResultat);
          conteneur.appendChild(divResultat);
          // J'ajoute un marqueur sur la carte pour cette localisation
          let marker = L.marker([lat, lng]).addTo(window.markersLayer);
          marker.bindPopup('<strong>Adresse :</strong> ' + propriete.label + '<br><em>Chargement des détails...</em>');
          divResultat.marker = marker;
          // Une fois la localisation affichée, je lance la recherche d'entreprises
          recupererEntreprises(postcode, divResultat, ville);
        });
      } else {
        conteneur.innerHTML = '<p>Aucun résultat trouvé.</p>';
      }
    }

    // Fonction pour récupérer les informations de la zone via l'API Geo et les afficher dans le bloc zone
    function recupererZone(citycode, conteneur) {
      var urlGeo = 'https://geo.api.gouv.fr/communes/' + citycode + '?fields=nom,centre,departement,region';
      fetch(urlGeo)
        .then(response => response.json())
        .then(data => {
          afficherZone(data, conteneur);
        })
        .catch(error => {
          console.error("Erreur lors de la récupération des données de la zone :", error);
        });
    }

    // Fonction pour afficher les informations de la zone
    function afficherZone(data, conteneur) {
      let divZone = conteneur.querySelector('.zone-info');
      if (!divZone) {
        divZone = document.createElement('div');
        divZone.className = 'zone-info mt-3 p-3 border-top';
        conteneur.appendChild(divZone);
      }
      let nomCommune = data.nom || "N/A";
      let nomDepartement = data.departement ? data.departement.nom : "N/A";
      let nomRegion = data.region ? data.region.nom : "N/A";
      let latitudeCentre = "N/A", longitudeCentre = "N/A";
      if (data.centre && data.centre.coordinates) {
        longitudeCentre = data.centre.coordinates[0];
        latitudeCentre = data.centre.coordinates[1];
      }
      divZone.innerHTML = '<p><strong>Département :</strong> ' + nomDepartement + '</p>' +
                          '<p><strong>Région :</strong> ' + nomRegion + '</p>' +
                          '<p><strong>Coordonnées du centre :</strong> Latitude: ' + latitudeCentre + ', Longitude: ' + longitudeCentre + '</p>';
      if (conteneur.marker) {
        let adresseLabel = conteneur.dataset.adresse;
        let newContent = '<strong>Adresse :</strong> ' + adresseLabel + '<br>' +
                         '<strong>Département :</strong> ' + nomDepartement + '<br>' +
                         '<strong>Région :</strong> ' + nomRegion + '<br>' +
                         '<strong>Coordonnées du centre :</strong> Latitude: ' + latitudeCentre + ', Longitude: ' + longitudeCentre;
        conteneur.marker.bindPopup(newContent);
      }
    }

    // Fonction pour récupérer et afficher les entreprises via l'API Sirene
    function recupererEntreprises(postcode, conteneur, ville) {
      let themeDetail = sousCategorieSelect.value;
      let categoriePrincipale = categoriePrincipaleSelect.value;
      // Je construis la requête avec le code postal et la ville (convertie en majuscules)
      let q = 'codePostalEtablissement:"' + postcode + '"';
      if (ville && ville.trim() !== '') {
        q += ' AND libelleCommuneEtablissement:"' + ville.toUpperCase() + '"';
      }
      if (themeDetail) {
        // J'ajoute le filtre pour la sous-catégorie si elle est sélectionnée
        q += ' AND activitePrincipaleUniteLegale:"' + themeDetail + '"';
      } else if (categoriePrincipale !== "") {
        // Sinon, je combine tous les codes NAF de la catégorie principale
        let codes = mappingAlimentation[categoriePrincipale].map(item => item.code);
        q += ' AND (' + codes.map(code => 'activitePrincipaleUniteLegale:"' + code + '"').join(' OR ') + ')';
      }
      console.log("Filtre Sirene:", q);
      let urlSirene = 'https://api.insee.fr/api-sirene/3.11/siret?q=' + encodeURIComponent(q) + '&nombre=100';
      fetch(urlSirene, {
        headers: {
          'X-INSEE-Api-Key-Integration': API_KEY_SIRENE,
          'Accept': 'application/json'
        }
      })
      .then(response => response.json())
      .then(data => {
        // Si la case "Filtrer uniquement sur les établissements en activité" est cochée, je ne garde que ceux avec le statut "A"
        if (filtreActifs.checked) {
          data.etablissements = data.etablissements.filter(function(etablissement) {
            let statut = etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0
              ? etablissement.periodesEtablissement[0].etatAdministratifEtablissement
              : "";
            return statut === "A";
          });
        }
        // Si une position utilisateur et un rayon de recherche sont définis, je filtre par distance
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
        console.log("Résultats Sirene:", data);
        // J'affiche les entreprises et j'ajoute les marqueurs sur la carte
        afficherEntreprises(data, conteneur);
        ajouterMarqueursEntreprises(data);
      })
      .catch(error => {
        console.error("Erreur lors de la récupération des données Sirene :", error);
      });
    }

    // Fonction pour afficher les entreprises dans un format structuré (chaque entreprise dans une card)
    function afficherEntreprises(data, conteneur) {
      let divEntreprises = conteneur.querySelector('.entreprises');
      if (!divEntreprises) {
        divEntreprises = document.createElement('div');
        divEntreprises.className = 'entreprises mt-3 p-3 border-top';
        conteneur.appendChild(divEntreprises);
      }
      if (data && data.etablissements && data.etablissements.length > 0) {
        let html = '<p><strong>Entreprises locales :</strong></p>';
        data.etablissements.forEach(function(etablissement) {
          let ul = etablissement.uniteLegale;
          let nomEntreprise = (ul && (ul.denominationUniteLegale || ul.nomUniteLegale))
                              ? (ul.denominationUniteLegale || ul.nomUniteLegale)
                              : 'Nom non disponible';
          let siren = etablissement.siren || 'SIREN non disponible';
          let siret = etablissement.siret || 'SIRET non disponible';
          let adresseObj = etablissement.adresseEtablissement || {};
          let numero = adresseObj.numeroVoieEtablissement || '';
          let typeVoie = adresseObj.typeVoieEtablissement || '';
          let libelleVoie = adresseObj.libelleVoieEtablissement || '';
          let codePostal = adresseObj.codePostalEtablissement || '';
          // Si aucun détail d'adresse n'est disponible, j'affiche "Non renseigné"
          let commune = adresseObj.libelleCommuneEtablissement || '';
          let adresseComplete = (numero || typeVoie || libelleVoie)
                                ? ((numero + " " + typeVoie + " " + libelleVoie).trim() + ", " + codePostal + " " + commune)
                                : "Non renseigné";
          let statutCode = (etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0)
                           ? etablissement.periodesEtablissement[0].etatAdministratifEtablissement
                           : '';
          let statut = (statutCode === 'A') ? "En Activité" : ((statutCode === 'F') ? "Fermé" : "Non précisé");
          let themeGeneralText = (categoriePrincipaleSelect.selectedIndex > 0)
                                 ? categoriePrincipaleSelect.selectedOptions[0].text
                                 : "Non précisé";
          let themeDetailText = (sousCategorieSelect.value !== "")
                                ? sousCategorieSelect.selectedOptions[0].text
                                : "Non précisé";
          // J'encadre chaque entreprise dans une "card" Bootstrap et j'affiche le nom en bleu
          html += '<div class="card mb-2">';
          html += '<div class="card-body">';
          html += '<h5 class="card-title text-primary">' + nomEntreprise + '</h5>';
          html += '<p class="card-text"><strong>Catégorie :</strong> ' + themeGeneralText + '<br>';
          html += '<strong>Sous-catégorie :</strong> ' + themeDetailText + '<br>';
          html += 'SIREN: ' + siren + ' - SIRET: ' + siret + '<br>';
          html += '<strong>Ville :</strong> ' + (adresseObj.libelleCommuneEtablissement || "Non renseigné") + '<br>';
          html += '<strong>Adresse :</strong> ' + adresseComplete + '<br>';
          html += '<strong>Statut :</strong> ' + statut + '</p>';
          html += '</div></div>';
        });
        divEntreprises.innerHTML = html;
      } else {
        divEntreprises.innerHTML = '<p>Aucune entreprise locale trouvée.</p>';
      }
    }

    // Fonction pour ajouter des marqueurs sur la carte pour chaque entreprise
    function ajouterMarqueursEntreprises(data) {
      if (data && data.etablissements && data.etablissements.length > 0) {
        data.etablissements.forEach(function(etablissement) {
          let adresseObj = etablissement.adresseEtablissement;
          if (adresseObj && adresseObj.coordonneeLambertAbscisseEtablissement && adresseObj.coordonneeLambertOrdonneeEtablissement) {
            let x = parseFloat(adresseObj.coordonneeLambertAbscisseEtablissement);
            let y = parseFloat(adresseObj.coordonneeLambertOrdonneeEtablissement);
            let coords = proj4("EPSG:2154", "EPSG:4326", [x, y]);
            let nomEntreprise = (etablissement.uniteLegale && (etablissement.uniteLegale.denominationUniteLegale || etablissement.uniteLegale.nomUniteLegale))
                                ? (etablissement.uniteLegale.denominationUniteLegale || etablissement.uniteLegale.nomUniteLegale)
                                : 'Nom non disponible';
            let siren = etablissement.siren || 'N/A';
            let siret = etablissement.siret || 'N/A';
            let commune = adresseObj.libelleCommuneEtablissement || "Non renseigné";
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
            let themeGeneralText = (categoriePrincipaleSelect.selectedIndex > 0)
                                   ? categoriePrincipaleSelect.selectedOptions[0].text
                                   : "Non précisé";
            let themeDetailText = (sousCategorieSelect.value !== "")
                                  ? sousCategorieSelect.selectedOptions[0].text
                                  : "Non précisé";
            let popupContent = '<strong>' + nomEntreprise + '</strong><br>' +
              '<strong>Catégorie :</strong> ' + themeGeneralText + '<br>' +
              '<strong>Sous-catégorie :</strong> ' + themeDetailText + '<br>' +
              'SIREN: ' + siren + '<br>' +
              'SIRET: ' + siret + '<br>' +
              '<strong>Ville :</strong> ' + commune + '<br>' +
              '<strong>Adresse :</strong> ' + adresseComplete + '<br>' +
              '<strong>Statut :</strong> ' + statut;
            let marker = L.marker([coords[1], coords[0]]).addTo(window.markersLayer);
            marker.bindPopup(popupContent);
          }
        });
      }
    }

    // Fonction pour calculer la distance entre deux points (formule de Haversine)
    function haversineDistance(lat1, lon1, lat2, lon2) {
      const toRad = x => x * Math.PI / 180;
      const R = 6371; // Rayon de la Terre en km
      const dLat = toRad(lat2 - lat1);
      const dLon = toRad(lon2 - lon1);
      const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      return R * c;
    }
  });
</script>
</body>
</html>
