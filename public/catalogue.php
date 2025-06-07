<?php
require_once '../includes/config.php';

try {
    $pdoCatalogue = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    $sql = "SELECT id, name, category, price, image FROM products";
    $stmt = $pdoCatalogue->query($sql);
    $catalogue = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur lors de la récupération des produits : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/header.php'; ?>
<div class="container mt-5">
    <h2 class="mb-4">Catalogue</h2>
    <?php if ($catalogue): ?>
        <ul class="list-unstyled">
            <?php foreach ($catalogue as $product): ?>
                <li class="mb-4">
                    <ul class="list-inline">
                        <li class="list-inline-item">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-fluid" style="max-width:100px;">
                        </li>
                        <li class="list-inline-item">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </li>
                        <li class="list-inline-item">
                            <?php echo number_format($product['price'], 2, ',', ' '); ?> €
                        </li>
                        <li class="list-inline-item">
                            <form method="post" action="ajouter-au-panier.php" class="d-inline">
                                <input type="hidden" name="produit_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="quantite" value="1">
                                <button type="submit" class="btn btn-primary btn-sm">Ajouter au panier</button>
                            </form>
                        </li>
                    </ul>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Aucun produit disponible.</p>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>