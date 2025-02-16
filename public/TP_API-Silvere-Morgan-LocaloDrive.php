<?php
// Version 13 : fusion des codes NAF 10.71B, 10.71C et 10.71D et implémentation de Composer pour cacher la clé API dans .env
// TP_API-Silvere-Morgan-LocaloDrive.php

// Je charge l'autoloader de Composer pour pouvoir utiliser les classes installées (ici phpdotenv)
require_once __DIR__ . '/../vendor/autoload.php'; // J'ajuste le chemin selon ma structure

// Je crée une instance de Dotenv pour charger les variables d'environnement depuis la racine du projet
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../'); 
$dotenv->load();

// J'extrais ma clé API Sirene depuis le fichier .env
$API_KEY_SIRENE = $_ENV['API_KEY_SIRENE'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Localo'Drive - Recherche et Carte</title>
  <!-- Inclusion de Bootstrap depuis le dossier node_modules -->
  <link rel="stylesheet" href="../node_modules/bootstrap/dist/css/bootstrap.min.css">
  <!-- Inclusion du fichier de styles personnalisé -->
  <link rel="stylesheet" href="styles.css">
  <!-- Inclusion du CSS de Leaflet pour l'affichage de la carte -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <!-- Inclusion de proj4js pour la conversion de coordonnées -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.7.5/proj4.js"></script>
  <script>
    // Je définis le système de projection EPSG:2154 pour la France métropolitaine
    proj4.defs("EPSG:2154", "+proj=lcc +lat_1=44 +lat_2=49 +lat_0=46.5 +lon_0=3 +x_0=700000 +y_0=6600000 +ellps=GRS80 +units=m +no_defs");
  </script>
</head>
<body>

<script>
  // Je passe la clé API de PHP à une variable JavaScript en toute sécurité
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

<!-- Inclusion des scripts JavaScript -->
<script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    // Je déclare une variable pour stocker la position de l'utilisateur
    let userPosition = null;
    // J'obtiens les références aux champs du formulaire
    const champVille = document.getElementById('champ-ville');
    const champAdresse = document.getElementById('champ-adresse');
    const rayonSelect = document.getElementById('rayon-select');
    const categoriePrincipaleSelect = document.getElementById('categorie-principale');
    const sousCategorieSelect = document.getElementById('sous-categorie');

    // Je définis un mapping pour les catégories d'alimentation et leurs codes NAF associés
    const mappingAlimentation = {
      "Production primaire": [
        { code: "01.11Z", label: "Culture de céréales (sauf riz)" },
        { code: "01.12Z", label: "Culture du riz" },
        { code: "01.13Z", label: "Culture de légumes, melons, racines et tubercules" },
        { code: "01.14Z", label: "Culture de fruits" },
        { code: "01.15Z", label: "Culture de la vigne" },
        { code: "01.16Z", label: "Autres cultures non permanentes" },
        { code: "01.19Z", label: "Culture d’autres plantes" },
        { code: "01.21Z", label: "Élevage de bovins et de buffles" },
        { code: "01.22Z", label: "Élevage d’ovins et de caprins" },
        { code: "01.23Z", label: "Élevage de porcins" },
        { code: "01.24Z", label: "Élevage de volailles" },
        { code: "01.25Z", label: "Autres élevages" },
        { code: "03.21Z", label: "Pêche en mer" },
        { code: "03.22Z", label: "Pêche en eau douce" },
        { code: "03.23Z", label: "Aquaculture" }
      ],
      "Transformation et fabrication de produits alimentaires": [
        { code: "10.11Z", label: "Transformation et conservation de la viande de boucherie" },
        { code: "10.12Z", label: "Fabrication de charcuterie" },
        { code: "10.13Z", label: "Transformation et conservation de poisson, crustacés et mollusques" },
        { code: "10.14Z", label: "Transformation et conservation de fruits et légumes" },
        { code: "10.15Z", label: "Fabrication d’huiles et de graisses" },
        { code: "10.16Z", label: "Fabrication de produits laitiers" },
        { code: "10.17Z", label: "Fabrication de produits à base de céréales" },
        // Pour "Boulangerie / Pâtisserie", j'utilise un filtre combiné pour couvrir 10.71B, 10.71C et 10.71D.
        { code: "10.71B", label: "Boulangerie / Pâtisserie" },
        { code: "10.19Z", label: "Fabrication d’aliments pour animaux" }
      ],
      "Fabrication de boissons": [
        { code: "11.01Z", label: "Distillation, rectification et mise en bouteille d’alcool" },
        { code: "11.02Z", label: "Fabrication de vins" },
        { code: "11.03Z", label: "Fabrication de bières" },
        { code: "11.04Z", label: "Fabrication de boissons non alcoolisées" }
      ],
      "Commerce alimentaire": [
        { code: "46.31Z", label: "Commerce de gros alimentaire" },
        { code: "47.22Z", label: "Commerce de détail alimentaire sur éventaires ou marchés" },
        { code: "47.29Z", label: "Autres commerces de détail alimentaires" }
      ],
      "Restauration et services liés à l’alimentation": [
        { code: "56.10A", label: "Restauration traditionnelle" },
        { code: "56.10B", label: "Restauration rapide" },
        { code: "56.21Z", label: "Restauration collective" },
        { code: "56.29Z", label: "Autres services de restauration" },
        { code: "56.30Z", label: "Débits de boissons" }
      ]
    };

    // Je mets à jour le menu déroulant des sous-catégories en fonction de la catégorie principale sélectionnée
    categoriePrincipaleSelect.addEventListener('change', function() {
      let categorie = this.value;
      sousCategorieSelect.innerHTML = '<option value="">-- Sous-catégorie --</option>';
      if(mappingAlimentation[categorie]) {
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

    // Je tente de récupérer la géolocalisation de l'utilisateur
    if(navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function(position) {
        userPosition = {
          lat: position.coords.latitude,
          lon: position.coords.longitude
        };
        // Je centre la carte sur la position de l'utilisateur
        map.setView([userPosition.lat, userPosition.lon], 13);
        L.marker([userPosition.lat, userPosition.lon]).addTo(map)
          .bindPopup("Vous êtes ici").openPopup();
        // Je lance le reverse géocodage pour préremplir la ville et l'adresse
        reverseGeocode(userPosition.lon, userPosition.lat);
      }, function(error) {
        console.error("Erreur de géolocalisation : " + error.message);
      }, { enableHighAccuracy: true });
    } else {
      console.error("La géolocalisation n'est pas supportée par ce navigateur.");
    }

    // Fonction de reverse géocodage pour obtenir la ville et l'adresse à partir des coordonnées
    function reverseGeocode(lon, lat) {
      var url = `https://api-adresse.data.gouv.fr/reverse/?lon=${lon}&lat=${lat}`;
      fetch(url)
        .then(response => response.json())
        .then(data => {
          if(data.features && data.features.length > 0) {
            let prop = data.features[0].properties;
            champVille.value = prop.city || prop.label || "";
            let adresseAuto = "";
            if(prop.housenumber) adresseAuto += prop.housenumber + " ";
            if(prop.street) adresseAuto += prop.street;
            champAdresse.value = adresseAuto;
          }
        })
        .catch(error => {
          console.error("Erreur lors du reverse géocodage :", error);
        });
    }

    // Lorsque l'utilisateur modifie la ville, je lance une recherche d'adresse
    champVille.addEventListener('change', function() {
      let ville = this.value.trim();
      let adresse = champAdresse.value.trim();
      if(ville !== "") {
        let query = (adresse === "") ? ville : adresse + " " + ville;
        rechercherAdresse(query, ville);
      }
    });

    // De même, lorsque l'utilisateur modifie l'adresse
    champAdresse.addEventListener('change', function() {
      let ville = champVille.value.trim();
      let adresse = this.value.trim();
      if(ville !== "") {
        let query = (adresse === "") ? ville : adresse + " " + ville;
        rechercherAdresse(query, ville);
      }
    });

    // Gestion de la soumission du formulaire de recherche
    document.getElementById('formulaire-adresse').addEventListener('submit', function(e) {
      e.preventDefault();
      let villeRecherche = champVille.value.trim();
      let adresseRecherche = champAdresse.value.trim();
      let categoriePrincipale = categoriePrincipaleSelect.value;
      if(villeRecherche === ""){
        alert("Veuillez entrer une ville");
        return;
      }
      if(categoriePrincipale === ""){
        alert("Veuillez sélectionner une catégorie principale");
        return;
      }
      let query = (adresseRecherche === "") ? villeRecherche : adresseRecherche + " " + villeRecherche;
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

    // Fonction pour afficher les résultats de la recherche d'adresse et lancer la recherche d'entreprises
    function afficherResultats(data, ville) {
      var conteneur = document.getElementById('resultats-api');
      conteneur.innerHTML = '';
      window.markersLayer.clearLayers();
      let features = data.features;
      // Si l'utilisateur n'a pas renseigné d'adresse mais seulement la ville, j'affiche uniquement le premier résultat
      if(champAdresse.value.trim() === "" && ville !== ""){
        features = [features[0]];
      }
      if(features && features.length > 0) {
        features.forEach(function(feature) {
          let propriete = feature.properties;
          let lat = feature.geometry.coordinates[1];
          let lng = feature.geometry.coordinates[0];
          let citycode = propriete.citycode;
          let postcode = propriete.postcode;
          let divResultat = document.createElement('div');
          divResultat.className = 'resultat p-3 mb-3 border rounded';
          divResultat.dataset.adresse = propriete.label;
          divResultat.innerHTML = '<p><strong>Adresse :</strong> ' + propriete.label + '</p>' +
                                  '<p><strong>Latitude :</strong> ' + lat + '</p>' +
                                  '<p><strong>Longitude :</strong> ' + lng + '</p>' +
                                  '<p><strong>Code postal :</strong> ' + postcode + '</p>';
          // Je récupère les informations géographiques complémentaires pour la zone
          recupererZone(citycode, divResultat);
          conteneur.appendChild(divResultat);
          // J'ajoute un marqueur sur la carte pour cette localisation
          let marker = L.marker([lat, lng]).addTo(window.markersLayer);
          marker.bindPopup('<strong>Adresse :</strong> ' + propriete.label + '<br><em>Chargement des détails...</em>');
          divResultat.marker = marker;
          // Une fois la localisation affichée, je lance la recherche d'entreprises dans cette zone
          recupererEntreprises(postcode, divResultat, ville);
        });
      } else {
        conteneur.innerHTML = '<p>Aucun résultat trouvé.</p>';
      }
    }

    // Fonction pour récupérer les informations de la zone (ville, département, région)
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

    // Fonction pour afficher les informations de la zone dans le résultat
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
      divZone.innerHTML = '<p><strong>Nom de la commune :</strong> ' + nomCommune + '</p>' +
                          '<p><strong>Département :</strong> ' + nomDepartement + '</p>' +
                          '<p><strong>Région :</strong> ' + nomRegion + '</p>' +
                          '<p><strong>Coordonnées du centre :</strong> Latitude: ' + latitudeCentre + ', Longitude: ' + longitudeCentre + '</p>';
      if (conteneur.marker) {
        let adresseLabel = conteneur.dataset.adresse;
        let newContent = '<strong>Adresse :</strong> ' + adresseLabel + '<br>' +
                         '<strong>Nom de la commune :</strong> ' + nomCommune + '<br>' +
                         '<strong>Département :</strong> ' + nomDepartement + '<br>' +
                         '<strong>Région :</strong> ' + nomRegion + '<br>' +
                         '<strong>Coordonnées du centre :</strong> Latitude: ' + latitudeCentre + ', Longitude: ' + longitudeCentre;
        conteneur.marker.bindPopup(newContent);
      }
    }

    // IMPORTANT : Pour afficher les résultats Sirene (entreprises), j'utilise la fonction recupererEntreprises
    function recupererEntreprises(postcode, conteneur, ville) {
      let themeDetail = sousCategorieSelect.value;
      let categoriePrincipale = categoriePrincipaleSelect.value;
      // Je construis la requête en encadrant le code postal entre guillemets pour éviter des erreurs de format
      let q = 'codePostalEtablissement:"' + postcode + '"';
  
      if (ville && ville.trim() !== '') {
        q += ' AND libelleCommuneEtablissement:"' + ville.toUpperCase() + '"';
      }
  
      if (themeDetail) {
        // Si la catégorie principale est Transformation et que l'utilisateur a sélectionné "10.71B" (boulangerie/pâtisserie)
        // Je combine les 3 codes correspondants pour couvrir l'ensemble des cas
        if (categoriePrincipale === "Transformation et fabrication de produits alimentaires" && themeDetail === "10.71B") {
          q += ' AND (activitePrincipaleUniteLegale:"10.71B" OR activitePrincipaleUniteLegale:"10.71C" OR activitePrincipaleUniteLegale:"10.71D")';
        } else {
          q += ' AND activitePrincipaleUniteLegale:"' + themeDetail + '"';
        }
      } else if (categoriePrincipale !== "") {
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
        // Si une position utilisateur et un rayon de recherche sont définis, je filtre les résultats par distance
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
        // J'affiche les entreprises et j'ajoute leurs marqueurs sur la carte
        afficherEntreprises(data, conteneur);
        ajouterMarqueursEntreprises(data);
      })
      .catch(error => {
        console.error("Erreur lors de la récupération des données Sirene :", error);
      });
    }

    // Fonction pour afficher les entreprises dans la colonne de résultats
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
          let nomEntreprise = (ul && (ul.denominationUniteLegale || ul.nomUniteLegale)) ? (ul.denominationUniteLegale || ul.nomUniteLegale) : 'Nom non disponible';
          let siren = etablissement.siren || 'SIREN non disponible';
          let siret = etablissement.siret || 'SIRET non disponible';
          let adresseObj = etablissement.adresseEtablissement || {};
          let numero = adresseObj.numeroVoieEtablissement || '';
          let typeVoie = adresseObj.typeVoieEtablissement || '';
          let libelleVoie = adresseObj.libelleVoieEtablissement || '';
          let codePostal = adresseObj.codePostalEtablissement || '';
          let commune = adresseObj.libelleCommuneEtablissement || '';
          let adresseComplete = (numero + " " + typeVoie + " " + libelleVoie).trim() + ", " + codePostal + " " + commune;
          let statutCode = (etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0) ? etablissement.periodesEtablissement[0].etatAdministratifEtablissement : '';
          let statut = (statutCode === 'A') ? "En Activité" : ((statutCode === 'F') ? "Fermé" : "Non précisé");
          html += '<div class="mb-2"><strong>' + nomEntreprise + '</strong><br>';
          html += 'SIREN: ' + siren + ' - SIRET: ' + siret + '<br>';
          html += 'Ville: ' + commune + '<br>';
          html += 'Adresse: ' + adresseComplete + '<br>';
          html += 'Statut: ' + statut + '</div>';
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
            let commune = adresseObj.libelleCommuneEtablissement || 'N/A';
            let numero = adresseObj.numeroVoieEtablissement || '';
            let typeVoie = adresseObj.typeVoieEtablissement || '';
            let libelleVoie = adresseObj.libelleVoieEtablissement || '';
            let codePostal = adresseObj.codePostalEtablissement || '';
            let adresseComplete = (numero + " " + typeVoie + " " + libelleVoie).trim() + ", " + codePostal + " " + commune;
            let statutCode = (etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0) ? etablissement.periodesEtablissement[0].etatAdministratifEtablissement : '';
            let statut = (statutCode === 'A') ? "En Activité" : ((statutCode === 'F') ? "Fermé" : "Non précisé");

            let themeGeneralText = (categoriePrincipaleSelect.selectedIndex > 0) ? categoriePrincipaleSelect.selectedOptions[0].text : "Non précisé";
            let themeDetailText = (sousCategorieSelect.selectedIndex > 0) ? sousCategorieSelect.selectedOptions[0].text : "Non précisé";

            let popupContent = '<strong>' + nomEntreprise + '</strong><br>' +
                               '<strong>Catégorie :</strong> ' + themeGeneralText + '<br>' +
                               '<strong>Sous-catégorie :</strong> ' + themeDetailText + '<br>' +
                               'SIREN: ' + siren + '<br>' +
                               'SIRET: ' + siret + '<br>' +
                               'Ville: ' + commune + '<br>' +
                               'Adresse: ' + adresseComplete + '<br>' +
                               'Statut: ' + statut;
            let marker = L.marker([coords[1], coords[0]]).addTo(window.markersLayer);
            marker.bindPopup(popupContent);
          }
        });
      }
    }

    // Fonction pour calculer la distance entre deux points en utilisant la formule de Haversine
    function haversineDistance(lat1, lon1, lat2, lon2) {
      const toRad = x => x * Math.PI / 180;
      const R = 6371; // Rayon de la Terre en km
      const dLat = toRad(lat2 - lat1);
      const dLon = toRad(lon2 - lon1);
      const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
                Math.sin(dLon/2) * Math.sin(dLon/2);
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      return R * c;
    }
  });
</script>
</body>
</html>
