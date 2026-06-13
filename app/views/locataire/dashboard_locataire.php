<?php
session_start();
 
// Sécurité : locataire connecté obligatoire
if (!isset($_SESSION['id_locataire'])) {
    header('Location: ../../auth/login-locataire.php');
    exit;
}
 
// Bloquer si mot de passe non encore changé
if (isset($_SESSION['force_change_mdp']) && $_SESSION['force_change_mdp'] === true) {
    header('Location: profil_locataire.php?first_login=1');
    exit;
}
 
$nom     = $_SESSION['nom']    ?? '';
$prenom = $_SESSION['prenom'] ?? '';
$id_locataire = $_SESSION['id_locataire'];
 
// ===========================================================
// CONNEXION BDD
// ===========================================================
require_once(__DIR__ . '/../../config/database.php');
 
$loyer_mensuel     = null;
$statut_contrat    = null;
$prochaine_echeance = null;
$statut_paiement   = 'Inconnu';
$mois_courant      = date('Y-m');
 
try {
    $database = new Database();
    $conn = $database->connect();
 
    // --- 1. Récupérer le contrat actif du locataire ---
    $stmt = $conn->prepare("
        SELECT id_contrat, montant, date_debut, date_fin, statut 
        FROM contrats 
        WHERE id_locataire = :id AND statut = 'Actif' 
        LIMIT 1
    ");
    $stmt->execute([':id' => $id_locataire]);
    $contrat = $stmt->fetch(PDO::FETCH_ASSOC);
 
    if ($contrat) {
        $id_contrat     = $contrat['id_contrat'];
        $loyer_mensuel  = $contrat['montant'];
        $statut_contrat = $contrat['statut'];
 
        // --- 2. Prochaine échéance : trouver le premier mois non payé (Complet) ---
        $stmt2 = $conn->prepare("
            SELECT mois_annee FROM paiements 
            WHERE id_contrat = :id_contrat AND statut = 'Complet'
        ");
        $stmt2->execute([':id_contrat' => $id_contrat]);
        $mois_payes = $stmt2->fetchAll(PDO::FETCH_COLUMN);
 
        $debut = new DateTime($contrat['date_debut']);
        $fin   = new DateTime($contrat['date_fin']);
        $cursor = clone $debut;
 
        $prochaine_echeance = null;
        while ($cursor <= $fin) {
            $mois_str = $cursor->format('Y-m');
            if (!in_array($mois_str, $mois_payes)) {
                $prochaine_echeance = $cursor->format('d M. Y');
                break;
            }
            $cursor->modify('+1 month');
        }
        if (!$prochaine_echeance) {
            $prochaine_echeance = 'Tout payé ✅';
        }
 
        // --- 3. Statut paiement du mois courant ---
        $stmt3 = $conn->prepare("
            SELECT statut, SUM(montant_verse) as total_verse
            FROM paiements 
            WHERE id_contrat = :id_contrat AND mois_annee = :mois
            GROUP BY statut
            ORDER BY FIELD(statut, 'Complet', 'Acompte') 
            LIMIT 1
        ");
        $stmt3->execute([':id_contrat' => $id_contrat, ':mois' => $mois_courant]);
        $paiement_mois = $stmt3->fetch(PDO::FETCH_ASSOC);
 
        if ($paiement_mois) {
            $total = floatval($paiement_mois['total_verse']);
            $loyer = floatval($loyer_mensuel);
            if ($total >= $loyer) {
                $statut_paiement = 'Payé';
            } else {
                $statut_paiement = 'Acompte (' . number_format($total, 0, ',', ' ') . ' / ' . number_format($loyer, 0, ',', ' ') . ' $)';
            }
        } else {
            $statut_paiement = 'Non payé';
        }
    }
 
} catch (PDOException $e) {
    $loyer_mensuel     = 'N/A';
    $statut_contrat    = 'N/A';
    $prochaine_echeance = 'N/A';
    $statut_paiement   = 'Erreur BDD';
}
 
$couleur_statut = match(true) {
    str_starts_with($statut_paiement, 'Payé')     => ['icon' => 'icon-green',  'fa' => 'fa-circle-check',      'color' => '#10b981', 'bg' => '#ecfdf5'],
    str_starts_with($statut_paiement, 'Acompte')   => ['icon' => 'icon-orange', 'fa' => 'fa-circle-half-stroke', 'color' => '#f59e0b', 'bg' => '#fef3c7'],
    default                                         => ['icon' => 'icon-red',    'fa' => 'fa-circle-xmark',      'color' => '#ef4444', 'bg' => '#fef2f2'],
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - RentMaster</title>
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
            --shadow-md: 0 10px 15px -3px rgba(15, 23, 42, 0.08);
            
            --danger: #ef4444;
            --danger-soft: #fef2f2;
            --warning: #f59e0b;
            --warning-soft: #fef3c7;
            --success: #10b981;
            --success-soft: #ecfdf5;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        body { background: var(--bg-app); color: var(--text-main); min-height: 100vh; display: flex; }
        
        /* SIDEBAR COMPACTE & MODERNE */
        aside {
            width: 260px;
            background: var(--surface);
            border-right: 1px solid var(--border-color);
            padding: 24px 16px;
            position: fixed;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: 0.3s ease;
            z-index: 999;
        }
        .logo { text-align: center; margin-bottom: 32px; display: flex; flex-direction: column; align-items: center; gap: 10px; }
        .logo img {
            width: 70px; height: 70px;
            border-radius: 50%;
            border: 3px solid var(--primary-soft);
            object-fit: cover;
        }
        .logo h2 { color: var(--primary); font-size: 1.25rem; font-weight: 700; letter-spacing: -0.02em; }
        aside nav { flex: 1; display: flex; flex-direction: column; gap: 4px; }
        aside a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: var(--radius-md);
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        aside a:hover {
            background: var(--primary-soft);
            color: var(--primary);
        }
        aside a.active {
            background: var(--primary);
            color: white;
            font-weight: 600;
        }
        aside a i { width: 20px; text-align: center; font-size: 16px; }
        
        .sidebar-footer { border-top: 1px solid var(--border-color); padding-top: 16px; }
        .sidebar-footer a { color: var(--danger); background: var(--danger-soft); }
        .sidebar-footer a:hover { background: var(--danger); color: white; }
        
        /* CONTENT SPA */
        .content { margin-left: 260px; width: 100%; padding: 40px; }
        
        /* ALERTE SECURITE */
        .banner-first-login {
            background: var(--warning-soft);
            border-left: 4px solid var(--warning);
            padding: 16px 20px;
            border-radius: var(--radius-md);
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            box-shadow: var(--shadow-sm);
        }
        .banner-first-login .txt h3 { color: #92400e; font-size: 15px; font-weight: 600; margin-bottom: 2px; }
        .banner-first-login .txt p  { color: #b45309; font-size: 13px; }
        .banner-first-login a {
            background: var(--primary);
            color: white;
            padding: 10px 16px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: 0.2s ease;
        }
        .banner-first-login a:hover { background: var(--primary-light); transform: translateY(-1px); }
        
        /* TOPBAR & TITRE */
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .topbar h1 { font-size: 24px; font-weight: 700; color: var(--text-main); letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; }
        
        /* WELCOME BLOCK ÉPURÉ */
        .welcome {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 28px;
            border-radius: var(--radius-lg);
            margin-bottom: 32px;
            box-shadow: var(--shadow-md);
        }
        .welcome h2 { font-size: 22px; font-weight: 700; margin-bottom: 6px; letter-spacing: -0.01em; }
        .welcome p  { opacity: 0.85; font-size: 14px; }
        
        /* GRILLE DE KPI CARDS */
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .card {
            background: var(--surface); padding: 24px; border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm); display: flex; align-items: center; gap: 16px; 
            border: 1px solid var(--border-color); transition: all 0.2s ease;
        }
        .card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); border-color: #cbd5e1; }
        .card .icon {
            width: 48px; height: 48px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: white; flex-shrink: 0;
        }
        .icon-blue   { background: var(--primary-soft); color: var(--primary); }
        .icon-orange { background: var(--warning-soft); color: var(--warning); }
        .icon-red    { background: var(--danger-soft); color: var(--danger); }
        .icon-green  { background: var(--success-soft); color: var(--success); }
        
        .card .val   { font-size: 16px; font-weight: 700; color: var(--text-main); letter-spacing: -0.01em; }
        .card .lbl   { font-size: 13px; color: var(--text-muted); margin-top: 2px; }
        
        /* ACTION PRINCIPALE */
        .btn-payer {
            background: var(--success); color: white; border: none; padding: 14px 24px;
            border-radius: var(--radius-md); font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s ease;
            display: inline-flex; align-items: center; gap: 8px; margin-bottom: 32px; text-decoration: none;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }
        .btn-payer:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3); }
        
        .no-contrat { background: var(--danger-soft); border-left: 4px solid var(--danger); padding: 16px 20px; border-radius: var(--radius-md); color: #b91c1c; margin-bottom: 32px; font-weight: 500; font-size: 14px; }
        .menu-btn { display: none; width: 40px; height: 40px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); background: var(--surface); color: var(--text-main); font-size: 16px; cursor: pointer; }
        
        /* GRILLE ACTIONS RAPIDES */
        .section-title { font-size: 14px; font-weight: 600; color: var(--text-main); margin-bottom: 16px; text-transform: uppercase; letter-spacing: 0.05em; }
        .quick { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; margin-bottom: 40px; }
        .quick-btn {
            background: var(--surface); border: 1px solid var(--border-color); padding: 20px 16px; border-radius: var(--radius-lg); text-align: center;
            cursor: pointer; color: var(--primary); font-weight: 600; font-size: 14px; transition: all 0.2s ease; text-decoration: none; display: block;
            box-shadow: var(--shadow-sm);
        }
        .quick-btn i { display: block; font-size: 20px; margin-bottom: 10px; color: var(--primary-light); }
        .quick-btn:hover {
            border-color: var(--primary-light); background: var(--primary-soft); transform: translateY(-2px); box-shadow: var(--shadow-md);
        }
        
        footer { text-align: center; padding: 24px 0; color: var(--text-muted); font-size: 13px; border-top: 1px solid var(--border-color); margin-top: auto; }
        
        /* RESPONSIVE TABLETTE & MOBILE */
        @media (max-width: 900px) {
            aside { left: -280px; }
            aside.show { left: 0; box-shadow: var(--shadow-md); }
            .content { margin-left: 0; padding: 24px 16px; }
            .menu-btn { display: flex; align-items: center; justify-content: center; }
            .topbar { margin-top: 10px; }
        }
    </style>
</head>
<body>
 
<aside id="sidebar">
    <div class="logo">
        <img src="../../../public/assets/images/logo.png" alt="logo">
        <h2>RentMaster</h2>
    </div>
    <nav>
        <a href="dashboard_locataire.php" class="active"><i class="fa fa-home"></i> Dashboard</a>
        <a href="contrat_locataire.php"><i class="fa fa-file-contract"></i> Contrat</a>
        <a href="paiement_locataire.php"><i class="fa fa-receipt"></i> Reçus</a>
        <a href="historique.php"><i class="fa fa-history"></i> Historique</a>
        <a href="profil_locataire.php"><i class="fa fa-user"></i> Profil</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../../controllers/logout.php"><i class="fa fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</aside>
 
<div class="content">
    <?php if (isset($_SESSION['first_login']) && $_SESSION['first_login'] == 0): ?>
    <div class="banner-first-login">
        <div class="txt">
            <h3><i class="fa fa-triangle-exclamation"></i> Première connexion détectée</h3>
            <p>Votre mot de passe par défaut doit être changé pour sécuriser votre compte.</p>
        </div>
        <a href="profil_locataire.php?first_login=1"><i class="fa fa-lock"></i> Changer maintenant</a>
    </div>
    <?php endif; ?>
 
    <div class="topbar">
        <h1><i class="fa-solid fa-gauge-high" style="color: var(--primary);"></i> Dashboard</h1>
        <button class="menu-btn" onclick="document.getElementById('sidebar').classList.toggle('show')">
            <i class="fa fa-bars"></i>
        </button>
    </div>
 
    <div class="welcome">
        <h2>👋 Bienvenue, <?= htmlspecialchars($prenom . ' ' . $nom) ?> !</h2>
        <p>Voici un résumé global de votre espace de gestion locative.</p>
    </div>
 
    <?php if (!$contrat): ?>
    <div class="no-contrat">
        <i class="fa fa-circle-exclamation"></i> Aucun contrat actif trouvé pour votre compte. Contactez votre bailleur.
    </div>
    <?php else: ?>
 
    <div class="cards">
        <div class="card">
            <div class="icon icon-blue"><i class="fa fa-money-bill-wave"></i></div>
            <div>
                <div class="val"><?= number_format(floatval($loyer_mensuel), 0, ',', ' ') ?> $</div>
                <div class="lbl">Loyer mensuel</div>
            </div>
        </div>
 
        <div class="card">
            <div class="icon icon-orange"><i class="fa fa-calendar-alt"></i></div>
            <div>
                <div class="val"><?= htmlspecialchars($prochaine_echeance) ?></div>
                <div class="lbl">Prochaine échéance</div>
            </div>
        </div>
 
        <div class="card" style="border-left: 4px solid <?= $couleur_statut['color'] ?>;">
            <div class="icon" style="background: <?= $couleur_statut['bg'] ?>; color: <?= $couleur_statut['color'] ?>;">
                <i class="fa <?= $couleur_statut['fa'] ?>"></i>
            </div>
            <div>
                <div class="val" style="color:<?= $couleur_statut['color'] ?>; font-size:15px;"><?= htmlspecialchars($statut_paiement) ?></div>
                <div class="lbl">Paiement <?= date('M. Y') ?></div>
            </div>
        </div>
 
        <div class="card">
            <div class="icon icon-green"><i class="fa fa-file-contract"></i></div>
            <div>
                <div class="val"><?= htmlspecialchars($statut_contrat) ?></div>
                <div class="lbl">Statut Contrat</div>
            </div>
        </div>
    </div>
 
    <a href="paiement_locataire.php" class="btn-payer"><i class="fa fa-credit-card"></i> Effectuer un règlement</a>
    <?php endif; ?>
 
    <div class="section-title">Actions rapides</div>
    <div class="quick">
        <a href="message_locataire.php" class="quick-btn"><i class="fa fa-envelope"></i>Messages</a>
        <a href="notification_locataire.php" class="quick-btn"><i class="fa fa-bell"></i>Notifications</a>
        <a href="guide.php" class="quick-btn"><i class="fa fa-book"></i>Guide</a>
        <a href="calendrier_locataire.php" class="quick-btn"><i class="fa fa-calendar"></i>Calendrier</a>
    </div>
 
    <footer>&copy; 2026 RentMaster — Tous droits réservés</footer>
</div>
 
<script>
document.querySelector('.menu-btn')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('show');
});
</script>
</body>
</html>