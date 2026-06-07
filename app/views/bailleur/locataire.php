<?php
// Inclusion du contrôleur avec le bon chemin
require_once '../../controllers/LocataireController.php'; 
$controller = new LocataireController();

// Récupération de la liste complète pour le tableau
$listeLocataires = $controller->getAllLocataires();

// Si une modification est demandée, on récupère les infos du locataire cible
$locataireEnCours = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $locataireEnCours = $controller->getLocataireById($_GET['id']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Locataires - RentMaster</title>
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

        /* BOUTONS D'ACTION HARMONISÉS */
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

        /* ZONE DE BOUTONS INFERIEURE */
        .btn-group { display: flex; justify-content: flex-end; gap: 12px; margin-top: 8px; border-top: 1px solid var(--border-color); padding-top: 20px; }
        .btn-group button, .btn-group a { padding: 11px 20px; border-radius: var(--radius-sm); font-weight: 600; font-size: 14px; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-save { background: var(--primary); color: white; }
        .btn-save:hover { background: var(--primary-light); }
        .btn-cancel { background: #f1f5f9; color: var(--text-muted); justify-content: center; }
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
            <a href="locataire.php" class="active"><i class="fa fa-users"></i> Locataires</a>
            <a href="contrat.php"><i class="fa fa-file-contract"></i> Contrats</a>
            <a href="paiement.php"><i class="fa fa-credit-card"></i> Paiements</a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h1><i class="fa fa-users"></i> Gestion des Locataires</h1>
            <p>Ajoutez, modifiez ou supprimez vos locataires en temps réel dans la base de données.</p>
        </div>

        <div class="actions-top">
            <input type="text" id="searchInput" placeholder="Rechercher par nom, email, téléphone..." onkeyup="filtrerLocataires()">
            <button class="btn-primary" onclick="openForm()">
                <i class="fa fa-plus"></i> Ajouter un locataire
            </button>
        </div>

        <div class="table-container">
            <table id="tableLocataires">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Matricule</th>
                        <th>Nom complet</th>
                        <th>Date de naissance</th>
                        <th>Sexe</th>
                        <th>État civil</th>
                        <th>Nationalité</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Adresse</th>
                        <th>Profession</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($listeLocataires)): ?>
                    <tr>
                        <td colspan="12">
                            <div class="empty-state">
                                <i class="fa-regular fa-folder-open"></i>
                                <p>Aucun locataire enregistré pour le moment.</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach($listeLocataires as $l): ?>
                    <tr>
                        <td><span style="font-weight: 600; color: var(--text-muted);">#<?= htmlspecialchars($l['id_locataire']) ?></span></td>
                        <td><span style="font-weight: 500;"><?= htmlspecialchars($l['matricule']) ?></span></td>
                        <td><strong style="color: var(--text-main);"><?= htmlspecialchars($l['nom'] . ' ' . $l['prenom']) ?></strong></td>
                        <td><?= htmlspecialchars($l['date_naissance']) ?></td>
                        <td><?= htmlspecialchars($l['sexe']) ?></td>
                        <td><?= htmlspecialchars($l['etat_civil']) ?></td>
                        <td><?= htmlspecialchars($l['nationalite']) ?></td>
                        <td><?= htmlspecialchars($l['email']) ?></td>
                        <td><?= htmlspecialchars($l['telephone']) ?></td>
                        <td><?= htmlspecialchars($l['adresse']) ?></td>
                        <td><?= htmlspecialchars($l['profession']) ?></td>
                        <td>
                            <div class="actions-cell">
                                <a href="locataire.php?action=edit&id=<?= $l['id_locataire'] ?>" class="action-btn edit" title="Modifier">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <a href="../../controllers/LocataireController.php?action=delete&id=<?= $l['id_locataire'] ?>" class="action-btn delete" title="Supprimer" onclick="return confirm('Supprimer définitivement ce locataire ?')">
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

<div id="formOverlay" class="form-overlay <?= $locataireEnCours ? 'show' : '' ?>">
    <div class="form-modal">
        <div class="modal-header">
            <h2><?= $locataireEnCours ? '<i class="fa fa-edit"></i> Modifier le profil du locataire' : '<i class="fa fa-plus"></i> Enregistrer un nouveau locataire' ?></h2>
        </div>
        
        <form action="../../controllers/LocataireController.php" method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id_locataire" value="<?= $locataireEnCours['id_locataire'] ?? '' ?>">

                <div class="form-group">
                    <label>Matricule *</label>
                    <input type="text" name="matricule" required value="<?= htmlspecialchars($locataireEnCours['matricule'] ?? '') ?>" placeholder="Ex: LOC001">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nom *</label>
                        <input type="text" name="nom" required value="<?= htmlspecialchars($locataireEnCours['nom'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Prénom *</label>
                        <input type="text" name="prenom" required value="<?= htmlspecialchars($locataireEnCours['prenom'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Date de naissance *</label>
                    <input type="date" name="date_naissance" required value="<?= htmlspecialchars($locataireEnCours['date_naissance'] ?? '') ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Sexe</label>
                        <select name="sexe">
                            <option value="Homme" <?= isset($locataireEnCours['sexe']) && $locataireEnCours['sexe'] === 'Homme' ? 'selected' : '' ?>>Homme</option>
                            <option value="Femme" <?= isset($locataireEnCours['sexe']) && $locataireEnCours['sexe'] === 'Femme' ? 'selected' : '' ?>>Femme</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>État civil</label>
                        <select name="etat_civil">
                            <option value="Célibataire" <?= isset($locataireEnCours['etat_civil']) && $locataireEnCours['etat_civil'] === 'Célibataire' ? 'selected' : '' ?>>Célibataire</option>
                            <option value="Marié(e)" <?= isset($locataireEnCours['etat_civil']) && $locataireEnCours['etat_civil'] === 'Marié(e)' ? 'selected' : '' ?>>Marié(e)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Nationalité</label>
                        <input type="text" name="nationalite" value="<?= htmlspecialchars($locataireEnCours['nationalite'] ?? '') ?>" placeholder="Ex: Congolaise">
                    </div>
                    <div class="form-group">
                        <label>Profession</label>
                        <input type="text" name="profession" value="<?= htmlspecialchars($locataireEnCours['profession'] ?? '') ?>" placeholder="Ex: Cadre IT">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($locataireEnCours['email'] ?? '') ?>" placeholder="exemple@email.com">
                    </div>
                    <div class="form-group">
                        <label>Téléphone *</label>
                        <input type="text" name="telephone" required value="<?= htmlspecialchars($locataireEnCours['telephone'] ?? '') ?>" placeholder="Ex: +24385000000">
                    </div>
                </div>

                <div class="form-group">
                    <label>Adresse de résidence</label>
                    <input type="text" name="adresse" value="<?= htmlspecialchars($locataireEnCours['adresse'] ?? '') ?>" placeholder="Adresse complète actuelle">
                </div>

                <div class="btn-group">
                    <a href="locataire.php" class="btn-cancel"><i class="fa fa-times"></i> Annuler</a>
                    <button type="submit" class="btn-save"><i class="fa fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>
    </div>
</div>

<footer>
    <p>&copy; 2026 RentMaster. Tous droits réservés. | Support : <a href="mailto:divinekatende23@gmail.com">divinekatende23@gmail.com</a> | <a href="https://divinekatende.github.io/DIVKT/" target="_blank">Portfolio</a></p>
</footer>

<script>
function openForm() { 
    document.getElementById('formOverlay').classList.add('show'); 
}

function closeForm() {
    <?php if($locataireEnCours): ?>
        window.location.href = 'locataire.php';
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

function filtrerLocataires() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#tableLocataires tbody tr');
    
    rows.forEach(row => {
        if(row.cells.length > 1) {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(input) ? '' : 'none';
        }
    });
}
</script>
</body>
</html>