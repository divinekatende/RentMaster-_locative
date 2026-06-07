<?php

require_once __DIR__ . '/../../auth/session.php';
verifierConnexion();
verifierRole(['bailleur']);

require_once __DIR__ . '/../../config/database.php';

$conn = (new Database())->connect();
$id_bailleur = $_SESSION['id'];

/* ==========================
   STATISTIQUES
========================== */
function fetchCount($conn, $sql, $params = []) {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

$nbBiens = fetchCount($conn, "SELECT COUNT(*) FROM biens WHERE id_bailleur = ?", [$id_bailleur]);
$nbLocataires = fetchCount($conn, "SELECT COUNT(*) FROM locataires WHERE id_bailleur = ?", [$id_bailleur]);
$nbContrats = fetchCount($conn, "SELECT COUNT(*) FROM contrats WHERE id_bailleur = ?", [$id_bailleur]);
$nbPaiements = fetchCount($conn, "
    SELECT COUNT(*)
    FROM paiements p
    INNER JOIN contrats c ON p.id_contrat = c.id_contrat
    WHERE c.id_bailleur = ?
", [$id_bailleur]);

$revenuTotal = fetchCount($conn, "
    SELECT COALESCE(SUM(p.montant_verse), 0)
    FROM paiements p
    INNER JOIN contrats c ON p.id_contrat = c.id_contrat
    WHERE c.id_bailleur = ?
", [$id_bailleur]);

$biensDisponibles = fetchCount($conn, "
    SELECT COUNT(*)
    FROM biens
    WHERE id_bailleur = ? AND statut='Disponible'
", [$id_bailleur]);

/* ==========================
   REVENUS MENSUELS
========================== */
$revenusMois = array_fill(0, 12, 0);
$stmt = $conn->prepare("
    SELECT
        MONTH(p.date_paiement) AS mois,
        SUM(p.montant_verse) AS total
    FROM paiements p
    INNER JOIN contrats c ON p.id_contrat = c.id_contrat
    WHERE c.id_bailleur = ?
    GROUP BY MONTH(p.date_paiement)
");
$stmt->execute([$id_bailleur]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $mois = (int)$row['mois'];
    if ($mois >= 1 && $mois <= 12) {
        $revenusMois[$mois - 1] = (float)$row['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - RentMaster</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
    :root {
        --bg: #f8fafc;
        --white: #ffffff;
        --blue: #2563eb;
        --blue-light: #3b82f6;
        --blue-soft: #eff6ff;
        --text: #0f172a;
        --text-soft: #64748b;
        --border: #e2e8f0;
        --radius: 16px;
        --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05);
    }

    body.dark {
        --bg: #0f172a;
        --white: #1e293b;
        --blue: #3b82f6;
        --blue-light: #60a5fa;
        --blue-soft: #1e293b;
        --text: #f8fafc;
        --text-soft: #94a3b8;
        --border: #334155;
        --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.2);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; transition: background-color 0.3s, border-color 0.3s; }
    body { background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }
    
    .app { display: flex; width: 100%; }

    /* SIDEBAR */
    .sidebar {
        width: 260px;
        height: 100vh;
        position: fixed;
        background: var(--white);
        border-right: 1px solid var(--border);
        padding: 24px;
        display: flex;
        flex-direction: column;
        z-index: 100;
    }
    .sidebar.collapsed { width: 85px; padding: 24px 12px; }
    .brand { display: flex; align-items: center; justify-content: space-between; margin-bottom: 35px; }
    .brand-logo { display: flex; align-items: center; gap: 12px; }
    .brand-logo i { background: var(--blue); color: #fff; padding: 10px; border-radius: 10px; font-size: 18px; }
    .brand-logo span { font-weight: 700; font-size: 20px; color: var(--text); }
    .sidebar.collapsed .brand-logo span { display: none; }
    .sidebar.collapsed .toggle-sidebar-btn { margin: 0 auto; }

    .nav { display: flex; flex-direction: column; gap: 6px; }
    .nav a {
        display: flex; align-items: center; gap: 12px; padding: 12px;
        border-radius: 10px; text-decoration: none; color: var(--text-soft); font-weight: 500; transition: .2s;
    }
    .nav a:hover, .nav a.active { background: var(--blue-soft); color: var(--blue); }
    .nav a.logout { margin-top: 40px; color: #ef4444; }
    .nav a.logout:hover { background: #fef2f2; color: #ef4444; }
    body.dark .nav a.logout:hover { background: #2d1f1f; }
    .sidebar.collapsed .nav span { display: none; }

    /* MAIN */
    .main { margin-left: 260px; padding: 40px; width: calc(100% - 260px); }
    .sidebar.collapsed + .main { margin-left: 85px; width: calc(100% - 85px); }

    /* TOPBAR */
    .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; gap: 20px; }
    .topbar h2 { font-size: 14px; font-weight: 600; color: var(--blue); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;}
    .topbar h1 { font-size: 28px; font-weight: 700; color: var(--text); }
    .topbar p { color: var(--text-soft); font-size: 15px; }
    .actions { display: flex; gap: 12px; align-items: center; }

    /* BUTTONS */
    .btn { padding: 11px 20px; border: none; border-radius: 12px; cursor: pointer; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-primary { background: var(--blue); color: #fff; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2); }
    .btn-primary:hover { background: var(--blue-light); }
    .btn-ghost { background: var(--white); border: 1px solid var(--border); color: var(--text); box-shadow: var(--shadow); }
    .btn-ghost:hover { background: var(--bg); }

    /* HERO BANNER */
    .hero { background: linear-gradient(135deg, #1e3a8a, var(--blue)); color: #fff; padding: 30px; border-radius: var(--radius); margin-bottom: 30px; box-shadow: 0 10px 20px rgba(37, 99, 235, 0.15); }
    .hero h2 { font-size: 24px; font-weight: 700; margin-bottom: 8px; }
    .hero p { opacity: 0.9; font-size: 15px; }

    /* STATS CARDS */
    .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .card { background: var(--white); padding: 24px; border-radius: var(--radius); box-shadow: var(--shadow); display: flex; align-items: center; justify-content: space-between; }
    .card-info h3 { color: var(--text-soft); font-size: 14px; font-weight: 500; text-transform: uppercase; margin-bottom: 6px; }
    .card-info p { font-size: 28px; font-weight: 700; color: var(--text); }
    .card-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
    
    .card-icon.blue { background: #eff6ff; color: #2563eb; }
    .card-icon.green { background: #ecfdf5; color: #10b981; }
    .card-icon.purple { background: #f5f3ff; color: #8b5cf6; }
    .card-icon.orange { background: #fff7ed; color: #f97316; }

    /* CALC BANNER LINK */
    .calc-banner { display: flex; align-items: center; gap: 16px; background: var(--white); border: 1px solid var(--border); padding: 20px; border-radius: var(--radius); color: var(--text); text-decoration: none; margin-bottom: 30px; box-shadow: var(--shadow); }
    .calc-banner:hover { border-color: var(--blue); }
    .calc-banner i { font-size: 24px; color: var(--blue); background: var(--blue-soft); padding: 12px; border-radius: 12px; }

    /* ASYMMETRIC GRID */
    .grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; margin-bottom: 30px; }
    .panel { background: var(--white); padding: 26px; border-radius: var(--radius); box-shadow: var(--shadow); }
    .panel h3 { font-size: 18px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }

    /* QUICK ACTIONS */
    .quick { display: flex; flex-direction: column; gap: 12px; }
    .quick a { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; border-radius: 12px; background: var(--bg); border: 1px solid var(--border); text-decoration: none; color: var(--text); font-weight: 600; }
    .quick a:hover { border-color: var(--blue); color: var(--blue); }
    .quick a span { display: flex; align-items: center; gap: 10px; }

    /* TABLE */
    .table-responsive { width: 100%; overflow-x: auto; margin-top: 15px; }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    th { padding: 14px; border-bottom: 2px solid var(--border); color: var(--text-soft); font-weight: 600; font-size: 14px; }
    td { padding: 14px; border-bottom: 1px solid var(--border); font-size: 15px; color: var(--text); }
    tr:last-child td { border-bottom: none; }

    /* BADGE */
    .badge { background: #ef4444; color: #fff; padding: 4px 8px; border-radius: 20px; font-size: 11px; font-weight: 700; }

    /* RESPONSIVE */
    @media (max-width: 1024px) {
        .grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
        .topbar { flex-direction: column; align-items: flex-start; }
        .actions { width: 100%; justify-content: flex-start; flex-wrap: wrap; }
        .sidebar { width: 85px; padding: 24px 12px; }
        .sidebar .brand-logo span, .sidebar .nav span { display: none; }
        .main { margin-left: 85px; width: calc(100% - 85px); padding: 20px; }
    }
    </style>
</head>

<body>
<div class="app">

    <div class="sidebar" id="sidebar">
      <div class="brand">
         <div class="brand-logo">
            <i class="fa-solid fa-building"></i>
            <span>RentMaster</span>
         </div>
         <button class="btn btn-ghost toggle-sidebar-btn" onclick="toggleSidebar()" style="padding: 8px 12px;"><i class="fa fa-bars"></i></button>
      </div>

      <div class="nav">
        <a href="dashboard.php" class="active"><i class="fa fa-chart-line"></i><span>Dashboard</span></a>
        <a href="bien.php"><i class="fa fa-building"></i><span>Biens</span></a>
        <a href="locataire.php"><i class="fa fa-users"></i><span>Locataires</span></a>
        <a href="contrat.php"><i class="fa fa-file-contract"></i><span>Contrats</span></a>
        <a href="paiement.php"><i class="fa fa-credit-card"></i><span>Paiements</span></a>
        <a href="profil.php"><i class="fa fa-user-circle"></i><span>Mon Profil</span></a>
        <a href="../../controllers/logout.php" class="logout"><i class="fas fa-sign-out-alt"></i><span>Déconnexion</span></a>
      </div>
    </div>

    <div class="main">
        
        <div class="topbar">
          <div>
            <h2>Bienvenue, <?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></h2>
            <h1>Portefeuille locatif</h1>
          </div>

          <div class="actions">
            <button class="btn btn-ghost" onclick="window.location='calendrier.php'">
              <i class="fa fa-calendar"></i> Calendrier
            </button>
            <button id="themeToggle" class="btn btn-ghost">
              <i class="fas fa-moon"></i>
            </button>
            <button class="btn btn-primary" onclick="window.location='bien.php#formAjout'">
              <i class="fa fa-plus"></i> Ajouter un bien
            </button>
          </div>
        </div>

        <div class="hero">
          <h2>Suivi centralisé de votre activité</h2>
          <p>Visualisez vos indicateurs clés de performance et optimisez vos rendements immobiliers en temps réel.</p>
        </div>

        <div class="stats">
            <div class="card">
                <div class="card-info">
                    <h3>Biens Immobilier</h3>
                    <p><?= $nbBiens ?></p>
                </div>
                <div class="card-icon blue"><i class="fa fa-building"></i></div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3>Locataires Actifs</h3>
                    <p><?= $nbLocataires ?></p>
                </div>
                <div class="card-icon green"><i class="fa fa-users"></i></div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3>Contrats Générés</h3>
                    <p><?= $nbContrats ?></p>
                </div>
                <div class="card-icon purple"><i class="fa fa-file-contract"></i></div>
            </div>
            <div class="card">
                <div class="card-info">
                    <h3>Total Paiements</h3>
                    <p><?= $nbPaiements ?></p>
                </div>
                <div class="card-icon orange"><i class="fa fa-credit-card"></i></div>
            </div>
        </div>

        <a href="calculatrice.html" class="calc-banner">
            <i class="fas fa-calculator"></i>
            <div>
                <strong>Prévisions financières :</strong> Simulez vos flux de trésorerie futurs et estimez précisément vos charges déductibles.
            </div>
        </a>

        <div class="grid">
            <div class="panel">
                <h3><i class="fa fa-chart-area" style="color:var(--blue);"></i> Revenus mensuels</h3>
                <div style="position: relative; height:280px; width:100%;">
                    <canvas id="chart"></canvas>
                </div>
            </div>

            <div class="panel">
                <h3>⚡ Actions rapides</h3>
                <div class="quick">
                    <a href="message.php"><span><i class="fa fa-comment" style="color: var(--blue);"></i> Messages</span><i class="fa fa-chevron-right"></i></a>
                    <a href="notification.php"><span><i class="fa fa-bell" style="color: #f59e0b;"></i> Notifications</span><span id="badge" class="badge">3</span></a>
                    <a href="contrat.php#formAjout"><span><i class="fa fa-plus-circle" style="color: #10b981;"></i> Nouveau contrat</span><i class="fa fa-chevron-right"></i></a>
                </div>
            </div>
        </div>

        <div class="panel">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h3><i class="fa fa-history" style="color:var(--text-soft)"></i> Dernières actions</h3>
            <button class="btn btn-ghost" onclick="window.location='historique_bailleur.php'">
              <i class="fa fa-clock"></i> Historique global
            </button>
          </div>

          <div class="table-responsive">
              <table>
                <thead>
                  <tr><th>Événement / Action</th><th>Date d'exécution</th></tr>
                </thead>
                <tbody id="tableNotifications">
                    <tr><td>Création du contrat de bail pour l'Appartement T2</td><td>07 Juin 2026</td></tr>
                    <tr><td>Paiement reçu du locataire Martin S.</td><td>05 Juin 2026</td></tr>
                </tbody>
              </table>
          </div>
        </div>

    </div>
</div>

<script>
/* ================= SIDEBAR ================= */
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('collapsed');
}

/* ================= GRAPH ================= */
const revenus = <?php echo json_encode(array_values($revenusMois)); ?>;
const ctx = document.getElementById("chart");

if (ctx) {
    new Chart(ctx, {
        type: "line",
        data: {
            labels: ["Jan","Fév","Mar","Avr","Mai","Juin","Juil","Août","Sep","Oct","Nov","Déc"],
            datasets: [{
                label: "Revenus (€)",
                data: revenus,
                borderColor: "#2563eb",
                backgroundColor: "rgba(37,99,235,0.06)",
                pointBackgroundColor: "#2563eb",
                fill: true,
                tension: 0.35,
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { borderDash: [5, 5] } },
                x: { grid: { display: false } }
            }
        }
    });
}

/* ================= THEME ================= */
const themeToggle = document.getElementById("themeToggle");
if (themeToggle) {
    themeToggle.addEventListener("click", () => {
        document.body.classList.toggle("dark");
        themeToggle.innerHTML = document.body.classList.contains("dark")
            ? '<i class="fas fa-sun"></i>'
            : '<i class="fas fa-moon"></i>';
    });
}
</script>
</body>
</html>