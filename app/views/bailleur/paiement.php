<?php
session_start();
require_once('../../config/database.php');
require_once('../../controllers/PaiementController.php');
 
$conn = (new Database())->connect();
 
$id_bailleur = $_SESSION['id'] ?? null;
if (!$id_bailleur) die("Accès refusé");
 
$ctrl    = new PaiementController();
$erreur  = null;
$succes  = null;
 
/* =========================
   SUPPRESSION
========================= */
if (isset($_GET['delete'])) {
    $ctrl->delete((int)$_GET['delete'], $id_bailleur);
    header("Location: paiement.php"); exit;
}
 
/* =========================
   INSERT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'id_bailleur'   => $id_bailleur,
        'id_contrat'    => $_POST['id_contrat']    ?? null,
        'mois_annee'    => $_POST['mois_annee']    ?? null,  // format "2026-03"
        'montant_verse' => $_POST['montant_verse'] ?? 0,
        'date_paiement' => $_POST['date_paiement'] ?? date('Y-m-d'),
    ];
 
    $result = $ctrl->create($data);
 
    if ($result['ok']) {
        $succes = $result['msg'];
    } else {
        $erreur = $result['msg'];
    }
}
 
/* =========================
   DONNÉES
========================= */
$paiements = $ctrl->index($id_bailleur);
$contrats  = $ctrl->getContratsActifs($id_bailleur);
 
// Mois disponibles : on génère les 12 derniers mois + 3 prochains
$moisOptions = [];
for ($i = -3; $i <= 11; $i++) {
    $ts  = strtotime("first day of this month $i months");
    $val = date('Y-m', $ts);
    $moisOptions[$val] = ucfirst(strftime('%B %Y', $ts));
    // fallback si strftime non dispo (PHP 8.1+)
    if (empty($moisOptions[$val]) || $moisOptions[$val] === $val) {
        $moisFr = ['01'=>'Janvier','02'=>'Février','03'=>'Mars','04'=>'Avril',
                   '05'=>'Mai','06'=>'Juin','07'=>'Juillet','08'=>'Août',
                   '09'=>'Septembre','10'=>'Octobre','11'=>'Novembre','12'=>'Décembre'];
        [$y, $m] = explode('-', $val);
        $moisOptions[$val] = ($moisFr[$m] ?? $m) . ' ' . $y;
    }
}
 
// Résumés par contrat+mois pour JS (loyer + déjà payé)
$resumesJson = [];
foreach ($contrats as $c) {
    foreach ($moisOptions as $mv => $_) {
        $resume = $ctrl->resumeMoisContrat($c['id_contrat'], $mv);
        $resumesJson[$c['id_contrat']][$mv] = [
            'loyer'       => (float)$c['montant'],
            'deja_paye'   => (float)$resume['total_verse'],
            'reste'       => (float)$c['montant'] - (float)$resume['total_verse'],
        ];
    }
}
$resumesJson = json_encode($resumesJson);
$contratsJson = json_encode($contrats);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Paiements - RentMaster</title>
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
            --shadow-sm: 0 4px 6px -1px rgba(15, 23, 42, 0.05);
            --shadow-md: 0 10px 15px -3px rgba(15, 23, 42, 0.08);
            
            --success: #10b981;
            --success-soft: #ecfdf5;
            --warning: #f59e0b;
            --warning-soft: #fffbe6;
            --danger: #ef4444;
            --danger-soft: #fef2f2;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        body { background: var(--bg-app); color: var(--text-main); min-height: 100vh; }
        .container { display: flex; }
         
        /* SIDEBAR HARMONISÉE */
        .sidebar {
            width: 260px; background: var(--primary); color: white;
            height: 100vh; position: fixed;
            display: flex; flex-direction: column;
            padding: 24px 16px; transition: 0.3s ease; z-index: 1000;
            box-shadow: 4px 0 24px rgba(30, 64, 175, 0.08);
        }
        .sidebar .logo {
            display: flex; align-items: center; gap: 12px;
            padding-bottom: 24px; margin-bottom: 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar .logo img {
            width: 40px; height: 40px; border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.2); object-fit: cover;
        }
        .sidebar h2 { font-size: 18px; font-weight: 700; letter-spacing: -0.02em; }
        .sidebar nav { display: flex; flex-direction: column; gap: 6px; }
        .sidebar nav a {
            display: flex; align-items: center; gap: 12px;
            color: rgba(255, 255, 255, 0.75); text-decoration: none;
            padding: 12px 16px; border-radius: var(--radius-sm);
            font-size: 14px; font-weight: 500; transition: all 0.2s ease;
        }
        .sidebar nav a i { width: 18px; font-size: 16px; }
        .sidebar nav a.active, .sidebar nav a:hover {
            background: rgba(255, 255, 255, 0.15); color: white; font-weight: 600;
        }
         
        /* MOBILE MENU BUTTON */
        .btn-menu-mobile {
            display: none; background: var(--primary); color: white;
            border: none; width: 42px; height: 42px; border-radius: var(--radius-sm);
            cursor: pointer; font-size: 18px;
            position: fixed; top: 16px; left: 16px; z-index: 2000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
         
        /* ESPACE PRINCIPAL */
        .main-content { margin-left: 260px; padding: 40px; flex: 1; max-width: calc(100% - 260px); }
        .page-header { margin-bottom: 32px; }
        .page-header h1 { font-size: 26px; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 6px; }
        .page-header p { color: var(--text-muted); font-size: 14px; line-height: 1.5; }
         
        /* ALERTES */
        .alert {
            padding: 14px 18px; border-radius: var(--radius-md); margin-bottom: 20px;
            font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px;
        }
        .alert-error  { background: var(--danger-soft); border: 1px solid #fca5a5; color: #b91c1c; }
        .alert-succes { background: var(--success-soft); border: 1px solid #6ee7b7; color: #047857; }
         
        /* ZONE COMMANDE SUPÉRIEURE */
        .actions-top { display: flex; gap: 12px; margin-bottom: 24px; }
        .btn-primary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 24px; border-radius: var(--radius-md);
            background: var(--primary); color: white; border: none;
            font-weight: 600; font-size: 14px; cursor: pointer; white-space: nowrap;
            transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(30, 64, 175, 0.15);
        }
        .btn-primary:hover { background: var(--primary-light); transform: translateY(-1px); }

        /* ==========================================================================
           GRILLE DE CARTES / BLOCS (Génération Moderne)
           ========================================================================== */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
        }
        .payment-card {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            padding: 20px;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .payment-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 14px;
        }
        .card-title-area h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 4px;
        }
        .card-id {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
        }
        
        /* Contenu masqué par défaut (Accordéon) */
        .card-details {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            border-top: 0 solid var(--border-color);
            margin-top: 0;
            padding-top: 0;
        }
        .card-details.open {
            max-height: 500px; /* Assez grand pour contenir les infos */
            border-top: 1px solid var(--border-color);
            margin-top: 14px;
            padding-top: 14px;
            transition: max-height 0.4s ease-in;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .info-item span { color: var(--text-muted); }
        .info-item strong { color: var(--text-main); font-weight: 600; }

        .card-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px dashed var(--border-color);
        }
         
        /* BADGES */
        .badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600;
        }
        .badge-complet  { background: var(--success-soft); color: #047857; border: 1px solid #a7f3d0; }
        .badge-acompte  { background: var(--warning-soft); color: #b45309; border: 1px solid #fde68a; }
         
        /* BARRE DE PROGRESSION TABLEAU */
        .progress-wrap { background: #e2e8f0; border-radius: 10px; height: 6px; min-width: 90px; overflow: hidden; }
        .progress-bar  { background: var(--primary-light); border-radius: 10px; height: 10px; transition: width 0.4s ease; }
        .progress-bar.complet { background: var(--success); }
         
        /* BOUTONS D'ACTION */
        .action-btn {
            height: 34px; padding: 0 12px; border-radius: var(--radius-sm);
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            border: 1px solid var(--border-color); background: var(--surface);
            color: var(--text-muted); cursor: pointer; transition: all 0.2s;
            text-decoration: none; font-size: 13px; font-weight: 500;
        }
        .action-btn.toggle-btn { color: var(--primary); border-color: rgba(59, 130, 246, 0.3); background: var(--primary-soft); }
        .action-btn.toggle-btn:hover { background: var(--primary-light); color: white; }
        .action-btn.delete:hover { border-color: #fca5a5; color: var(--danger); background: var(--danger-soft); }
         
        /* EMPTY STATE */
        .empty-state { text-align: center; padding: 48px 24px; color: var(--text-muted); grid-column: 1 / -1; }
        .empty-state i { font-size: 48px; margin-bottom: 16px; color: #cbd5e1; display: block; }
        .empty-state p { font-size: 15px; font-weight: 500; }
         
        /* OVERLAY MODAL FLOU */
        .form-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px);
            z-index: 3000; align-items: center; justify-content: center; padding: 20px;
        }
        .form-overlay.show { display: flex; }
         
        /* STRUCTURE DU FORMULAIRE FENÊTRE */
        .form-modal {
            background: var(--surface); border-radius: var(--radius-lg);
            width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid var(--border-color); animation: slideUp 0.25s ease;
            padding: 30px;
        }
        @keyframes slideUp {
            from { transform: translateY(15px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
        .form-modal h2 { font-size: 20px; font-weight: 700; color: var(--text-main); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .form-modal label { display: block; margin-top: 14px; font-size: 13px; font-weight: 600; color: var(--text-main); }
        .form-modal input, .form-modal select {
            width: 100%; padding: 11px 14px; margin-top: 6px;
            border-radius: var(--radius-sm); border: 1px solid var(--border-color);
            outline: none; font-size: 14px; background: #f8fafc; transition: all 0.2s;
        }
        .form-modal input:focus, .form-modal select:focus {
            border-color: var(--primary-light); background: var(--surface);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
         
        /* INFO BOX LOYER ENCART */
        .info-box {
            border-radius: var(--radius-md); padding: 16px; margin-top: 16px;
            border: 1px solid #bfdbfe; background: #f0f7ff;
            display: flex; flex-direction: column; gap: 8px;
        }
        .info-box .info-row {
            display: flex; justify-content: space-between; font-size: 13px; color: #1e3a8a;
        }
        .info-box .info-row strong { color: var(--text-main); font-weight: 600; }
        .info-box .reste { color: var(--danger); font-weight: 700; }
        .info-box .complet-msg { color: var(--success); font-weight: 700; text-align: center; padding: 4px 0; font-size: 14px; }
         
        /* BARRE PROGRESS FORMULAIRE */
        .prog-form-wrap { background: #e2e8f0; border-radius: 10px; height: 6px; margin-top: 4px; overflow: hidden; }
        .prog-form-bar  { background: var(--primary-light); border-radius: 10px; height: 6px; transition: width 0.3s; }
        .prog-form-bar.ok { background: var(--success); }
         
        /* SÉPARATEUR */
        .form-divider { border: none; border-top: 1px solid var(--border-color); margin: 20px 0 8px; }
         
        /* ZONE DE BOUTONS INFERIEURE */
        .btn-group { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
        .btn-group button {
            flex: 1; padding: 12px; border-radius: var(--radius-sm);
            border: none; cursor: pointer; font-weight: 600; font-size: 14px;
            display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.2s;
        }
        .btn-save   { background: var(--primary); color: white; }
        .btn-save:hover   { background: var(--primary-light); }
        .btn-cancel { background: #f1f5f9; color: var(--text-muted); }
        .btn-cancel:hover { background: #e2e8f0; }
         
        /* PIED DE PAGE */
        footer {
            text-align: center; padding: 24px;
            background: #f1f5f9; margin-top: 40px; font-size: 13px; color: var(--text-muted); border-top: 1px solid var(--border-color);
        }
        footer a { color: var(--primary); text-decoration: none; font-weight: 500; }
        footer a:hover { text-decoration: underline; }
         
        /* RESPONSIVE FLUIDE */
        @media(max-width: 900px) {
            .sidebar { left: -280px; width: 260px; }
            .sidebar.show { left: 0; }
            .main-content { margin-left: 0; padding: 80px 16px 24px; max-width: 100%; }
            .btn-menu-mobile { display: flex; align-items: center; justify-content: center; }
            .btn-group { flex-direction: column; }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <script src="theme.js"></script>
</head>
<body>
 
<button id="menuToggle" class="btn-menu-mobile"><i class="fas fa-bars"></i></button>
 
<div class="container">
 
<aside class="sidebar" id="appSidebar">
  <div class="logo">
    <img src="../../../public/assets/images/logo.png" alt="Logo">
    <h2>RentMaster</h2>
  </div>
  <nav>
    <a href="dashboard.php"><i class="fa fa-home"></i> Dashboard</a>
    <a href="bien.php"><i class="fa fa-building"></i> Biens</a>
    <a href="locataire.php"><i class="fa fa-users"></i> Locataires</a>
    <a href="contrat.php"><i class="fa fa-file-contract"></i> Contrats</a>
    <a href="paiement.php" class="active"><i class="fa fa-credit-card"></i> Paiements</a>
  </nav>
</aside>
 
<main class="main-content">
  <div class="page-header">
    <h1>💳 Gestion des Paiements</h1>
    <p>Enregistrez les paiements par mois. Un acompte partiel est possible — le solde se complète au versement suivant.</p>
  </div>
 
  <?php if (!empty($erreur)): ?>
  <div class="alert alert-error"><i class="fa fa-exclamation-triangle"></i> <?= htmlspecialchars($erreur) ?></div>
  <?php endif; ?>
  <?php if (!empty($succes)): ?>
  <div class="alert alert-succes"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($succes) ?></div>
  <?php endif; ?>
 
  <div class="actions-top">
    <button class="btn-primary" onclick="openForm()">
      <i class="fa fa-plus"></i> Enregistrer un paiement
    </button>
  </div>
 
  <div class="cards-grid">
      <?php if (empty($paiements)): ?>
        <div class="empty-state">
          <i class="fa-regular fa-credit-card"></i>
          <p>Aucun paiement enregistré pour le moment.</p>
        </div>
      <?php else: ?>
      <?php
        // Calculer progression par contrat+mois
        $progCache = [];
        foreach ($paiements as $p) {
            $key = $p['id_contrat'] . '_' . $p['mois_annee'];
            if (!isset($progCache[$key])) {
                $resume = $ctrl->resumeMoisContrat($p['id_contrat'], $p['mois_annee']);
                $progCache[$key] = [
                    'total' => (float)$resume['total_verse'],
                    'loyer' => (float)$p['loyer_mensuel']
                ];
            }
        }
        $moisFr = ['01'=>'Janv.','02'=>'Févr.','03'=>'Mars','04'=>'Avr.',
                   '05'=>'Mai','06'=>'Juin','07'=>'Juil.','08'=>'Août',
                   '09'=>'Sept.','10'=>'Oct.','11'=>'Nov.','12'=>'Déc.'];
      ?>
      <?php foreach($paiements as $p):
        $key  = $p['id_contrat'] . '_' . $p['mois_annee'];
        $prog = $progCache[$key];
        $pct  = $prog['loyer'] > 0 ? min(100, round($prog['total'] / $prog['loyer'] * 100)) : 0;
        $complet = $pct >= 100;
        [$annee, $mNum] = explode('-', $p['mois_annee']);
        $nomMois = ($moisFr[$mNum] ?? $mNum) . ' ' . $annee;
        $badgeCls = $p['statut'] === 'Complet' ? 'badge-complet' : 'badge-acompte';
        $badgeIco = $p['statut'] === 'Complet' ? '<i class="fa fa-check-circle"></i>' : '<i class="fa fa-spinner fa-spin" style="font-size:11px"></i>';
      ?>
      
      <div class="payment-card">
          <div class="card-header">
              <div class="card-title-area">
                  <h3><?= htmlspecialchars($p['bien_nom']) ?></h3>
                  <span class="card-id">ID Versement: #<?= $p['id_paiement'] ?></span>
              </div>
              <span class="badge <?= $badgeCls ?>"><?= $badgeIco ?> <?= $p['statut'] ?></span>
          </div>

          <div class="card-details" id="details-<?= $p['id_paiement'] ?>">
              <div class="info-item">
                  <span>Locataire :</span>
                  <strong><?= htmlspecialchars($p['loc_nom'].' '.$p['loc_prenom']) ?></strong>
              </div>
              <div class="info-item">
                  <span>Période :</span>
                  <strong><?= $nomMois ?></strong>
              </div>
              <div class="info-item">
                  <span>Ce versement :</span>
                  <strong style="color:var(--primary);"><?= number_format($p['montant_verse'], 2) ?> $</strong>
              </div>
              <div class="info-item">
                  <span>Loyer Mensuel :</span>
                  <strong><?= number_format($p['loyer_mensuel'], 2) ?> $</strong>
              </div>
              <div class="info-item">
                  <span>Date :</span>
                  <strong><?= $p['date_paiement'] ?></strong>
              </div>
              
              <div style="margin-top: 12px; margin-bottom: 6px;">
                  <span style="font-size: 12px; color: var(--text-muted); font-weight:600; display:block; margin-bottom:4px;">Progression du mois :</span>
                  <div style="display:flex; align-items:center; gap:10px;">
                    <div class="progress-wrap" style="flex:1;">
                      <div class="progress-bar <?= $complet ? 'complet' : '' ?>" style="width:<?= $pct ?>%"></div>
                    </div>
                    <span style="font-size:12px; font-weight:600; color:var(--text-muted);"><?= $pct ?>%</span>
                  </div>
              </div>

              <div class="card-actions">
                  <a href="paiement.php?delete=<?= $p['id_paiement'] ?>"
                     class="action-btn delete"
                     onclick="return confirm('Supprimer ce versement ?')" title="Supprimer">
                    <i class="fa fa-trash"></i> Supprimer
                  </a>
              </div>
          </div>

          <button class="action-btn toggle-btn" onclick="toggleDetails(<?= $p['id_paiement'] ?>, this)" style="margin-top:14px; width:100%;">
              <i class="fa fa-chevron-down"></i> Voir plus
          </button>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
  </div>
</main>
</div>
 
<div id="formOverlay" class="form-overlay">
  <div class="form-modal">
    <h2><i class="fa fa-credit-card" style="color:var(--primary-light);"></i> Enregistrer un paiement</h2>
 
    <form method="POST">
 
      <label> Contrat (Locataire — Bien) *</label>
      <select name="id_contrat" id="contratSelect" required onchange="updateInfo()">
        <option value="">-- Choisir un contrat actif --</option>
        <?php foreach($contrats as $c): ?>
          <option value="<?= $c['id_contrat'] ?>"
                  data-loyer="<?= $c['montant'] ?>">
            <?= htmlspecialchars($c['loc_nom'].' '.$c['loc_prenom']) ?>
            — <?= htmlspecialchars($c['bien_nom']) ?>
            (<?= number_format($c['montant'], 2) ?> $/mois)
          </option>
        <?php endforeach; ?>
      </select>
 
      <label> Mois concerné *</label>
      <select name="mois_annee" id="moisSelect" required onchange="updateInfo()">
        <option value="">-- Choisir le mois --</option>
        <?php foreach($moisOptions as $val => $label): ?>
          <option value="<?= $val ?>"
            <?= $val === date('Y-m') ? 'selected' : '' ?>>
            <?= $label ?>
          </option>
        <?php endforeach; ?>
      </select>
 
      <div id="infoBox" class="info-box" style="display:none;">
        <div class="info-row">
          <span> Loyer mensuel</span>
          <strong id="infoLoyer">—</strong>
        </div>
        <div class="info-row">
          <span> Déjà versé</span>
          <strong id="infoDejaPayé">—</strong>
        </div>
        <div class="info-row">
          <span> Reste à payer</span>
          <span class="reste" id="infoReste">—</span>
        </div>
        <div class="prog-form-wrap">
          <div class="prog-form-bar" id="progBar" style="width:0%"></div>
        </div>
        <div id="completMsg" class="complet-msg" style="display:none;">
           Ce mois est entièrement réglé !
        </div>
      </div>
 
      <hr class="form-divider">
 
      <label> Montant versé ($) *</label>
      <input type="number" name="montant_verse" id="montantVerse"
             min="0.01" step="0.01" required
             placeholder="Ex: 300"
             onchange="verifierMontant()" oninput="verifierMontant()">
      <div id="msgMontant" style="font-size:12px; margin-top:6px; font-weight:500;"></div>
 
      <label> Date du paiement *</label>
      <input type="date" name="date_paiement"
             value="<?= date('Y-m-d') ?>" required>
 
      <div class="btn-group">
        <button type="button" class="btn-cancel" onclick="closeForm()">
          <i class="fa fa-times"></i> Annuler
        </button>
        <button type="submit" class="btn-save" id="btnSave">
          <i class="fa fa-check"></i> Enregistrer
        </button>
      </div>
 
    </form>
  </div>
</div>
 
<footer>
    <p>&copy; 2026 RentMaster. Interface d'administration foncière. | 
      <a href="mailto:divinekatende23@gmail.com">Support client</a> | 
      <a href="https://divinekatende.github.io/DIVKT/" target="_blank">Portfolio Développeur</a>
    </p>
</footer>
 
<script>
// Données PHP → JS
var resumes  = <?= $resumesJson ?>;
var contrats = <?= $contratsJson ?>;

// Fonction pour dérouler les détails d'un bloc de paiement
function toggleDetails(id, btn) {
    var details = document.getElementById('details-' + id);
    if(details.classList.contains('open')) {
        details.classList.remove('open');
        btn.innerHTML = '<i class="fa fa-chevron-down"></i> Voir plus';
    } else {
        details.classList.add('open');
        btn.innerHTML = '<i class="fa fa-chevron-up"></i> Moins d\'infos';
    }
}
 
function updateInfo() {
    var contratId = document.getElementById('contratSelect').value;
    var mois      = document.getElementById('moisSelect').value;
    var infoBox   = document.getElementById('infoBox');
 
    if (!contratId || !mois) { infoBox.style.display = 'none'; return; }
 
    var data = resumes[contratId] && resumes[contratId][mois];
    if (!data) { infoBox.style.display = 'none'; return; }
 
    var loyer     = data.loyer;
    var dejaPaye  = data.deja_paye;
    var reste     = data.reste;
    var pct       = loyer > 0 ? Math.min(100, Math.round(dejaPaye / loyer * 100)) : 0;
 
    document.getElementById('infoLoyer').innerText    = loyer.toFixed(2) + ' $';
    document.getElementById('infoDejaPayé').innerText = dejaPaye.toFixed(2) + ' $';
    document.getElementById('infoReste').innerText    = reste.toFixed(2) + ' $';
 
    var bar = document.getElementById('progBar');
    bar.style.width = pct + '%';
    bar.className   = 'prog-form-bar' + (pct >= 100 ? ' ok' : '');
 
    var completMsg = document.getElementById('completMsg');
    var btnSave    = document.getElementById('btnSave');
    var montantInput = document.getElementById('montantVerse');
 
    if (reste <= 0) {
        completMsg.style.display = 'block';
        btnSave.disabled = true;
        montantInput.disabled = true;
        montantInput.value = '';
    } else {
        completMsg.style.display = 'none';
        btnSave.disabled = false;
        montantInput.disabled = false;
        montantInput.max = reste.toFixed(2);
    }
 
    infoBox.style.display = 'flex';
    verifierMontant();
}
 
function verifierMontant() {
    var contratId = document.getElementById('contratSelect').value;
    var mois      = document.getElementById('moisSelect').value;
    var montant   = parseFloat(document.getElementById('montantVerse').value) || 0;
    var msg       = document.getElementById('msgMontant');
 
    if (!contratId || !mois || montant <= 0) { msg.innerText = ''; return; }
 
    var data  = resumes[contratId] && resumes[contratId][mois];
    if (!data) { msg.innerText = ''; return; }
 
    var reste = data.reste;
    var apres = data.deja_paye + montant;
 
    if (montant > reste) {
        msg.style.color  = '#ef4444';
        msg.innerHTML    = '<i class="fa fa-times-circle"></i> Le montant dépasse le solde restant dû (' + reste.toFixed(2) + ' $)';
    } else if (apres >= data.loyer) {
        msg.style.color  = '#10b981';
        msg.innerHTML    = '<i class="fa fa-check-circle"></i> Excellent ! Ce versement solde le mois en cours.';
    } else {
        msg.style.color  = '#f59e0b';
        msg.innerHTML    = '<i class="fa fa-info-circle"></i> Versement partiel : il restera ' + (reste - montant).toFixed(2) + ' $ à percevoir.';
    }
}
 
function openForm() {
    document.getElementById('formOverlay').classList.add('show');
}
function closeForm() {
    document.getElementById('formOverlay').classList.remove('show');
}
 
document.getElementById('formOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeForm();
});
 
<?php if (!empty($erreur)): ?>
window.addEventListener('DOMContentLoaded', function() { openForm(); });
<?php endif; ?>
 
document.getElementById('menuToggle').addEventListener('click', function() {
    document.getElementById('appSidebar').classList.toggle('show');
});
</script>
 
</body>
</html>