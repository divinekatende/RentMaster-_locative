<?php
session_start();
require_once('../config/database.php');

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = trim($_POST['mot_de_passe'] ?? '');

    if (!empty($email) && !empty($mot_de_passe)) {

        try {
            $database = new Database();
            $conn = $database->connect();

            /*
            =========================
            1. VERIFIER ADMIN
            =========================
            */
            $sqlAdmin = "SELECT * FROM administrateurs WHERE email = :email LIMIT 1";
            $stmt = $conn->prepare($sqlAdmin);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && $mot_de_passe === $admin['mot_de_passe']) {

                $_SESSION['id'] = $admin['id_admin'];
                $_SESSION['nom'] = $admin['nom'];
                $_SESSION['email'] = $admin['email'];
                $_SESSION['role'] = 'admin';
                header("Location: ../views/administrateur/dashboard.php");
                   exit();
                
            }

            /*
            =========================
            2. VERIFIER BAILLEUR
            =========================
            */
            $sqlBailleur = "SELECT * FROM bailleurs WHERE email = :email LIMIT 1";
            $stmt = $conn->prepare($sqlBailleur);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $bailleur = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($bailleur && $mot_de_passe === $bailleur['mot_de_passe']) {

                $_SESSION['id'] = $bailleur['id_bailleur'];
                $_SESSION['nom'] = $bailleur['nom'];
                $_SESSION['prenom'] = $bailleur['prenom'];
                $_SESSION['email'] = $bailleur['email'];
                $_SESSION['role'] = 'bailleur';

                header("Location: ../views/bailleur/dashboard.php");
                    exit();
            }

            /*
            =========================
            3. ERREUR
            =========================
            */
            $message = "Email ou mot de passe incorrect.";

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
<title>RentMaster | Connexion Bailleur</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
}

/* =========================
   BODY - CENTRAGE PROPRE
========================= */
body {
    min-height: 100vh;

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
   CONTAINER - POSITION OPTIMISÉE
========================= */
.container {
    width: 100%;
    max-width: 420px;

    background: #ffffff;
    padding: 35px 30px;
    border-radius: 18px;

    text-align: center;
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);

    display: flex;
    flex-direction: column;
    gap: 15px;
}

/* =========================
   LOGO
========================= */
.logo {
    width: 180px;
    margin: 0 auto;
    animation: bounce 1.5s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

/* =========================
   TITRE
========================= */
.page-title {
    font-size: 20px;
    color: #1e293b;
    margin-top: 5px;
}

/* =========================
   FORMULAIRE - ALIGNEMENT PROPRE
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
    gap: 6px;
}

.form-group label {
    font-size: 14px;
    color: #334155;
}

.form-group input {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    font-size: 14px;
}

.form-group input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 8px rgba(37, 99, 235, 0.2);
    outline: none;
}

/* =========================
   BOUTON
========================= */
button {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    color: white;
    transition: 0.3s;
}

button:hover {
    transform: translateY(-2px);
}

/* =========================
   MESSAGE
========================= */
#messageErreur {
    font-size: 13px;
    text-align: center;
}

.error {
    color: #dc2626;
}

/* =========================
   LIENS
========================= */
.links {
    margin-top: 5px;
    text-align: center;
}

.links a {
    color: #2563eb;
    text-decoration: none;
}

/* =========================
   FOOTER
========================= */
footer {
    font-size: 12px;
    color: #64748b;
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
        width: 140px;
    }

    .page-title {
        font-size: 18px;
    }
}
</style>
</head>

<body>

<main class="container">

    <img src="../../public/assets/images/logo.png" alt="Logo RentMaster" class="logo">

    <h2 class="page-title">Connexion Bailleur</h2>

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
            <label>Email</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Mot de passe</label>
            <input type="password" name="mot_de_passe" required>
        </div>

        <button type="submit">Se connecter</button>
        <p id="messageErreur"></p>
    </form>

    <div class="links">
    <a href="../controllers/logout.php"> ← Retour à l’accueil</a>
    </div>

    <footer>
        <p>&copy; 2026 RentMaster</p>
    </footer>

</main>


</body>
</html>