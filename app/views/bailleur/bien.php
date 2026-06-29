<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

$conn = (new Database())->connect();

/* =========================
   SECURITE SESSION
========================= */
$id_bailleur = $_SESSION['id'] ?? null;
if (!$id_bailleur) {
    header('Location: ../../auth/login.php');
    exit;
}

/* =========================
   SUPPRESSION
========================= */
if (isset($_GET['delete'])) {
    $id_bien = (int) $_GET['delete'];

    // Récupérer l'image pour la supprimer du disque
    $stmt = $conn->prepare("SELECT image FROM biens WHERE id_bien=:id AND id_bailleur=:b");
    $stmt->execute(['id' => $id_bien, 'b' => $id_bailleur]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['image'])) {
        $fichier = __DIR__ . '/../../../public/uploads/' . $row['image'];
        if (file_exists($fichier)) @unlink($fichier);
    }

    $conn->prepare("DELETE FROM biens WHERE id_bien=:id AND id_bailleur=:b")
         ->execute(['id' => $id_bien, 'b' => $id_bailleur]);

    header("Location: bien.php");
    exit;
}

/* =========================
   EDIT MODE
========================= */
$editMode = false;
$bienEdit = null;

if (isset($_GET['edit'])) {
    $editMode = true;
    $id_bien  = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM biens WHERE id_bien=:id AND id_bailleur=:b");
    $stmt->execute(['id' => $id_bien, 'b' => $id_bailleur]);
    $bienEdit = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* =========================
   INSERT / UPDATE
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $image     = null;
    $uploadDir = __DIR__ . '/../../../public/uploads/';

    // Créer le dossier si nécessaire
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!empty($_FILES["image"]["name"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
        $ext           = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $extsAutorisees = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $extsAutorisees)) {
            $image = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES["image"]["tmp_name"], $uploadDir . $image);
        }
    }

    if (!empty($_POST['id_bien'])) {
        /* ── UPDATE ── */
        $sql = "UPDATE biens SET
                    titre=:titre, adresse=:adresse, type_bien=:type_bien,
                    surface=:surface, nombre_pieces=:nombre_pieces,
                    prix=:prix, statut=:statut, description=:description"
             . ($image ? ", image=:image" : "") .
               " WHERE id_bien=:id_bien AND id_bailleur=:id_bailleur";

        $params = [
            'titre'         => $_POST['titre'],
            'adresse'       => $_POST['adresse'],
            'type_bien'     => $_POST['type_bien'],
            'surface'       => $_POST['surface'],
            'nombre_pieces' => $_POST['nombre_pieces'],
            'prix'          => $_POST['prix'],
            'statut'        => $_POST['statut'],
            'description'   => $_POST['description'],
            'id_bien'       => $_POST['id_bien'],
            'id_bailleur'   => $id_bailleur,
        ];
        if ($image) $params['image'] = $image;
        $conn->prepare($sql)->execute($params);

    } else {
        /* ── INSERT ── */
        $sql = "INSERT INTO biens
                    (id_bailleur, image, titre, adresse, type_bien, surface, nombre_pieces, prix, statut, description)
                VALUES
                    (:id_bailleur, :image, :titre, :adresse, :type_bien, :surface, :nombre_pieces, :prix, :statut, :description)";

        $conn->prepare($sql)->execute([
            'id_bailleur'   => $id_bailleur,
            'image'         => $image ?? '',
            'titre'         => $_POST['titre']         ?? '',
            'adresse'       => $_POST['adresse']        ?? '',
            'type_bien'     => $_POST['type_bien']      ?? '',
            'surface'       => $_POST['surface']        ?? null,
            'nombre_pieces' => $_POST['nombre_pieces']  ?? null,
            'prix'          => $_POST['prix']           ?? 0,
            'statut'        => $_POST['statut']         ?? 'Disponible',
            'description'   => $_POST['description']    ?? '',
        ]);
    }

    header("Location: bien.php");
    exit;
}

/* =========================
   LISTE BIENS
========================= */
$stmt = $conn->prepare("SELECT * FROM biens WHERE id_bailleur=:b ORDER BY id_bien DESC");
$stmt->execute(['b' => $id_bailleur]);
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
            --shadow-sm: 0 4px 6px -1px rgba(15,23,42,0.05);
            --shadow-md: 0 10px 15px -3px rgba(15,23,42,0.08);
        }

        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; -webkit-font-smoothing:antialiased; }
        body { background:var(--bg-app); color:var(--text-main); min-height:100vh; }
        .container { display:flex; }

        /* ── SIDEBAR ── */
        .sidebar {
            width:260px; background:var(--primary); color:#fff;
            height:100vh; position:fixed;
            display:flex; flex-direction:column;
            padding:28px 16px; z-index:100;
        }
        .sidebar .brand { display:flex; align-items:center; gap:12px; margin-bottom:36px; }
        .sidebar .brand i { background:rgba(255,255,255,0.15); padding:10px; border-radius:10px; font-size:18px; }
        .sidebar .brand span { font-size:20px; font-weight:700; }
        .sidebar nav { display:flex; flex-direction:column; gap:4px; }
        .sidebar nav a {
            display:flex; align-items:center; gap:12px;
            padding:12px 16px; border-radius:var(--radius-sm);
            color:rgba(255,255,255,0.75); text-decoration:none;
            font-size:14px; font-weight:500; transition:.2s;
        }
        .sidebar nav a:hover, .sidebar nav a.active {
            background:rgba(255,255,255,0.15); color:#fff; font-weight:600;
        }
        .sidebar nav a.logout { margin-top:40px; color:#fca5a5; }
        .sidebar nav a.logout:hover { background:rgba(239,68,68,0.15); }

        /* ── MAIN ── */
        .main-content { margin-left:260px; padding:40px; flex:1; max-width:calc(100% - 260px); }
        .page-header { margin-bottom:32px; }
        .page-header h1 { font-size:26px; font-weight:700; letter-spacing:-0.02em; margin-bottom:6px; }
        .page-header p { color:var(--text-muted); font-size:14px; }

        /* ── ACTIONS TOP ── */
        .actions-top { display:flex; gap:12px; margin-bottom:28px; align-items:center; }
        .actions-top input[type="text"] {
            flex:1; padding:12px 18px; border-radius:30px;
            border:1px solid var(--border-color); outline:none; font-size:14px;
            background:var(--surface); transition:.2s;
        }
        .actions-top input:focus { border-color:var(--primary-light); box-shadow:0 0 0 3px rgba(59,130,246,0.1); }

        .btn-primary {
            display:inline-flex; align-items:center; gap:8px;
            padding:12px 22px; border-radius:var(--radius-sm);
            background:var(--primary); color:#fff; border:none;
            font-weight:600; font-size:14px; cursor:pointer; white-space:nowrap;
            box-shadow:0 4px 12px rgba(30,64,175,0.2); transition:.2s;
        }
        .btn-primary:hover { background:var(--primary-light); transform:translateY(-1px); }

        /* ── GRILLE ── */
        .biens-grid {
            display:grid;
            grid-template-columns:repeat(auto-fill, minmax(300px, 1fr));
            gap:24px;
        }

        .bien-card {
            background:var(--surface); border-radius:var(--radius-lg);
            border:1px solid var(--border-color); box-shadow:var(--shadow-sm);
            overflow:hidden; display:flex; flex-direction:column;
            transition:transform .2s, box-shadow .2s;
        }
        .bien-card:hover { transform:translateY(-4px); box-shadow:var(--shadow-md); }

        /* ── IMAGE ── */
        .bien-image-wrapper {
            position:relative; height:200px; overflow:hidden;
            background:linear-gradient(135deg,#e0e7ff,#c7d2fe);
        }
        .bien-image-wrapper img {
            width:100%; height:100%; object-fit:cover;
            transition:transform .3s ease;
        }
        .bien-card:hover .bien-image-wrapper img { transform:scale(1.04); }
        .bien-image-placeholder {
            width:100%; height:100%;
            display:flex; flex-direction:column; align-items:center; justify-content:center;
            color:var(--primary); gap:8px;
        }
        .bien-image-placeholder i { font-size:36px; opacity:.5; }
        .bien-image-placeholder span { font-size:12px; font-weight:500; opacity:.6; }

        /* ── BADGE STATUT ── */
        .statut-badge {
            position:absolute; top:12px; right:12px;
            padding:4px 12px; border-radius:20px; font-size:11px; font-weight:600;
            display:inline-flex; align-items:center; gap:5px;
            backdrop-filter:blur(4px);
        }
        .statut-badge::before { content:''; width:6px; height:6px; border-radius:50%; display:inline-block; }
        .badge-disponible { background:rgba(240,253,244,0.95); color:#166534; }
        .badge-disponible::before { background:#22c55e; }
        .badge-loue { background:rgba(254,242,242,0.95); color:#991b1b; }
        .badge-loue::before { background:#ef4444; }
        .badge-maintenance { background:rgba(255,251,235,0.95); color:#92400e; }
        .badge-maintenance::before { background:#f59e0b; }

        /* ── INFOS CARTE ── */
        .bien-info { padding:20px; flex:1; display:flex; flex-direction:column; }
        .bien-info h3 { font-size:17px; font-weight:700; margin-bottom:6px; line-height:1.3; }
        .bien-adresse { font-size:13px; color:var(--text-muted); display:flex; align-items:center; gap:6px; margin-bottom:12px; }
        .bien-prix { font-size:22px; font-weight:700; color:var(--primary); margin-bottom:14px; }
        .bien-prix span { font-size:13px; font-weight:500; color:var(--text-muted); }

        .bien-details-mini {
            display:flex; gap:16px; border-top:1px solid var(--border-color);
            padding-top:12px; margin-top:auto;
        }
        .bien-details-mini span { font-size:12px; color:var(--text-muted); display:flex; align-items:center; gap:5px; }

        /* ── ACTIONS CARTE ── */
        .bien-actions {
            display:grid; grid-template-columns:2fr 1fr 1fr; gap:8px;
            padding:14px 18px; background:#f8fafc; border-top:1px solid var(--border-color);
        }
        .btn-action {
            display:inline-flex; align-items:center; justify-content:center; gap:6px;
            padding:9px 12px; font-size:13px; font-weight:600; border-radius:var(--radius-sm);
            cursor:pointer; text-decoration:none; transition:.2s; border:1px solid transparent;
        }
        .btn-action.view { background:var(--primary-soft); color:var(--primary); }
        .btn-action.view:hover { background:var(--primary); color:#fff; }
        .btn-action.edit { background:#fff; border-color:var(--border-color); color:var(--text-muted); }
        .btn-action.edit:hover { border-color:var(--primary-light); color:var(--primary-light); background:var(--primary-soft); }
        .btn-action.delete { background:#fff; border-color:var(--border-color); color:#ef4444; }
        .btn-action.delete:hover { background:#fef2f2; border-color:#fca5a5; }

        /* ── EMPTY STATE ── */
        .empty-state { text-align:center; padding:60px 20px; color:var(--text-muted); grid-column:1/-1; }
        .empty-state i { font-size:52px; color:#cbd5e1; display:block; margin-bottom:16px; }

        /* ── OVERLAY / MODAL ── */
        .overlay {
            display:none; position:fixed; inset:0;
            background:rgba(15,23,42,0.45); backdrop-filter:blur(4px);
            z-index:2000; align-items:center; justify-content:center; padding:20px;
        }
        .overlay.show { display:flex; }

        .form-modal {
            background:var(--surface); border-radius:var(--radius-lg);
            width:100%; max-width:560px; max-height:90vh; overflow-y:auto;
            box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);
            animation:popIn .22s ease;
        }
        @keyframes popIn {
            from { transform:scale(.96); opacity:0; }
            to   { transform:scale(1);   opacity:1; }
        }
        .modal-header {
            padding:24px 28px; border-bottom:1px solid var(--border-color);
            display:flex; justify-content:space-between; align-items:center;
        }
        .modal-header h2 { font-size:17px; font-weight:700; }
        .modal-header .close-btn { background:none; border:none; font-size:18px; color:var(--text-muted); cursor:pointer; }

        .modal-body { padding:26px 28px; display:flex; flex-direction:column; gap:16px; }
        .form-group { display:flex; flex-direction:column; gap:6px; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .form-group label { font-size:13px; font-weight:600; }
        .form-group input, .form-group select, .form-group textarea {
            padding:10px 14px; border-radius:var(--radius-sm);
            border:1px solid var(--border-color); outline:none;
            font-size:14px; background:#f8fafc; transition:.2s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color:var(--primary-light); background:#fff;
            box-shadow:0 0 0 3px rgba(59,130,246,0.1);
        }
        .form-group textarea { resize:vertical; min-height:80px; }

        /* Aperçu image dans le formulaire */
        .img-preview-wrap { position:relative; margin-top:6px; }
        #imgPreview {
            display:none; width:100%; height:160px; object-fit:cover;
            border-radius:var(--radius-sm); border:1px solid var(--border-color);
        }

        .btn-group { display:flex; justify-content:flex-end; gap:10px; padding-top:16px; border-top:1px solid var(--border-color); }
        .btn-save { padding:11px 24px; border-radius:var(--radius-sm); border:none; background:var(--primary); color:#fff; font-weight:600; font-size:14px; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
        .btn-save:hover { background:var(--primary-light); }
        .btn-cancel { padding:11px 18px; border-radius:var(--radius-sm); border:1px solid var(--border-color); background:var(--bg-app); color:var(--text-muted); font-weight:600; font-size:14px; cursor:pointer; }

        /* Modal détail */
        .detail-image {
            width:100%; height:220px; object-fit:cover;
            border-radius:var(--radius-md); margin-bottom:16px;
            border:1px solid var(--border-color);
        }
        .detail-placeholder {
            width:100%; height:160px; border-radius:var(--radius-md);
            background:var(--primary-soft); display:flex; align-items:center; justify-content:center;
            color:var(--primary); font-size:32px; margin-bottom:16px;
        }
        .info-list { display:flex; flex-direction:column; gap:10px; }
        .info-row { display:flex; justify-content:space-between; padding-bottom:8px; border-bottom:1px dashed var(--border-color); font-size:14px; }
        .info-row span { color:var(--text-muted); }
        .info-row strong { color:var(--text-main); }
        .desc-box { padding:12px 14px; background:#f8fafc; border-radius:var(--radius-sm); border:1px solid var(--border-color); font-size:13px; line-height:1.6; white-space:pre-line; margin-top:4px; }

        /* ── FOOTER ── */
        footer { text-align:center; padding:24px; background:#f1f5f9; margin-top:40px; font-size:13px; color:var(--text-muted); border-top:1px solid var(--border-color); }
        footer a { color:var(--primary); text-decoration:none; font-weight:500; }

        /* ── RESPONSIVE ── */
        @media (max-width:900px) {
            .sidebar { display:none; }
            .main-content { margin-left:0; padding:20px; max-width:100%; }
            .form-row { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="brand">
            <i class="fa-solid fa-building"></i>
            <span>RentMaster</span>
        </div>
        <nav>
            <a href="dashboard.php"><i class="fa fa-chart-line"></i> Dashboard</a>
            <a href="bien.php" class="active"><i class="fa fa-building"></i> Biens</a>
            <a href="locataire.php"><i class="fa fa-users"></i> Locataires</a>
            <a href="contrat.php"><i class="fa fa-file-contract"></i> Contrats</a>
            <a href="paiement.php"><i class="fa fa-credit-card"></i> Paiements</a>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <div class="page-header">
            <h1><i class="fa fa-building" style="color:var(--primary);margin-right:8px;"></i>Gestion des Biens</h1>
            <p>Ajoutez, modifiez ou supprimez vos biens immobiliers.</p>
        </div>

        <div class="actions-top">
            <input type="text" id="searchInput" placeholder="🔍  Rechercher par titre, adresse, type..." onkeyup="filtrer()">
            <button class="btn-primary" onclick="ouvrirForm()">
                <i class="fa fa-plus"></i> Ajouter un bien
            </button>
        </div>

        <div class="biens-grid" id="biensGrid">
            <?php if (empty($biens)): ?>
                <div class="empty-state">
                    <i class="fa-regular fa-folder-open"></i>
                    <p>Aucun bien enregistré. Ajoutez votre premier bien !</p>
                </div>
            <?php else: ?>
                <?php foreach ($biens as $b):
                    $statut = $b['statut'] ?? 'Disponible';
                    $cls = match($statut) {
                        'Loué'        => 'badge-loue',
                        'Maintenance' => 'badge-maintenance',
                        default       => 'badge-disponible',
                    };
                    // ✅ Chemin correct depuis views/bailleur/ → public/uploads/
                    $imgSrc = !empty($b['image'])
                        ? '../../../public/uploads/' . htmlspecialchars($b['image'])
                        : null;
                ?>
                <div class="bien-card" data-search="<?= strtolower(htmlspecialchars($b['titre'].' '.$b['adresse'].' '.$b['type_bien'])) ?>">

                    <div class="bien-image-wrapper">
                        <span class="statut-badge <?= $cls ?>"><?= htmlspecialchars($statut) ?></span>

                        <?php if ($imgSrc): ?>
                            <img src="<?= $imgSrc ?>"
                                 alt="<?= htmlspecialchars($b['titre']) ?>"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="bien-image-placeholder" style="display:none;">
                                <i class="fa fa-building"></i>
                                <span>Image introuvable</span>
                            </div>
                        <?php else: ?>
                            <div class="bien-image-placeholder">
                                <i class="fa fa-building"></i>
                                <span>Aucune image</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="bien-info">
                        <h3><?= htmlspecialchars($b['titre']) ?></h3>
                        <div class="bien-adresse">
                            <i class="fa fa-location-dot"></i>
                            <?= htmlspecialchars($b['adresse']) ?>
                        </div>
                        <div class="bien-prix">
                            <?= number_format((float)$b['prix'], 2) ?> $
                            <span>/ mois</span>
                        </div>
                        <div class="bien-details-mini">
                            <span><i class="fa fa-vector-square"></i> <?= $b['surface'] ?? '—' ?> m²</span>
                            <span><i class="fa fa-door-open"></i> <?= $b['nombre_pieces'] ?? '—' ?> pcs</span>
                            <span><i class="fa fa-layer-group"></i> <?= htmlspecialchars($b['type_bien'] ?? '') ?></span>
                        </div>
                    </div>

                    <div class="bien-actions">
                        <button class="btn-action view" onclick='ouvrirDetail(<?= json_encode($b) ?>)'>
                            <i class="fa fa-eye"></i> Voir plus
                        </button>
                        <a href="bien.php?edit=<?= $b['id_bien'] ?>" class="btn-action edit" title="Modifier">
                            <i class="fa fa-pen"></i>
                        </a>
                        <a href="bien.php?delete=<?= $b['id_bien'] ?>" class="btn-action delete" title="Supprimer"
                           onclick="return confirm('Supprimer ce bien définitivement ?')">
                            <i class="fa fa-trash"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- ══════════════════════════════════════
     OVERLAY 1 — FORMULAIRE AJOUT / MODIF
══════════════════════════════════════ -->
<div class="overlay <?= $editMode ? 'show' : '' ?>" id="overlayForm">
    <div class="form-modal">
        <div class="modal-header">
            <h2 id="formTitre">
                <?= $editMode
                    ? '<i class="fa fa-pen"></i> Modifier le bien'
                    : '<i class="fa fa-plus"></i> Ajouter un bien' ?>
            </h2>
            <button class="close-btn" onclick="fermerForm()"><i class="fa fa-times"></i></button>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                <?php if ($editMode && $bienEdit): ?>
                    <input type="hidden" name="id_bien" value="<?= $bienEdit['id_bien'] ?>">
                <?php endif; ?>

                <!-- Image + aperçu -->
                <div class="form-group">
                    <label>Image du bien (JPG, PNG, WEBP — max 5 Mo)</label>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                           onchange="previewImage(this)">
                    <div class="img-preview-wrap">
                        <?php if ($editMode && !empty($bienEdit['image'])): ?>
                            <img id="imgPreview"
                                 src="../../../public/uploads/<?= htmlspecialchars($bienEdit['image']) ?>"
                                 style="display:block;">
                        <?php else: ?>
                            <img id="imgPreview">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Titre *</label>
                    <input type="text" name="titre" required placeholder="Ex: Appartement T3 Centre-ville"
                           value="<?= htmlspecialchars($bienEdit['titre'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Adresse *</label>
                    <input type="text" name="adresse" required placeholder="Ex: 12 avenue Kasa-Vubu, Kinshasa"
                           value="<?= htmlspecialchars($bienEdit['adresse'] ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Type de bien</label>
                        <select name="type_bien">
                            <?php foreach (['Appartement','Maison','Studio','Bureau','Villa'] as $t): ?>
                                <option <?= ($bienEdit['type_bien'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Loyer mensuel ($) *</label>
                        <input type="number" name="prix" required min="0" step="0.01"
                               placeholder="Ex: 800"
                               value="<?= htmlspecialchars($bienEdit['prix'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Surface (m²)</label>
                        <input type="number" name="surface" min="1" placeholder="Ex: 65"
                               value="<?= htmlspecialchars($bienEdit['surface'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Nombre de pièces</label>
                        <input type="number" name="nombre_pieces" min="1" placeholder="Ex: 3"
                               value="<?= htmlspecialchars($bienEdit['nombre_pieces'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Statut</label>
                    <select name="statut">
                        <?php foreach (['Disponible','Loué','Maintenance'] as $s): ?>
                            <option <?= ($bienEdit['statut'] ?? 'Disponible') === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="Équipements, commodités..."><?= htmlspecialchars($bienEdit['description'] ?? '') ?></textarea>
                </div>

                <div class="btn-group">
                    <button type="button" class="btn-cancel" onclick="fermerForm()">Annuler</button>
                    <button type="submit" class="btn-save">
                        <i class="fa fa-save"></i>
                        <?= $editMode ? 'Enregistrer les modifications' : 'Ajouter le bien' ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════
     OVERLAY 2 — DÉTAIL DU BIEN
══════════════════════════════════════ -->
<div class="overlay" id="overlayDetail">
    <div class="form-modal">
        <div class="modal-header">
            <h2 id="detailTitre"><i class="fa fa-building"></i> Détails</h2>
            <button class="close-btn" onclick="fermerDetail()"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div id="detailImg"></div>
            <div class="info-list">
                <div class="info-row"><span>Type</span><strong id="dType"></strong></div>
                <div class="info-row"><span>Adresse</span><strong id="dAdresse"></strong></div>
                <div class="info-row"><span>Surface</span><strong id="dSurface"></strong></div>
                <div class="info-row"><span>Pièces</span><strong id="dPieces"></strong></div>
                <div class="info-row"><span>Loyer mensuel</span><strong id="dPrix"></strong></div>
                <div class="info-row"><span>Statut</span><strong id="dStatut"></strong></div>
            </div>
            <div class="form-group" style="margin-top:12px;">
                <label>Description</label>
                <div class="desc-box" id="dDesc"></div>
            </div>
            <div class="btn-group">
                <button class="btn-cancel" onclick="fermerDetail()"><i class="fa fa-times"></i> Fermer</button>
                <a id="dEditLink" href="#" class="btn-save" style="text-decoration:none;">
                    <i class="fa fa-pen"></i> Modifier
                </a>
            </div>
        </div>
    </div>
</div>

<footer>
    <p>&copy; 2026 RentMaster. Tous droits réservés. |
        <a href="mailto:divinekatende23@gmail.com">Support</a> |
        <a href="https://divinekatende.github.io/DIVKT/" target="_blank">Portfolio</a>
    </p>
</footer>

<script>
/* ── RECHERCHE ── */
function filtrer() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.bien-card').forEach(c => {
        c.style.display = c.dataset.search.includes(q) ? '' : 'none';
    });
}

/* ── APERÇU IMAGE dans le formulaire ── */
function previewImage(input) {
    const prev = document.getElementById('imgPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { prev.src = e.target.result; prev.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}

/* ── FORM OVERLAY ── */
function ouvrirForm() {
    document.getElementById('overlayForm').classList.add('show');
}
function fermerForm() {
    <?php if ($editMode): ?>
        window.location.href = 'bien.php';
    <?php else: ?>
        document.getElementById('overlayForm').classList.remove('show');
    <?php endif; ?>
}

/* ── DETAIL OVERLAY ── */
function ouvrirDetail(b) {
    const imgBase = '../../../public/uploads/';
    const imgEl   = document.getElementById('detailImg');

    if (b.image) {
        imgEl.innerHTML = `<img src="${imgBase}${b.image}" class="detail-image"
            onerror="this.parentElement.innerHTML='<div class=\'detail-placeholder\'><i class=\'fa fa-building\'></i></div>'">`;
    } else {
        imgEl.innerHTML = `<div class="detail-placeholder"><i class="fa fa-building"></i></div>`;
    }

    document.getElementById('detailTitre').innerHTML = `<i class="fa fa-building"></i> ${b.titre}`;
    document.getElementById('dType').textContent    = b.type_bien    || '—';
    document.getElementById('dAdresse').textContent = b.adresse      || '—';
    document.getElementById('dSurface').textContent = (b.surface     || '—') + ' m²';
    document.getElementById('dPieces').textContent  = b.nombre_pieces || '—';
    document.getElementById('dPrix').textContent    = parseFloat(b.prix || 0).toFixed(2) + ' $ / mois';
    document.getElementById('dStatut').textContent  = b.statut       || '—';
    document.getElementById('dDesc').textContent    = b.description  || 'Aucune description.';
    document.getElementById('dEditLink').href       = `bien.php?edit=${b.id_bien}`;

    document.getElementById('overlayDetail').classList.add('show');
}
function fermerDetail() {
    document.getElementById('overlayDetail').classList.remove('show');
}

/* Fermer en cliquant hors du modal */
['overlayForm','overlayDetail'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) {
            id === 'overlayForm' ? fermerForm() : fermerDetail();
        }
    });
});
</script>
</body>
</html>