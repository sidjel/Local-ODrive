<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';

if (isset($_GET['email'])) {
    $email = filter_input(INPUT_GET, 'email', FILTER_SANITIZE_EMAIL);
    
    if (!empty($email)) {
        // Vérifier si l'utilisateur existe et n'est pas déjà vérifié
        $sql = "SELECT id, prenom, nom, email_verified FROM users WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && !$user['email_verified']) {
            try {
                // Générer un nouveau token
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                // Supprimer l'ancien token s'il existe
                $sql = "DELETE FROM email_verifications WHERE user_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user['id']]);
                
                // Insérer le nouveau token
                $sql = "INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user['id'], $token, $expires_at]);

                // Envoyer le nouvel email
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
                $mail->addAddress($email, $user['prenom'] . ' ' . $user['nom']);
                $mail->isHTML(true);
                $mail->Subject = 'Validation de votre compte LocalO\'drive';
                
                // Corps du message
                $verificationLink = APP_URL . '/public/verify.php?token=' . $token;
                $mail->Body = "
                    <h1>Validation de votre compte LocalO'drive</h1>
                    <p>Bonjour " . $user['prenom'] . ",</p>
                    <p>Vous avez demandé un nouvel email de validation. Pour activer votre compte, veuillez cliquer sur le lien ci-dessous :</p>
                    <p><a href='{$verificationLink}'>{$verificationLink}</a></p>
                    <p>Ce lien expirera dans 24 heures.</p>
                    <p>Si vous n'avez pas demandé cet email, vous pouvez l'ignorer.</p>
                ";

                $mail->send();
                $success = "Un nouvel email de validation a été envoyé à votre adresse email.";
            } catch (Exception $e) {
                $error = "Une erreur est survenue lors de l'envoi de l'email : " . $mail->ErrorInfo;
            }
        } else {
            $error = "Aucun compte non vérifié trouvé avec cet email.";
        }
    } else {
        $error = "Email invalide.";
    }
} else {
    $error = "Aucun email spécifié.";
}

// Rediriger vers la page de connexion avec le message approprié
header('Location: login.php?message=' . urlencode($error ? $error : $success));
exit; 