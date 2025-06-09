<?php
require_once '../includes/config.php';

// ─────────────────────────────────────────────
// 1. Lecture des paramètres GET + nettoyage
// ─────────────────────────────────────────────
$search   = filter_input(INPUT_GET, 'q',   FILTER_SANITIZE_SPECIAL_CHARS);
$category = filter_input(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
$minPrice = filter_input(INPUT_GET, 'min', FILTER_VALIDATE_FLOAT);
$maxPrice = filter_input(INPUT_GET, 'max', FILTER_VALIDATE_FLOAT);

$page    = max(1, (int)filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT));
$perPage = 12;
$offset  = ($page - 1) * $perPage;

// ─────────────────────────────────────────────
// 2. Construction dynamique du WHERE
// ─────────────────────────────────────────────
$conditions = [];
$params     = [];

if ($search) {
    $conditions[]          = '(p.name LIKE :searchName OR c.label LIKE :searchCat)';
    $params[':searchName'] = "%{$search}%";
    $params[':searchCat']  = "%{$search}%";
}

if ($category) {
    $conditions[]        = 'p.category_id = :category';
    $params[':category'] = $category;
}
if ($minPrice !== null) {
    $conditions[]        = 'p.price >= :minPrice';
    $params[':minPrice'] = $minPrice;
}
if ($maxPrice !== null) {
    $conditions[]        = 'p.price <= :maxPrice';
    $params[':maxPrice'] = $maxPrice;
}

$whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// ─────────────────────────────────────────────
// 3. Nombre total d’éléments (pagination)
// ─────────────────────────────────────────────
$countSql = "SELECT COUNT(*) FROM products p $whereSql";
$stmt     = $pdo->prepare($countSql);
$stmt->execute($params);
$totalItems = (int) $stmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalItems / $perPage));

// ─────────────────────────────────────────────
// 4. Sélection des produits (avec jointure cat.)
// ─────────────────────────────────────────────
$sql = "
  SELECT p.id, p.name, p.price, p.image, c.label AS category
  FROM products p
  JOIN categories c ON p.category_id = c.id
  $whereSql
  ORDER BY p.name
  LIMIT :limit OFFSET :offset
";
// Préparation de la requête
$stmt = $pdo->prepare($sql);

// paramètres dynamiques (recherche, filtres…)
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
// pagination
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);

$stmt->execute();
$catalogue = $stmt->fetchAll();

// ─────────────────────────────────────────────
// 5. Liste des catégories pour la barre de filtre
// ─────────────────────────────────────────────
$categories = $pdo->query('SELECT id, label FROM categories ORDER BY label')
                  ->fetchAll();
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

    <form method="get" class="mb-4">
        <div class="row align-items-end">
            <div class="col-md-4 mb-2">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search ?? ''); ?>" class="form-control" placeholder="Rechercher...">
            </div>
            <div class="col-md-3 mb-2">
                <select name="cat" class="form-select">
                    <option value="">Catégorie</option>
                    <?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['label']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <input type="number" step="0.01" name="min" class="form-control" placeholder="Prix min" value="<?php echo htmlspecialchars($minPrice ?? ''); ?>">
            </div>
            <div class="col-md-2 mb-2">
                <input type="number" step="0.01" name="max" class="form-control" placeholder="Prix max" value="<?php echo htmlspecialchars($maxPrice ?? ''); ?>">
            </div>
            <div class="col-md-1 mb-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i></button>
            </div>
        </div>
    </form>

    <?php if ($catalogue): ?>
        <div class="row">
            <?php foreach ($catalogue as $product): ?>
                <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
                    <div class="card h-100">
                        <?php if ($product['image']): ?>
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text mb-2"><?php echo number_format($product['price'], 2); ?> €</p>
                            <form method="post" action="ajouter-au-panier.php" class="mt-auto add-to-cart-form">
                                <input type="hidden" name="produit_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="quantite" value="1">
                                <button type="submit" class="btn btn-success btn-sm w-100">Ajouter au panier</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <p>Aucun produit disponible.</p>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/catalogue.js"></script>
</body>
</html>