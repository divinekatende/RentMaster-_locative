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
        'id_bailleur'  => $id_bailleur,
        'id_bien'      => $_POST['bien']       ?? null,
        'id_locataire' => $_POST['locataire']  ?? null,
        'date_debut'   => $_POST['date_debut'] ?? null,
        'date_fin'     => $_POST['date_fin']   ?? null,
        'statut'       => $_POST['statut']     ?? 'Actif',
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
 
$stmt = $conn->prepare("SELECT * FROM locataires WHERE id_bailleur=?");
$stmt->execute([$id_bailleur]);
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
         
        /* ZONE COMMANDE SUPÉRIEURE */
        .actions-top { margin-bottom: 24px; }
        .btn-primary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 24px; border-radius: var(--radius-md);
            background: var(--primary); color: white; border: none;
            font-weight: 600; font-size: 14px; cursor: pointer;
            transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(30, 64, 175, 0.15);
        }
        .btn-primary:hover { background: var(--primary-light); transform: translateY(-1px); }
         
        /* ERREURS ET ALERTES */
        .alert-error {
            background: #fef2f2; border: 1px solid #fca5a5;
            border-radius: var(--radius-md); padding: 16px 20px;
            margin-bottom: 24px; color: #991b1b; font-size: 14px;
            font-weight: 500; display: flex; align-items: center; gap: 12px;
        }
         
        /* TABLEAU DU CONTENU */
        .table-container {
            background: var(--surface); border-radius: var(--radius-lg);
            border: 1px solid var(--border-color); box-shadow: var(--shadow-md);
            overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th {
            background: #f8fafc; color: var(--text-muted);
            font-weight: 600; font-size: 13px; text-transform: uppercase;
            letter-spacing: 0.03em; padding: 16px 20px; border-bottom: 1px solid var(--border-color);
        }
        td { padding: 16px 20px; border-bottom: 1px solid var(--border-color); font-size: 14px; color: var(--text-main); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #fafcff; }
         
        /* BADGES REVISITÉS */
        .badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600;
        }
        .badge::before {
            content: ''; display: inline-block; width: 6px; height: 6px; border-radius: 50%;
        }
        .badge-actif { background: var(--success-bg); color: var(--success-text); }
        .badge-actif::before { background: var(--success-dot); }
        .badge-termine { background: #f1f5f9; color: #475569; }
        .badge-termine::before { background: #94a3b8; }
         
        /* ENSEMBLE BOUTONS D'ACTION */
        .actions-cell { display: flex; gap: 8px; }
        .action-btn {
            width: 32px; height: 32px; border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            border: 1px solid var(--border-color); background: var(--surface);
            color: var(--text-muted); cursor: pointer; transition: all 0.2s;
            text-decoration: none;
        }
        .action-btn.edit:hover { border-color: var(--primary-light); color: var(--primary-light); background: var(--primary-soft); }
        .action-btn.delete:hover { border-color: #fca5a5; color: #ef4444; background: #fef2f2; }
         
        /* EMPTY STATE STATE */
        .empty-state { text-align: center; padding: 48px 24px; color: var(--text-muted); }
        .empty-state i { font-size: 48px; margin-bottom: 16px; color: #cbd5e1; display: block; }
        .empty-state p { font-size: 15px; font-weight: 500; }
         
        /* OVERLAY MODAL FLOU */
        .form-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px);
            z-index: 3000; align-items: center; justify-content: center; padding: 20px;
        }
        .form-overlay.show { display: flex; }
         
        /* STRUCTURE DU FORMULAIRE FENETRE */
        .form-modal {
            background: var(--surface); border-radius: var(--radius-lg);
            width: 100%; max-width: 540px; max-height: 90vh; overflow-y: auto;
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
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

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
         
        /* AFFICHAGE ENCADRÉ LOYER DU BIEN */
        .loyer-box {
            background: var(--primary-soft); border: 1px dashed var(--primary-light);
            border-radius: var(--radius-sm); padding: 14px 18px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .loyer-box .loyer-label { font-size: 13px; color: var(--text-muted); font-weight: 500; }
        .loyer-box .loyer-montant { font-size: 18px; font-weight: 700; color: var(--primary); }
         
        .form-divider { border: none; border-top: 1px solid var(--border-color); margin: 8px 0; }
         
        /* ZONE DE BOUTONS INFERIEURE */
        .btn-group { display: flex; justify-content: flex-end; gap: 12px; margin-top: 8px; border-top: 1px solid var(--border-color); padding-top: 20px; }
        .btn-group button { padding: 11px 20px; border-radius: var(--radius-sm); font-weight: 600; font-size: 14px; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-save { background: var(--primary); color: white; }
        .btn-save:hover { background: var(--primary-light); }
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
            .table-container { display: block; overflow-x: auto; white-space: nowrap; }
            .form-row { grid-template-columns: 1fr; gap: 16px; }
            .btn-group { flex-direction: column-reverse; }
            .btn-group button { justify-content: center; width: 100%; }
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
    <p>Créez et gérez les baux d'habitation de votre parc immobilier. Un logement ne peut contenir qu'un seul contrat actif.</p>
  </div>
 
  <?php if (!empty($erreur)): ?>
  <div class="alert-error">
    <i class="fa-solid fa-circle-exclamation"></i>
    <?= htmlspecialchars($erreur) ?>
  </div>
  <?php endif; ?>
 
  <div class="actions-top">
    <button class="btn-primary" onclick="openForm()">
      <i class="fa fa-plus"></i> Générer un contrat
    </button>
  </div>
 
  <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Bien immobilier</th>
            <th>Locataire titulaire</th>
            <th>Loyer net</th>
            <th>Date d'effet</th>
            <th>Date d'échéance</th>
            <th>Statut bail</th>
            <th style="text-align: center;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($contrats)): ?>
          <tr><td colspan="8">
            <div class="empty-state">
              <i class="fa-regular fa-folder-open"></i>
              <p>Aucun contrat actif ou archivé n'est répertorié dans votre espace.</p>
            </div>
          </td></tr>
          <?php else: ?>
          <?php foreach($contrats as $c): ?>
          <tr>
            <td><span style="font-weight: 600; color: var(--text-muted);">#<?= $c['id_contrat'] ?></span></td>
            <td style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($c['bien_nom'] ?? '—') ?></td>
            <td><?= htmlspecialchars(trim(($c['loc_nom'] ?? '').' '.($c['prenom'] ?? ''))) ?></td>
            <td><span style="font-weight: 700; color: var(--text-main);"><?= number_format($c['montant'] ?? 0, 2) ?> $</span></td>
            <td><?= date('d/m/Y', strtotime($c['date_debut'])) ?></td>
            <td><?= date('d/m/Y', strtotime($c['date_fin'])) ?></td>
            <td>
              <?php $cls = ($c['statut'] === 'Actif') ? 'badge-actif' : 'badge-termine'; ?>
              <span class="badge <?= $cls ?>"><?= htmlspecialchars($c['statut']) ?></span>
            </td>
            <td>
              <div class="actions-cell">
                  <a href="contrat.php?edit=<?= $c['id_contrat'] ?>" class="action-btn edit" title="Modifier le bail">
                    <i class="fa fa-edit"></i>
                  </a>
                  <a href="contrat.php?delete=<?= $c['id_contrat'] ?>" class="action-btn delete" title="Supprimer définitivement" onclick="return confirm('Voulez-vous révoquer ce contrat ? Le logement redeviendra instantanément Disponible.')">
                    <i class="fa fa-trash"></i>
                  </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
  </div>
</main>
</div>
 
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
     
          <div class="form-group">
              <label> Logement / Bien immobilier *</label>
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
     
          <div class="form-group">
              <label>👤 Titulaire du bail (Locataire) *</label>
              <select name="locataire" required>
                <option value="">-- Associer un locataire existant --</option>
                <?php foreach($locataires as $l): ?>
                  <option value="<?= $l['id_locataire'] ?>"
                    <?= isset($edit) && $edit['id_locataire'] == $l['id_locataire'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($l['nom'].' '.$l['prenom']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
          </div>
     
          <hr class="form-divider">
     
          <div class="form-group">
              <label> Note financière (Rappel)</label>
              <div class="loyer-box">
                <span class="loyer-label" id="loyerLabel">
                  <?= isset($edit) ? 'Loyer de base défini' : 'Aucun bien sélectionné' ?>
                </span>
                <span class="loyer-montant" id="loyerMontant">
                  <?= isset($edit) ? number_format($edit['montant'], 2).' $' : '—' ?>
                </span>
              </div>
          </div>
     
          <div class="form-row">
              <div class="form-group">
                  <label> Date d'effet (Début) *</label>
                  <input type="date" name="date_debut" id="date_debut" value="<?= $edit['date_debut'] ?? '' ?>" required>
              </div>
              <div class="form-group">
                  <label>Date d'échéance (Fin) *</label>
                  <input type="date" name="date_fin" id="date_fin" value="<?= $edit['date_fin'] ?? '' ?>" required>
              </div>
          </div>
     
          <div class="form-group">
              <label> État réglementaire</label>
              <select name="statut">
                <option value="Actif"   <?= isset($edit) && $edit['statut']=='Actif'   ? 'selected' : '' ?>>Actif (En cours d'occupation)</option>
                <option value="Terminé" <?= isset($edit) && $edit['statut']=='Terminé' ? 'selected' : '' ?>>Terminé (Clôturé / Sorti)</option>
              </select>
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
function setPrixBien() {
    var select  = document.getElementById('bienSelect');
    var option  = select.options[select.selectedIndex];
    var prix    = option.getAttribute('data-prix');
    var label   = document.getElementById('loyerLabel');
    var montant = document.getElementById('loyerMontant');
 
    if (prix && prix !== '') {
        label.innerText   = 'Loyer mensuel brut';
        montant.innerText = parseFloat(prix).toFixed(2) + ' $';
    } else {
        label.innerText   = 'Aucun bien sélectionné';
        montant.innerText = '—';
    }
}
 
function openForm() {
    document.getElementById('id_contrat').value = '';
    document.getElementById('bienSelect').selectedIndex = 0;
    document.querySelector('select[name="locataire"]').selectedIndex = 0;
    document.getElementById('loyerLabel').innerText   = 'Aucun bien sélectionné';
    document.getElementById('loyerMontant').innerText = '—';
    document.getElementById('date_debut').value = '';
    document.getElementById('date_fin').value   = '';
    document.querySelector('select[name="statut"]').selectedIndex = 0;
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
 
document.getElementById('formOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeForm();
});
 
document.getElementById('menuToggle').addEventListener('click', function() {
    document.getElementById('appSidebar').classList.toggle('show');
});
</script>
</body>
</html>