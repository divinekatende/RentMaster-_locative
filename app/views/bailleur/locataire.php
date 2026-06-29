<?php
require_once __DIR__ . '/../../auth/session.php';
verifierConnexion();
verifierRole(['bailleur']);

require_once __DIR__ . '/../../controllers/LocataireController.php';

$controller = new LocataireController();
$listeLocataires = $controller->getAllLocataires();

$locataireEnCours = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $locataireEnCours = $controller->getLocataireById($_GET['id']);
}

$erreur = $_SESSION['erreur_upload'] ?? null;
unset($_SESSION['erreur_upload']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locataires - RentMaster</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg: #f8fafc;
            --surface: #ffffff;
            --primary: #1e40af;
            --primary-light: #3b82f6;
            --primary-soft: #eff6ff;
            --text: #0f172a;
            --text-soft: #64748b;
            --border: #e2e8f0;
            --radius: 16px;
            --radius-sm: 10px;
            --shadow: 0 4px 6px -1px rgba(15,23,42,0.06), 0 2px 4px -2px rgba(15,23,42,0.04);
            --shadow-md: 0 10px 25px -5px rgba(15,23,42,0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 260px; background: var(--primary); color: #fff;
            height: 100vh; position: fixed;
            display: flex; flex-direction: column;
            padding: 28px 16px; z-index: 100;
        }
        .sidebar .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 36px; }
        .sidebar .brand i { background: rgba(255,255,255,0.15); padding: 10px; border-radius: 10px; font-size: 18px; }
        .sidebar .brand span { font-size: 20px; font-weight: 700; }
        .sidebar nav { display: flex; flex-direction: column; gap: 4px; }
        .sidebar nav a {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; border-radius: var(--radius-sm);
            color: rgba(255,255,255,0.75); text-decoration: none;
            font-size: 14px; font-weight: 500; transition: .2s;
        }
        .sidebar nav a:hover, .sidebar nav a.active {
            background: rgba(255,255,255,0.15); color: #fff; font-weight: 600;
        }
        .sidebar nav a.logout { margin-top: auto; color: #fca5a5; }
        .sidebar nav a.logout:hover { background: rgba(239,68,68,0.15); color: #fca5a5; }
/* MOBILE MENU BUTTON */
        .btn-menu-mobile {
            display: none; background: var(--primary); color: white;
            border: none; width: 42px; height: 42px; border-radius: var(--radius-sm);
            cursor: pointer; font-size: 18px;
            position: fixed; top: 16px; left: 16px; z-index: 2000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
         
        /* ── MAIN ── */
        .main { margin-left: 260px; padding: 40px; flex: 1; }

        /* ── TOPBAR ── */
        .topbar { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; gap: 16px; flex-wrap: wrap; }
        .topbar h1 { font-size: 26px; font-weight: 700; letter-spacing: -0.02em; }
        .topbar p { color: var(--text-soft); font-size: 14px; margin-top: 4px; }

        .btn-primary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 22px; border-radius: var(--radius-sm);
            background: var(--primary); color: #fff; border: none;
            font-weight: 600; font-size: 14px; cursor: pointer;
            box-shadow: 0 4px 12px rgba(30,64,175,0.2); transition: .2s; white-space: nowrap;
        }
        .btn-primary:hover { background: var(--primary-light); transform: translateY(-1px); }

        /* ── BARRE DE RECHERCHE ── */
        .search-bar {
            display: flex; gap: 12px; margin-bottom: 28px; align-items: center;
        }
        .search-bar input {
            flex: 1; padding: 12px 18px; border-radius: 30px;
            border: 1px solid var(--border); outline: none; font-size: 14px;
            background: var(--surface); transition: .2s;
        }
        .search-bar input:focus { border-color: var(--primary-light); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }

        /* ── GRILLE DE CARTES ── */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }

        .locataire-card {
            background: var(--surface);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: transform .2s, box-shadow .2s;
            display: flex; flex-direction: column;
        }
        .locataire-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }

        .card-photo {
            height: 160px; background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
            display: flex; align-items: center; justify-content: center; overflow: hidden;
            position: relative;
        }
        .card-photo img { width: 100%; height: 100%; object-fit: cover; }
        .card-photo .initiales {
            width: 72px; height: 72px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            color: #fff; font-size: 26px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
        }
        .card-badge {
            position: absolute; top: 10px; right: 10px;
            background: #10b981; color: #fff;
            font-size: 10px; font-weight: 600; padding: 3px 8px; border-radius: 20px;
            text-transform: uppercase; letter-spacing: 0.05em;
        }

        .card-body { padding: 18px; flex: 1; }
        .card-body .nom { font-size: 15px; font-weight: 700; margin-bottom: 4px; }
        .card-body .matricule {
            font-size: 12px; color: var(--primary); font-weight: 600;
            background: var(--primary-soft); padding: 3px 8px; border-radius: 20px;
            display: inline-block; margin-bottom: 10px;
        }
        .card-body .info-row { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-soft); margin-bottom: 5px; }
        .card-body .info-row i { width: 14px; color: var(--primary-light); }

        .card-footer { padding: 14px 18px; border-top: 1px solid var(--border); }
        .btn-voir-plus {
            width: 100%; padding: 10px; border-radius: var(--radius-sm);
            border: 1px solid var(--border); background: var(--bg);
            color: var(--text); font-weight: 600; font-size: 13px;
            cursor: pointer; transition: .2s; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-voir-plus:hover { background: var(--primary-soft); border-color: var(--primary-light); color: var(--primary); }

        /* ── EMPTY STATE ── */
        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-soft); }
        .empty-state i { font-size: 52px; color: #cbd5e1; display: block; margin-bottom: 16px; }
        .empty-state p { font-size: 15px; }

        /* ── ERREUR ── */
        .alert-error {
            background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c;
            padding: 14px 18px; border-radius: var(--radius-sm); margin-bottom: 20px;
            font-size: 14px; display: flex; align-items: center; gap: 10px;
        }

        /* ── OVERLAY ── */
        .overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(15,23,42,0.45); backdrop-filter: blur(4px);
            z-index: 2000; align-items: center; justify-content: center; padding: 20px;
        }
        .overlay.show { display: flex; }

        /* ── MODAL VOIR PLUS ── */
        .detail-modal {
            background: var(--surface); border-radius: var(--radius);
            width: 100%; max-width: 520px; max-height: 90vh; overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3);
            animation: popIn .22s ease;
        }
        @keyframes popIn {
            from { transform: scale(.96); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }

        .detail-header {
            display: flex; align-items: center; gap: 20px;
            padding: 28px 28px 20px; border-bottom: 1px solid var(--border);
        }
        .detail-avatar {
            width: 72px; height: 72px; border-radius: 50%; overflow: hidden;
            border: 3px solid var(--primary-soft); flex-shrink: 0;
        }
        .detail-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .detail-avatar .initiales {
            width: 100%; height: 100%; background: linear-gradient(135deg, var(--primary-light), var(--primary));
            color: #fff; font-size: 24px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
        }
        .detail-header-info .nom { font-size: 18px; font-weight: 700; }
        .detail-header-info .matricule {
            font-size: 12px; font-weight: 600; color: var(--primary);
            background: var(--primary-soft); padding: 3px 10px; border-radius: 20px;
            display: inline-block; margin-top: 4px;
        }

        .detail-body { padding: 24px 28px; display: flex; flex-direction: column; gap: 12px; }
        .detail-row { display: flex; align-items: center; gap: 12px; font-size: 14px; }
        .detail-row i { width: 32px; height: 32px; border-radius: 8px; background: var(--primary-soft); color: var(--primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .detail-row .label { color: var(--text-soft); font-size: 12px; margin-bottom: 1px; }
        .detail-row .value { font-weight: 600; }

        .detail-actions {
            padding: 18px 28px; border-top: 1px solid var(--border);
            display: flex; gap: 10px; justify-content: flex-end;
        }
        .btn-modifier {
            padding: 10px 20px; border-radius: var(--radius-sm); border: none;
            background: var(--primary); color: #fff;
            font-weight: 600; font-size: 13px; cursor: pointer; transition: .2s;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-modifier:hover { background: var(--primary-light); }
        .btn-supprimer {
            padding: 10px 20px; border-radius: var(--radius-sm); border: none;
            background: #fef2f2; color: #ef4444;
            font-weight: 600; font-size: 13px; cursor: pointer; transition: .2s;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-supprimer:hover { background: #fee2e2; }
        .btn-fermer {
            padding: 10px 16px; border-radius: var(--radius-sm);
            border: 1px solid var(--border); background: var(--bg);
            color: var(--text-soft); font-weight: 600; font-size: 13px; cursor: pointer;
        }

        /* ── MODAL FORMULAIRE ── */
        .form-modal {
            background: var(--surface); border-radius: var(--radius);
            width: 100%; max-width: 580px; max-height: 90vh; overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3);
            animation: popIn .22s ease;
        }
        .modal-header { padding: 24px 28px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { font-size: 17px; font-weight: 700; }
        .modal-header .close-btn { background: none; border: none; font-size: 18px; color: var(--text-soft); cursor: pointer; }

        .modal-body { padding: 24px 28px; display: flex; flex-direction: column; gap: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 13px; font-weight: 600; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group input, .form-group select {
            padding: 10px 14px; border-radius: var(--radius-sm);
            border: 1px solid var(--border); outline: none;
            font-size: 14px; background: #f8fafc; transition: .2s;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary-light); background: var(--surface);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .form-group input[type="file"] { padding: 8px; }
        .info-auto {
            font-size: 12px; color: var(--text-soft); background: var(--bg);
            padding: 10px 14px; border-radius: var(--radius-sm); border: 1px dashed var(--border);
        }
        .info-auto i { color: #10b981; }

        .btn-group { display: flex; justify-content: flex-end; gap: 10px; padding-top: 16px; border-top: 1px solid var(--border); }
        .btn-save { padding: 11px 24px; border-radius: var(--radius-sm); border: none; background: var(--primary); color: #fff; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .btn-save:hover { background: var(--primary-light); }
        .btn-cancel { padding: 11px 18px; border-radius: var(--radius-sm); border: 1px solid var(--border); background: var(--bg); color: var(--text-soft); font-weight: 600; font-size: 14px; cursor: pointer; }

        /* ── RESPONSIVE ── */
        @media (max-width: 900px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 20px; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<button id="menuToggle" class="btn-menu-mobile"><i class="fas fa-bars"></i></button>
 
<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="brand">
        <i class="fa-solid fa-building"></i>
        <span>RentMaster</span>
    </div>
    <nav>
        <a href="dashboard.php"><i class="fa fa-chart-line"></i> Dashboard</a>
        <a href="bien.php"><i class="fa fa-building"></i> Biens</a>
        <a href="locataire.php" class="active"><i class="fa fa-users"></i> Locataires</a>
        <a href="contrat.php"><i class="fa fa-file-contract"></i> Contrats</a>
        <a href="paiement.php"><i class="fa fa-credit-card"></i> Paiements</a>
      
        <a href="../../controllers/logout.php" class="logout"><i class="fa fa-sign-out-alt"></i> Déconnexion</a>
    </nav>
</aside>

<!-- MAIN -->
<main class="main">

    <div class="topbar">
        <div>
            <h1><i class="fa fa-users" style="color:var(--primary); margin-right:8px;"></i>Locataires</h1>
            <p>Gérez vos locataires, consultez leurs profils et suivez leurs informations.</p>
        </div>
        <button class="btn-primary" onclick="ouvrirFormulaire()">
            <i class="fa fa-plus"></i> Nouveau locataire
        </button>
    </div>

    <?php if ($erreur): ?>
        <div class="alert-error"><i class="fa fa-circle-exclamation"></i> <?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="🔍  Rechercher par nom, matricule, email..." onkeyup="filtrer()">
    </div>

    <?php if (empty($listeLocataires)): ?>
        <div class="empty-state">
            <i class="fa-regular fa-folder-open"></i>
            <p>Aucun locataire enregistré pour le moment.</p>
        </div>
    <?php else: ?>
        <div class="cards-grid" id="cardsGrid">
            <?php foreach ($listeLocataires as $l): ?>
                <?php
                    $initiales = strtoupper(substr($l['prenom'], 0, 1) . substr($l['nom'], 0, 1));
                    $photoSrc = !empty($l['photo'])
                        ? '../../../public/uploads/locataires/' . htmlspecialchars($l['photo'])
                        : null;
                ?>
                <div class="locataire-card" data-search="<?= strtolower(htmlspecialchars($l['nom'] . ' ' . $l['prenom'] . ' ' . $l['matricule'] . ' ' . $l['email'])) ?>">

                    <div class="card-photo">
                        <?php if ($photoSrc): ?>
                            <img src="<?= $photoSrc ?>" alt="Photo de <?= htmlspecialchars($l['prenom']) ?>">
                        <?php else: ?>
                            <div class="initiales"><?= $initiales ?></div>
                        <?php endif; ?>
                        <span class="card-badge"><?= htmlspecialchars($l['statut'] ?? 'actif') ?></span>
                    </div>

                    <div class="card-body">
                        <div class="nom"><?= htmlspecialchars($l['prenom'] . ' ' . $l['nom']) ?></div>
                        <span class="matricule"><?= htmlspecialchars($l['matricule']) ?></span>
                        <div class="info-row"><i class="fa fa-phone"></i><?= htmlspecialchars($l['telephone']) ?></div>
                        <div class="info-row"><i class="fa fa-envelope"></i><?= htmlspecialchars($l['email']) ?></div>
                        <div class="info-row"><i class="fa fa-briefcase"></i><?= htmlspecialchars($l['profession'] ?? '—') ?></div>
                    </div>

                    <div class="card-footer">
                        <button class="btn-voir-plus" onclick="ouvrirDetail(<?= htmlspecialchars(json_encode($l)) ?>)">
                            <i class="fa fa-eye"></i> Voir le profil
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>

<!-- ═══════════════════════════════════════════
     OVERLAY 1 : DÉTAIL DU LOCATAIRE
═══════════════════════════════════════════ -->
<div class="overlay" id="overlayDetail">
    <div class="detail-modal">

        <div class="detail-header">
            <div class="detail-avatar" id="detailAvatar"></div>
            <div class="detail-header-info">
                <div class="nom" id="detailNom"></div>
                <span class="matricule" id="detailMatricule"></span>
            </div>
        </div>

        <div class="detail-body">
            <div class="detail-row">
                <i class="fa fa-cake-candles"></i>
                <div><div class="label">Date de naissance</div><div class="value" id="detailNaissance"></div></div>
            </div>
            <div class="detail-row">
                <i class="fa fa-venus-mars"></i>
                <div><div class="label">Sexe</div><div class="value" id="detailSexe"></div></div>
            </div>
            <div class="detail-row">
                <i class="fa fa-heart"></i>
                <div><div class="label">État civil</div><div class="value" id="detailEtatCivil"></div></div>
            </div>
            <div class="detail-row">
                <i class="fa fa-flag"></i>
                <div><div class="label">Nationalité</div><div class="value" id="detailNationalite"></div></div>
            </div>
            <div class="detail-row">
                <i class="fa fa-briefcase"></i>
                <div><div class="label">Profession</div><div class="value" id="detailProfession"></div></div>
            </div>
            <div class="detail-row">
                <i class="fa fa-phone"></i>
                <div><div class="label">Téléphone</div><div class="value" id="detailTelephone"></div></div>
            </div>
            <div class="detail-row">
                <i class="fa fa-envelope"></i>
                <div><div class="label">Email</div><div class="value" id="detailEmail"></div></div>
            </div>
            <div class="detail-row">
                <i class="fa fa-location-dot"></i>
                <div><div class="label">Adresse</div><div class="value" id="detailAdresse"></div></div>
            </div>
        </div>

        <div class="detail-actions">
            <button class="btn-fermer" onclick="fermerDetail()"><i class="fa fa-times"></i> Fermer</button>
            <button class="btn-supprimer" id="btnSupprimer"><i class="fa fa-trash"></i> Supprimer</button>
            <button class="btn-modifier" id="btnModifier"><i class="fa fa-pen"></i> Modifier</button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════
     OVERLAY 2 : FORMULAIRE AJOUT / MODIFICATION
═══════════════════════════════════════════ -->
<div class="overlay <?= $locataireEnCours ? 'show' : '' ?>" id="overlayForm">
    <div class="form-modal">
        <div class="modal-header">
            <h2 id="formTitre"><i class="fa fa-plus"></i> Nouveau locataire</h2>
            <button class="close-btn" onclick="fermerFormulaire()"><i class="fa fa-times"></i></button>
        </div>

        <form action="../../controllers/LocataireController.php" method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id_locataire" id="inputId" value="<?= $locataireEnCours['id_locataire'] ?? '' ?>">

                <!-- Info automatique -->
                <div class="info-auto">
                    <i class="fa fa-circle-info"></i>
                    Le <strong>matricule</strong> (R + 4 chiffres) et le <strong>mot de passe</strong> (1111) seront générés automatiquement et envoyés par email au locataire.
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nom *</label>
                        <input type="text" name="nom" required id="inputNom" value="<?= htmlspecialchars($locataireEnCours['nom'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Prénom *</label>
                        <input type="text" name="prenom" required id="inputPrenom" value="<?= htmlspecialchars($locataireEnCours['prenom'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Date de naissance *</label>
                    <input type="date" name="date_naissance" required id="inputNaissance" value="<?= htmlspecialchars($locataireEnCours['date_naissance'] ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Sexe</label>
                        <select name="sexe" id="inputSexe">
                            <option value="Homme" <?= isset($locataireEnCours['sexe']) && $locataireEnCours['sexe'] === 'Homme' ? 'selected' : '' ?>>Homme</option>
                            <option value="Femme" <?= isset($locataireEnCours['sexe']) && $locataireEnCours['sexe'] === 'Femme' ? 'selected' : '' ?>>Femme</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>État civil</label>
                        <select name="etat_civil" id="inputEtatCivil">
                            <option value="Célibataire" <?= isset($locataireEnCours['etat_civil']) && $locataireEnCours['etat_civil'] === 'Célibataire' ? 'selected' : '' ?>>Célibataire</option>
                            <option value="Marié(e)" <?= isset($locataireEnCours['etat_civil']) && $locataireEnCours['etat_civil'] === 'Marié(e)' ? 'selected' : '' ?>>Marié(e)</option>
                            <option value="Divorcé(e)" <?= isset($locataireEnCours['etat_civil']) && $locataireEnCours['etat_civil'] === 'Divorcé(e)' ? 'selected' : '' ?>>Divorcé(e)</option>
                            <option value="Veuf(ve)" <?= isset($locataireEnCours['etat_civil']) && $locataireEnCours['etat_civil'] === 'Veuf(ve)' ? 'selected' : '' ?>>Veuf(ve)</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Nationalité</label>
                        <input type="text" name="nationalite" id="inputNationalite" placeholder="Ex: Congolaise" value="<?= htmlspecialchars($locataireEnCours['nationalite'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Profession</label>
                        <input type="text" name="profession" id="inputProfession" placeholder="Ex: Ingénieur" value="<?= htmlspecialchars($locataireEnCours['profession'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required id="inputEmail" placeholder="exemple@email.com" value="<?= htmlspecialchars($locataireEnCours['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Téléphone *</label>
                        <input type="text" name="telephone" required id="inputTelephone" placeholder="+243 8X XXX XXXX" value="<?= htmlspecialchars($locataireEnCours['telephone'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Adresse de résidence</label>
                    <input type="text" name="adresse" id="inputAdresse" placeholder="Adresse complète" value="<?= htmlspecialchars($locataireEnCours['adresse'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Photo de profil</label>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
                </div>

                <div class="btn-group">
                    <button type="button" class="btn-cancel" onclick="fermerFormulaire()">Annuler</button>
                    <button type="submit" class="btn-save"><i class="fa fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
/* ── RECHERCHE ── */
function filtrer() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.locataire-card').forEach(card => {
        card.style.display = card.dataset.search.includes(q) ? '' : 'none';
    });
}

/* ── OVERLAY DÉTAIL ── */
function ouvrirDetail(data) {
    const photoBase = '../../../public/uploads/locataires/';
    const initiales = (data.prenom.charAt(0) + data.nom.charAt(0)).toUpperCase();

    const avatarEl = document.getElementById('detailAvatar');
    if (data.photo) {
        avatarEl.innerHTML = `<img src="${photoBase}${data.photo}" alt="photo">`;
    } else {
        avatarEl.innerHTML = `<div class="initiales">${initiales}</div>`;
    }

    document.getElementById('detailNom').textContent      = data.prenom + ' ' + data.nom;
    document.getElementById('detailMatricule').textContent = data.matricule;
    document.getElementById('detailNaissance').textContent = data.date_naissance ?? '—';
    document.getElementById('detailSexe').textContent      = data.sexe ?? '—';
    document.getElementById('detailEtatCivil').textContent = data.etat_civil ?? '—';
    document.getElementById('detailNationalite').textContent = data.nationalite ?? '—';
    document.getElementById('detailProfession').textContent = data.profession ?? '—';
    document.getElementById('detailTelephone').textContent = data.telephone ?? '—';
    document.getElementById('detailEmail').textContent     = data.email ?? '—';
    document.getElementById('detailAdresse').textContent   = data.adresse ?? '—';

    document.getElementById('btnModifier').onclick = () => {
        fermerDetail();
        ouvrirFormulaire(data);
    };

    document.getElementById('btnSupprimer').onclick = () => {
        if (confirm(`Supprimer définitivement ${data.prenom} ${data.nom} ?`)) {
            window.location.href = `../../controllers/LocataireController.php?action=delete&id=${data.id_locataire}`;
        }
    };

    document.getElementById('overlayDetail').classList.add('show');
}

function fermerDetail() {
    document.getElementById('overlayDetail').classList.remove('show');
}

/* ── OVERLAY FORMULAIRE ── */
function ouvrirFormulaire(data = null) {
    const titre = document.getElementById('formTitre');

    if (data) {
        titre.innerHTML = '<i class="fa fa-pen"></i> Modifier le locataire';
        document.getElementById('inputId').value          = data.id_locataire;
        document.getElementById('inputNom').value         = data.nom;
        document.getElementById('inputPrenom').value      = data.prenom;
        document.getElementById('inputNaissance').value   = data.date_naissance ?? '';
        document.getElementById('inputSexe').value        = data.sexe ?? 'Homme';
        document.getElementById('inputEtatCivil').value   = data.etat_civil ?? 'Célibataire';
        document.getElementById('inputNationalite').value = data.nationalite ?? '';
        document.getElementById('inputProfession').value  = data.profession ?? '';
        document.getElementById('inputEmail').value       = data.email ?? '';
        document.getElementById('inputTelephone').value   = data.telephone ?? '';
        document.getElementById('inputAdresse').value     = data.adresse ?? '';
    } else {
        titre.innerHTML = '<i class="fa fa-plus"></i> Nouveau locataire';
        document.getElementById('inputId').value = '';
        document.querySelector('#overlayForm form').reset();
    }

    document.getElementById('overlayForm').classList.add('show');
}

function fermerFormulaire() {
    document.getElementById('overlayForm').classList.remove('show');
    <?php if ($locataireEnCours): ?>
        window.location.href = 'locataire.php';
    <?php endif; ?>
}

/* Fermer en cliquant hors du modal */
document.getElementById('overlayDetail').addEventListener('click', function(e) {
    if (e.target === this) fermerDetail();
});
document.getElementById('overlayForm').addEventListener('click', function(e) {
    if (e.target === this) fermerFormulaire();
});
</script>
</body>
</html>