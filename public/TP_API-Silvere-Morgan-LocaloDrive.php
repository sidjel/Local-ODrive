<!-- Version 2 : Intégration de l'API Base Adresse Nationale -->
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

    <!-- je crée une section dédiée à l'API Base Adresse Nationale -->
    <div class="api-container">
        <h2>Recherche d'adresse (API Base Adresse Nationale)</h2>
        <form id="formulaire-adresse">
            <input type="text" id="champ-adresse" placeholder="Entrez une adresse">
            <button type="submit">Rechercher</button>
        </form>
        <div id="resultats-api">
            <!-- je vais afficher ici les résultats de l'API -->
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
            // je lance la recherche d'adresse via l'API
            rechercherAdresse(adresseRecherche);
        });

        function rechercherAdresse(adresse) {
            // je prépare l'URL d'appel de l'API Base Adresse Nationale
            const url = `https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(adresse)}`;
            // je fais appel à l'API via fetch
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    // je traite et affiche les résultats de l'API
                    afficherResultats(data);
                })
                .catch(error => {
                    console.error('Erreur lors de la récupération des données :', error);
                });
        }

        function afficherResultats(data) {
            // je récupère le conteneur qui va afficher les résultats
            const conteneurResultats = document.getElementById('resultats-api');
            // je vide le conteneur pour ne pas empiler d'anciens résultats
            conteneurResultats.innerHTML = '';

            if (data.features && data.features.length > 0) {
                // je parcours chaque résultat retourné par l'API
                data.features.forEach(feature => {
                    const propriete = feature.properties;
                    const divResultat = document.createElement('div');
                    divResultat.classList.add('resultat');
                    divResultat.innerHTML = `<p><strong>Adresse :</strong> ${propriete.label}</p>
                                             <p><strong>Latitude :</strong> ${feature.geometry.coordinates[1]}</p>
                                             <p><strong>Longitude :</strong> ${feature.geometry.coordinates[0]}</p>`;
                    conteneurResultats.appendChild(divResultat);
                });
            } else {
                // je signale qu'aucun résultat n'a été trouvé
                conteneurResultats.innerHTML = '<p>Aucun résultat trouvé.</p>';
            }
        }
    </script>
</body>
</html>
