<?php
// TP_API-Silvere-Morgan-LocaloDrive.php
// Version 4 : ajout de l'affichage sur une carte OpenStreetMap via Leaflet et intégration de Bootstrap
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Localo'Drive - API et Carte</title>
    <!-- Inclusion (avant le CSS) de Bootstrap (v5.3) -->
    <link rel="stylesheet" href="../node_modules/bootstrap/dist/css/bootstrap.min.css">
    <!-- Inclusion de Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <!-- Inclusion du CSS personnalisé -->
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center">Localo'Drive - Recherche et Carte</h1>
        <p class="text-center">Exploitez les données de l'API Base Adresse Nationale et GeoZone sur une carte OpenStreetMap</p>

        <!-- je crée le formulaire de recherche d'adresse -->
        <form id="formulaire-adresse" class="d-flex justify-content-center mb-4">
            <input type="text" id="champ-adresse" class="form-control me-2" placeholder="Entrez une adresse" style="max-width: 300px;">
            <button type="submit" class="btn btn-success">Rechercher</button>
        </form>

        <!-- Conteneur pour afficher les résultats des API -->
        <div id="resultats-api"></div>

        <!-- Conteneur pour la carte Leaflet -->
        <div id="map" style="height: 500px; margin-top: 20px;"></div>
    </div>

    <!-- Inclusion de Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Inclusion de Leaflet JS -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <!-- Script JavaScript personnalisé -->
    <script>
        // Initialisation de la carte avec Leaflet, centrée sur la France
        var map = L.map('map').setView([46.603354, 1.888334], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Création d'un layerGroup pour gérer les marqueurs (pins)
        window.markersLayer = L.layerGroup().addTo(map);

        // Gestion du formulaire de recherche
        document.getElementById('formulaire-adresse').addEventListener('submit', function(e) {
            e.preventDefault(); // je préviens le rechargement de la page
            var adresseRecherche = document.getElementById('champ-adresse').value;
            if (adresseRecherche.trim() === '') {
                alert("Veuillez entrer une adresse");
                return;
            }
            // je lance la recherche via l'API Base Adresse Nationale
            rechercherAdresse(adresseRecherche);
        });

        // Fonction de recherche d'adresse
        function rechercherAdresse(adresse) {
            var url = 'https://api-adresse.data.gouv.fr/search/?q=' + encodeURIComponent(adresse);
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    afficherResultats(data);
                })
                .catch(error => {
                    console.error('Erreur lors de la récupération des données :', error);
                });
        }

        // Affichage des résultats de l'API Base Adresse Nationale
        function afficherResultats(data) {
            var conteneur = document.getElementById('resultats-api');
            conteneur.innerHTML = '';
            // On vide les marqueurs existants
            window.markersLayer.clearLayers();
            if (data.features && data.features.length > 0) {
                data.features.forEach(function(feature) {
                    var propriete = feature.properties;
                    var lat = feature.geometry.coordinates[1];
                    var lng = feature.geometry.coordinates[0];

                    // Création d'un conteneur pour chaque résultat
                    var divResultat = document.createElement('div');
                    divResultat.className = 'resultat p-3 mb-3 border rounded';
                    // Stockage de l'adresse dans un attribut pour utilisation ultérieure
                    divResultat.dataset.adresse = propriete.label;
                    divResultat.innerHTML = '<p><strong>Adresse :</strong> ' + propriete.label + '</p>' +
                                            '<p><strong>Latitude :</strong> ' + lat + '</p>' +
                                            '<p><strong>Longitude :</strong> ' + lng + '</p>';

                    // Création du bouton pour afficher la zone via l'API GeoZone
                    var boutonZone = document.createElement('button');
                    boutonZone.className = 'btn btn-primary';
                    boutonZone.textContent = 'Afficher la zone';
                    boutonZone.setAttribute('data-citycode', propriete.citycode);
                    boutonZone.addEventListener('click', function() {
                        var citycode = this.getAttribute('data-citycode');
                        recupererZone(citycode, divResultat);
                    });
                    divResultat.appendChild(boutonZone);
                    conteneur.appendChild(divResultat);

                    // Ajout d'un marqueur sur la carte pour cet emplacement
                    var marker = L.marker([lat, lng]).addTo(window.markersLayer);
                    // Popup initial avec l'adresse et indication que les détails sont en cours de chargement
                    marker.bindPopup('<strong>Adresse :</strong> ' + propriete.label + '<br><em>Chargement des détails...</em>');
                    // Association du marqueur au conteneur de résultat
                    divResultat.marker = marker;
                });
            } else {
                conteneur.innerHTML = '<p>Aucun résultat trouvé.</p>';
            }
        }

        // Récupération des informations de la zone via l'API GeoZone
        function recupererZone(citycode, conteneur) {
            var urlGeo = 'https://geo.api.gouv.fr/communes/' + citycode + '?fields=nom,centre,departement,region';
            fetch(urlGeo)
                .then(response => response.json())
                .then(data => {
                    afficherZone(data, conteneur);
                })
                .catch(error => {
                    console.error('Erreur lors de la récupération des données de la zone :', error);
                });
        }

        // Affichage des informations de la zone et mise à jour du popup du marqueur
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
                // Attention : l'ordre retourné par l'API est [longitude, latitude]
                longitudeCentre = data.centre.coordinates[0];
                latitudeCentre = data.centre.coordinates[1];
            }
            divZone.innerHTML = '<p><strong>Nom de la commune :</strong> ' + nomCommune + '</p>' +
                                '<p><strong>Département :</strong> ' + nomDepartement + '</p>' +
                                '<p><strong>Région :</strong> ' + nomRegion + '</p>' +
                                '<p><strong>Coordonnées du centre :</strong> Latitude: ' + latitudeCentre + ', Longitude: ' + longitudeCentre + '</p>';

            // Mise à jour du popup du marqueur associé à ce résultat
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
    </script>
</body>
</html>
