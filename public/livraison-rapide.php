<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livraison Rapide - LocalO'drive</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/poppins.css" rel="stylesheet">
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
                    <h1 class="display-4 fw-bold">Livraison Rapide</h1>
                    <p class="lead">Recevez vos produits locaux en un temps record</p>
                </div>
                <div class="col-lg-6">
                    <img src="../img/delivery-hero.jpg" alt="Livraison rapide" class="img-fluid rounded-3 shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Process Section -->
    <section class="process-section">
        <div class="container">
            <h2 class="section-title">Notre Processus de Livraison</h2>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="process-card">
                        <div class="process-number">1</div>
                        <i class="fas fa-shopping-cart process-icon"></i>
                        <h3>Commande</h3>
                        <p>Passez votre commande en ligne 24h/24</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="process-card">
                        <div class="process-number">2</div>
                        <i class="fas fa-box process-icon"></i>
                        <h3>Préparation</h3>
                        <p>Nos équipes préparent votre commande avec soin</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="process-card">
                        <div class="process-number">3</div>
                        <i class="fas fa-truck process-icon"></i>
                        <h3>Livraison</h3>
                        <p>Livraison express par nos livreurs locaux</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="process-card">
                        <div class="process-number">4</div>
                        <i class="fas fa-home process-icon"></i>
                        <h3>Réception</h3>
                        <p>Recevez vos produits frais à domicile</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Zones Section -->
    <section class="zones-section">
        <div class="container">
            <h2 class="section-title">Nos Zones de Livraison</h2>
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="zone-card coming-soon">
                        <div class="coming-soon-badge">Prochainement</div>
                        <i class="fas fa-map-marked-alt zone-icon"></i>
                        <h3>Lyon et Agglomération</h3>
                        <p>Livraison en 2h dans tout Lyon et sa périphérie</p>
                        <div class="coming-soon-info">
                            <i class="fas fa-clock"></i>
                            <span>Disponible très prochainement</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="zone-card">
                        <i class="fas fa-map-marked-alt zone-icon"></i>
                        <h3>Région Auvergne-Rhône-Alpes</h3>
                        <p>Livraison en 24h dans toute la région</p>
                        <div class="zone-status">
                            <i class="fas fa-check-circle"></i>
                            <span>Disponible maintenant</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container text-center">
            <h2 class="cta-title">Prêt à recevoir vos produits ?</h2>
            <a href="TP_API-Silvere-Morgan-LocaloDrive.php" class="cta-button">Commander maintenant</a>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html> 