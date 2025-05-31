<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require '../vendor/autoload.php';

use LocalOdrive\Auth\Auth;

$error = '';
$success = '';

if (isset($_GET['email'])) {
    $email = filter_input(INPUT_GET, 'email', FILTER_SANITIZE_EMAIL);
    
    if (!empty($email)) {
        try {
            $auth = new Auth($pdo);
            if ($auth->resendVerificationEmail($email)) {
                $success = "Un nouvel email de validation a été envoyé à votre adresse email.";
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
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