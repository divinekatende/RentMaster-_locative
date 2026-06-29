<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../models/Locataire.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Nettoyage des données saisies dans le formulaire
    $matricule = isset($_POST['matricule']) ? trim($_POST['matricule']) : '';
    $mot_de_passe = isset($_POST['mot_de_passe']) ? trim($_POST['mot_de_passe']) : '';

    if (!empty($matricule) && !empty($mot_de_passe)) {
        try {
            // Connexion identique à celle utilisée partout ailleurs dans l'app (même host/port/db)
            $dbConnection = new PDO(
                "mysql:host=127.0.0.1;port=3307;dbname=rentmaster;charset=utf8mb4",
                "root",
                "",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );

            $locataireModel = new Locataire($dbConnection);
            
            // On cherche en nettoyant le matricule
            $locataire = $locataireModel->findByMatricule($matricule);

            if ($locataire) {
                // 2. Nettoyage STRICT des données venant de la base de données
                $mot_de_passe_saisi = $mot_de_passe; // Déjà nettoyé plus haut
                $mot_de_passe_bdd = trim($locataire['mot_de_passe']);

                // 3. Comparaison en texte clair nettoyé
                if ($mot_de_passe_saisi === $mot_de_passe_bdd) {
                    $_SESSION['id'] = $locataire['id_locataire'];
                    $_SESSION['id_locataire'] = $locataire['id_locataire'];
                    $_SESSION['id_bailleur'] = $locataire['id_bailleur'];
                    $_SESSION['matricule'] = trim($locataire['matricule']); 
                    $_SESSION['nom'] = trim($locataire['nom']);
                    $_SESSION['prenom'] = trim($locataire['prenom']);
                    $_SESSION['role'] = 'locataire';

                    // 4. Vérification souple du flag de première connexion (converti en entier)
                    if ((int)$locataire['first_login'] === 1) {
                        header('Location: changement_mdp.php');
                        exit();
                    }

                    // Redirection classique si déjà configuré
                    header('Location: ../views/locataire/dashboard_locataire.php');
                    exit();

                } else {
                    $message = "Identifiant ou mot de passe incorrect.";
                }
            } else {
                $message = "Identifiant ou mot de passe incorrect.";
            }

        } catch (Exception $e) {
            $message = "Une erreur est survenue. Veuillez réessayer.";
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
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { min-height: 100vh; background-image: url("../../public/assets/images/fond.png"); background-size: cover; background-position: center; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .container { background: #fff; width: 100%; max-width: 420px; padding: 35px 30px; border-radius: 16px; text-align: center; box-shadow: 0 12px 30px rgba(0,0,0,0.12); }
        .logo { width: 180px; margin: auto; }
        h2 { color: #2563eb; margin-bottom: 15px; }
        .form { display: flex; flex-direction: column; gap: 15px; text-align: left; }
        input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; }
        input:focus { border-color: #2563eb; outline: none; }
        button { padding: 13px; border: none; border-radius: 10px; background: #2563eb; color: white; font-weight: 600; cursor: pointer; }
        button:hover { background: #1e40af; }
        .links { margin-top: 15px; text-align: center; }
        .links a { color: #2563eb; text-decoration: none; }
        footer { margin-top: 20px; font-size: 12px; color: #64748b; text-align: center; }
        .error { background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 8px; margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="container">
    <img src="../../public/assets/images/logo.png" class="logo" alt="Logo">
    <h2>Connexion Locataire</h2>

    <?php if(!empty($message)) : ?>
        <div class="error"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form class="form" action="" method="POST">
        <input type="text" name="matricule" placeholder="Matricule" required>
        <input type="password" name="mot_de_passe" placeholder="Mot de passe" required>
        <button type="submit">Se connecter</button>
    </form>

    <div class="links">
        <a href="../controllers/logout.php"> ← Retour à l’accueil</a>
    </div>
    <footer><p>&copy; 2026 RentMaster</p></footer>
</div>
</body>
</html>