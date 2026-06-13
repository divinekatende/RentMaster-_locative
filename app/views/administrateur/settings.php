<?php

require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/database.php';

verifierConnexion();
verifierRole(['admin']);

$database = new Database();
$conn = $database->connect();

$message = "";
$message_type = "success"; // success ou danger

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

    // Rafraîchir les données locales après modification
    $admin['nom'] = $nom;
    $admin['email'] = $email;

    $message = "Votre profil a été mis à jour avec succès.";
    $message_type = "success";
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

        $message = "Votre mot de passe a été modifié avec succès.";
        $message_type = "success";

    } else {
        $message = "L'ancien mot de passe fourni est incorrect.";
        $message_type = "danger";
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentMaster — Paramètres</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg-app: #f8fafc;
            --surface: #ffffff;
            --primary: #1e40af;
            --primary-light: #3b82f6;
            --primary-soft: #eff6ff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --radius-lg: 16px;
            --radius-md: 12px;
            --radius-sm: 8px;
            --shadow-sm: 0 4px 6px -1px rgba(15, 23, 42, 0.05), 0 2px 4px -2px rgba(15, 23, 42, 0.05);
            
            --success: #10b981;
            --success-soft: #ecfdf5;
            --danger: #ef4444;
            --danger-soft: #fef2f2;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        body { background: var(--bg-app); color: var(--text-main); min-height: 100vh; display: flex; }

        /* SIDEBAR */
        aside {
            width: 260px; background: var(--surface);
            border-right: 1px solid var(--border-color); padding: 24px 16px; position: fixed; height: 100%;
            display: flex; flex-direction: column; transition: .3s ease; z-index: 99;
        }
        .logo { text-align: center; margin-bottom: 32px; display: flex; flex-direction: column; align-items: center; gap: 8px; }
        .logo-box { width: 52px; height: 52px; background: var(--primary); color: white; font-weight: 800; font-size: 18px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(30, 64, 175, 0.2); }
        .logo h3 { color: var(--primary); font-size: 1.2rem; font-weight: 700; letter-spacing: -0.02em; margin-top: 4px; }
        
        aside nav { flex: 1; display: flex; flex-direction: column; gap: 4px; }
        aside button {
            display: flex; align-items: center; gap: 12px; padding: 12px 14px; width: 100%; border: none; background: transparent;
            border-radius: var(--radius-md); text-decoration: none; color: var(--text-muted); font-weight: 500; font-size: 14px; cursor: pointer; transition: .2s ease;
        }
        aside button:hover { background: var(--primary-soft); color: var(--primary); }
        aside button.active { background: var(--primary); color: white; font-weight: 600; }
        aside button i { width: 20px; text-align: center; font-size: 16px; }
        
        .sidebar-footer { border-top: 1px solid var(--border-color); padding-top: 16px; }
        .sidebar-footer button { color: var(--danger); background: var(--danger-soft); }
        .sidebar-footer button:hover { background: var(--danger); color: white; }

        /* MAIN CONTENT */
        .main { margin-left: 260px; width: 100%; padding: 40px; display: flex; flex-direction: column; min-height: 100vh; }
        
        .page-header { margin-bottom: 32px; }
        .page-header h2 { font-size: 24px; font-weight: 700; letter-spacing: -0.02em; }
        .page-header p { color: var(--text-muted); font-size: 14px; margin-top: 4px; }

        /* NOTIFICATIONS BULLES */
        .alert { padding: 16px; border-radius: var(--radius-md); font-weight: 500; font-size: 14px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }
        .alert-success { background: var(--success-soft); color: var(--success); border: 1px solid #a7f3d0; }
        .alert-danger { background: var(--danger-soft); color: var(--danger); border: 1px solid #fca5a5; }

        /* CARDS & PANELS FORMULAIRES */
        .settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 24px; align-items: start; }
        .panel-card { background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); padding: 28px; }
        .panel-card h3 { font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; }
        .panel-card h3 i { color: var(--primary); }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; }
        .panel-card input { width: 100%; padding: 11px 14px; font-size: 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); outline: none; transition: border 0.2s, box-shadow 0.2s; color: var(--text-main); }
        .panel-card input:focus { border-color: var(--primary-light); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        
        .btn-save { padding: 11px 20px; font-size: 14px; font-weight: 600; border-radius: var(--radius-sm); border: 1px solid var(--primary); background: var(--primary); color: white; cursor: pointer; transition: all 0.2s ease; width: 100%; margin-top: 8px; }
        .btn-save:hover { background: var(--primary-light); border-color: var(--primary-light); }

        /* JOURNAL LOGS COMPLIANCE */
        .log-item { display: flex; gap: 12px; font-size: 13px; line-height: 1.5; padding: 12px; border-radius: var(--radius-sm); background: #fafafa; border: 1px solid var(--border-color); margin-bottom: 10px; color: var(--text-main); align-items: flex-start; }
        .log-item i { color: var(--success); margin-top: 2px; }
        .log-item .log-time { font-size: 11px; color: var(--text-muted); margin-left: auto; white-space: nowrap; }

        @media(max-width: 1024px) {
            aside { width: 70px; padding: 24px 8px; }
            aside .logo h3, aside button span { display: none; }
            .main { margin-left: 70px; padding: 24px 16px; }
            .settings-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<aside>
    <div class="logo">
        <div class="logo-box">RM</div>
        <h3>RentMaster</h3>
    </div>
    <nav>
        <button onclick="location.href='dashboard.php'"><i class="fa fa-chart-line"></i> <span>Dashboard</span></button>
        <button onclick="location.href='gestion.php'"><i class="fa fa-building"></i> <span>Gestion</span></button>
        <button class="active" onclick="location.href='settings.php'"><i class="fa fa-gear"></i> <span>Paramètres</span></button>
    </nav>
    <div class="sidebar-footer">
        <button onclick="location.href='../../controllers/logout.php'"><i class="fa fa-right-from-bracket"></i> <span>Déconnexion</span></button>
    </div>
</aside>

<div class="main">

    <div class="page-header">
        <h2>Paramètres de configuration</h2>
        <p>Gérez vos informations de compte administrateur et auditez la sécurité du système.</p>
    </div>

    <?php if($message): ?>
        <div class="alert alert-<?= $message_type ?>">
            <i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i> 
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="settings-grid">
        
        <section class="panel-card">
            <h3><i class="fa-regular fa-user"></i> Profil Administrateur</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Nom complet</label>
                    <input type="text" name="nom" value="<?= htmlspecialchars($admin['nom'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Adresse e-mail système</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($admin['email'] ?? '') ?>" required>
                </div>
                <button type="submit" name="update_profile" class="btn-save">Mettre à jour le profil</button>
            </form>
        </section>

        <section class="panel-card">
            <h3><i class="fa fa-shield-halved"></i> Sécurité du compte</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Ancien mot de passe</label>
                    <input type="password" name="old_password" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label>Nouveau mot de passe</label>
                    <input type="password" name="new_password" placeholder="Min. 8 caractères" required>
                </div>
                <button type="submit" name="change_password" class="btn-save">Modifier le mot de passe</button>
            </form>
        </section>

        <section class="panel-card" style="grid-column: span 1;">
            <h3><i class="fa fa-list-check"></i> Activités système récentes</h3>
            
            <div class="log-item">
                <i class="fa-solid fa-circle-check"></i>
                <div>Connexion administrateur réussie.</div>
                <div class="log-time">À l'instant</div>
            </div>
            
            <div class="log-item">
                <i class="fa-solid fa-circle-check"></i>
                <div>Intégrité de la base de données vérifiée.</div>
                <div class="log-time">Il y a 10m</div>
            </div>
        </section>

    </div>

</div>

</body>
</html>