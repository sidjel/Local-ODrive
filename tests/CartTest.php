<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/functions.php';

class CartTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec(
            "CREATE TABLE paniers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL
            );"
        );

        $this->pdo->exec(
            "CREATE TABLE panier_details (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                panier_id INTEGER NOT NULL,
                produit_id INTEGER NOT NULL,
                quantite INTEGER NOT NULL
            );"
        );
    }

    public function testAjouterAuPanierCreeUnEnregistrementAvecQuantite()
    {
        $userId = 1;
        $produitId = 42;
        $quantite = 3;

        $result = ajouterAuPanier($this->pdo, $userId, $produitId, $quantite);
        $this->assertTrue($result);

        $stmt = $this->pdo->prepare(
            "SELECT pd.quantite FROM panier_details pd
             JOIN paniers p ON pd.panier_id = p.id
             WHERE p.user_id = ? AND pd.produit_id = ?"
        );
        $stmt->execute([$userId, $produitId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($row);
        $this->assertEquals($quantite, $row['quantite']);
    }
}
