<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "app/config/database.php";

$db = new Database();
$conn = $db->connect();

echo "Connexion réussie à RENTMASTER";


