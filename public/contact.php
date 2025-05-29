<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - LocalO'drive</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/poppins.css" rel="stylesheet">
    <link href="../css/home.css" rel="stylesheet">
    <link href="../css/pages.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <?php 
    // Chargement des variables d'environnement pour le reCAPTCHA
    require_once __DIR__ . "/../vendor/autoload.php";
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
    include '../includes/header.php'; 
    ?>

    <!-- Hero Section -->
    <section class="page-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold">Contactez-nous</h1>
                    <p class="lead">Une question ? Un projet ? N'hésitez pas à nous contacter</p>
                </div>
                <div class="col-lg-6">
                    <img src="../img/contact-hero.jpg" alt="Contact LocalO'drive" class="img-fluid rounded-3 shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section py-5">
        <div class="container">
            <div class="row g-4">
                <!-- Informations de contact -->
                <div class="col-lg-4">
                    <div class="contact-info">
                        <h2 class="section-title mb-4">Nos Coordonnées</h2>
                        <div class="info-item mb-4">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <h3>Adresse</h3>
                                <p>123 Rue des Producteurs<br>69000 Lyon, France</p>
                            </div>
                        </div>
                        <div class="info-item mb-4">
                            <i class="fas fa-phone"></i>
                            <div>
                                <h3>Téléphone</h3>
                                <p>+33 4 72 00 00 00</p>
                            </div>
                        </div>
                        <div class="info-item mb-4">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <h3>Email</h3>
                                <p>contact@localodrive.fr</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h3>Horaires</h3>
                                <p>Lundi - Vendredi: 9h - 18h<br>Samedi: 9h - 12h</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulaire de contact -->
                <div class="col-lg-8">
                    <div class="contact-form">
                        <h2 class="section-title mb-4">Envoyez-nous un message</h2>
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success" role="alert">
                                Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php
                                switch ($_GET['error']) {
                                    case 'missing_fields':
                                        echo "Veuillez remplir tous les champs obligatoires.";
                                        break;
                                    case 'mail_error':
                                        echo "Une erreur est survenue lors de l'envoi du message. Veuillez réessayer plus tard.";
                                        break;
                                    case 'captcha':
                                        echo "Veuillez valider le reCAPTCHA pour prouver que vous n'êtes pas un robot.";
                                        break;
                                    case 'invalid_email':
                                        echo "L'adresse email fournie n'est pas valide.";
                                        break;
                                    case 'server_error':
                                        echo "Une erreur est survenue sur le serveur. Veuillez réessayer plus tard.";
                                        if (isset($_GET['details'])) {
                                            echo "<br><small>Détails techniques : " . htmlspecialchars($_GET['details']) . "</small>";
                                        }
                                        break;
                                    default:
                                        echo "Une erreur est survenue. Veuillez réessayer.";
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                        <form id="contactForm" action="process_contact.php" method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Nom complet *</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email *</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="subject">Sujet *</label>
                                        <input type="text" class="form-control" id="subject" name="subject" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="message">Message *</label>
                                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                                    </div>
                                </div>
                                <div class="col-12 mb-3">
                                    <!-- Ajout du widget reCAPTCHA avec vérification de la clé -->
                                    <?php if (isset($_ENV['RECAPTCHA_SITE_KEY'])): ?>
                                        <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($_ENV['RECAPTCHA_SITE_KEY']); ?>"></div>
                                    <?php else: ?>
                                        <div class="alert alert-warning">
                                            La clé reCAPTCHA n'est pas configurée. Veuillez contacter l'administrateur.
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Envoyer le message</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section py-5">
        <div class="container">
            <h2 class="section-title mb-4">Notre Localisation</h2>
            <div class="map-container rounded-3 shadow">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2784.332792161!2d4.8357!3d45.7578!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDXCsDQ1JzI4LjEiTiA0wrA1MCcxMi41IkU!5e0!3m2!1sfr!2sfr!4v1635000000000!5m2!1sfr!2sfr" 
                    width="100%" 
                    height="100%" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
            </div>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html> 