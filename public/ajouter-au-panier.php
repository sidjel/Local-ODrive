<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour ajouter des produits au panier']);
    exit;
}

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer et valider les données
$produit_id = filter_input(INPUT_POST, 'produit_id', FILTER_VALIDATE_INT);
$quantite = filter_input(INPUT_POST, 'quantite', FILTER_VALIDATE_INT);

if (!$produit_id || !$quantite || $quantite <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

// Vérifier si le produit existe et est en stock
$sql = "SELECT stock FROM produits WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$produit_id]);
$produit = $stmt->fetch();

if (!$produit) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Produit non trouvé']);
    exit;
}

if ($produit['stock'] < $quantite) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Stock insuffisant']);
    exit;
}

// Ajouter au panier
if (ajouterAuPanier($pdo, $_SESSION['user_id'], $produit_id, $quantite)) {
    // Récupérer le nombre total d'articles dans le panier
    $sql = "SELECT SUM(quantite) as total FROM panier_details 
            WHERE panier_id = (SELECT id FROM paniers WHERE user_id = ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $total = $stmt->fetch()['total'] ?? 0;

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Produit ajouté au panier',
        'total_items' => $total
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout au panier']);
} 