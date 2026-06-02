<?php

require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/database.php';

verifierConnexion();
verifierRole(['admin']);

$database = new Database();
$conn = $database->connect();

$message = "";

/*
========================
RECUPERATION ADMIN
========================
*/
$admin_id = $_SESSION['admin']['id_admin'] ?? 0;

$sql = "SELECT * FROM administrateurs WHERE id_admin = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$admin_id]);

$admin = $stmt->fetch(PDO::FETCH_ASSOC);

/*
========================
UPDATE PROFIL
========================
*/
if(isset($_POST['update_profile'])){

    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);

    $sql = "UPDATE administrateurs 
            SET nom=?, email=? 
            WHERE id_admin=?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$nom, $email, $admin_id]);

    $message = "Profil mis à jour avec succès";
}

/*
========================
CHANGE PASSWORD
========================
*/
if(isset($_POST['change_password'])){

    $old = trim($_POST['old_password']);
    $new = trim($_POST['new_password']);

    if($admin && password_verify($old, $admin['mot_de_passe'])){

        $sql = "UPDATE administrateurs 
                SET mot_de_passe=? 
                WHERE id_admin=?";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            password_hash($new, PASSWORD_DEFAULT),
            $admin_id
        ]);

        $message = "Mot de passe modifié avec succès";

    } else {
        $message = "Ancien mot de passe incorrect";
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RentMaster - Settings</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

/* ===== STYLE SIMPLE ET PRO ===== */

:root{
    --bg:#f4f7fe;
    --white:#fff;
    --primary:#2563eb;
    --text:#1e293b;
    --border:#dbe4f0;
}

.dark{
    --bg:#0f172a;
    --white:#1e293b;
    --text:#fff;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Inter, sans-serif;
}

body{
    background:var(--bg);
    color:var(--text);
}

.layout{display:flex;}

.sidebar{
    width:250px;
    height:100vh;
    background:#0f172a;
    color:white;
    padding:20px;
    position:fixed;
}

.main{
    margin-left:250px;
    padding:20px;
}

.card{
    background:var(--white);
    padding:20px;
    border-radius:15px;
    margin-bottom:20px;
}

input{
    width:100%;
    padding:12px;
    margin-top:10px;
    border:1px solid var(--border);
    border-radius:10px;
}

button{
    margin-top:10px;
    padding:12px;
    border:none;
    border-radius:10px;
    background:var(--primary);
    color:white;
    cursor:pointer;
}

.log{
    padding:10px;
    border-left:4px solid var(--primary);
    margin-bottom:10px;
    background:#eff6ff;
}

</style>
</head>

<body>

<div class="layout">

<!-- SIDEBAR -->
<aside class="sidebar">

    <h2>RentMaster</h2>

    <a href="dashboard.php" style="color:white;display:block;margin:10px 0;">
        <i class="fa fa-chart-line"></i> Dashboard
    </a>

    <a href="gestion.php" style="color:white;display:block;margin:10px 0;">
        <i class="fa fa-building"></i> Gestion
    </a>

    <a href="settings.php" style="color:white;display:block;margin:10px 0;">
        <i class="fa fa-gear"></i> Paramètres
    </a>

    <a href="../../controllers/logout.php" style="color:white;display:block;margin:10px 0;">
        <i class="fa fa-right-from-bracket"></i> Déconnexion
    </a>

</aside>

<!-- MAIN -->
<div class="main">

<h2>Paramètres</h2>

<?php if($message): ?>
<div class="card" style="background:#dcfce7;color:#166534;">
    <?= $message ?>
</div>
<?php endif; ?>

<!-- PROFIL -->
<div class="card">

<h3>Profil administrateur</h3>

<form method="POST">

    <input type="text" name="nom" value="<?= htmlspecialchars($admin['nom'] ?? '') ?>" required>

    <input type="email" name="email" value="<?= htmlspecialchars($admin['email'] ?? '') ?>" required>

    <button type="submit" name="update_profile">
        Mettre à jour
    </button>

</form>

</div>

<!-- PASSWORD -->
<div class="card">

<h3>Sécurité</h3>

<form method="POST">

    <input type="password" name="old_password" placeholder="Ancien mot de passe" required>
    <input type="password" name="new_password" placeholder="Nouveau mot de passe" required>

    <button type="submit" name="change_password">
        Changer mot de passe
    </button>

</form>

</div>

<!-- LOGS -->
<div class="card">

<h3>Activités récentes</h3>

<div class="log">✔ Connexion admin réussie</div>
<div class="log">✔ Système actif</div>

</div>

</div>
</div>

</body>
</html>