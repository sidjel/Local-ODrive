<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Récupérer le panier de l'utilisateur
$sql = "SELECT p.*, pr.name as produit_nom, pr.price, pr.image, pr.stock, pr.unit, prd.name as producteur_nom
        FROM panier_details p
        JOIN products pr ON p.produit_id = pr.id
        JOIN producteurs prd ON pr.producteur_id = prd.id
        WHERE p.panier_id = (SELECT id FROM paniers WHERE user_id = ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$panier_items = $stmt->fetchAll();

$total = 0;
foreach ($panier_items as $item) {
    $total += $item['price'] * $item['quantite'];
}

// Traitement des actions sur le panier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update':
                $quantite = filter_input(INPUT_POST, 'quantite', FILTER_VALIDATE_INT);
                $produit_id = filter_input(INPUT_POST, 'produit_id', FILTER_VALIDATE_INT);
                
                if ($quantite > 0) {
                    $sql = "UPDATE panier_details SET quantite = ? WHERE panier_id = (SELECT id FROM paniers WHERE user_id = ?) AND produit_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$quantite, $_SESSION['user_id'], $produit_id]);
                }
                break;
                
            case 'remove':
                $produit_id = filter_input(INPUT_POST, 'produit_id', FILTER_VALIDATE_INT);
                
                $sql = "DELETE FROM panier_details WHERE panier_id = (SELECT id FROM paniers WHERE user_id = ?) AND produit_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_SESSION['user_id'], $produit_id]);
                break;
                
            case 'clear':
                $sql = "DELETE FROM panier_details WHERE panier_id = (SELECT id FROM paniers WHERE user_id = ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_SESSION['user_id']]);
                break;
        }
        
        // Rediriger pour éviter la soumission multiple du formulaire
        header('Location: panier.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Panier - LocalO'drive</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-5">
        <h2 class="mb-4">Mon Panier</h2>

        <?php if (empty($panier_items)): ?>
            <div class="alert alert-info">
                Votre panier est vide. <a href="index.php">Continuer vos achats</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-8">
                    <?php foreach ($panier_items as $item): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-2">
                                        <?php if ($item['image']): ?>
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['produit_nom']); ?>">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <h5 class="card-title"><?php echo htmlspecialchars($item['produit_nom']); ?></h5>
                                        <p class="card-text">
                                            Producteur : <?php echo htmlspecialchars($item['producteur_nom']); ?><br>
                                            Prix unitaire : <?php echo number_format($item['price'], 2); ?> € / <?php echo htmlspecialchars($item['unit']); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-2">
                                        <form method="POST" action="" class="d-flex align-items-center">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="produit_id" value="<?php echo $item['produit_id']; ?>">
                                            <input type="number" name="quantite" value="<?php echo $item['quantite']; ?>" 
                                                   min="1" max="<?php echo $item['stock']; ?>" 
                                                   class="form-control form-control-sm" 
                                                   onchange="this.form.submit()">
                                        </form>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <p class="card-text">
                                            <strong><?php echo number_format($item['price'] * $item['quantite'], 2); ?> €</strong>
                                        </p>
                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="produit_id" value="<?php echo $item['produit_id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="d-flex justify-content-between mb-4">
                        <a href="index.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left"></i> Continuer mes achats
                        </a>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fas fa-trash"></i> Vider le panier
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Récapitulatif</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <span>Sous-total</span>
                                <span><?php echo number_format($total, 2); ?> €</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Livraison</span>
                                <span>Calculé à l'étape suivante</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total</strong>
                                <strong><?php echo number_format($total, 2); ?> €</strong>
                            </div>
                            <a href="checkout.php" class="btn btn-primary w-100">
                                Passer la commande
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html> 