<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LocalO'drive - Livraison de produits locaux</title>
    
    <!-- Préchargement des polices -->
    <link rel="preload" href="../assets/css/poppins.css" as="style">
    
    <!-- Styles -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/poppins.css" rel="stylesheet">
    <link href="../css/home.css" rel="stylesheet">
    <!-- CSS critique inline pour éviter le FOUC -->
    <style>
        :root {
            --primary-color: #84df84;
            --secondary-color: #2c3e50;
            --accent-color: #e67e22;
            --text-color: #333;
            --light-bg: #f8f9fa;
        }

        body {
            opacity: 0;
            visibility: hidden;
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
        }

        .loaded {
            opacity: 1;
            visibility: visible;
            transition: opacity 0.3s ease;
        }

        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('../img/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            margin-bottom: 50px;
        }

        .hero-content {
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 30px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 12px 30px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #6bc76b;
            border-color: #6bc76b;
            transform: translateY(-2px);
        }

        .features-section {
            padding: 80px 0;
            background-color: var(--light-bg);
        }

        .feature-card {
            text-align: center;
            padding: 30px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .products-section {
            padding: 80px 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title h2 {
            font-size: 2.5rem;
            color: var(--secondary-color);
            margin-bottom: 15px;
        }

        .product-card {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            background: white;
            margin-bottom: 30px;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .product-image {
            height: 250px;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.1);
        }

        .product-content {
            padding: 25px;
        }

        .product-title {
            font-size: 1.5rem;
            color: var(--secondary-color);
            margin-bottom: 15px;
        }

        .product-description {
            color: #666;
            line-height: 1.6;
        }

        .cta-section {
            background: linear-gradient(45deg, var(--primary-color), #6bc76b);
            color: white;
            padding: 80px 0;
            text-align: center;
        }

        .cta-title {
            font-size: 2.5rem;
            margin-bottom: 30px;
        }

        .cta-button {
            background: white;
            color: var(--primary-color);
            padding: 15px 40px;
            border-radius: 30px;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.body.classList.add('loaded');
    });
</script>
<?php include '../includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Découvrez les Saveurs Locales</h1>
                <p class="hero-subtitle">Des produits frais et authentiques de la région Auvergne-Rhône-Alpes, livrés directement chez vous.</p>
                <a href="../public/TP_API-Silvere-Morgan-LocaloDrive.php" class="btn btn-primary btn-lg">Découvrir nos produits</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <a href="produits-locaux.php" class="text-decoration-none">
                        <div class="feature-card">
                            <i class="fas fa-store feature-icon"></i>
                            <h3>Produits Locaux</h3>
                            <p>Découvrez la richesse des produits de notre région, cultivés et produits localement.</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="livraison-rapide.php" class="text-decoration-none">
                        <div class="feature-card">
                            <i class="fas fa-truck feature-icon"></i>
                            <h3>Livraison Rapide</h3>
                            <p>Recevez vos produits locaux en un temps record, directement à votre porte.</p>
                        </div>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="qualite-garantie.php" class="text-decoration-none">
                        <div class="feature-card">
                            <i class="fas fa-certificate feature-icon"></i>
                            <h3>Qualité Garantie</h3>
                            <p>Des produits sélectionnés avec soin et contrôlés pour votre satisfaction.</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="products-section">
        <div class="container">
            <div class="section-title">
                <h2>Nos Produits Vedettes</h2>
                <p>Découvrez notre sélection de produits locaux de qualité</p>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="product-card">
                        <div class="product-image">
                            <img src="../img/Confiture.jpg" alt="Confitures Bio">
                        </div>
                        <div class="product-content">
                            <h3 class="product-title">Confitures Bio</h3>
                            <p class="product-description">
                                Nos confitures bio artisanales sont préparées avec les meilleurs fruits de la région, 
                                cueillis à maturité pour une explosion de saveurs naturelles.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="product-card">
                        <div class="product-image">
                            <img src="../img/beurre.jpg" alt="Beurre Bio">
                        </div>
                        <div class="product-content">
                            <h3 class="product-title">Beurre Bio</h3>
                            <p class="product-description">
                                Notre beurre bio local est fabriqué à partir de lait provenant de fermes respectueuses 
                                de l'environnement et des normes biologiques les plus strictes.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="product-card">
                        <div class="product-image">
                            <img src="../img/oeuf.webp" alt="Oeufs Bio">
                        </div>
                        <div class="product-content">
                            <h3 class="product-title">Oeufs Bio</h3>
                            <p class="product-description">
                                Nos œufs bio locaux sont produits dans le respect total de l'environnement et du bien-être animal, 
                                par des fermes certifiées bio de la région.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2 class="cta-title">Prêt à découvrir nos produits locaux ?</h2>
            <a href="../public/TP_API-Silvere-Morgan-LocaloDrive.php" class="btn cta-button">Commencez vos achats</a>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
