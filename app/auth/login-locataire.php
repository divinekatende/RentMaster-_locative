<?php
session_start();

require_once('../config/database.php');

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $matricule = trim($_POST['matricule'] ?? '');
    $mot_de_passe = trim($_POST['mot_de_passe'] ?? '');

    if (!empty($matricule) && !empty($mot_de_passe)) {

        try {

            $database = new Database();
            $conn = $database->connect();

            $sql = "SELECT * FROM locataires WHERE matricule = :matricule LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':matricule', $matricule);
            $stmt->execute();

            $locataire = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($locataire) {

                // ⚠️ LOGIQUE MOT DE PASSE (actuelle simple)
                if ($mot_de_passe === $locataire['mot_de_passe']) {

                    // =========================
                    // SESSION USER
                    // =========================
                    $_SESSION['id'] = $locataire['id_locataire'];
                    $_SESSION['id_bailleur'] = $locataire['id_bailleur'];
                    $_SESSION['matricule'] = $locataire['matricule'];
                    $_SESSION['nom'] = $locataire['nom'];
                    $_SESSION['postnom'] = $locataire['postnom'];
                    $_SESSION['prenom'] = $locataire['prenom'];
                    $_SESSION['role'] = 'locataire';

                    $_SESSION['first_login'] = $locataire['first_login'];

                    // =========================
                    // REDIRECTION LOGIQUE
                    // =========================
                    if ($locataire['first_login'] == 1) {

                        header('Location: ../views/locataire/profil_locataire.php');
                        exit();

                    } else {

                        header('Location: ../views/locataire/dashboard_locataire.php');
                        exit();
                    }

                } else {
                    $message = "Mot de passe incorrect.";
                }

            } else {
                $message = "Matricule introuvable.";
            }

        } catch (PDOException $e) {
            $message = "Erreur serveur.";
        }

    } else {
        $message = "Veuillez remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RentMaster | Connexion Locataire</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
}

body {
    min-height: 100vh;
    background-image: url("../../public/assets/images/fond.png");
    background-size: cover;
    background-position: center;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.container {
    background: #fff;
    width: 100%;
    max-width: 420px;
    padding: 35px 30px;
    border-radius: 16px;
    text-align: center;
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
}

.logo {
    width: 180px;
    margin: auto;
}

h2 {
    color: #2563eb;
    margin-bottom: 15px;
}

.form {
    display: flex;
    flex-direction: column;
    gap: 15px;
    text-align: left;
}

input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 8px;
}

input:focus {
    border-color: #2563eb;
    outline: none;
}

button {
    padding: 13px;
    border: none;
    border-radius: 10px;
    background: #2563eb;
    color: white;
    font-weight: 600;
    cursor: pointer;
}

button:hover {
    background: #1e40af;
}

.error {
    background: #fee2e2;
    color: #b91c1c;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 10px;
}
</style>
</head>

<body>

<div class="container">

    <img src="../../public/assets/images/logo.png" class="logo">

    <h2>Connexion Locataire</h2>

    <?php if(!empty($message)) : ?>
        <div class="error">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form class="form" method="POST">

        <input type="text" name="matricule" placeholder="Matricule" required>

        <input type="password" name="mot_de_passe" placeholder="Mot de passe" required>

        <button type="submit">Se connecter</button>

    </form>

</div>

</body>
</html>