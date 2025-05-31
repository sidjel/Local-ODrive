<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

$error = '';
$success = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        $pdo->beginTransaction();
        
        // Vérifier si le token existe et n'est pas expiré
        $sql = "SELECT user_id FROM email_verifications 
                WHERE token = ? AND expires_at > NOW() 
                AND NOT EXISTS (
                    SELECT 1 FROM users 
                    WHERE id = email_verifications.user_id 
                    AND email_verified = TRUE
                )";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$token]);
        
        if ($stmt->rowCount() > 0) {
            $user_id = $stmt->fetchColumn();
            
            // Marquer l'email comme vérifié
            $sql = "UPDATE users SET email_verified = TRUE WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id]);
            
            // Supprimer le token utilisé
            $sql = "DELETE FROM email_verifications WHERE token = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$token]);
            
            $pdo->commit();
            $success = "Votre email a été validé avec succès ! Vous pouvez maintenant vous connecter.";
        } else {
            $error = "Lien de validation invalide ou expiré.";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Une erreur est survenue lors de la validation de votre email.";
    }
} else {
    $error = "Token de validation manquant.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation d'email - LocalO'drive</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Validation d'email</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <div class="text-center mt-3">
                            <a href="login.php" class="btn btn-primary">Se connecter</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html> 