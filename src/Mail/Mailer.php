<?php
namespace LocalOdrive\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {
    private $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->configureMailer();
    }

    private function configureMailer() {
        $this->mailer->isSMTP();
        $this->mailer->Host = MAIL_HOST;
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = MAIL_USERNAME;
        $this->mailer->Password = MAIL_PASSWORD;
        $this->mailer->SMTPSecure = MAIL_ENCRYPTION;
        $this->mailer->Port = MAIL_PORT;
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
    }

    public function sendVerificationEmail($email, $prenom, $nom, $token) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $prenom . ' ' . $nom);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Validation de votre compte LocalO\'drive';
            
            $verificationLink = APP_URL . '/public/verify.php?token=' . $token;
            $this->mailer->Body = "
                <h1>Bienvenue sur LocalO'drive !</h1>
                <p>Merci de vous être inscrit. Pour activer votre compte, veuillez cliquer sur le lien ci-dessous :</p>
                <p><a href='{$verificationLink}'>{$verificationLink}</a></p>
                <p>Ce lien expirera dans 24 heures.</p>
                <p>Si vous n'avez pas créé de compte, vous pouvez l'ignorer.</p>
            ";

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            throw new \Exception("Erreur lors de l'envoi de l'email : " . $this->mailer->ErrorInfo);
        }
    }

    public function sendPasswordResetEmail($email, $prenom, $nom, $token) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $prenom . ' ' . $nom);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Réinitialisation de votre mot de passe';
            
            $resetLink = APP_URL . '/public/reset-password.php?token=' . $token;
            $this->mailer->Body = "
                <h1>Réinitialisation de votre mot de passe</h1>
                <p>Bonjour {$prenom},</p>
                <p>Vous avez demandé la réinitialisation de votre mot de passe. Pour définir un nouveau mot de passe, veuillez cliquer sur le lien ci-dessous :</p>
                <p><a href='{$resetLink}'>{$resetLink}</a></p>
                <p>Ce lien expirera dans 1 heure.</p>
                <p>Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email.</p>
            ";

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            throw new \Exception("Erreur lors de l'envoi de l'email : " . $this->mailer->ErrorInfo);
        }
    }
} 