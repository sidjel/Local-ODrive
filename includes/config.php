<?php
// Charger les variables d'environnement
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Configuration de l'application
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_DEBUG', getenv('APP_DEBUG') === 'true' || APP_ENV === 'development');
define('APP_NAME', 'LocalO\'drive');
define('APP_URL', getenv('APP_URL'));

// Configuration de la base de données
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'localodrive'); 
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Configuration des emails
define('MAIL_HOST', getenv('MAIL_HOST'));
define('MAIL_PORT', getenv('MAIL_PORT'));
define('MAIL_USERNAME', getenv('MAIL_USERNAME'));
define('MAIL_PASSWORD', getenv('MAIL_PASSWORD'));
define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION'));
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'noreply@localodrive.fr');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: APP_NAME);

// Configuration des services externes
define('API_KEY_SIRENE', getenv('API_KEY_SIRENE'));
define('GOOGLE_MAPS_API_KEY', getenv('GOOGLE_MAPS_API_KEY'));
define('RECAPTCHA_SITE_KEY', getenv('RECAPTCHA_SITE_KEY'));
define('RECAPTCHA_SECRET_KEY', getenv('RECAPTCHA_SECRET_KEY'));

// Configuration de la sécurité
define('SESSION_LIFETIME', getenv('SESSION_LIFETIME') ?: 120);
define('COOKIE_LIFETIME', getenv('COOKIE_LIFETIME') ?: 120);

// Configuration des sessions
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME * 60);
    ini_set('session.cookie_lifetime', COOKIE_LIFETIME * 60);
    
    session_set_cookie_params([
        'lifetime' => COOKIE_LIFETIME * 60,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}

// Connexion à la base de données
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Configuration du fuseau horaire
date_default_timezone_set('Europe/Paris');

// Configuration des erreurs
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
} 