<?php

session_start();

// utilisateur connecté ?
if (!isset($_SESSION['id'])) {

    header("Location: ../../login.php");
    exit();
}

// vérifier rôle
function checkRole($role)
{
    if ($_SESSION['role'] != $role) {

        echo "❌ Accès refusé";
        exit();
    }
}