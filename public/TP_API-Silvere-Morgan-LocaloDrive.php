<?php
// TP_API-Silvere-Morgan-LocaloDrive.php
// Version 6 (adaptée) : Intégration des API Base Adresse Nationale, GeoZone et Sirene (entreprise)
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
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center">Localo'Drive - Recherche et Carte</h1>
        <p class="text-center">Faciliter l'accès aux produits locaux en connectant producteurs et consommateurs</p>

        <!-- Formulaire de recherche d'adresse -->
        <form id="formulaire-adresse" class="d-flex justify-content-center mb-4">
            <input type="text" id="champ-adresse" class="form-control me-2" placeholder="Entrez une adresse" style="max-width:300px;">
            <button type="submit" class="btn btn-success">Rechercher</button>
        </form>

        <div class="row">
            <!-- Colonne des résultats (à gauche) -->
            <div class="col-md-4" id="colonne-resultats">
                <div id="resultats-api"></div>
            </div>
            <!-- Colonne de la carte (à droite) -->
            <div class="col-md-8" id="colonne-carte">
                <div id="map" style="height:500px;"></div>
            </div>
        </div>
    </div>

    <!-- Inclusion de Bootstrap JS Bundle -->
    <script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Inclusion de Leaflet JS -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <!-- Script JavaScript personnalisé -->
    <script>
        // Clé API Sirene (à adapter avec votre clé)
        const API_KEY_SIRENE = ***REMOVED***;

        // Initialisation de la carte centrée sur la France
        var map = L.map('map').setView([46.603354, 1.888334], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Création d'un layerGroup pour les marqueurs
        window.markersLayer = L.layerGroup().addTo(map);

        // Gestion du formulaire de recherche
        document.getElementById('formulaire-adresse').addEventListener('submit', function(e) {
            e.preventDefault();
            var adresseRecherche = document.getElementById('champ-adresse').value;
            if (adresseRecherche.trim() === '') {
                alert("Veuillez entrer une adresse");
                return;
            }
            rechercherAdresse(adresseRecherche);
        });

        // Fonction de recherche d'adresse via l'API Base Adresse Nationale
        function rechercherAdresse(adresse) {
            var url = 'https://api-adresse.data.gouv.fr/search/?q=' + encodeURIComponent(adresse);
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    afficherResultats(data);
                })
                .catch(error => {
                    console.error("Erreur lors de la récupération des données :", error);
                });
        }

        // Affichage des résultats et déclenchement des appels vers GeoZone et Sirene
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
                    var citycode = propriete.citycode;
                    var postcode = propriete.postcode; // Extraction du code postal

                    // Création d'un conteneur pour le résultat
                    var divResultat = document.createElement('div');
                    divResultat.className = 'resultat p-3 mb-3 border rounded';
                    divResultat.dataset.adresse = propriete.label;
                    divResultat.innerHTML = '<p><strong>Adresse :</strong> ' + propriete.label + '</p>' +
                                            '<p><strong>Latitude :</strong> ' + lat + '</p>' +
                                            '<p><strong>Longitude :</strong> ' + lng + '</p>' +
                                            '<p><strong>Code postal :</strong> ' + postcode + '</p>';

                    // Appel automatique de l'API GeoZone pour récupérer les détails de la zone
                    recupererZone(citycode, divResultat);
                    // Appel automatique de l'API Sirene pour récupérer les entreprises locales
                    // On ajoute un filtre sur l'activité pour les producteurs alimentaires (ici, on suppose que leur NAF commence par "10")
                    recupererEntreprises(postcode, divResultat);

                    conteneur.appendChild(divResultat);

                    // Ajout d'un marqueur sur la carte pour cet emplacement
                    var marker = L.marker([lat, lng]).addTo(window.markersLayer);
                    marker.bindPopup('<strong>Adresse :</strong> ' + propriete.label + '<br><em>Chargement des détails...</em>');
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
                // L'ordre retourné est [longitude, latitude]
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

        // Récupération des entreprises locales via l'API Sirene à partir du code postal
        function recupererEntreprises(postcode, conteneur) {
            // Construction de la requête avec un filtre supplémentaire pour les producteurs alimentaires
            // Ici, on suppose que les producteurs alimentaires ont un code NAF débutant par "10"
            var query = 'codePostalEtablissement:' + postcode; //filtre sur code postal et entreprise (toute, limite de 20)
            var urlSirene = 'https://api.insee.fr/api-sirene/3.11/siret?q=' + encodeURIComponent(query);
            fetch(urlSirene, {
                headers: {
                    'X-INSEE-Api-Key-Integration': API_KEY_SIRENE,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                afficherEntreprises(data, conteneur);
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
            // Pour afficher le nom de l'entreprise, on vérifie d'abord "denominationUniteLegale", sinon "nomUniteLegale"
            if (data && data.etablissements && data.etablissements.length > 0) {
                var html = '<p><strong>Entreprises locales :</strong></p><ul>';
                data.etablissements.forEach(function(etablissement) {
                    var ul = etablissement.uniteLegale;
                    var nomEntreprise = (ul && (ul.denominationUniteLegale || ul.nomUniteLegale)) ? (ul.denominationUniteLegale || ul.nomUniteLegale) : 'Nom non disponible';
                    var siren = etablissement.siren ? etablissement.siren : 'SIREN non disponible';
                    html += '<li>' + nomEntreprise + ' (SIREN: ' + siren + ')</li>';
                });
                html += '</ul>';
                divEntreprises.innerHTML = html;
            } else {
                divEntreprises.innerHTML = '<p>Aucune entreprise locale trouvée.</p>';
            }
        }
    </script>
</body>
</html>
