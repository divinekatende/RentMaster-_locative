<?php
session_start();
require_once('../../config/database.php');
 
$conn = (new Database())->connect();
 
/* =========================
   SECURITE SESSION
========================= */
$id_bailleur = $_SESSION['id'] ?? null;
if (!$id_bailleur) die("Accès refusé : session invalide");
 
/* =========================
   SUPPRESSION
========================= */
if (isset($_GET['delete'])) {
    $id_bien = (int) $_GET['delete'];
    $conn->prepare("DELETE FROM biens WHERE id_bien=:id_bien AND id_bailleur=:id_bailleur")
         ->execute(['id_bien' => $id_bien, 'id_bailleur' => $id_bailleur]);
    header("Location: bien.php"); exit;
}
 
/* =========================
   EDIT MODE
========================= */
$editMode = false;
$bienEdit = null;
 
if (isset($_GET['edit'])) {
    $editMode = true;
    $id_bien  = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM biens WHERE id_bien=:id_bien AND id_bailleur=:id_bailleur");
    $stmt->execute(['id_bien' => $id_bien, 'id_bailleur' => $id_bailleur]);
    $bienEdit = $stmt->fetch(PDO::FETCH_ASSOC);
}
 
/* =========================
   INSERT / UPDATE
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
 
    $image = null;
    if (!empty($_FILES["image"]["name"])) {
        $image = time() . "_" . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], "../../public/uploads/" . $image);
    }
 
    if (!empty($_POST['id_bien'])) {
        /* UPDATE */
        $sql = "UPDATE biens SET
                    titre=:titre, adresse=:adresse, type_bien=:type_bien,
                    surface=:surface, nombre_pieces=:nombre_pieces,
                    prix=:prix, statut=:statut, description=:description"
             . ($image ? ", image=:image" : "") . "
                WHERE id_bien=:id_bien AND id_bailleur=:id_bailleur";
 
        $params = [
            'titre'        => $_POST['titre'],
            'adresse'      => $_POST['adresse'],
            'type_bien'    => $_POST['type_bien'],
            'surface'      => $_POST['surface'],
            'nombre_pieces'=> $_POST['nombre_pieces'],
            'prix'         => $_POST['prix'],
            'statut'       => $_POST['statut'],
            'description'  => $_POST['description'],
            'id_bien'      => $_POST['id_bien'],
            'id_bailleur'  => $id_bailleur
        ];
        if ($image) $params['image'] = $image;
        $conn->prepare($sql)->execute($params);
 
    } else {
     /* INSERT */
/* INSERT */
$sql = "INSERT INTO biens (
            id_bailleur,
            image,
            titre,
            adresse,
            type_bien,
            surface,
            nombre_pieces,
            prix,
            statut,
            description
        ) VALUES (
            :id_bailleur,
            :image,
            :titre,
            :adresse,
            :type_bien,
            :surface,
            :nombre_pieces,
            :prix,
            :statut,
            :description
        )";

$stmt = $conn->prepare($sql);

$stmt->execute([
    ':id_bailleur'   => $id_bailleur,
    ':image'         => $image ?? '',
    ':titre'         => $_POST['titre'] ?? '',
    ':adresse'       => $_POST['adresse'] ?? '',
    ':type_bien'     => $_POST['type_bien'] ?? '',
    ':surface'       => $_POST['surface'] ?? null,
    ':nombre_pieces' => $_POST['nombre_pieces'] ?? null,
    ':prix'          => $_POST['prix'] ?? 0,
    ':statut'        => $_POST['statut'] ?? 'Disponible',
    ':description'   => $_POST['description'] ?? ''
]);
    }
 
    header("Location: bien.php"); exit;
}
 
/* =========================
   LISTE BIENS
========================= */
$stmt = $conn->prepare("SELECT * FROM biens WHERE id_bailleur=:id_bailleur ORDER BY id_bien DESC");
$stmt->execute(['id_bailleur' => $id_bailleur]);
$biens = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Biens - RentMaster</title>
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
            --danger-bg: #fef2f2;
            --danger-text: #991b1b;
            --danger-dot: #ef4444;
            --warning-bg: #fffbeb;
            --warning-text: #92400e;
            --warning-dot: #f59e0b;
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
        .identite { width: 44px; height: 44px; border-radius: 10px; object-fit: cover; border: 2px solid #e0e7ff; }
         
        /* BADGES REVISITÉS */
        .badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600;
        }
        .badge::before { content: ''; display: inline-block; width: 6px; height: 6px; border-radius: 50%; }
        
        .badge-disponible { background: var(--success-bg); color: var(--success-text); }
        .badge-disponible::before { background: var(--success-dot); }
        
        .badge-loue { background: var(--danger-bg); color: var(--danger-text); }
        .badge-loue::before { background: var(--danger-dot); }
        
        .badge-maintenance { background: var(--warning-bg); color: var(--warning-text); }
        .badge-maintenance::before { background: var(--warning-dot); }
         
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
         
        /* EMPTY STATE */
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
            width: 100%; max-width: 560px; max-height: 90vh; overflow-y: auto;
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
        .form-modal input, .form-modal select, .form-modal textarea {
            padding: 10px 14px; border-radius: var(--radius-sm);
            border: 1px solid var(--border-color); outline: none;
            font-size: 14px; background: #f8fafc; transition: all 0.2s;
        }
        .form-modal input:focus, .form-modal select:focus, .form-modal textarea:focus {
            border-color: var(--primary-light); background: var(--surface);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-modal textarea { resize: vertical; min-height: 80px; }
         
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
            .actions-top { flex-direction: column; align-items: stretch; }
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
    <a href="bien.php" class="active"><i class="fa fa-building"></i> Biens</a>
    <a href="locataire.php"><i class="fa fa-users"></i> Locataires</a>
    <a href="contrat.php"><i class="fa fa-file-contract"></i> Contrats</a>
    <a href="paiement.php"><i class="fa fa-credit-card"></i> Paiements</a>
  </nav>
</aside>
 
<main class="main-content">
  <div class="page-header">
    <h1><i class="fa fa-building"></i> Gestion des Biens</h1>
    <p>Ajoutez, modifiez ou supprimez vos biens immobiliers. Le statut se met à jour automatiquement selon les contrats.</p>
  </div>
 
  <div class="actions-top">
    <input type="text" id="searchInput" placeholder="Rechercher par nom, adresse, statut..." onkeyup="filtrerBiens()">
    <button class="btn-primary" onclick="openForm()">
      <i class="fa fa-plus"></i> Ajouter un bien
    </button>
  </div>
 
  <div class="table-container">
      <table id="tableBiens">
        <thead>
          <tr>
            <th>ID</th>
            <th>Image</th>
            <th>Titre</th>
            <th>Adresse</th>
            <th>Type</th>
            <th>Surface</th>
            <th>Pièces</th>
            <th>Loyer</th>
            <th>Statut</th>
            <th style="text-align: center;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($biens)): ?>
          <tr><td colspan="10">
            <div class="empty-state">
              <i class="fa-regular fa-folder-open"></i>
              <p>Aucun bien enregistré dans votre espace. Ajoutez votre premier bien !</p>
            </div>
          </td></tr>
          <?php else: ?>
          <?php foreach($biens as $b): ?>
          <tr>
            <td><span style="font-weight: 600; color: var(--text-muted);">#<?= $b['id_bien'] ?></span></td>
            <td>
              <?php if(!empty($b['image'])): ?>
                <img src="../../public/uploads/<?= htmlspecialchars($b['image']) ?>" class="identite" alt="<?= htmlspecialchars($b['titre']) ?>">
              <?php else: ?>
                <div style="width:44px;height:44px;border-radius:10px;background:var(--primary-soft);display:flex;align-items:center;justify-content:center;color:var(--primary);">
                  <i class="fa fa-building"></i>
                </div>
              <?php endif; ?>
            </td>
            <td><strong style="color: var(--text-main);"><?= htmlspecialchars($b['titre'] ?? '') ?></strong></td>
            <td><?= htmlspecialchars($b['adresse'] ?? '') ?></td>
            <td><?= htmlspecialchars($b['type_bien'] ?? '') ?></td>
            <td><?= $b['surface'] ?? '—' ?> m²</td>
            <td><?= $b['nombre_pieces'] ?? '—' ?></td>
            <td><strong style="color: var(--text-main);"><?= number_format($b['prix'] ?? 0, 2) ?> $</strong></td>
            <td>
              <?php
                $s = $b['statut'] ?? '';
                $cls = match($s) {
                  'Loué'        => 'badge-loue',
                  'Maintenance' => 'badge-maintenance',
                  default       => 'badge-disponible'
                };
              ?>
              <span class="badge <?= $cls ?>"><?= htmlspecialchars($s) ?></span>
            </td>
            <td>
              <div class="actions-cell">
                  <a href="bien.php?edit=<?= $b['id_bien'] ?>" class="action-btn edit" title="Modifier">
                    <i class="fa fa-edit"></i>
                  </a>
                  <a href="bien.php?delete=<?= $b['id_bien'] ?>" class="action-btn delete" title="Supprimer" onclick="return confirm('Supprimer ce bien définitivement ?')">
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
 
<div id="formOverlay" class="form-overlay <?= $editMode ? 'show' : '' ?>">
  <div class="form-modal">
    <div class="modal-header">
        <h2 id="formTitle">
          <?= $editMode ? '<i class="fa fa-edit"></i> Modifier les spécifications du bien' : '<i class="fa fa-plus"></i> Enregistrer un nouveau bien' ?>
        </h2>
    </div>
 
    <form method="POST" enctype="multipart/form-data">
      <div class="modal-body">
          <?php if($editMode && $bienEdit): ?>
            <input type="hidden" name="id_bien" value="<?= $bienEdit['id_bien'] ?>">
          <?php endif; ?>
     
          <div class="form-group">
              <label>Image de présentation</label>
              <input type="file" name="image" accept="image/*">
          </div>
     
          <div class="form-group">
              <label>Titre descriptif *</label>
              <input type="text" name="titre" value="<?= htmlspecialchars($bienEdit['titre'] ?? '') ?>" required placeholder="Ex: Appartement T3 Centre-ville">
          </div>
     
          <div class="form-group">
              <label>Adresse physique *</label>
              <input type="text" name="adresse" value="<?= htmlspecialchars($bienEdit['adresse'] ?? '') ?>" required placeholder="Ex: 12 rue de la Paix, Kinshasa">
          </div>
     
          <div class="form-row">
              <div class="form-group">
                  <label>Type de bien</label>
                  <select name="type_bien">
                    <?php foreach(['Appartement','Maison','Studio','Bureau'] as $t): ?>
                      <option <?= ($bienEdit['type_bien'] ?? '') == $t ? 'selected' : '' ?>><?= $t ?></option>
                    <?php endforeach; ?>
                  </select>
              </div>
              <div class="form-group">
                  <label>Loyer mensuel ($) *</label>
                  <input type="number" name="prix" value="<?= $bienEdit['prix'] ?? '' ?>" required placeholder="Ex: 800" min="0" step="0.01">
              </div>
          </div>
     
          <div class="form-row">
              <div class="form-group">
                  <label>Surface habitable (m²)</label>
                  <input type="number" name="surface" value="<?= $bienEdit['surface'] ?? '' ?>" placeholder="Ex: 65" min="1">
              </div>
              <div class="form-group">
                  <label>Nombre de pièces</label>
                  <input type="number" name="nombre_pieces" value="<?= $bienEdit['nombre_pieces'] ?? '' ?>" placeholder="Ex: 3" min="1">
              </div>
          </div>
     
          <div class="form-group">
              <label>Statut initial</label>
              <select name="statut">
                <?php foreach(['Disponible','Loué','Maintenance'] as $s): ?>
                  <option <?= ($bienEdit['statut'] ?? 'Disponible') == $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
          </div>
     
          <div class="form-group">
              <label>Description complémentaire</label>
              <textarea name="description" placeholder="Équipements, commodités ou spécificités du logement..."><?= htmlspecialchars($bienEdit['description'] ?? '') ?></textarea>
          </div>
     
          <div class="btn-group">
            <button type="button" class="btn-cancel" onclick="closeForm()">
              <i class="fa fa-times"></i> Annuler
            </button>
            <button type="submit" class="btn-save">
              <i class="fa fa-check"></i> <?= $editMode ? 'Appliquer les modifications' : 'Enregistrer le bien' ?>
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
function openForm() {
    document.getElementById('formOverlay').classList.add('show');
}
function closeForm() {
    <?php if($editMode): ?>
        window.location.href = 'bien.php';
    <?php else: ?>
        document.getElementById('formOverlay').classList.remove('show');
    <?php endif; ?>
}
 
document.getElementById('formOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeForm();
});
 
document.getElementById("menuToggle").addEventListener("click", function() {
    document.getElementById("appSidebar").classList.toggle("show");
});
 
function filtrerBiens() {
    var val = document.getElementById('searchInput').value.toLowerCase();
    var rows = document.querySelectorAll('#tableBiens tbody tr');
    rows.forEach(function(row) {
        if(row.cells.length > 1) {
            row.style.display = row.innerText.toLowerCase().includes(val) ? '' : 'none';
        }
    });
}
</script>
</body>
</html>