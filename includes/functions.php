<?php
// Ce fichier contient les fonctions utilitaires pour la gestion du panier et des utilisateurs.

/**
 * Ajoute un produit au panier
 * @param int $user_id ID de l'utilisateur
 * @param int $produit_id ID du produit
 * @param int $quantite Quantité à ajouter
 * @return bool Succès de l'opération
 */
function ajouterAuPanier($pdo, $user_id, $produit_id, $quantite = 1) {
    try {
        // Vérifier si l'utilisateur a déjà un panier
        $sql = "SELECT id FROM paniers WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $panier = $stmt->fetch();

        if (!$panier) {
            // Créer un nouveau panier
            $sql = "INSERT INTO paniers (user_id) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id]);
            $panier_id = $pdo->lastInsertId();
        } else {
            $panier_id = $panier['id'];
        }

        // Vérifier si le produit est déjà dans le panier
        $sql = "SELECT quantite FROM panier_details WHERE panier_id = ? AND produit_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$panier_id, $produit_id]);
        $existant = $stmt->fetch();

        if ($existant) {
            // Mettre à jour la quantité
            $sql = "UPDATE panier_details SET quantite = quantite + ? WHERE panier_id = ? AND produit_id = ?";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([$quantite, $panier_id, $produit_id]);
        } else {
            // Ajouter le produit
            $sql = "INSERT INTO panier_details (panier_id, produit_id, quantite) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([$panier_id, $produit_id, $quantite]);
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de l'ajout au panier : " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère le contenu du panier d'un utilisateur
 * @param int $user_id ID de l'utilisateur
 * @return array Contenu du panier
 */
function getPanier($pdo, $user_id) {
    try {
        $sql = "SELECT p.*, pr.nom as produit_nom, pr.prix, pr.image_url, pr.stock, pr.unite, prd.nom as producteur_nom 
                FROM panier_details p 
                JOIN produits pr ON p.produit_id = pr.id 
                JOIN producteurs prd ON pr.producteur_id = prd.id 
                WHERE p.panier_id = (SELECT id FROM paniers WHERE user_id = ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération du panier : " . $e->getMessage());
        return [];
    }
}

/**
 * Calcule le total du panier
 * @param array $panier_items Items du panier
 * @return float Total du panier
 */
function calculerTotalPanier($panier_items) {
    $total = 0;
    foreach ($panier_items as $item) {
        $total += $item['prix'] * $item['quantite'];
    }
    return $total;
}

/**
 * Met à jour la quantité d'un produit dans le panier
 * @param int $user_id ID de l'utilisateur
 * @param int $produit_id ID du produit
 * @param int $quantite Nouvelle quantité
 * @return bool Succès de l'opération
 */
function mettreAJourQuantitePanier($pdo, $user_id, $produit_id, $quantite) {
    try {
        if ($quantite <= 0) {
            return supprimerDuPanier($pdo, $user_id, $produit_id);
        }

        $sql = "UPDATE panier_details SET quantite = ? 
                WHERE panier_id = (SELECT id FROM paniers WHERE user_id = ?) 
                AND produit_id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$quantite, $user_id, $produit_id]);
    } catch (PDOException $e) {
        error_log("Erreur lors de la mise à jour du panier : " . $e->getMessage());
        return false;
    }
}

/**
 * Supprime un produit du panier
 * @param int $user_id ID de l'utilisateur
 * @param int $produit_id ID du produit
 * @return bool Succès de l'opération
 */
function supprimerDuPanier($pdo, $user_id, $produit_id) {
    try {
        $sql = "DELETE FROM panier_details 
                WHERE panier_id = (SELECT id FROM paniers WHERE user_id = ?) 
                AND produit_id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$user_id, $produit_id]);
    } catch (PDOException $e) {
        error_log("Erreur lors de la suppression du panier : " . $e->getMessage());
        return false;
    }
}

/**
 * Vide le panier d'un utilisateur
 * @param int $user_id ID de l'utilisateur
 * @return bool Succès de l'opération
 */
function viderPanier($pdo, $user_id) {
    try {
        $sql = "DELETE FROM panier_details 
                WHERE panier_id = (SELECT id FROM paniers WHERE user_id = ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$user_id]);
    } catch (PDOException $e) {
        error_log("Erreur lors du vidage du panier : " . $e->getMessage());
        return false;
    }
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour vérifier si l'utilisateur est admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Fonction pour vérifier si l'utilisateur est producteur
function isProducteur() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'producteur';
}

// Fonction pour sécuriser les sorties HTML
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Fonction pour formater les prix
function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' €';
}

// Fonction pour formater les dates
function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}
