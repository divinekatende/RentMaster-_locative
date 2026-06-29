<?php
session_start();

// Sécurité : On s'assure que l'ID du locataire est bien actif en session
if (!isset($_SESSION['id_locataire'])) {
    header('Location: login-locataire.php');
    exit();
}

$erreur = "";
$succes = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nouveau_mdp = trim($_POST['nouveau_mdp'] ?? '');
    $confirmez_mdp = trim($_POST['confirmez_mdp'] ?? '');

    if (empty($nouveau_mdp) || empty($confirmez_mdp)) {
        $erreur = "Veuillez remplir tous les champs.";
    } elseif ($nouveau_mdp === '1111') {
        $erreur = "Veuillez choisir un mot de passe différent de '1111'.";
    } elseif ($nouveau_mdp !== $confirmez_mdp) {
        $erreur = "Les deux mots de passe ne correspondent pas.";
    } else {
        try {
            // Connexion à la base de données via tes paramètres PDO RentMaster
            $pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=rentmaster;charset=utf8mb4", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Mise à jour du mot de passe et désactivation du flag de première connexion (first_login = 0)
            $stmt = $pdo->prepare("UPDATE locataires SET mot_de_passe = ?, first_login = 0 WHERE id_locataire = ?");
            $stmt->execute([$nouveau_mdp, $_SESSION['id_locataire']]);

            $succes = "Votre mot de passe a été mis à jour avec succès ! Redirection...";

            // Redirection après 2 secondes vers le tableau de bord du locataire
            header("refresh:2;url=../views/locataire/dashboard_locataire.php"); 

        } catch (Exception $e) {
            $erreur = "Erreur serveur : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premier accès - RentMaster</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root { 
            --bg-app: #f8fafc; 
            --surface: #ffffff; 
            --primary: #1e40af; 
            --primary-light: #3b82f6; 
            --text-main: #0f172a; 
            --text-muted: #64748b; 
            --border-color: #e2e8f0; 
            --radius-md: 12px; 
            --shadow-md: 0 10px 15px -3px rgba(15, 23, 42, 0.08); 
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-app); color: var(--text-main); display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .card-mdp { background: var(--surface); padding: 40px; border-radius: var(--radius-md); box-shadow: var(--shadow-md); border: 1px solid var(--border-color); width: 100%; max-width: 450px; }
        .header-mdp { text-align: center; margin-bottom: 24px; }
        .header-mdp i { font-size: 40px; color: var(--primary); margin-bottom: 16px; }
        .header-mdp h1 { font-size: 22px; font-weight: 700; margin-bottom: 8px; }
        .header-mdp p { color: var(--text-muted); font-size: 14px; line-height: 1.5; }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
        label { font-size: 13px; font-weight: 600; }
        input { padding: 12px 14px; border-radius: 8px; border: 1px solid var(--border-color); outline: none; font-size: 14px; background: #f8fafc; transition: all 0.2s; }
        input:focus { border-color: var(--primary-light); background: #fff; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .btn-submit { width: 100%; padding: 12px; border-radius: 8px; background: var(--primary); color: white; border: none; font-weight: 600; font-size: 14px; cursor: pointer; margin-top: 10px; transition: all 0.2s; }
        .btn-submit:hover { background: var(--primary-light); }
        .alert { padding: 12px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .alert-danger { background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }
    </style>
</head>
<body>
<div class="card-mdp">
    <div class="header-mdp">
        <i class="fa-solid fa-shield-halved"></i>
        <h1>Sécurisez votre compte</h1>
        <p>Veuillez définir un nouveau mot de passe personnalisé pour remplacer votre code temporaire.</p>
    </div>

    <?php if(!empty($erreur)): ?>
        <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <?php if(!empty($succes)): ?>
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($succes) ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label for="nouveau_mdp">Nouveau mot de passe *</label>
            <input type="password" id="nouveau_mdp" name="nouveau_mdp" placeholder="Minimum 4 caractères" required>
        </div>
        <div class="form-group">
            <label for="confirmez_mdp">Confirmez le mot de passe *</label>
            <input type="password" id="confirmez_mdp" name="confirmez_mdp" placeholder="Répétez le mot de passe" required>
        </div>
        <button type="submit" class="btn-submit">Enregistrer mon mot de passe</button>
    </form>
</div>
</body>
</html>