<?php
// Activation de l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../vendor/autoload.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    // Chargement des variables d'environnement
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    // Vérification des données du formulaire
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Vérification du reCAPTCHA
        if (!isset($_POST['g-recaptcha-response'])) {
            throw new Exception('reCAPTCHA manquant');
        }

        $recaptcha_response = $_POST['g-recaptcha-response'];
        $verify_url = "https://www.google.com/recaptcha/api/siteverify";
        $data = [
            'secret' => $_ENV['RECAPTCHA_SECRET_KEY'],
            'response' => $recaptcha_response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context = stream_context_create($options);
        $verify_response = file_get_contents($verify_url, false, $context);
        
        if ($verify_response === false) {
            throw new Exception('Erreur lors de la vérification du reCAPTCHA');
        }

        $captcha_success = json_decode($verify_response);
        if (!$captcha_success->success) {
            header('Location: contact.php?error=captcha');
            exit;
        }

        // Validation des champs du formulaire
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
        $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

        if (!$name || !$email || !$subject || !$message) {
            header('Location: contact.php?error=missing_fields');
            exit;
        }

        // Vérification de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: contact.php?error=invalid_email');
            exit;
        }

        // Configuration de PHPMailer
        $mail = new PHPMailer(true);

        // Configuration du serveur
        $mail->isSMTP();
        $mail->Host = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['MAIL_USERNAME'];
        $mail->Password = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['MAIL_PORT'];
        $mail->CharSet = 'UTF-8';

        // Destinataires
        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
        
        // Récupération des adresses email depuis le fichier .env
        if (!isset($_ENV['MAIL_TO_ADDRESSES'])) {
            throw new Exception('MAIL_TO_ADDRESSES non défini dans le fichier .env');
        }

        $recipients = explode(',', $_ENV['MAIL_TO_ADDRESSES']);
        $validRecipients = false;
        
        foreach ($recipients as $recipient) {
            $recipient = trim($recipient);
            if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                $mail->addAddress($recipient);
                $validRecipients = true;
            }
        }

        if (!$validRecipients) {
            throw new Exception('Aucun destinataire valide trouvé');
        }
        
        $mail->addReplyTo($email, $name);

        // Contenu
        $mail->isHTML(true);
        $mail->Subject = "Nouveau message de contact : " . $subject;
        
        $emailBody = "
            <h2>Nouveau message de contact</h2>
            <p><strong>Nom :</strong> {$name}</p>
            <p><strong>Email :</strong> {$email}</p>
            <p><strong>Sujet :</strong> {$subject}</p>
            <p><strong>Message :</strong></p>
            <p>" . nl2br(htmlspecialchars($message)) . "</p>
        ";
        
        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags($emailBody);

        $mail->send();
        header('Location: contact.php?success=true');
        exit;
    } else {
        header('Location: contact.php');
        exit;
    }
} catch (Exception $e) {
    // Log de l'erreur
    error_log("Erreur dans process_contact.php : " . $e->getMessage());
    
    // Redirection avec message d'erreur
    header('Location: contact.php?error=server_error');
    exit;
} 