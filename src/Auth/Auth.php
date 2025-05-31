<?php
namespace LocalOdrive\Auth;

use PDO;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Auth {
    private $pdo;
    private $mail;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->mail = new PHPMailer(true);
        $this->configureMailer();
    }

    private function configureMailer() {
        $this->mail->isSMTP();
        $this->mail->Host = MAIL_HOST;
        $this->mail->SMTPAuth = true;
        $this->mail->Username = MAIL_USERNAME;
        $this->mail->Password = MAIL_PASSWORD;
        $this->mail->SMTPSecure = MAIL_ENCRYPTION;
        $this->mail->Port = MAIL_PORT;
        $this->mail->CharSet = 'UTF-8';
    }

    public function register($email, $password, $prenom, $nom, $telephone = null, $adresse = null, $code_postal = null, $ville = null) {
        try {
            $this->pdo->beginTransaction();

            // Vérifier si l'email existe déjà
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                throw new \Exception("Cet email est déjà utilisé");
            }

            // Créer le compte
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("INSERT INTO users (email, password, prenom, nom, telephone, adresse, code_postal, ville, role) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'client')");
            $stmt->execute([$email, $hashed_password, $prenom, $nom, $telephone, $adresse, $code_postal, $ville]);
            
            $user_id = $this->pdo->lastInsertId();

            // Générer le token de validation
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $stmt = $this->pdo->prepare("INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $token, $expires_at]);

            // Envoyer l'email de validation
            $this->sendVerificationEmail($email, $prenom, $nom, $token);

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function login($email, $password) {
        $stmt = $this->pdo->prepare("SELECT id, email, password, prenom, nom, role, email_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if (!$user['email_verified']) {
                throw new \Exception("Veuillez valider votre email avant de vous connecter");
            }
            return $user;
        }
        return false;
    }

    public function verifyEmail($token) {
        $stmt = $this->pdo->prepare("SELECT user_id, expires_at FROM email_verifications WHERE token = ?");
        $stmt->execute([$token]);
        $verification = $stmt->fetch();

        if (!$verification) {
            throw new \Exception("Token de validation invalide");
        }

        if (strtotime($verification['expires_at']) < time()) {
            throw new \Exception("Le lien de validation a expiré");
        }

        $this->pdo->beginTransaction();
        try {
            // Marquer l'email comme vérifié
            $stmt = $this->pdo->prepare("UPDATE users SET email_verified = TRUE WHERE id = ?");
            $stmt->execute([$verification['user_id']]);

            // Supprimer le token
            $stmt = $this->pdo->prepare("DELETE FROM email_verifications WHERE token = ?");
            $stmt->execute([$token]);

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function resendVerificationEmail($email) {
        $stmt = $this->pdo->prepare("SELECT id, prenom, nom, email_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || $user['email_verified']) {
            throw new \Exception("Aucun compte non vérifié trouvé avec cet email");
        }

        try {
            // Générer un nouveau token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Supprimer l'ancien token
            $stmt = $this->pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            
            // Insérer le nouveau token
            $stmt = $this->pdo->prepare("INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['id'], $token, $expires_at]);

            // Envoyer le nouvel email
            $this->sendVerificationEmail($email, $user['prenom'], $user['nom'], $token);
            return true;
        } catch (\Exception $e) {
            throw new \Exception("Erreur lors de l'envoi de l'email : " . $e->getMessage());
        }
    }

    private function sendVerificationEmail($email, $prenom, $nom, $token) {
        try {
            $this->mail->clearAddresses();
            $this->mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $this->mail->addAddress($email, $prenom . ' ' . $nom);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Validation de votre compte LocalO\'drive';
            
            $verificationLink = APP_URL . '/public/verify.php?token=' . $token;
            $this->mail->Body = "
                <h1>Bienvenue sur LocalO'drive !</h1>
                <p>Merci de vous être inscrit. Pour activer votre compte, veuillez cliquer sur le lien ci-dessous :</p>
                <p><a href='{$verificationLink}'>{$verificationLink}</a></p>
                <p>Ce lien expirera dans 24 heures.</p>
                <p>Si vous n'avez pas créé de compte, vous pouvez l'ignorer.</p>
            ";

            $this->mail->send();
        } catch (Exception $e) {
            throw new \Exception("Erreur lors de l'envoi de l'email : " . $this->mail->ErrorInfo);
        }
    }
} 