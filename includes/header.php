<?php
// Récupérer le nombre d'articles dans le panier si l'utilisateur est connecté
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $sql = "SELECT SUM(quantite) as total FROM panier_details 
            WHERE panier_id = (SELECT id FROM paniers WHERE user_id = ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $cart_count = $result['total'] ?? 0;
}
?>
<header>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="/">Local<span class="text-vert-pomme">O'</span>drive</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/public/index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/public/TP_API-Silvere-Morgan-LocaloDrive.php">API LocaloDrive</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/public/contact.php">Contact</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="panier.php">
                                <i class="fas fa-shopping-cart"></i>
                                <?php if ($cart_count > 0): ?>
                                    <span id="cart-count" class="badge bg-primary"><?php echo $cart_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profil.php">Mon Profil</a></li>
                                <?php if ($_SESSION['user_role'] === 'producteur'): ?>
                                    <li><a class="dropdown-item" href="producteur/dashboard.php">Tableau de bord</a></li>
                                <?php endif; ?>
                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                    <li><a class="dropdown-item" href="admin/dashboard.php">Administration</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Déconnexion</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/public/login.php">Connexion</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/public/register.php">Inscription</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>

<!-- Scripts -->
<script src="../assets/js/bootstrap.bundle.min.js"></script>
