<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require '../vendor/autoload.php';

use LocalOdrive\Auth\Auth;

$error = '';
$success = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        $auth = new Auth($pdo);
        if ($auth->verifyEmail($token)) {
            $success = "Votre compte a été validé avec succès ! Vous pouvez maintenant vous connecter.";
        }
    } catch (\Exception $e) {
        $error = $e->getMessage();
    }
} else {
    $error = "Token de validation manquant";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation du compte - LocalO'drive</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Validation du compte</h3>
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