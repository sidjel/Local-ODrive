<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produits Locaux - LocalO'drive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../css/home.css" rel="stylesheet">
    <link href="../css/pages.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="page-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold">Produits Locaux</h1>
                    <p class="lead">Découvrez la richesse des produits de notre région</p>
                </div>
                <div class="col-lg-6">
                    <img src="../img/local-products-hero.jpg" alt="Produits locaux" class="img-fluid rounded-3 shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Avantages Section -->
    <section class="benefits-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="benefit-card">
                        <i class="fas fa-leaf benefit-icon"></i>
                        <h3>Frais et de Saison</h3>
                        <p>Des produits récoltés à maturité et livrés rapidement pour préserver leur fraîcheur.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="benefit-card">
                        <i class="fas fa-map-marker-alt benefit-icon"></i>
                        <h3>Origine Garantie</h3>
                        <p>Chaque produit est tracé et provient de producteurs locaux certifiés.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="benefit-card">
                        <i class="fas fa-handshake benefit-icon"></i>
                        <h3>Circuit Court</h3>
                        <p>Un lien direct entre les producteurs et les consommateurs pour des prix justes.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Catégories Section -->
    <section class="categories-section">
        <div class="container">
            <h2 class="section-title">Nos Catégories de Produits</h2>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="category-card">
                        <img src="../img/fruits-legumes.jpg" alt="Fruits et Légumes">
                        <div class="category-content">
                            <h3>Fruits et Légumes</h3>
                            <p>Produits frais de saison cultivés localement</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="category-card">
                        <img src="../img/fromages.jpg" alt="Fromages">
                        <div class="category-content">
                            <h3>Fromages</h3>
                            <p>Fromages affinés traditionnels de la région</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="category-card">
                        <img src="../img/viandes.jpg" alt="Viandes">
                        <div class="category-content">
                            <h3>Viandes</h3>
                            <p>Viandes de qualité issues d'élevages locaux</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container text-center">
            <h2 class="cta-title">Prêt à découvrir nos produits locaux ?</h2>
            <a href="TP_API-Silvere-Morgan-LocaloDrive.php" class="cta-button">Explorer les produits</a>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 