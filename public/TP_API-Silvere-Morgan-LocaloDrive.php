<!-- Version 3 : Intégration de l'API GeoZone en synergie avec l'API Base Adresse Nationale -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Localo'Drive - Page d'accueil</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>Bienvenue sur Localo'Drive</h1>
    <p>Découvrez les producteurs locaux et soutenez l'économie de proximité.</p>

    <!-- je crée une section dédiée à la recherche d'adresses et à l'affichage des zones -->
    <div class="api-container">
        <h2>Recherche d'adresse et affichage de la zone (API Base Adresse Nationale + GeoZone)</h2>
        <form id="formulaire-adresse">
            <input type="text" id="champ-adresse" placeholder="Entrez une adresse">
            <button type="submit">Rechercher</button>
        </form>
        <div id="resultats-api">
            <!-- je vais afficher ici les résultats de l'API Base Adresse Nationale -->
        </div>
    </div>

    <script>
        // je récupère le formulaire de recherche d'adresse
        const formulaireAdresse = document.getElementById('formulaire-adresse');

        formulaireAdresse.addEventListener('submit', function(e) {
            e.preventDefault(); // je préviens le rechargement de la page lors de la soumission
            const adresseRecherche = document.getElementById('champ-adresse').value;
            if (adresseRecherche.trim() === '') {
                alert("Veuillez entrer une adresse");
                return;
            }
            // je lance la recherche d'adresse via l'API Base Adresse Nationale
            rechercherAdresse(adresseRecherche);
        });

        function rechercherAdresse(adresse) {
            // je prépare l'URL de l'API Base Adresse Nationale
            const url = `https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(adresse)}`;
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    // je traite et affiche les résultats de l'API Base Adresse Nationale
                    afficherResultats(data);
                })
                .catch(error => {
                    console.error('Erreur lors de la récupération des données :', error);
                });
        }

        function afficherResultats(data) {
            const conteneurResultats = document.getElementById('resultats-api');
            conteneurResultats.innerHTML = '';

            if (data.features && data.features.length > 0) {
                // je parcours chaque résultat retourné par l'API
                data.features.forEach(feature => {
                    const propriete = feature.properties;
                    // création d'un conteneur pour chaque résultat
                    const divResultat = document.createElement('div');
                    divResultat.classList.add('resultat');

                    // affichage de l'adresse et des coordonnées
                    divResultat.innerHTML = `
                        <p><strong>Adresse :</strong> ${propriete.label}</p>
                        <p><strong>Latitude :</strong> ${feature.geometry.coordinates[1]}</p>
                        <p><strong>Longitude :</strong> ${feature.geometry.coordinates[0]}</p>
                    `;

                    // création du bouton pour afficher la zone
                    const boutonZone = document.createElement('button');
                    boutonZone.textContent = "Afficher la zone";
                    // je stocke le citycode dans un attribut data pour l'utiliser lors de la requête GeoZone
                    boutonZone.setAttribute('data-citycode', propriete.citycode);
                    boutonZone.addEventListener('click', function() {
                        // je récupère le code de la commune (citycode)
                        const citycode = this.getAttribute('data-citycode');
                        // je lance la récupération des informations de la zone
                        recupererZone(citycode, divResultat);
                    });

                    divResultat.appendChild(boutonZone);
                    conteneurResultats.appendChild(divResultat);
                });
            } else {
                // je signale qu'aucun résultat n'a été trouvé
                conteneurResultats.innerHTML = '<p>Aucun résultat trouvé.</p>';
            }
        }

        function recupererZone(citycode, conteneur) {
            // je prépare l'URL de l'API GeoZone avec les champs souhaités
            const urlGeo = `https://geo.api.gouv.fr/communes/${citycode}?fields=nom,centre,departement,region`;
            fetch(urlGeo)
                .then(response => response.json())
                .then(data => {
                    // je traite et affiche les informations de la zone
                    afficherZone(data, conteneur);
                })
                .catch(error => {
                    console.error('Erreur lors de la récupération des données de la zone :', error);
                });
        }

        function afficherZone(data, conteneur) {
            // création ou récupération du conteneur pour afficher les informations de la zone
            let divZone = conteneur.querySelector('.zone-info');
            if (!divZone) {
                divZone = document.createElement('div');
                divZone.classList.add('zone-info');
                conteneur.appendChild(divZone);
            }
            // extraction des informations de la zone
            const nomCommune = data.nom || "N/A";
            const nomDepartement = data.departement ? data.departement.nom : "N/A";
            const nomRegion = data.region ? data.region.nom : "N/A";
            let latitudeCentre = "N/A", longitudeCentre = "N/A";
            if (data.centre && data.centre.coordinates) {
                // attention : dans la réponse de l'API, l'ordre est [longitude, latitude]
                longitudeCentre = data.centre.coordinates[0];
                latitudeCentre = data.centre.coordinates[1];
            }
            // affichage des informations de la zone
            divZone.innerHTML = `
                <p><strong>Nom de la commune :</strong> ${nomCommune}</p>
                <p><strong>Département :</strong> ${nomDepartement}</p>
                <p><strong>Région :</strong> ${nomRegion}</p>
                <p><strong>Coordonnées du centre :</strong> Latitude: ${latitudeCentre}, Longitude: ${longitudeCentre}</p>
            `;
        }
    </script>
</body>
</html>
