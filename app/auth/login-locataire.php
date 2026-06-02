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

                if ($mot_de_passe === $locataire['mot_de_passe']) {

                    $_SESSION['id'] = $locataire['id_locataire'];
                    $_SESSION['id_bailleur'] = $locataire['id_bailleur'];
                    $_SESSION['matricule'] = $locataire['matricule'];
                    $_SESSION['nom'] = $locataire['nom'];
                    $_SESSION['postnom'] = $locataire['postnom'];
                    $_SESSION['prenom'] = $locataire['prenom'];
                    $_SESSION['role'] = 'locataire';

                    header('Location: ../views/locataire/dashboard_locataire.php');
                      exit();

                } else {

                    $message = "Mot de passe incorrect.";

                }

            } else {

                $message = "Matricule introuvable.";

            }

        } catch (PDOException $e) {

            $message = "Erreur : " . $e->getMessage();

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

/* =========================
   RESET
========================= */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
}

/* =========================
   BODY (IMAGE DE FOND AJOUTÉE)
========================= */
body {
    min-height: 100vh;

    /* 🔥 IMAGE DE FOND */
    background-image: url("../../public/assets/images/fond.png");
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;

    display: flex;
    justify-content: center;
    align-items: center;

    padding: 20px;
}

/* =========================
   CONTAINER
========================= */
.container {
    background: #ffffff;
    width: 100%;
    max-width: 420px;

    padding: 35px 30px;
    border-radius: 16px;

    text-align: center;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);

    display: flex;
    flex-direction: column;
    gap: 15px;
}

/* =========================
   LOGO (AGRANDI)
========================= */
.logo {
    width: 180px;   /* 🔥 AGRANDI */
    height: auto;
    margin: 0 auto;
}

/* =========================
   TITRES
========================= */
.app-name {
    font-size: 26px;
    color: #2563eb;
}

.page-title {
    font-size: 18px;
    color: #333;
}

/* =========================
   FORM
========================= */
.form {
    display: flex;
    flex-direction: column;
    gap: 15px;
    text-align: left;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 14px;
}

.form-group input:focus {
    border-color: #2563eb;
    outline: none;
}

/* =========================
   BOUTON
========================= */
button {
    width: 100%;
    padding: 13px;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    background-color: #2563eb;
    color: white;
    transition: 0.3s;
}

button:hover {
    background-color: #1e40af;
    transform: translateY(-2px);
}

/* =========================
   LIENS
========================= */
.links {
    text-align: center;
    font-size: 14px;
}

/* =========================
   FOOTER
========================= */
footer {
    font-size: 12px;
    color: #888;
    text-align: center;
}

/* =========================
   RESPONSIVE
========================= */
@media (max-width: 480px) {
    .container {
        padding: 25px 20px;
    }

    .logo {
        width: 140px; /* responsive logo */
    }

    .app-name {
        font-size: 22px;
    }

    .page-title {
        font-size: 16px;
    }
}

</style>
</head>

<body>

<main class="container">

    <!-- Logo AGRANDI -->
    <img src="../../public/assets/images/logo.png" alt="Logo RentMaster" class="logo">


    <h2 class="page-title">Connexion Locataire</h2>

    <?php if(!empty($message)) : ?>
<div style="
background:#fee2e2;
color:#b91c1c;
padding:10px;
border-radius:8px;
margin-bottom:10px;
text-align:center;
">
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>
    <form class="form" method="POST">

       <div class="form-group">
    <input type="text"
           name="matricule"
           id="matricule"
           placeholder="Matricule"
           required>
</div>

<div class="form-group">
    <input type="password"
           name="mot_de_passe"
           id="mot_de_passe"
           placeholder="Mot de passe"
           required>
</div>

        <button type="submit">Se connecter</button>

    </form>

    <div class="links">
        <a href="../controllers/logout.php">← Retour à l’accueil</a></p>
    </div>

    <footer>
        <p>&copy; 2026 RentMaster</p>
    </footer>

</main>



</body>
</html>