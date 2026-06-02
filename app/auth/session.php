<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Vérifie si l'utilisateur est connecté
 */
function verifierConnexion()
{
    if (!isset($_SESSION['id']) || !isset($_SESSION['role'])) {
        header('Location: ../../index.html');
        exit();
    }
}

/**
 * Vérifie si le rôle est autorisé
 */
function verifierRole($roles = [])
{
    if (!isset($_SESSION['role'])) {
        header('Location: ../../index.html');
        exit();
    }

    if (!in_array($_SESSION['role'], $roles)) {
        http_response_code(403);
        echo "<h2>⛔ Accès refusé</h2>";
        exit();
    }
}