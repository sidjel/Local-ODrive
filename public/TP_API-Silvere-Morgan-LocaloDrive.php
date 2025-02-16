<?php
// Version 11 : Ajout de la géolocalisation automatique et auto-remplissage des champs Ville et Adresse,
// suppression de la case "Utiliser ma géolocalisation"
// TP_API-Silvere-Morgan-LocaloDrive.php
// Intégration des API Base Adresse Nationale, GeoZone et Sirene avec auto-remplissage des champs et mise à jour de la carte.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Localo'Drive - Recherche et Carte</title>
  <!-- Inclusion de Bootstrap installé localement -->
  <link rel="stylesheet" href="../node_modules/bootstrap/dist/css/bootstrap.min.css">
  <!-- Inclusion de Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <!-- Inclusion du CSS personnalisé -->
  <link rel="stylesheet" href="styles.css">
  <!-- Inclusion de Proj4js pour la conversion Lambert93 -> WGS84 -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/proj4js/2.7.5/proj4.js"></script>
  <script>
    // Définition officielle de Lambert93 (EPSG:2154)
    proj4.defs("EPSG:2154", "+proj=lcc +lat_1=44 +lat_2=49 +lat_0=46.5 +lon_0=3 +x_0=700000 +y_0=6600000 +ellps=GRS80 +units=m +no_defs");
  </script>
</head>
<body>
  <div class="container mt-4">
    <h1 class="text-center">Localo'Drive - Recherche et Carte</h1>
    <p class="text-center">Faciliter l'accès aux produits locaux en connectant producteurs et consommateurs</p>

    <!-- Formulaire de recherche -->
    <form id="formulaire-adresse" class="d-flex flex-wrap justify-content-center mb-4">
      <!-- Champ Ville (auto-rempli, modifiable) -->
      <input type="text" id="champ-ville" class="form-control me-2 mb-2" placeholder="Ville" style="max-width:300px;">
      <!-- Champ Adresse (auto-rempli, modifiable) -->
      <input type="text" id="champ-adresse" class="form-control me-2 mb-2" placeholder="Adresse (facultatif)" style="max-width:300px;">
      <!-- Menu déroulant pour le rayon de recherche -->
      <select id="rayon-select" class="form-select me-2 mb-2" style="max-width:200px;">
        <option value="">-- Rayon de recherche --</option>
        <option value="3">3 km</option>
        <option value="5">5 km</option>
        <option value="10">10 km</option>
      </select>
      <!-- Menu déroulant pour le thème général -->
      <select id="theme-general" class="form-select me-2 mb-2" style="max-width:200px;">
        <option value="">-- Thème général --</option>
        <option value="transformes">Alimentation Transformés</option>
        <option value="nonTransformes">Alimentation non Transformé</option>
      </select>
      <!-- Menu déroulant pour la sous-catégorie -->
      <select id="theme-detail" class="form-select me-2 mb-2" style="max-width:300px;">
        <option value="">-- Sous-catégorie --</option>
      </select>
      <button type="submit" class="btn btn-success mb-2">Rechercher</button>
    </form>

    <div class="row">
      <!-- Colonne des résultats -->
      <div class="col-md-4" id="colonne-resultats">
        <div id="resultats-api"></div>
      </div>
      <!-- Colonne de la carte -->
      <div class="col-md-8" id="colonne-carte">
        <div id="map" style="height:500px;"></div>
      </div>
    </div>
  </div>

  <!-- Inclusion de Bootstrap JS Bundle -->
  <script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Inclusion de Leaflet JS -->
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script>
    // Version 11

    // Clé API Sirene (à adapter avec votre clé personnelle)
    const API_KEY_SIRENE = ***REMOVED***;

    // Variables globales pour la géolocalisation
    let userPosition = null; // {lat: ..., lon: ...}

    // Références aux éléments du formulaire
    const champVille = document.getElementById('champ-ville');
    const champAdresse = document.getElementById('champ-adresse');
    const rayonSelect = document.getElementById('rayon-select');

    // Références aux menus déroulants de thème
    const themeGeneralSelect = document.getElementById('theme-general');
    const themeDetailSelect = document.getElementById('theme-detail');

    // Options pour les thèmes
    const optionsTransformes = [
      { label: "Producteur de lait / crème", code: "10.51A" },
      { label: "Producteur de pâtes", code: "10.73Z" },
      { label: "Producteur de viandes", code: "10.11Z" },
      { label: "Producteur de chips", code: "10.31Z" },
      { label: "Producteur de pain", code: "10.71B" }
    ];
    const optionsNonTransformes = [
      { label: "Producteur d'œufs", code: "01.47Z" },
      { label: "Producteur de miel", code: "01.49Z" }
    ];

    // Mise à jour du menu détail en fonction du thème général sélectionné
    themeGeneralSelect.addEventListener('change', function() {
      const selected = this.value;
      themeDetailSelect.innerHTML = '<option value="">-- Sous-catégorie --</option>';
      let options = [];
      if (selected === 'transformes') {
        options = optionsTransformes;
      } else if (selected === 'nonTransformes') {
        options = optionsNonTransformes;
      }
      options.forEach(function(opt) {
        const optionEl = document.createElement('option');
        optionEl.value = opt.code;
        optionEl.textContent = opt.label;
        themeDetailSelect.appendChild(optionEl);
      });
    });

    // Initialisation de la carte centrée sur la France
    var map = L.map('map').setView([46.603354, 1.888334], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    window.markersLayer = L.layerGroup().addTo(map);

    // Demande de géolocalisation dès l'arrivée (en utilisant enableHighAccuracy pour Chrome)
    if(navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function(position) {
        userPosition = {
          lat: position.coords.latitude,
          lon: position.coords.longitude
        };
        // Recentrer la carte sur la position de l'utilisateur et ajouter un marqueur "Vous êtes ici"
        map.setView([userPosition.lat, userPosition.lon], 13);
        L.marker([userPosition.lat, userPosition.lon]).addTo(map)
          .bindPopup("Vous êtes ici").openPopup();
        // Reverse géocodage pour auto-remplir Ville et Adresse
        reverseGeocode(userPosition.lon, userPosition.lat);
      }, function(error) {
        console.error("Erreur de géolocalisation : " + error.message);
      }, { enableHighAccuracy: true });
    } else {
      console.error("La géolocalisation n'est pas supportée par ce navigateur.");
    }

    // Fonction de reverse géocodage via l'API Base Adresse Nationale
    function reverseGeocode(lon, lat) {
      var url = `https://api-adresse.data.gouv.fr/reverse/?lon=${lon}&lat=${lat}`;
      fetch(url)
        .then(response => response.json())
        .then(data => {
          if(data.features && data.features.length > 0) {
            let prop = data.features[0].properties;
            // Remplir automatiquement les champs Ville et Adresse
            champVille.value = prop.city || prop.label || "";
            let adresseAuto = "";
            if(prop.housenumber) adresseAuto += prop.housenumber + " ";
            if(prop.street) adresseAuto += prop.street;
            champAdresse.value = adresseAuto;
            // Lancer une recherche avec ces données
            let query = (adresseAuto === "") ? (prop.city || prop.label) : adresseAuto + " " + (prop.city || prop.label);
            rechercherAdresse(query, prop.city || prop.label);
          }
        })
        .catch(error => {
          console.error("Erreur lors du reverse géocodage :", error);
        });
    }

    // Écoute des modifications sur les champs Ville et Adresse pour mettre à jour la carte
    champVille.addEventListener('change', function() {
      let ville = this.value.trim();
      let adresse = champAdresse.value.trim();
      if(ville !== "") {
        let query = (adresse === "") ? ville : adresse + " " + ville;
        rechercherAdresse(query, ville);
      }
    });
    champAdresse.addEventListener('change', function() {
      let ville = champVille.value.trim();
      let adresse = this.value.trim();
      if(ville !== "") {
        let query = (adresse === "") ? ville : adresse + " " + ville;
        rechercherAdresse(query, ville);
      }
    });

    // Gestion du formulaire (bouton "Rechercher")
    document.getElementById('formulaire-adresse').addEventListener('submit', function(e) {
      e.preventDefault();
      let villeRecherche = champVille.value.trim();
      let adresseRecherche = champAdresse.value.trim();
      if(villeRecherche === ""){
        alert("Veuillez entrer une ville");
        return;
      }
      let query = (adresseRecherche === "") ? villeRecherche : adresseRecherche + " " + villeRecherche;
      rechercherAdresse(query, villeRecherche);
    });

    // Recherche d'adresse via l'API Base Adresse Nationale
    function rechercherAdresse(query, ville) {
      var url = 'https://api-adresse.data.gouv.fr/search/?q=' + encodeURIComponent(query);
      fetch(url)
        .then(response => response.json())
        .then(data => {
          afficherResultats(data, ville);
        })
        .catch(error => {
          console.error("Erreur lors de la récupération des données :", error);
        });
    }

    // Affichage des résultats et appel aux API GeoZone et Sirene
    function afficherResultats(data, ville) {
      var conteneur = document.getElementById('resultats-api');
      conteneur.innerHTML = '';
      window.markersLayer.clearLayers();

      let features = data.features;
      if(champAdresse.value.trim() === "" && ville !== ""){
        features = [features[0]];
      }
      if(features && features.length > 0) {
        features.forEach(function(feature) {
          var propriete = feature.properties;
          var lat = feature.geometry.coordinates[1];
          var lng = feature.geometry.coordinates[0];
          var citycode = propriete.citycode;
          var postcode = propriete.postcode;

          var divResultat = document.createElement('div');
          divResultat.className = 'resultat p-3 mb-3 border rounded';
          divResultat.dataset.adresse = propriete.label;
          divResultat.innerHTML = '<p><strong>Adresse :</strong> ' + propriete.label + '</p>' +
                                  '<p><strong>Latitude :</strong> ' + lat + '</p>' +
                                  '<p><strong>Longitude :</strong> ' + lng + '</p>' +
                                  '<p><strong>Code postal :</strong> ' + postcode + '</p>';

          recupererZone(citycode, divResultat);
          recupererEntreprises(postcode, divResultat, ville);
          conteneur.appendChild(divResultat);

          var marker = L.marker([lat, lng]).addTo(window.markersLayer);
          marker.bindPopup('<strong>Adresse :</strong> ' + propriete.label + '<br><em>Chargement des détails...</em>');
          divResultat.marker = marker;
        });
      } else {
        conteneur.innerHTML = '<p>Aucun résultat trouvé.</p>';
      }
    }

    // Récupération des informations GeoZone
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

    function afficherZone(data, conteneur) {
      var divZone = conteneur.querySelector('.zone-info');
      if (!divZone) {
        divZone = document.createElement('div');
        divZone.className = 'zone-info mt-3 p-3 border-top';
        conteneur.appendChild(divZone);
      }
      var nomCommune = data.nom || "N/A";
      var nomDepartement = data.departement ? data.departement.nom : "N/A";
      var nomRegion = data.region ? data.region.nom : "N/A";
      var latitudeCentre = "N/A", longitudeCentre = "N/A";
      if (data.centre && data.centre.coordinates) {
        longitudeCentre = data.centre.coordinates[0];
        latitudeCentre = data.centre.coordinates[1];
      }
      divZone.innerHTML = '<p><strong>Nom de la commune :</strong> ' + nomCommune + '</p>' +
                          '<p><strong>Département :</strong> ' + nomDepartement + '</p>' +
                          '<p><strong>Région :</strong> ' + nomRegion + '</p>' +
                          '<p><strong>Coordonnées du centre :</strong> Latitude: ' + latitudeCentre + ', Longitude: ' + longitudeCentre + '</p>';

      if (conteneur.marker) {
        var adresseLabel = conteneur.dataset.adresse;
        var newContent = '<strong>Adresse :</strong> ' + adresseLabel + '<br>' +
                         '<strong>Nom de la commune :</strong> ' + nomCommune + '<br>' +
                         '<strong>Département :</strong> ' + nomDepartement + '<br>' +
                         '<strong>Région :</strong> ' + nomRegion + '<br>' +
                         '<strong>Coordonnées du centre :</strong> Latitude: ' + latitudeCentre + ', Longitude: ' + longitudeCentre;
        conteneur.marker.bindPopup(newContent);
      }
    }

    // Récupération des entreprises via l'API Sirene
    function recupererEntreprises(postcode, conteneur, ville) {
      var themeDetail = document.getElementById('theme-detail').value;
      var query = 'codePostalEtablissement:' + postcode;
      if(ville && ville.trim() !== '') {
        query += ' AND libelleCommuneEtablissement:' + ville;
      }
      if(themeDetail) {
        query += ' AND activitePrincipaleUniteLegale:' + themeDetail;
      }
      var urlSirene = 'https://api.insee.fr/api-sirene/3.11/siret?q=' + encodeURIComponent(query);
      fetch(urlSirene, {
        headers: {
          'X-INSEE-Api-Key-Integration': API_KEY_SIRENE,
          'Accept': 'application/json'
        }
      })
      .then(response => response.json())
      .then(data => {
        // Filtrer par distance si la géolocalisation est disponible et un rayon est défini
        if(userPosition && rayonSelect.value) {
          let rayon = parseFloat(rayonSelect.value);
          data.etablissements = data.etablissements.filter(function(etablissement) {
            var adresseObj = etablissement.adresseEtablissement;
            if(adresseObj && adresseObj.coordonneeLambertAbscisseEtablissement && adresseObj.coordonneeLambertOrdonneeEtablissement) {
              var x = parseFloat(adresseObj.coordonneeLambertAbscisseEtablissement);
              var y = parseFloat(adresseObj.coordonneeLambertOrdonneeEtablissement);
              var coords = proj4("EPSG:2154", "EPSG:4326", [x, y]);
              var d = haversineDistance(userPosition.lat, userPosition.lon, coords[1], coords[0]);
              return d <= rayon;
            }
            return false;
          });
        }
        afficherEntreprises(data, conteneur);
        ajouterMarqueursEntreprises(data);
      })
      .catch(error => {
        console.error("Erreur lors de la récupération des données Sirene :", error);
      });
    }

    function afficherEntreprises(data, conteneur) {
      var divEntreprises = conteneur.querySelector('.entreprises');
      if (!divEntreprises) {
        divEntreprises = document.createElement('div');
        divEntreprises.className = 'entreprises mt-3 p-3 border-top';
        conteneur.appendChild(divEntreprises);
      }
      if(data && data.etablissements && data.etablissements.length > 0) {
        var html = '<p><strong>Entreprises locales :</strong></p>';
        data.etablissements.forEach(function(etablissement) {
          var ul = etablissement.uniteLegale;
          var nomEntreprise = (ul && (ul.denominationUniteLegale || ul.nomUniteLegale)) ? (ul.denominationUniteLegale || ul.nomUniteLegale) : 'Nom non disponible';
          var siren = etablissement.siren || 'SIREN non disponible';
          var siret = etablissement.siret || 'SIRET non disponible';
          var adresseObj = etablissement.adresseEtablissement || {};
          var numero = adresseObj.numeroVoieEtablissement || '';
          var typeVoie = adresseObj.typeVoieEtablissement || '';
          var libelleVoie = adresseObj.libelleVoieEtablissement || '';
          var codePostal = adresseObj.codePostalEtablissement || '';
          var commune = adresseObj.libelleCommuneEtablissement || '';
          var adresseComplete = (numero + " " + typeVoie + " " + libelleVoie).trim() + ", " + codePostal + " " + commune;
          var statutCode = (etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0) ? etablissement.periodesEtablissement[0].etatAdministratifEtablissement : '';
          var statut = (statutCode === 'A') ? "En Activité" : ((statutCode === 'F') ? "Fermé" : "Non précisé");
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

    // Ajout de marqueurs sur la carte pour les entreprises via conversion Lambert93 -> WGS84
    function ajouterMarqueursEntreprises(data) {
      if(data && data.etablissements && data.etablissements.length > 0) {
        data.etablissements.forEach(function(etablissement) {
          var adresseObj = etablissement.adresseEtablissement;
          if(adresseObj && adresseObj.coordonneeLambertAbscisseEtablissement && adresseObj.coordonneeLambertOrdonneeEtablissement) {
            var x = parseFloat(adresseObj.coordonneeLambertAbscisseEtablissement);
            var y = parseFloat(adresseObj.coordonneeLambertOrdonneeEtablissement);
            var coords = proj4("EPSG:2154", "EPSG:4326", [x, y]);
            var nomEntreprise = (etablissement.uniteLegale && (etablissement.uniteLegale.denominationUniteLegale || etablissement.uniteLegale.nomUniteLegale))
              ? (etablissement.uniteLegale.denominationUniteLegale || etablissement.uniteLegale.nomUniteLegale)
              : 'Nom non disponible';
            var siren = etablissement.siren || 'N/A';
            var siret = etablissement.siret || 'N/A';
            var commune = adresseObj.libelleCommuneEtablissement || 'N/A';
            var numero = adresseObj.numeroVoieEtablissement || '';
            var typeVoie = adresseObj.typeVoieEtablissement || '';
            var libelleVoie = adresseObj.libelleVoieEtablissement || '';
            var codePostal = adresseObj.codePostalEtablissement || '';
            var adresseComplete = (numero + " " + typeVoie + " " + libelleVoie).trim() + ", " + codePostal + " " + commune;
            var statutCode = (etablissement.periodesEtablissement && etablissement.periodesEtablissement.length > 0) ? etablissement.periodesEtablissement[0].etatAdministratifEtablissement : '';
            var statut = (statutCode === 'A') ? "En Activité" : ((statutCode === 'F') ? "Fermé" : "Non précisé");

            var themeGeneralText = (themeGeneralSelect.selectedIndex > 0) ? themeGeneralSelect.selectedOptions[0].text : "Non précisé";
            var themeDetailText = (themeDetailSelect.selectedIndex > 0) ? themeDetailSelect.selectedOptions[0].text : "Non précisé";

            var popupContent = '<strong>' + nomEntreprise + '</strong><br>' +
                               '<strong>Catégorie:</strong> ' + themeGeneralText + '<br>' +
                               '<strong>Sous-catégorie:</strong> ' + themeDetailText + '<br>' +
                               'SIREN: ' + siren + '<br>' +
                               'SIRET: ' + siret + '<br>' +
                               'Ville: ' + commune + '<br>' +
                               'Adresse: ' + adresseComplete + '<br>' +
                               'Statut: ' + statut;
            var marker = L.marker([coords[1], coords[0]]).addTo(window.markersLayer);
            marker.bindPopup(popupContent);
          }
        });
      }
    }

    // Fonction de calcul de distance en km (Formule de Haversine)
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
  </script>
</body>
</html>
