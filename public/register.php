<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $prenom = htmlspecialchars(trim($_POST['prenom']), ENT_QUOTES, 'UTF-8');
    $nom = htmlspecialchars(trim($_POST['nom']), ENT_QUOTES, 'UTF-8');
    $telephone = htmlspecialchars(trim($_POST['telephone']), ENT_QUOTES, 'UTF-8');
    $adresse = htmlspecialchars(trim($_POST['adresse']), ENT_QUOTES, 'UTF-8');
    $code_postal = htmlspecialchars(trim($_POST['code_postal']), ENT_QUOTES, 'UTF-8');
    $ville = htmlspecialchars(trim($_POST['ville']), ENT_QUOTES, 'UTF-8');

    // Validation de l'email
    if (empty($email)) {
        $error = "L'email est obligatoire";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format d'email invalide";
    } else {
        // Vérifier si l'email existe déjà
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Cet email est déjà utilisé";
        } else {
            // Validation des autres champs
            if (empty($password) || empty($confirm_password) || empty($prenom) || empty($nom)) {
                $error = "Veuillez remplir tous les champs obligatoires";
            } elseif ($password !== $confirm_password) {
                $error = "Les mots de passe ne correspondent pas";
            } elseif (strlen($password) < 8) {
                $error = "Le mot de passe doit contenir au moins 8 caractères";
            } else {
                try {
                    $pdo->beginTransaction();

                    // Créer le compte
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "INSERT INTO users (email, password, prenom, nom, telephone, adresse, code_postal, ville, role) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'client')";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$email, $hashed_password, $prenom, $nom, $telephone, $adresse, $code_postal, $ville]);
                    
                    $user_id = $pdo->lastInsertId();

                    // Générer le token de validation
                    $token = bin2hex(random_bytes(32));
                    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
                    
                    $sql = "INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$user_id, $token, $expires_at]);

                    // Envoyer l'email de validation
                    $verification_link = APP_URL . "/public/verify.php?token=" . $token;
                    $to = $email;
                    $subject = "Validation de votre compte LocalO'drive";
                    $message = "Bonjour " . $prenom . ",\n\n";
                    $message .= "Merci de vous être inscrit sur LocalO'drive. Pour valider votre compte, veuillez cliquer sur le lien suivant :\n\n";
                    $message .= $verification_link . "\n\n";
                    $message .= "Ce lien est valable pendant 24 heures.\n\n";
                    $message .= "Cordialement,\nL'équipe LocalO'drive";
                    $headers = "From: " . MAIL_FROM_ADDRESS . "\r\n";
                    $headers .= "Reply-To: " . MAIL_FROM_ADDRESS . "\r\n";
                    $headers .= "X-Mailer: PHP/" . phpversion();

                    $mail = new PHPMailer(true);
                    
                    // Configuration du serveur SMTP
                    $mail->isSMTP();
                    $mail->Host = MAIL_HOST;
                    $mail->SMTPAuth = true;
                    $mail->Username = MAIL_USERNAME;
                    $mail->Password = MAIL_PASSWORD;
                    $mail->SMTPSecure = MAIL_ENCRYPTION;
                    $mail->Port = MAIL_PORT;
                    $mail->CharSet = 'UTF-8';

                    // Configuration de l'email
                    $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
                    $mail->addAddress($email, $prenom . ' ' . $nom);
                    $mail->isHTML(true);
                    $mail->Subject = 'Vérification de votre compte LocalO\'drive';
                    
                    // Corps du message
                    $verificationLink = APP_URL . '/verify.php?token=' . $token;
                    $mail->Body = "
                        <h1>Bienvenue sur LocalO'drive !</h1>
                        <p>Merci de vous être inscrit. Pour activer votre compte, veuillez cliquer sur le lien ci-dessous :</p>
                        <p><a href='{$verificationLink}'>{$verificationLink}</a></p>
                        <p>Ce lien expirera dans 24 heures.</p>
                        <p>Si vous n'avez pas créé de compte, vous pouvez ignorer cet email.</p>
                    ";

                    $mail->send();
                    $pdo->commit();
                    $success = "Compte créé avec succès ! Un email de validation a été envoyé à votre adresse email.";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Une erreur est survenue lors de la création du compte : " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - LocalO'drive</title>
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
                        <h3 class="text-center">Inscription</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="prenom" class="form-label">Prénom *</label>
                                    <input type="text" class="form-control" id="prenom" name="prenom" required>
                                    <div class="invalid-feedback">Veuillez entrer votre prénom</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="nom" class="form-label">Nom *</label>
                                    <input type="text" class="form-control" id="nom" name="nom" required>
                                    <div class="invalid-feedback">Veuillez entrer votre nom</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required 
                                       pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
                                <div class="invalid-feedback">Veuillez entrer une adresse email valide</div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Mot de passe *</label>
                                    <input type="password" class="form-control" id="password" name="password" required 
                                           minlength="8">
                                    <div class="invalid-feedback">Le mot de passe doit contenir au moins 8 caractères</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirmer le mot de passe *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <div class="invalid-feedback">Les mots de passe ne correspondent pas</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" 
                                       pattern="[0-9]{10}">
                                <div class="invalid-feedback">Veuillez entrer un numéro de téléphone valide (10 chiffres)</div>
                            </div>

                            <div class="mb-3">
                                <label for="adresse" class="form-label">Adresse</label>
                                <input type="text" class="form-control" id="adresse" name="adresse">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="code_postal" class="form-label">Code postal</label>
                                    <input type="text" class="form-control" id="code_postal" name="code_postal" 
                                           pattern="[0-9]{5}">
                                    <div class="invalid-feedback">Veuillez entrer un code postal valide (5 chiffres)</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="ville" class="form-label">Ville</label>
                                    <input type="text" class="form-control" id="ville" name="ville">
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">S'inscrire</button>
                            </div>
                        </form>

                        <div class="mt-3 text-center">
                            <p>Déjà inscrit ? <a href="login.php">Se connecter</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
    // Validation côté client
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()
    </script>
</body>
</html> 