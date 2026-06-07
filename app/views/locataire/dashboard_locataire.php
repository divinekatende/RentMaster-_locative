<?php
session_start();

// Sécurité : locataire connecté obligatoire
if (!isset($_SESSION['id_locataire'])) {
    header('Location: ../../controllers/login_locataire.php');
    exit;
}

// Bloquer si mot de passe non encore changé
if (isset($_SESSION['force_change_mdp']) && $_SESSION['force_change_mdp'] === true) {
    header('Location: profil_locataire.php?first_login=1');
    exit;
}

$nom    = $_SESSION['nom']    ?? '';
$prenom = $_SESSION['prenom'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - RentMaster</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }

:root {
    --blue: #2563eb;
    --blue2: #3b82f6;
    --dark: #0f172a;
    --gray: #64748b;
    --white: #fff;
}

body {
    background: linear-gradient(135deg, #eef5ff, #ffffff);
    min-height: 100vh;
    display: flex;
}

/* SIDEBAR */
aside {
    width: 240px;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    border-right: 1px solid #dbeafe;
    padding: 20px;
    position: fixed;
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: 0.3s;
    z-index: 999;
}

.logo { text-align: center; margin-bottom: 30px; }
.logo img {
    width: 90px; height: 90px;
    border-radius: 50%;
    border: 4px solid #dbeafe;
    object-fit: cover;
}
.logo h2 { color: var(--blue); margin-top: 10px; font-size: 1.2em; }

aside nav { flex: 1; }

aside a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 13px 14px;
    margin-bottom: 6px;
    border-radius: 14px;
    text-decoration: none;
    color: var(--dark);
    font-weight: 600;
    font-size: 0.95em;
    transition: 0.3s;
}
aside a:hover, aside a.active {
    background: linear-gradient(135deg, var(--blue), var(--blue2));
    color: white;
    transform: translateX(5px);
}
aside a i { width: 18px; text-align: center; }

.sidebar-footer {
    border-top: 1px solid #dbeafe;
    padding-top: 14px;
}
.sidebar-footer a {
    color: #e74c3c !important;
    background: #fff5f5;
}
.sidebar-footer a:hover {
    background: #e74c3c !important;
    color: white !important;
    transform: translateX(5px);
}

/* MAIN */
.content {
    margin-left: 240px;
    width: 100%;
    padding: 30px;
}

/* BANNER PREMIERE CONNEXION */
.banner-first-login {
    background: linear-gradient(135deg, #fff7ed, #fffbeb);
    border-left: 5px solid #f59e0b;
    padding: 18px 22px;
    border-radius: 14px;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    box-shadow: 0 4px 15px rgba(245,158,11,0.15);
}
.banner-first-login .txt h3 { color: #92400e; font-size: 1.05em; margin-bottom: 4px; }
.banner-first-login .txt p  { color: #b45309; font-size: 0.88em; }
.banner-first-login a {
    background: linear-gradient(135deg, var(--blue), var(--blue2));
    color: white;
    padding: 10px 18px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.88em;
    white-space: nowrap;
    transition: 0.3s;
}
.banner-first-login a:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(37,99,235,0.3); }

/* TOP BAR */
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 10px;
}
.topbar h1 { color: var(--blue); font-size: 1.8em; }

/* WELCOME */
.welcome {
    background: linear-gradient(135deg, var(--blue), var(--blue2));
    color: white;
    padding: 24px 28px;
    border-radius: 20px;
    margin-bottom: 24px;
    box-shadow: 0 10px 25px rgba(37,99,235,0.2);
}
.welcome h2 { font-size: 1.4em; margin-bottom: 4px; }
.welcome p  { opacity: 0.85; font-size: 0.9em; }

/* STATS CARDS */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 18px;
    margin-bottom: 24px;
}
.card {
    background: white;
    padding: 22px;
    border-radius: 18px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: 0.3s;
}
.card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
.card .icon {
    width: 50px; height: 50px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3em;
    color: white;
    flex-shrink: 0;
}
.icon-blue   { background: linear-gradient(135deg, #2563eb, #3b82f6); }
.icon-orange { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
.icon-red    { background: linear-gradient(135deg, #ef4444, #f87171); }
.icon-green  { background: linear-gradient(135deg, #22c55e, #4ade80); }
.card .val   { font-size: 1.3em; font-weight: 700; color: var(--dark); }
.card .lbl   { font-size: 0.78em; color: var(--gray); }

/* QUICK ACTIONS */
.section-title { font-size: 1.05em; font-weight: 700; color: var(--dark); margin-bottom: 14px; }
.quick {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 14px;
    margin-bottom: 30px;
}
.quick-btn {
    background: white;
    border: 1.5px solid #dbeafe;
    padding: 22px 16px;
    border-radius: 18px;
    text-align: center;
    cursor: pointer;
    color: var(--blue);
    font-weight: 700;
    font-size: 0.88em;
    transition: 0.3s;
    text-decoration: none;
    display: block;
}
.quick-btn i { display: block; font-size: 1.6em; margin-bottom: 8px; }
.quick-btn:hover {
    background: linear-gradient(135deg, var(--blue), var(--blue2));
    color: white;
    border-color: transparent;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(37,99,235,0.25);
}

/* BTN PAYER */
.btn-payer {
    background: linear-gradient(135deg, #22c55e, #4ade80);
    color: white;
    border: none;
    padding: 16px 28px;
    border-radius: 14px;
    font-size: 1em;
    font-weight: 700;
    cursor: pointer;
    transition: 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 28px;
}
.btn-payer:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(34,197,94,0.35);
}

/* MENU MOBILE */
.menu-btn {
    display: none;
    width: 44px; height: 44px;
    border: none;
    border-radius: 12px;
    background: var(--blue);
    color: white;
    font-size: 1.1em;
    cursor: pointer;
}

footer {
    text-align: center;
    padding: 16px;
    color: var(--gray);
    font-size: 0.82em;
    border-top: 1px solid #e2e8f0;
    margin-top: 10px;
}

@media (max-width: 900px) {
    aside { left: -260px; }
    aside.show { left: 0; }
    .content { margin-left: 0; padding: 70px 14px 20px; }
    .menu-btn { display: flex; align-items: center; justify-content: center; }
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside id="sidebar">
    <div class="logo">
        <img src="../../../public/assets/images/logo.png" alt="logo">
        <h2>RentMaster</h2>
    </div>
    <nav>
        <a href="dashboard_locataire.php" class="active"><i class="fa fa-home"></i> Dashboard</a>
        <a href="contrat_locataire.php"><i class="fa fa-file-contract"></i> Contrat</a>
        <a href="paiement_locataire.php"><i class="fa fa-credit-card"></i> Paiement</a>
        <a href="historique.php"><i class="fa fa-history"></i> Historique</a>
        <a href="profil_locataire.php"><i class="fa fa-user"></i> Profil</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../../controllers/logout.php"><i class="fa fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</aside>

<!-- MAIN -->
<div class="content">

    <!-- BANNIÈRE PREMIÈRE CONNEXION -->
    <?php if (isset($_SESSION['first_login']) && $_SESSION['first_login'] == 0): ?>
    <div class="banner-first-login">
        <div class="txt">
            <h3><i class="fa fa-triangle-exclamation"></i> Première connexion détectée</h3>
            <p>Votre mot de passe par défaut doit être changé pour sécuriser votre compte.</p>
        </div>
        <a href="profil_locataire.php?first_login=1">
            <i class="fa fa-lock"></i> Changer maintenant
        </a>
    </div>
    <?php endif; ?>

    <!-- TOP BAR -->
    <div class="topbar">
        <h1><i class="fa fa-home"></i> Dashboard</h1>
        <button class="menu-btn" onclick="document.getElementById('sidebar').classList.toggle('show')">
            <i class="fa fa-bars"></i>
        </button>
    </div>

    <!-- WELCOME -->
    <div class="welcome">
        <h2>👋 Bienvenue, <?= htmlspecialchars($prenom . ' ' . $nom) ?> !</h2>
        <p>Voici un résumé de votre espace locataire RentMaster.</p>
    </div>

    <!-- CARDS -->
    <div class="cards">
        <div class="card">
            <div class="icon icon-blue"><i class="fa fa-money-bill-wave"></i></div>
            <div>
                <div class="val">500 $</div>
                <div class="lbl">Loyer mensuel</div>
            </div>
        </div>
        <div class="card">
            <div class="icon icon-orange"><i class="fa fa-calendar-alt"></i></div>
            <div>
                <div class="val">05 Juil.</div>
                <div class="lbl">Prochaine échéance</div>
            </div>
        </div>
        <div class="card">
            <div class="icon icon-red"><i class="fa fa-circle-xmark"></i></div>
            <div>
                <div class="val" style="color:#ef4444;">Non payé</div>
                <div class="lbl">Statut paiement</div>
            </div>
        </div>
        <div class="card">
            <div class="icon icon-green"><i class="fa fa-file-contract"></i></div>
            <div>
                <div class="val">Actif</div>
                <div class="lbl">Contrat</div>
            </div>
        </div>
    </div>

    <!-- PAYER -->
    <button class="btn-payer" id="payBtn" onclick="payer()">
        <i class="fa fa-credit-card"></i> Payer maintenant
    </button>

    <!-- ACTIONS RAPIDES -->
    <p class="section-title">Actions rapides</p>
    <div class="quick">
        <a href="message_locataire.php"      class="quick-btn"><i class="fa fa-envelope"></i> Messages</a>
        <a href="notification_locataire.php" class="quick-btn"><i class="fa fa-bell"></i> Notifications</a>
        <a href="guide.php"                  class="quick-btn"><i class="fa fa-book"></i> Guide</a>
        <a href="calendrier_locataire.php"   class="quick-btn"><i class="fa fa-calendar"></i> Calendrier</a>
    </div>

    <footer>&copy; 2026 RentMaster — Tous droits réservés</footer>
</div>

<script>
function payer() {
    const btn = document.getElementById('payBtn');
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Redirection...';
    btn.style.background = 'linear-gradient(135deg,#16a34a,#22c55e)';
    setTimeout(() => { window.location.href = 'paiement_locataire.php'; }, 1000);
}
</script>
</body>
</html>