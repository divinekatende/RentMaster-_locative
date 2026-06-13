<?php

require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/database.php';

verifierConnexion();
verifierRole(['admin']);

$conn = (new Database())->connect();

$message = "";

/* =========================
REDIRECTION
========================= */
function redirect(){
    header("Location: gestion.php");
    exit();
}

/* =========================
ACTIONS (SUPPRESSION / BLOCAGE)
========================= */
if(isset($_GET['action'], $_GET['type'], $_GET['id'])){

    $type = $_GET['type'];
    $id = (int)$_GET['id'];

    if($type === "bailleur"){
        if($_GET['action'] === "block"){
            $conn->prepare("UPDATE bailleurs SET statut='bloqué' WHERE id_bailleur=?")->execute([$id]);
        }
        if($_GET['action'] === "delete"){
            $conn->prepare("DELETE FROM bailleurs WHERE id_bailleur=?")->execute([$id]);
        }
    }

    if($type === "locataire"){
        if($_GET['action'] === "block"){
            $conn->prepare("UPDATE locataires SET statut='bloqué' WHERE id_locataire=?")->execute([$id]);
        }
        if($_GET['action'] === "delete"){
            $conn->prepare("DELETE FROM locataires WHERE id_locataire=?")->execute([$id]);
        }
    }

    if($type === "bien"){
        if($_GET['action'] === "delete"){
            $conn->prepare("DELETE FROM biens WHERE id_bien=?")->execute([$id]);
        }
    }

    redirect();
}

/* =========================
TRAITEMENT DES AJOUTS
========================= */
if(isset($_POST['ajouter_bailleur'])){
    $conn->prepare("INSERT INTO bailleurs(nom, email, telephone, mot_de_passe, statut) VALUES(?, ?, ?, ?, 'actif')")->execute([
        $_POST['nom'],
        $_POST['email'],
        $_POST['telephone'],
        password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT)
    ]);
    $message = "Bailleur ajouté avec succès.";
}

if(isset($_POST['ajouter_locataire'])){
    $conn->prepare("INSERT INTO locataires(nom, email, telephone, statut) VALUES(?, ?, ?, 'actif')")->execute([
        $_POST['nom'],
        $_POST['email'],
        $_POST['telephone']
    ]);
    $message = "Locataire ajouté avec succès.";
}

if(isset($_POST['ajouter_bien'])){
    $conn->prepare("INSERT INTO biens(titre, adresse, loyer, statut) VALUES(?, ?, ?, 'disponible')")->execute([
        $_POST['titre'],
        $_POST['adresse'],
        $_POST['loyer']
    ]);
    $message = "Bien immobilier ajouté avec succès.";
}

/* =========================
RECUPERATION DES DONNEES
========================= */
$bailleurs = $conn->query("SELECT * FROM bailleurs ORDER BY id_bailleur DESC")->fetchAll(PDO::FETCH_ASSOC);
$locataires = $conn->query("SELECT * FROM locataires ORDER BY id_locataire DESC")->fetchAll(PDO::FETCH_ASSOC);
$biens = $conn->query("SELECT * FROM biens ORDER BY id_bien DESC")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentMaster — Configuration & Gestion</title>
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
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .page-header h2 { font-size: 24px; font-weight: 700; letter-spacing: -0.02em; }

        /* NAVIGATION PAR ONGLETS */
        .tabs-nav { display: flex; gap: 8px; background: #e2e8f0; padding: 5px; border-radius: var(--radius-md); align-self: flex-start; margin-bottom: 24px; }
        .tab-btn { padding: 8px 20px; border: none; border-radius: var(--radius-sm); cursor: pointer; background: transparent; font-size: 14px; font-weight: 600; color: var(--text-muted); transition: all 0.2s ease; }
        .tab-btn.active { background: var(--surface); color: var(--primary); box-shadow: var(--shadow-sm); }

        /* NOTIFICATION CARD */
        .alert-success { background: var(--success-soft); color: var(--success); border: 1px solid #a7f3d0; padding: 16px; border-radius: var(--radius-md); font-weight: 500; font-size: 14px; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; }

        /* CARD PANELS & TABLES */
        .panel-card { background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); overflow: hidden; }
        .panel-header { padding: 24px 32px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); }
        .panel-header h3 { font-size: 16px; font-weight: 700; color: var(--text-main); }
        
        /* STANDARD BUTTONS */
        .btn-action { padding: 8px 16px; font-size: 13px; font-weight: 600; border-radius: var(--radius-sm); border: 1px solid var(--border-color); cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s ease; }
        .btn-primary { background: var(--primary); color: white; border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-light); }
        
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #f8fafc; padding: 14px 32px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); border-bottom: 1px solid var(--border-color); letter-spacing: 0.05em; }
        td { padding: 16px 32px; font-size: 14px; border-bottom: 1px solid var(--border-color); color: var(--text-main); }
        tr:last-child td { border-bottom: none; }
        
        /* ACTIONS ICONS CONTEXT */
        .row-btn { width: 32px; height: 32px; border: 1px solid var(--border-color); background: var(--surface); border-radius: var(--radius-sm); color: var(--text-muted); cursor: pointer; transition: all 0.15s ease; }
        .row-btn:hover { color: var(--primary); border-color: var(--primary-light); background: var(--primary-soft); }

        /* SYSTEM MODALS STYLING */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); justify-content: center; align-items: center; z-index: 999; }
        .modal-content { background: var(--surface); padding: 32px; border-radius: var(--radius-lg); width: 100%; max-width: 460px; box-shadow: var(--shadow-md); border: 1px solid var(--border-color); position: relative; }
        .modal-content h3 { font-size: 18px; font-weight: 700; margin-bottom: 20px; color: var(--text-main); }
        
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; }
        .modal-content input { width: 100%; padding: 10px 14px; font-size: 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); outline: none; transition: border 0.2s; }
        .modal-content input:focus { border-color: var(--primary-light); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        
        .modal-footer { display: flex; justify-content: flex-end; gap: 8px; margin-top: 24px; }
        .btn-cancel { background: #f1f5f9; color: var(--text-muted); border: none; }
        .btn-cancel:hover { background: #e2e8f0; }
        .btn-danger { background: var(--danger); color: white; border-color: var(--danger); }
        .btn-danger:hover { background: #f87171; }

        @media(max-width: 1024px) {
            aside { width: 70px; padding: 24px 8px; }
            aside .logo h3, aside button span { display: none; }
            .main { margin-left: 70px; padding: 24px 16px; }
            th, td { padding: 14px 16px; }
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
        <button class="active" onclick="location.href='gestion.php'"><i class="fa fa-building"></i> <span>Gestion</span></button>
        <button onclick="location.href='settings.php'"><i class="fa fa-gear"></i> <span>Paramètres</span></button>
    </nav>
    <div class="sidebar-footer">
        <button onclick="location.href='../../controllers/logout.php'"><i class="fa fa-right-from-bracket"></i> <span>Déconnexion</span></button>
    </div>
</aside>

<div class="main">

    <div class="page-header">
        <h2>Gestion Complète du Système</h2>
    </div>

    <?php if($message): ?>
        <div class="alert-success"><i class="fas fa-check-circle"></i> <?= $message ?></div>
    <?php endif; ?>

    <div class="tabs-nav">
        <button class="tab-btn active" onclick="showPage('bailleurs', this)">Bailleurs</button>
        <button class="tab-btn" onclick="showPage('locataires', this)">Locataires</button>
        <button class="tab-btn" onclick="showPage('biens', this)">Biens Immobiliers</button>
    </div>

    <div id="bailleurs" class="page">
        <div class="panel-card">
            <div class="panel-header">
                <h3>Répertoire des Bailleurs</h3>
                <button class="btn-action btn-primary" onclick="openAddModal('bailleur')"><i class="fa fa-plus"></i> Nouveau Bailleur</button>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Nom Complet</th><th>Adresse Email</th><th>Téléphone</th><th style="text-align: right;">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($bailleurs as $b): ?>
                        <tr>
                            <td style="font-weight: 600;"><?= htmlspecialchars($b['nom']) ?></td>
                            <td style="color: var(--text-muted);"><?= htmlspecialchars($b['email']) ?></td>
                            <td><?= htmlspecialchars($b['telephone'] ?? '—') ?></td>
                            <td style="text-align: right;">
                                <button class="row-btn" title="Gérer le compte" onclick="openActionModal('bailleur', <?= $b['id_bailleur'] ?>, '<?= htmlspecialchars($b['nom']) ?>')"><i class="fa fa-ellipsis-v"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="locataires" class="page" style="display:none;">
        <div class="panel-card">
            <div class="panel-header">
                <h3>Répertoire des Locataires</h3>
                <button class="btn-action btn-primary" onclick="openAddModal('locataire')"><i class="fa fa-plus"></i> Nouveau Locataire</button>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Nom Complet</th><th>Adresse Email</th><th>Téléphone</th><th style="text-align: right;">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($locataires as $l): ?>
                        <tr>
                            <td style="font-weight: 600;"><?= htmlspecialchars($l['nom']) ?></td>
                            <td style="color: var(--text-muted);"><?= htmlspecialchars($l['email']) ?></td>
                            <td><?= htmlspecialchars($l['telephone'] ?? '—') ?></td>
                            <td style="text-align: right;">
                                <button class="row-btn" title="Gérer le compte" onclick="openActionModal('locataire', <?= $l['id_locataire'] ?>, '<?= htmlspecialchars($l['nom']) ?>')"><i class="fa fa-ellipsis-v"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="biens" class="page" style="display:none;">
        <div class="panel-card">
            <div class="panel-header">
                <h3>Patrimoine Immobilier</h3>
                <button class="btn-action btn-primary" onclick="openAddModal('bien')"><i class="fa fa-plus"></i> Ajouter un Bien</button>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr><th>Désignation</th><th>Adresse Postale</th><th>Loyer Mensuel</th><th style="text-align: right;">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($biens as $bi): ?>
                        <tr>
                            <td style="font-weight: 600;"><?= htmlspecialchars($bi['titre']) ?></td>
                            <td style="color: var(--text-muted);"><?= htmlspecialchars($bi['adresse']) ?></td>
                            <td style="font-weight: 600; color: var(--primary);"><?= number_format($bi['loyer'], 2, ',', ' ') ?> $</td>
                            <td style="text-align: right;">
                                <button class="row-btn" title="Gérer le bien" onclick="openActionModal('bien', <?= $bi['id_bien'] ?>, '<?= htmlspecialchars($bi['titre']) ?>')"><i class="fa fa-ellipsis-v"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="modal" id="actionModal">
    <div class="modal-content">
        <h3 id="actionModalTitle">Options de gestion</h3>
        <input type="hidden" id="target_id">
        <input type="hidden" id="target_type">
        
        <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 20px;">Sélectionnez l'action de restriction ou de maintenance système à appliquer sur cet élément.</p>

        <div class="modal-footer">
            <button class="btn-action btn-cancel" onclick="closeModal('actionModal')">Annuler</button>
            <button class="btn-action btn-danger" id="blockBtn" onclick="executeBlock()">Bloquer le compte</button>
            <button class="btn-action btn-danger" style="background:#dc2626;" onclick="executeDelete()">Supprimer définitivement</button>
        </div>
    </div>
</div>

<div class="modal" id="addModal">
    <div class="modal-content">
        <h3 id="addModalTitle">Ajouter une entité</h3>
        <form method="POST" action="gestion.php" id="dynamicForm">
            
            <div id="formFieldsContainer"></div>

            <div class="modal-footer">
                <button type="button" class="btn-action btn-cancel" onclick="closeModal('addModal')">Annuler</button>
                <button type="submit" class="btn-action btn-primary">Enregistrer les données</button>
            </div>
        </form>
    </div>
</div>

<script>
// Switch de pages asynchrones par onglets
function showPage(id, btn){
    document.querySelectorAll(".page").forEach(p => p.style.display = "none");
    document.getElementById(id).style.display = "block";

    document.querySelectorAll(".tab-btn").forEach(b => b.classList.remove("active"));
    btn.classList.add("active");
}

// Fenêtre de gestion (Bloquer/Supprimer)
function openActionModal(type, id, name){
    document.getElementById("actionModal").style.display = "flex";
    document.getElementById("target_type").value = type;
    document.getElementById("target_id").value = id;
    document.getElementById("actionModalTitle").innerText = "Gestion : " + name;
    
    // Un bien ne possède pas d'option de blocage logique de compte
    if(type === 'bien') {
        document.getElementById("blockBtn").style.display = "none";
    } else {
        document.getElementById("blockBtn").style.display = "inline-flex";
    }
}

// Fenêtre de création d'entité adaptative
function openAddModal(type){
    document.getElementById("addModal").style.display = "flex";
    const container = document.getElementById("formFieldsContainer");
    container.innerHTML = "";
    
    if(type === 'bailleur'){
        document.getElementById("addModalTitle").innerText = "Nouveau Compte Bailleur";
        container.innerHTML = `
            <input type="hidden" name="ajouter_bailleur" value="1">
            <div class="form-group"><label>Nom Complet</label><input type="text" name="nom" required></div>
            <div class="form-group"><label>Adresse Email</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Téléphone</label><input type="text" name="telephone"></div>
            <div class="form-group"><label>Mot de passe initial</label><input type="password" name="mot_de_passe" required></div>
        `;
    } else if(type === 'locataire') {
        document.getElementById("addModalTitle").innerText = "Nouveau Compte Locataire";
        container.innerHTML = `
            <input type="hidden" name="ajouter_locataire" value="1">
            <div class="form-group"><label>Nom Complet</label><input type="text" name="nom" required></div>
            <div class="form-group"><label>Adresse Email</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Téléphone</label><input type="text" name="telephone"></div>
        `;
    } else if(type === 'bien') {
        document.getElementById("addModalTitle").innerText = "Nouveau Bien Immobilier";
        container.innerHTML = `
            <input type="hidden" name="ajouter_bien" value="1">
            <div class="form-group"><label>Titre de l'annonce / Désignation</label><input type="text" name="titre" placeholder="Ex: Studio Meublé - Centre Ville" required></div>
            <div class="form-group"><label>Adresse Géographique</label><input type="text" name="adresse" required></div>
            <div class="form-group"><label>Loyer Mensuel ($)</label><input type="number" step="0.01" name="loyer" required></div>
        `;
    }
}

function closeModal(modalId){
    document.getElementById(modalId).style.display = "none";
}

function executeDelete(){
    let id = document.getElementById("target_id").value;
    let type = document.getElementById("target_type").value;
    if(confirm("Confirmez-vous la suppression irréversible de cet élément du système RentMaster ?")){
        window.location = "?action=delete&type=" + type + "&id=" + id;
    }
}

function executeBlock(){
    let id = document.getElementById("target_id").value;
    let type = document.getElementById("target_type").value;
    window.location = "?action=block&type=" + type + "&id=" + id;
}
</script>

</body>
</html>