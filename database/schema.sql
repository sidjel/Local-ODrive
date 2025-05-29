-- Création de la base de données
CREATE DATABASE IF NOT EXISTS localodrive CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE localodrive;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    telephone VARCHAR(20),
    adresse TEXT,
    code_postal VARCHAR(10),
    ville VARCHAR(100),
    role ENUM('client', 'producteur', 'admin') DEFAULT 'client',
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des tokens de validation d'email
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des catégories de produits
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des producteurs
CREATE TABLE IF NOT EXISTS producteurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    siret VARCHAR(14) UNIQUE,
    nom_entreprise VARCHAR(255) NOT NULL,
    description TEXT,
    adresse TEXT NOT NULL,
    code_postal VARCHAR(10) NOT NULL,
    ville VARCHAR(100) NOT NULL,
    telephone VARCHAR(20),
    email VARCHAR(255),
    horaires TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des produits
CREATE TABLE IF NOT EXISTS produits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    producteur_id INT NOT NULL,
    categorie_id INT NOT NULL,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    unite VARCHAR(50) NOT NULL,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (producteur_id) REFERENCES producteurs(id) ON DELETE CASCADE,
    FOREIGN KEY (categorie_id) REFERENCES categories(id)
);

-- Table des commandes
CREATE TABLE IF NOT EXISTS commandes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    statut ENUM('en_attente', 'confirmee', 'en_preparation', 'en_livraison', 'livree', 'annulee') DEFAULT 'en_attente',
    adresse_livraison TEXT NOT NULL,
    code_postal_livraison VARCHAR(10) NOT NULL,
    ville_livraison VARCHAR(100) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Table des détails de commande
CREATE TABLE IF NOT EXISTS commande_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id)
);

-- Table des paniers
CREATE TABLE IF NOT EXISTS paniers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des détails de panier
CREATE TABLE IF NOT EXISTS panier_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    panier_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (panier_id) REFERENCES paniers(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id)
);

-- Insertion des catégories de base
INSERT INTO categories (nom, description) VALUES
('Fruits et Légumes', 'Produits frais locaux'),
('Produits Laitiers', 'Fromages, yaourts et autres produits laitiers'),
('Viandes et Volailles', 'Viandes et volailles locales'),
('Boulangerie', 'Pain et pâtisseries artisanales'),
('Boissons', 'Jus, cidres et autres boissons locales'); 