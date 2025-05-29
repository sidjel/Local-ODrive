<?php
/*
 * init.php
 * Fichier d'initialisation pour TP_API-Silvere-Morgan-LocaloDrive.php
 */

require_once __DIR__ . "/../vendor/autoload.php";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
$API_KEY_SIRENE = $_ENV['API_KEY_SIRENE']; 
