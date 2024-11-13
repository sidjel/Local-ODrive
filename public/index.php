<?php
    // Inclusion du header
    include('../includes/header.php');
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
            <h1>Bienvenue sur notre page de LocalO’drive</h1>
            <p>Découvrez notre sélection de produits locaux et éco-responsables.</p>
            <button id="learnMoreBtn" class="btn btn-primary">En savoir plus</button>
        </section>

        <!-- Exemples de produits -->
        <section class="products mt-5">
            <div class="row">
                <div class="col-md-4">
                    <div class="product-card">
                        <img src="images/product1.jpg" alt="Produit 1" class="img-fluid">
                        <h3>Produit Bio 1</h3>
                        <p>Description du produit bio 1...</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="product-card">
                        <img src="images/product2.jpg" alt="Produit 2" class="img-fluid">
                        <h3>Produit Bio 2</h3>
                        <p>Description du produit bio 2...</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="product-card">
                        <img src="images/product3.jpg" alt="Produit 3" class="img-fluid">
                        <h3>Produit Bio 3</h3>
                        <p>Description du produit bio 3...</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Inclusion du footer -->
    <?php include('../includes/footer.php'); ?>
</body>
</html>
