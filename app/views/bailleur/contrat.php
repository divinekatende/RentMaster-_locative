<?php
session_start();
require_once('../../config/database.php');
 
$conn = (new Database())->connect();
 
$id_bailleur = $_SESSION['id'] ?? null;
if (!$id_bailleur) die("Accès refusé");
 
$erreur = null;
 
/* =========================
   SUPPRESSION
========================= */
if (isset($_GET['delete'])) {
    require_once('../../controllers/ContratController.php');
    $ctrl = new ContratController();
    $ctrl->delete((int)$_GET['delete'], $id_bailleur);
    header("Location: contrat.php"); exit;
}
 
/* =========================
   INSERT / UPDATE
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once('../../controllers/ContratController.php');
    $ctrl = new ContratController();
 
    $data = [
        'id_bailleur'        => $id_bailleur,
        'id_bien'            => $_POST['bien']               ?? null,
        'id_locataire'       => $_POST['locataire']          ?? null,
        'date_debut'         => $_POST['date_debut']         ?? null,
        'date_fin'           => $_POST['date_fin']           ?? null,
        'montant'            => $_POST['montant']            ?? 0,
        'charges'            => $_POST['charges']            ?? 0,
        'charge_eau'         => $_POST['charge_eau']         ?? 'Locataire',
        'charge_electricite' => $_POST['charge_electricite'] ?? 'Locataire',
        'impot_locataire'    => $_POST['impot_locataire']    ?? 'Locataire',
        'statut'             => $_POST['statut']             ?? 'Actif',
    ];
 
    if (!empty($_POST['id_contrat'])) {
        $result = $ctrl->update((int)$_POST['id_contrat'], $data);
    } else {
        $result = $ctrl->create($data);
    }
 
    if ($result['ok']) {
        header("Location: contrat.php"); exit;
    } else {
        $erreur = $result['msg'];
    }
}
 
/* =========================
   LISTES
========================= */
$stmt = $conn->prepare("SELECT id_bien, titre, prix, statut FROM biens WHERE id_bailleur=?");
$stmt->execute([$id_bailleur]);
$biens = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
$stmt = $conn->prepare("
    SELECT DISTINCT l.* FROM locataires l
    WHERE l.id_bailleur = :id
       OR l.id_locataire IN (
           SELECT id_locataire FROM contrats WHERE id_bailleur = :id2
       )
    ORDER BY l.nom ASC
");
$stmt->execute(['id' => $id_bailleur, 'id2' => $id_bailleur]);
$locataires = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
$stmt = $conn->prepare("
    SELECT c.*, b.titre AS bien_nom, b.prix AS bien_prix,
           l.nom AS loc_nom, l.prenom
    FROM contrats c
    LEFT JOIN biens b ON c.id_bien = b.id_bien
    LEFT JOIN locataires l ON c.id_locataire = l.id_locataire
    WHERE c.id_bailleur=? ORDER BY c.id_contrat DESC
");
$stmt->execute([$id_bailleur]);
$contrats = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
/* =========================
   EDIT MODE
========================= */
$edit = null;
if (isset($_GET['edit'])) {
    $id   = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM contrats WHERE id_contrat=:id AND id_bailleur=:id_bailleur");
    $stmt->execute(['id' => $id, 'id_bailleur' => $id_bailleur]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}
 
$showForm = isset($edit) || !empty($erreur);

// Options pour les selects de répartition
$repartitionOptions = ['Locataire', 'Bailleur', 'Les deux'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Contrats - RentMaster</title>
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
            --success-bg: #f0fdf4;
            --success-text: #166534;
            --success-dot: #22c55e;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        body { background: var(--bg-app); color: var(--text-main); min-height: 100vh; }
        .container { display: flex; }
         
        /* SIDEBAR */
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
         
        /* ZONE COMMANDE SUPÉRIEURE */
        .actions-top { display: flex; gap: 12px; margin-bottom: 24px; align-items: center; }
        .actions-top input[type="text"] {
            flex: 1; padding: 12px 16px; border-radius: var(--radius-md);
            border: 1px solid var(--border-color); outline: none; font-size: 14px;
            transition: all 0.2s ease; background: var(--surface);
        }
        .actions-top input[type="text"]:focus { border-color: var(--primary-light); box-shadow: var(--shadow-sm); }

        .btn-primary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 24px; border-radius: var(--radius-md);
            background: var(--primary); color: white; border: none;
            font-weight: 600; font-size: 14px; cursor: pointer; white-space: nowrap;
            transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(30, 64, 175, 0.15);
        }
        .btn-primary:hover { background: var(--primary-light); transform: translateY(-1px); }
         
        /* ALERTES */
        .alert-error {
            background: #fef2f2; border: 1px solid #fca5a5;
            border-radius: var(--radius-md); padding: 16px 20px;
            margin-bottom: 24px; color: #991b1b; font-size: 14px;
            font-weight: 500; display: flex; align-items: center; gap: 12px;
        }

        /* GRILLE DE CONTRATS */
        .contrats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }
        .contrat-card {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            transition: all 0.25s ease;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .contrat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }
        .card-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
        .card-id { font-size: 13px; font-weight: 700; color: var(--text-muted); }
        
        .card-title { font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 4px; }
        .card-subtitle { font-size: 14px; color: var(--text-muted); margin-bottom: 16px; display: flex; align-items: center; gap: 6px; }
        
        .card-price-box {
            background: var(--bg-app); padding: 10px 14px; border-radius: var(--radius-sm);
            margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;
        }
        .card-price-label { font-size: 12px; color: var(--text-muted); font-weight: 500; }
        .card-price-amount { font-size: 16px; font-weight: 700; color: var(--primary); }

        .btn-view-more {
            width: 100%; padding: 10px;
            background: #f1f5f9; color: var(--text-main);
            border: none; border-radius: var(--radius-sm);
            font-size: 13px; font-weight: 600; cursor: pointer;
            transition: all 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-view-more:hover { background: var(--primary); color: white; }
         
        /* BADGES */
        .badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge::before { content: ''; display: inline-block; width: 6px; height: 6px; border-radius: 50%; }
        .badge-actif { background: var(--success-bg); color: var(--success-text); }
        .badge-actif::before { background: var(--success-dot); }
        .badge-termine { background: #f1f5f9; color: #475569; }
        .badge-termine::before { background: #94a3b8; }
         
        /* EMPTY STATE */
        .empty-state { text-align: center; padding: 48px 24px; color: var(--text-muted); grid-column: 1 / -1; }
        .empty-state i { font-size: 48px; margin-bottom: 16px; color: #cbd5e1; display: block; }
         
        /* OVERLAY MODAL */
        .form-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px);
            z-index: 3000; align-items: center; justify-content: center; padding: 20px;
        }
        .form-overlay.show { display: flex; }
         
        /* MODAL */
        .form-modal {
            background: var(--surface); border-radius: var(--radius-lg);
            width: 100%; max-width: 580px; max-height: 90vh; overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid var(--border-color); animation: slideUp 0.25s ease;
        }
        @keyframes slideUp {
            from { transform: translateY(15px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
        .modal-header { padding: 24px 30px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { font-size: 18px; font-weight: 700; color: var(--text-main); }

        .modal-body { padding: 30px; display: flex; flex-direction: column; gap: 16px; }

        /* DÉTAILS MODAL */
        .details-list { display: flex; flex-direction: column; gap: 14px; margin-bottom: 10px; }
        .details-item { display: flex; justify-content: space-between; padding-bottom: 10px; border-bottom: 1px dashed var(--border-color); font-size: 14px; }
        .details-item strong { color: var(--text-muted); font-weight: 500; }
        .details-item span { color: var(--text-main); font-weight: 600; }

        /* SECTION BLOC RÉPARTITION DANS DÉTAILS */
        .details-section-title {
            font-size: 12px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.05em; color: var(--primary); padding: 8px 0 4px;
            border-top: 2px solid var(--primary-soft); margin-top: 4px;
        }

        /* BADGE RÉPARTITION */
        .badge-repartition {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        .badge-locataire { background: #eff6ff; color: #1e40af; }
        .badge-bailleur  { background: #fff7ed; color: #c2410c; }
        .badge-lesdeux   { background: #f0fdf4; color: #166534; }

        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }

        .form-modal label { font-size: 13px; font-weight: 600; color: var(--text-main); }
        .form-modal input, .form-modal select {
            padding: 10px 14px; border-radius: var(--radius-sm);
            border: 1px solid var(--border-color); outline: none;
            font-size: 14px; background: #f8fafc; transition: all 0.2s;
        }
        .form-modal input:focus, .form-modal select:focus {
            border-color: var(--primary-light); background: var(--surface);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* BLOC SECTION FORMULAIRE */
        .form-section {
            background: var(--primary-soft);
            border: 1px solid #bfdbfe;
            border-radius: var(--radius-sm);
            padding: 16px;
            display: flex; flex-direction: column; gap: 16px;
        }
        .form-section-title {
            font-size: 12px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.05em; color: var(--primary); margin-bottom: 2px;
        }

        /* TOGGLE BUTTONS RÉPARTITION */
        .charge-row { display: flex; flex-direction: column; gap: 6px; }
        .charge-row-label {
            font-size: 13px; font-weight: 600; color: var(--text-main);
            display: flex; align-items: center; gap: 6px;
        }
        .toggle-group {
            display: flex; gap: 0; border-radius: var(--radius-sm);
            overflow: hidden; border: 1.5px solid var(--border-color);
            width: 100%;
        }
        .toggle-group input[type="radio"] { display: none; }
        .toggle-group label {
            flex: 1; padding: 10px 8px; text-align: center;
            font-size: 13px; font-weight: 600; cursor: pointer;
            background: var(--surface); color: var(--text-muted);
            border: none; border-right: 1.5px solid var(--border-color);
            transition: all 0.2s ease; user-select: none;
            display: flex; align-items: center; justify-content: center; gap: 5px;
        }
        .toggle-group label:last-of-type { border-right: none; }
        .toggle-group input[type="radio"]:checked + label {
            color: white; font-weight: 700;
        }
        /* Couleurs selon le choix */
        .toggle-group.grp-locataire input[value="Locataire"]:checked + label { background: #1e40af; border-color: #1e40af; }
        .toggle-group.grp-locataire input[value="Bailleur"]:checked  + label { background: #c2410c; border-color: #c2410c; }
        .toggle-group.grp-locataire input[value="Les deux"]:checked  + label { background: #166534; border-color: #166534; }
         
        /* LOYER BOX */
        .loyer-box {
            background: var(--primary-soft); border: 1px dashed var(--primary-light);
            border-radius: var(--radius-sm); padding: 14px 18px;
            display: flex; flex-direction: column; gap: 6px;
        }
        .loyer-row { display: flex; justify-content: space-between; align-items: center; }
        .loyer-box .loyer-label { font-size: 13px; color: var(--text-muted); font-weight: 500; }
        .loyer-box .loyer-montant { font-size: 15px; font-weight: 600; color: var(--text-main); }
        .loyer-total { border-top: 1px solid var(--border-color); padding-top: 6px; margin-top: 4px; }
         
        .form-divider { border: none; border-top: 1px solid var(--border-color); margin: 8px 0; }
         
        /* BOUTONS */
        .btn-group { display: flex; justify-content: flex-end; gap: 12px; margin-top: 8px; border-top: 1px solid var(--border-color); padding-top: 20px; }
        .btn-group button, .btn-group a { padding: 11px 20px; border-radius: var(--radius-sm); font-weight: 600; font-size: 14px; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-save { background: var(--primary); color: white; }
        .btn-save:hover { background: var(--primary-light); }
        .btn-cancel { background: #f1f5f9; color: var(--text-muted); justify-content: center; }
        .btn-cancel:hover { background: #e2e8f0; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-warning:hover { background: #d97706; }
         
        /* FOOTER */
        footer {
            text-align: center; padding: 24px;
            background: #f1f5f9; margin-top: 40px; font-size: 13px; color: var(--text-muted); border-top: 1px solid var(--border-color);
        }
        footer a { color: var(--primary); text-decoration: none; font-weight: 500; }
         
        /* RESPONSIVE */
        @media(max-width: 900px) {
            .sidebar { left: -280px; width: 260px; }
            .sidebar.show { left: 0; }
            .main-content { margin-left: 0; padding: 80px 16px 24px; max-width: 100%; }
            .btn-menu-mobile { display: flex; align-items: center; justify-content: center; }
            .actions-top { flex-direction: column; align-items: stretch; }
            .form-row, .form-row-3 { grid-template-columns: 1fr; gap: 16px; }
            .btn-group { flex-direction: column-reverse; }
            .btn-group button, .btn-group a { justify-content: center; width: 100%; }
        }
    </style>
</head>
<body>
 
<button id="menuToggle" class="btn-menu-mobile"><i class="fas fa-bars"></i></button>
 
<div class="container">
 
<aside class="sidebar" id="appSidebar">
  <div class="logo">
    <img src="../../../public/assets/images/logo.png" alt="Logo RentMaster">
    <h2>RentMaster</h2>
  </div>
  <nav>
    <a href="dashboard.php"><i class="fa fa-home"></i> Dashboard</a>
    <a href="bien.php"><i class="fa fa-building"></i> Biens</a>
    <a href="locataire.php"><i class="fa fa-users"></i> Locataires</a>
    <a href="contrat.php" class="active"><i class="fa fa-file-contract"></i> Contrats</a>
    <a href="paiement.php"><i class="fa fa-credit-card"></i> Paiements</a>
  </nav>
</aside>
 
<main class="main-content">
  <div class="page-header">
    <h1><i class="fa fa-file-contract"></i> Gestion des Contrats</h1>
    <p>Gérez les baux d'habitation de votre parc immobilier. Un logement ne peut contenir qu'un seul contrat actif.</p>
  </div>
 
  <?php if (!empty($erreur)): ?>
  <div class="alert-error">
    <i class="fa-solid fa-circle-exclamation"></i>
    <?= htmlspecialchars($erreur) ?>
  </div>
  <?php endif; ?>
 
  <div class="actions-top">
    <input type="text" id="searchInput" placeholder="Rechercher par bien, locataire ou statut..." onkeyup="filtrerContrats()">
    <button class="btn-primary" onclick="openForm()">
      <i class="fa fa-plus"></i> Générer un contrat
    </button>
  </div>
 
  <div class="contrats-grid" id="contratsGrid">
      <?php if(empty($contrats)): ?>
        <div class="empty-state">
          <i class="fa-regular fa-folder-open"></i>
          <p>Aucun contrat actif ou archivé n'est répertorié dans votre espace.</p>
        </div>
      <?php else: ?>
        <?php foreach($contrats as $c): 
            $badgeClass = ($c['statut'] === 'Actif') ? 'badge-actif' : 'badge-termine';
            $nomLocataire = trim(($c['loc_nom'] ?? '').' '.($c['prenom'] ?? ''));
            $searchString = strtolower($c['bien_nom'] . ' ' . $nomLocataire . ' ' . $c['statut']);
            $totalMensuel = ($c['montant'] ?? 0) + ($c['charges'] ?? 0);
        ?>
          <div class="contrat-card" data-search="<?= htmlspecialchars($searchString) ?>">
              <div>
                  <div class="card-top">
                      <span class="card-id">#CONTRAT-<?= $c['id_contrat'] ?></span>
                      <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($c['statut']) ?></span>
                  </div>
                  <div class="card-title"><?= htmlspecialchars($c['bien_nom'] ?? 'Bien inconnu') ?></div>
                  <div class="card-subtitle"><i class="fa fa-user" style="color: var(--text-muted)"></i> <?= htmlspecialchars($nomLocataire) ?></div>
                  
                  <div class="card-price-box">
                      <span class="card-price-label">Loyer CC</span>
                      <span class="card-price-amount"><?= number_format($totalMensuel, 2) ?> $</span>
                  </div>
              </div>
              
              <button class="btn-view-more" onclick="openDetails(<?= htmlspecialchars(json_encode($c)) ?>)">
                  <i class="fa fa-eye"></i> Voir plus
              </button>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
  </div>
</main>
</div>

<!-- =============================================
     MODAL DÉTAILS DU CONTRAT
     ============================================= -->
<div id="detailsOverlay" class="form-overlay">
    <div class="form-modal">
        <div class="modal-header">
            <h2><i class="fa fa-file-invoice"></i> Termes Exécutifs du Bail</h2>
        </div>
        <div class="modal-body">
            <div class="details-list">
                <div class="details-item"><strong>ID Référence :</strong> <span id="detId" style="color: var(--primary);"></span></div>
                <div class="details-item"><strong>Bien immobilier :</strong> <span id="detBien"></span></div>
                <div class="details-item"><strong>Locataire titulaire :</strong> <span id="detLocataire"></span></div>
                <div class="details-item"><strong>Loyer de base :</strong> <span id="detMontant" style="color: #475569;"></span></div>
                <div class="details-item"><strong>Charges Locatives :</strong> <span id="detCharges" style="color: #475569;"></span></div>
                <div class="details-item"><strong>Total Mensuel :</strong> <span id="detTotal" style="color: #16a34a; font-weight: 700;"></span></div>
                <div class="details-item"><strong>Date d'effet (Début) :</strong> <span id="detDebut"></span></div>
                <div class="details-item"><strong>Date d'échéance (Fin) :</strong> <span id="detFin"></span></div>
                <div class="details-item"><strong>Statut Réglementaire :</strong> <span id="detStatut"></span></div>

                <!-- SECTION RÉPARTITION DES CHARGES & IMPÔTS -->
                <div class="details-section-title"><i class="fa fa-scale-balanced"></i> Répartition des charges & impôts</div>
                <div class="details-item">
                    <strong>Charge eau :</strong>
                    <span id="detChargeEau"></span>
                </div>
                <div class="details-item">
                    <strong>Charge électricité :</strong>
                    <span id="detChargeElec"></span>
                </div>
                <div class="details-item">
                    <strong>Impôts & taxes :</strong>
                    <span id="detImpot"></span>
                </div>
            </div>
            
            <div class="btn-group">
                <button onclick="closeDetails()" class="btn-cancel"><i class="fa fa-times"></i> Fermer</button>
                <a id="btnActionDelete" href="#" class="btn-danger" onclick="return confirm('Voulez-vous révoquer ce contrat ? Le logement redeviendra instantanément Disponible.')"><i class="fa fa-trash"></i> Supprimer</a>
                <button id="btnActionEdit" class="btn-warning"><i class="fa fa-edit"></i> Modifier</button>
            </div>
        </div>
    </div>
</div>

<!-- =============================================
     MODAL FORMULAIRE CRÉATION / MODIFICATION
     ============================================= -->
<div id="formOverlay" class="form-overlay <?= $showForm ? 'show' : '' ?>">
  <div class="form-modal">
    <div class="modal-header">
        <h2 id="formTitle">
          <?= isset($edit) ? '<i class="fa fa-edit"></i> Ajuster les termes du contrat' : '<i class="fa fa-plus"></i> Enregistrer un nouveau bail' ?>
        </h2>
    </div>
 
    <form method="POST">
      <div class="modal-body">
          <input type="hidden" name="id_contrat" id="id_contrat" value="<?= $edit['id_contrat'] ?? '' ?>">
          <input type="hidden" name="montant" id="montantInput" value="<?= $edit['montant'] ?? 0 ?>">
     
          <!-- BIEN -->
          <div class="form-group">
              <label>Logement / Bien immobilier *</label>
              <select name="bien" id="bienSelect" required onchange="setPrixBien()">
                <option value="">-- Sélectionner l'adresse du bien --</option>
                <?php foreach($biens as $b):
                  $estBienDuContrat = isset($edit) && $edit['id_bien'] == $b['id_bien'];
                  $estLoue = ($b['statut'] === 'Loué') && !$estBienDuContrat;
                ?>
                  <option
                    value="<?= $b['id_bien'] ?>"
                    data-prix="<?= $b['prix'] ?>"
                    <?= isset($edit) && $edit['id_bien'] == $b['id_bien'] ? 'selected' : '' ?>
                    <?= $estLoue ? 'disabled' : '' ?>
                  >
                    <?= htmlspecialchars($b['titre']) ?> — (<?= number_format($b['prix'], 2) ?> $)
                    <?= $estLoue ? ' 🔴 [Occupé]' : ' 🟢 [Libre]' ?>
                  </option>
                <?php endforeach; ?>
              </select>
          </div>
     
          <!-- LOCATAIRE -->
          <div class="form-group">
              <label>Titulaire du bail (Locataire) *</label>
              <select name="locataire" id="locataireSelect" required>
                <option value="">-- Associer un locataire existant --</option>
                <?php foreach($locataires as $l): ?>
                  <option value="<?= $l['id_locataire'] ?>"
                    <?= isset($edit) && $edit['id_locataire'] == $l['id_locataire'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($l['nom'].' '.$l['prenom']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
          </div>

          <!-- CHARGES FINANCIÈRES -->
          <div class="form-row">
              <div class="form-group">
                  <label>Provisions pour Charges ($) *</label>
                  <input type="number" step="0.01" name="charges" id="chargesInput"
                         value="<?= $edit['charges'] ?? '0.00' ?>" required oninput="calculerTotalForm()">
              </div>
              <div class="form-group">
                  <label>État réglementaire</label>
                  <select name="statut" id="statutSelect">
                    <option value="Actif"   <?= isset($edit) && $edit['statut']=='Actif'   ? 'selected' : '' ?>>Actif (En cours)</option>
                    <option value="Terminé" <?= isset($edit) && $edit['statut']=='Terminé' ? 'selected' : '' ?>>Terminé (Clôturé)</option>
                  </select>
              </div>
          </div>

          <hr class="form-divider">

          <!-- SECTION RÉPARTITION CHARGES & IMPÔTS -->
          <div class="form-section">
              <div class="form-section-title">
                  <i class="fa fa-scale-balanced"></i> Répartition des charges & impôts
              </div>

              <?php
              $charges_config = [
                  ['name' => 'charge_eau',         'label' => 'Eau',           'icon' => 'fa-droplet',  'color' => '#3b82f6'],
                  ['name' => 'charge_electricite', 'label' => 'Électricité',   'icon' => 'fa-bolt',     'color' => '#f59e0b'],
                  ['name' => 'impot_locataire',    'label' => 'Impôts & taxes','icon' => 'fa-landmark', 'color' => '#6366f1'],
              ];
              foreach ($charges_config as $cfg):
                  $valActuelle = $edit[$cfg['name']] ?? 'Locataire';
              ?>
              <div class="charge-row">
                  <div class="charge-row-label">
                      <i class="fa <?= $cfg['icon'] ?>" style="color:<?= $cfg['color'] ?>"></i>
                      <?= $cfg['label'] ?>
                  </div>
                  <div class="toggle-group grp-locataire">
                      <input type="radio" name="<?= $cfg['name'] ?>" id="<?= $cfg['name'] ?>_loc" value="Locataire" <?= $valActuelle === 'Locataire' ? 'checked' : '' ?>>
                      <label for="<?= $cfg['name'] ?>_loc">👤 Locataire</label>

                      <input type="radio" name="<?= $cfg['name'] ?>" id="<?= $cfg['name'] ?>_bai" value="Bailleur" <?= $valActuelle === 'Bailleur' ? 'checked' : '' ?>>
                      <label for="<?= $cfg['name'] ?>_bai">🏠 Bailleur</label>

                      <input type="radio" name="<?= $cfg['name'] ?>" id="<?= $cfg['name'] ?>_les" value="Les deux" <?= $valActuelle === 'Les deux' ? 'checked' : '' ?>>
                      <label for="<?= $cfg['name'] ?>_les">🤝 Les deux</label>
                  </div>
              </div>
              <?php endforeach; ?>
          </div>
     
          <hr class="form-divider">
     
          <!-- NOTE FINANCIÈRE -->
          <div class="form-group">
              <label>Note financière prévisionnelle</label>
              <div class="loyer-box">
                <div class="loyer-row">
                    <span class="loyer-label">Loyer Net Hors Charges :</span>
                    <span class="loyer-montant" id="loyerMontantText">—</span>
                </div>
                <div class="loyer-row">
                    <span class="loyer-label">Charges Locatives :</span>
                    <span class="loyer-montant" id="chargesText">0.00 $</span>
                </div>
                <div class="loyer-row loyer-total">
                    <span class="loyer-label" style="font-weight:700;">Total mensuel (CC) :</span>
                    <span class="loyer-montant" id="totalMontantText" style="color:var(--primary); font-weight:700;">—</span>
                </div>
              </div>
          </div>
     
          <!-- DATES -->
          <div class="form-row">
              <div class="form-group">
                  <label>Date d'effet (Début) *</label>
                  <input type="date" name="date_debut" id="date_debut" value="<?= $edit['date_debut'] ?? '' ?>" required>
              </div>
              <div class="form-group">
                  <label>Date d'échéance (Fin) *</label>
                  <input type="date" name="date_fin" id="date_fin" value="<?= $edit['date_fin'] ?? '' ?>" required>
              </div>
          </div>
     
          <div class="btn-group">
            <button type="button" class="btn-cancel" onclick="closeForm()">
              <i class="fa fa-times"></i> Annuler
            </button>
            <button type="submit" class="btn-save">
              <i class="fa fa-check"></i>
              <?= isset($edit) ? 'Appliquer les modifications' : 'Générer le contrat' ?>
            </button>
          </div>
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
/* =============================================
   PRIX DU BIEN SÉLECTIONNÉ
   ============================================= */
function setPrixBien() {
    var select  = document.getElementById('bienSelect');
    var option  = select.options[select.selectedIndex];
    var prix    = option.getAttribute('data-prix');
    var montantInput = document.getElementById('montantInput');
    var loyerText = document.getElementById('loyerMontantText');
 
    if (prix && prix !== '') {
        montantInput.value = parseFloat(prix);
        loyerText.innerText = parseFloat(prix).toFixed(2) + ' $';
    } else {
        montantInput.value = 0;
        loyerText.innerText = '—';
    }
    calculerTotalForm();
}

function calculerTotalForm() {
    var loyer = parseFloat(document.getElementById('montantInput').value) || 0;
    var charges = parseFloat(document.getElementById('chargesInput').value) || 0;
    
    document.getElementById('chargesText').innerText = charges.toFixed(2) + ' $';
    
    if(loyer > 0) {
        var total = loyer + charges;
        document.getElementById('totalMontantText').innerText = total.toFixed(2) + ' $';
    } else {
        document.getElementById('totalMontantText').innerText = '—';
    }
}

/* =============================================
   BADGE RÉPARTITION
   ============================================= */
function badgeRepartition(valeur) {
    if (!valeur || valeur === '—') return '<span>—</span>';
    var cls = '';
    var icon = '';
    if (valeur === 'Locataire') { cls = 'badge-locataire'; icon = '👤'; }
    else if (valeur === 'Bailleur') { cls = 'badge-bailleur'; icon = '🏠'; }
    else if (valeur === 'Les deux') { cls = 'badge-lesdeux'; icon = '🤝'; }
    return '<span class="badge-repartition ' + cls + '">' + icon + ' ' + valeur + '</span>';
}

/* =============================================
   MODAL DÉTAILS
   ============================================= */
function openDetails(contrat) {
    var loyer   = parseFloat(contrat.montant || 0);
    var charges = parseFloat(contrat.charges || 0);
    var total   = loyer + charges;

    document.getElementById('detId').textContent        = '#CONTRAT-' + contrat.id_contrat;
    document.getElementById('detBien').textContent      = contrat.bien_nom || '—';
    document.getElementById('detLocataire').textContent = (contrat.loc_nom || '') + ' ' + (contrat.prenom || '');
    document.getElementById('detMontant').textContent   = loyer.toFixed(2) + ' $';
    document.getElementById('detCharges').textContent   = charges.toFixed(2) + ' $';
    document.getElementById('detTotal').textContent     = total.toFixed(2) + ' $';
    document.getElementById('detStatut').textContent    = contrat.statut;

    const dDebut = new Date(contrat.date_debut);
    const dFin   = new Date(contrat.date_fin);
    document.getElementById('detDebut').textContent = dDebut.toLocaleDateString('fr-FR');
    document.getElementById('detFin').textContent   = dFin.toLocaleDateString('fr-FR');

    // Répartition charges & impôts
    document.getElementById('detChargeEau').innerHTML  = badgeRepartition(contrat.charge_eau         || 'Locataire');
    document.getElementById('detChargeElec').innerHTML = badgeRepartition(contrat.charge_electricite  || 'Locataire');
    document.getElementById('detImpot').innerHTML      = badgeRepartition(contrat.impot_locataire     || 'Locataire');

    document.getElementById('btnActionDelete').href = 'contrat.php?delete=' + contrat.id_contrat;
    document.getElementById('btnActionEdit').onclick = function() {
        window.location.href = 'contrat.php?edit=' + contrat.id_contrat;
    };

    document.getElementById('detailsOverlay').classList.add('show');
}

function closeDetails() {
    document.getElementById('detailsOverlay').classList.remove('show');
}
 
/* =============================================
   MODAL FORMULAIRE
   ============================================= */
function openForm() {
    document.getElementById('id_contrat').value           = '';
    document.getElementById('bienSelect').selectedIndex   = 0;
    document.getElementById('locataireSelect').selectedIndex = 0;
    document.getElementById('montantInput').value         = 0;
    document.getElementById('chargesInput').value         = '0.00';
    document.getElementById('loyerMontantText').innerText = '—';
    document.getElementById('chargesText').innerText      = '0.00 $';
    document.getElementById('totalMontantText').innerText = '—';
    document.getElementById('date_debut').value           = '';
    document.getElementById('date_fin').value             = '';
    document.getElementById('statutSelect').selectedIndex = 0;
    // Reset radios répartition → Locataire par défaut
    ['charge_eau', 'charge_electricite', 'impot_locataire'].forEach(function(name) {
        var el = document.querySelector('input[name="' + name + '"][value="Locataire"]');
        if (el) el.checked = true;
    });
    document.getElementById('formTitle').innerHTML = '<i class="fa fa-plus"></i> Créer un nouveau bail';
    document.getElementById('formOverlay').classList.add('show');
}
 
function closeForm() {
    <?php if(isset($edit) || !empty($erreur)): ?>
        window.location.href = 'contrat.php';
    <?php else: ?>
        document.getElementById('formOverlay').classList.remove('show');
    <?php endif; ?>
}
 
document.getElementById('formOverlay').addEventListener('click', function(e) { if (e.target === this) closeForm(); });
document.getElementById('detailsOverlay').addEventListener('click', function(e) { if (e.target === this) closeDetails(); });
 
document.getElementById('menuToggle').addEventListener('click', function() {
    document.getElementById('appSidebar').classList.toggle('show');
});

/* =============================================
   FILTRE RECHERCHE
   ============================================= */
function filtrerContrats() {
    const query = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('.contrat-card');
    cards.forEach(card => {
        const searchTarget = card.getAttribute('data-search');
        card.style.display = searchTarget.includes(query) ? '' : 'none';
    });
}

// Lancement automatique du calcul si on est en mode édition au chargement de la page
<?php if(isset($edit)): ?>
    window.addEventListener('DOMContentLoaded', () => {
        var select = document.getElementById('bienSelect');
        if(select.selectedIndex > 0) {
            var prix = select.options[select.selectedIndex].getAttribute('data-prix');
            document.getElementById('loyerMontantText').innerText = parseFloat(prix).toFixed(2) + ' $';
            calculerTotalForm();
        }
    });
<?php endif; ?>
</script>
</body>
</html>