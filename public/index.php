<?php
// Inclusion du header
include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Vitrine - LocalO’drive</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../node_modules/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="../node_modules/bootstrap/dist/css/bootstrap.min.css">
    <script src="../js/script.js" defer></script>
</head>
<body>
    <!-- Section principale -->
    <main class="container mt-4">
        <section class="intro">
            <h1>Bienvenue sur notre page LocalO’drive</h1>
            <p>Découvrez notre sélection de produits locaux et éco-responsables d'</p>
            <button id="learnMoreBtn" class="btn btn-primary">En savoir plus</button>
        </section>

        <!-- Exemples de produits -->
        <section class="products mt-5">
            <div class="row">
                <div class="col-md-4">
                    <div class="product-card">
                        <img src="../img/Confiture.jpg" alt="Produit 1" class="img-fluid">
                        <h3>Confitures Bio – de la Région Auvergne-Rhône-Alpes</h3>
                        <p>
                        Nos confitures bio artisanales sont préparées avec les meilleurs fruits de la région Auvergne-Rhône-Alpes, cueillis à maturité pour offrir une explosion de saveurs naturelles. 
                        Élaborées selon des méthodes traditionnelles, ces confitures reflètent le savoir-faire local et la richesse de la nature préservée, 
                        dans un respect total des principes de l’agriculture biologique.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="product-card">
                        <img src="../img/beurre.jpg" alt="Produit 2" class="img-fluid">
                        <h3>Beurre</h3>
                        <p>
                            Notre beurre bio local est fabriqué à partir de lait provenant de fermes respectueuses de l’environnement et des normes les plus strictes de l’agriculture biologique.
                            Produit avec soin, ce beurre crémeux est un incontournable de la cuisine bio, idéal pour ajouter une touche de richesse et de goût à vos plats,
                            pâtisseries ou tartines.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="product-card">
                        <img src="../img/oeuf.webp" alt="Produit 3" class="img-fluid">
                        <h3>Oeuf Bios</h3>
                        <p>
                            Nos œufs bio locaux sont produits dans le respect total de l’environnement et du bien-être animal. En provenance directe de fermes certifiées bio, chaque œuf est le résultat d’une agriculture durable et responsable.
                            Les poules sont élevées en plein air, nourries avec des aliments 100% biologiques, sans OGM,
                            pesticides ni produits chimiques.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Inclusion du footer -->
    <?php include '../includes/footer.php';?>
</body>
</html>
