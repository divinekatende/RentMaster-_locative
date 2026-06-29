<?php
session_start();
 
if (!isset($_SESSION['id_locataire'])) {
    header('Location: ../../auth/login-locataire.php');
    exit;
}
 
$id_locataire = $_SESSION['id_locataire'];
$nom    = $_SESSION['nom']    ?? '';
$prenom = $_SESSION['prenom'] ?? '';

require_once(__DIR__ . '/../../config/database.php');
 
$contrat_bdd        = null;
$prochaine_echeance = "N/A";

try {
    $database = new Database();
    $conn     = $database->connect();
 
    // AJOUT OPTIMISÉ : Jointure avec 'biens' pour avoir le titre et l'adresse dans le bail
    $stmt = $conn->prepare("
        SELECT c.*, b.titre AS bien_titre, b.adresse AS bien_adresse
        FROM contrats c
        LEFT JOIN biens b ON c.id_bien = b.id_bien
        WHERE c.id_locataire = :id AND c.statut = 'Actif' 
        LIMIT 1
    ");
    $stmt->execute([':id' => $id_locataire]);
    $contrat_bdd = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($contrat_bdd) {
        $stmt2 = $conn->prepare("SELECT mois_annee FROM paiements WHERE id_contrat = :id AND statut = 'Complet'");
        $stmt2->execute([':id' => $contrat_bdd['id_contrat']]);
        $mois_payes = $stmt2->fetchAll(PDO::FETCH_COLUMN);
 
        $debut  = new DateTime($contrat_bdd['date_debut']);
        $fin    = new DateTime($contrat_bdd['date_fin']);
        $cursor = clone $debut;
 
        while ($cursor <= $fin) {
            $mois_str = $cursor->format('Y-m');
            if (!in_array($mois_str, $mois_payes)) {
                $prochaine_echeance = $cursor->format('d M. Y');
                break;
            }
            $cursor->modify('+1 month');
        }
    }
} catch (PDOException $e) {
    $contrat_bdd = null;
}

// Helper pour afficher la répartition
function labelRepartition($val) {
    switch ($val) {
        case 'Bailleur':  return '🏠 Bailleur';
        case 'Les deux':  return '🤝 Les deux parties';
        default:          return '👤 Locataire';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Contrat - RentMaster</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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

        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        body { background: var(--bg-app); color: var(--text-main); min-height:100vh; display:flex; }
        
        /* SIDEBAR */
        aside {
            width:260px; background: var(--surface);
            border-right:1px solid var(--border-color); padding:24px 16px; position:fixed; height:100%;
            display: flex; flex-direction: column; transition:.3s ease; z-index: 99;
        }
        .logo { text-align:center; margin-bottom:32px; display: flex; flex-direction: column; align-items: center; gap: 10px; }
        .logo img { width:70px; height:70px; border-radius:50%; border:3px solid var(--primary-soft); object-fit: cover; }
        .logo h2 { color:var(--primary); font-size: 1.25rem; font-weight:700; letter-spacing: -0.02em; }
        aside nav { flex: 1; display: flex; flex-direction: column; gap: 4px; }
        aside a {
            display:flex; align-items:center; gap:12px; padding:12px 14px;
            border-radius:var(--radius-md); text-decoration:none; color:var(--text-muted); font-weight:500; font-size: 14px; transition:.2s ease;
        }
        aside a:hover { background: var(--primary-soft); color: var(--primary); }
        aside a.active { background:var(--primary); color:white; font-weight:600; }
        aside a i { width: 20px; text-align: center; font-size: 16px; }
        .sidebar-footer { border-top: 1px solid var(--border-color); padding-top: 16px; }
        .sidebar-footer a { color: var(--danger); background: var(--danger-soft); }
        .sidebar-footer a:hover { background: var(--danger); color: white; }

        /* CONTENT */
        .content { margin-left:260px; width:100%; padding:40px; display: flex; flex-direction: column; min-height: 100vh; }
        .top { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
        .top h1 { color:var(--text-main); font-size:24px; font-weight: 700; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; }
        .menu-btn { display:none; width:40px; height:40px; border:1px solid var(--border-color); border-radius:var(--radius-sm); background:var(--surface); color:var(--text-main); font-size:16px; cursor: pointer; }
        
        .alert { background: var(--primary-soft); color: var(--primary); padding:16px 20px; border-radius:var(--radius-md); margin-bottom:20px; font-weight:500; font-size: 14px; display: flex; align-items: center; gap: 10px; border: 1px solid #bfdbfe; }
        .date-box { background: var(--surface); color: var(--text-main); border: 1px solid var(--border-color); padding:18px; border-radius:var(--radius-md); margin-bottom:24px; text-align:center; font-weight:600; font-size:15px; box-shadow: var(--shadow-sm); }
        
        /* CARD DESIGN */
        .card { background: var(--surface); border-radius:var(--radius-lg); padding:24px; margin-bottom:20px; box-shadow:var(--shadow-sm); border: 1px solid var(--border-color); }
        .card h3 { margin-bottom:16px; color:var(--text-main); font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .card h3 i { color:var(--primary); }
        .card p { margin-bottom:12px; color:var(--text-muted); font-size:14px; line-height: 1.5; }
        .card p strong { color: var(--text-main); font-weight: 500; }

        /* TABLEAU RÉPARTITION */
        .repartition-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 14px; }
        .repartition-table th {
            background: var(--primary-soft); color: var(--primary);
            padding: 10px 14px; text-align: left; font-weight: 600; font-size: 12px;
            text-transform: uppercase; letter-spacing: 0.04em;
        }
        .repartition-table th:first-child { border-radius: 8px 0 0 0; }
        .repartition-table th:last-child  { border-radius: 0 8px 0 0; }
        .repartition-table td { padding: 10px 14px; border-bottom: 1px solid var(--border-color); color: var(--text-main); }
        .repartition-table tr:last-child td { border-bottom: none; }
        .repartition-table tr:hover td { background: #f8fafc; }
        
        input[type="file"] { width:100%; padding:12px; border:1px solid var(--border-color); border-radius:var(--radius-md); background:var(--bg-app); margin-bottom:16px; font-size: 14px; }
        
        button { width:100%; border:none; background: var(--primary); color:white; padding:14px; border-radius:var(--radius-md); font-weight:600; font-size:14px; cursor:pointer; transition:.2s ease; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        button:hover { background: var(--primary-light); transform:translateY(-1px); box-shadow: var(--shadow-md); }
        
        .btn-view { background: linear-gradient(135deg, var(--primary), var(--primary-light)); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2); }
        .btn-view:hover { background: var(--primary-light); }

        ul { list-style:none; }
        li { background: var(--bg-app); padding:14px; border-radius:var(--radius-md); margin-bottom:10px; display:flex; justify-content:space-between; align-items:center; border: 1px solid var(--border-color); font-size: 14px; color: var(--text-main); }
        li:hover { background: var(--primary-soft); border-color: #bfdbfe; }
        .delete { color: var(--danger); cursor:pointer; transition:.2s ease; padding: 4px; }
        .delete:hover { transform:scale(1.15); }
        
        .no-contrat { background: var(--danger-soft); border-left: 4px solid var(--danger); padding: 16px 20px; border-radius: var(--radius-md); color: #b91c1c; margin-bottom: 32px; font-weight: 500; font-size: 14px; }
        footer { text-align:center; padding: 24px 0; color: var(--text-muted); font-size: 13px; border-top: 1px solid var(--border-color); margin-top: auto; }

        /* MODALE D'APERÇU DU CONTRAT */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
            display: flex; justify-content: center; align-items: center;
            z-index: 1000; opacity: 0; pointer-events: none; transition: all 0.3s ease;
        }
        .modal-overlay.show { opacity: 1; pointer-events: auto; }
        
        .modal-container {
            background: #f1f5f9; width: 90%; max-width: 850px; height: 90vh;
            border-radius: var(--radius-lg); display: flex; flex-direction: column;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden;
            transform: translateY(20px); transition: all 0.3s ease;
        }
        .modal-overlay.show .modal-container { transform: translateY(0); }
        
        .modal-header {
            background: var(--surface); padding: 16px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex; justify-content: space-between; align-items: center;
        }
        .modal-header h2 { font-size: 16px; font-weight: 600; color: var(--text-main); }
        .header-actions { display: flex; gap: 12px; }
        .btn-modal-close { background: #e2e8f0; color: var(--text-main); width: auto; padding: 10px 16px; }
        .btn-modal-close:hover { background: #cbd5e1; }
        .btn-modal-download { background: var(--success); width: auto; padding: 10px 20px; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2); }
        .btn-modal-download:hover { background: #059669; }

        .modal-body-scroll { flex: 1; overflow-y: auto; padding: 40px 20px; display: flex; justify-content: center; }
        
        /* FEUILLE A4 POUR CONTRAT */
        .paper-page {
            background: var(--surface); width: 100%; max-width: 700px;
            padding: 50px; border-radius: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.03);
            border: 1px solid var(--border-color); color: #1e293b; line-height: 1.6; font-size: 14px;
        }

        @media(max-width:900px){
            aside { left:-280px; }
            aside.show { left:0; box-shadow: var(--shadow-md); }
            .content { margin-left:0; padding:24px 16px; }
            .menu-btn { display:flex; align-items: center; justify-content: center; }
            .top h1 { font-size:22px; }
            .modal-container { width: 96%; height: 95vh; }
            .paper-page { padding: 20px; }
        }
    </style>
</head>
<body>

<aside id="sidebar">
    <div class="logo">
        <img src="../../../public/assets/images/logo.png" alt="logo">
        <h2>RentMaster</h2>
    </div>
    <nav>
        <a href="dashboard_locataire.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="contrat_locataire.php" class="active"><i class="fas fa-file-contract"></i> Contrat</a>
        <a href="paiement_locataire.php"><i class="fas fa-receipt"></i> Reçus</a>
        <a href="historique.php"><i class="fas fa-history"></i> Historique</a>
        <a href="profil_locataire.php"><i class="fas fa-user"></i> Profil</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../../controllers/logout.php"><i class="fa fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</aside>

<div class="content">
    <div class="top">
        <h1><i class="fas fa-file-contract" style="color: var(--primary)"></i> Mon Contrat</h1>
        <button class="menu-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div class="alert">
        <i class="fas fa-circle-info"></i> Consultez régulièrement votre contrat et vos échéances réglementaires.
    </div>

    <div class="date-box">
        📅 Prochaine échéance de paiement : <span style="color: var(--primary); font-weight:700;"><?= htmlspecialchars($prochaine_echeance) ?></span>
    </div>

    <div id="contratInfo">
        <?php if ($contrat_bdd): ?>
            <div class="card">
                <h3><i class="fas fa-file-signature"></i> Informations du bail en cours</h3>
                <p><strong>Numéro de Contrat :</strong> #<?= htmlspecialchars($contrat_bdd['id_contrat']) ?></p>
                <p><strong>Bien rattaché :</strong> <?= htmlspecialchars($contrat_bdd['bien_titre'] ?? 'Non spécifié') ?></p>
                <p><strong>Montant du Loyer :</strong> <?= number_format($contrat_bdd['montant'], 0, ',', ' ') ?> $</p>
                <?php if (!empty($contrat_bdd['charges']) && $contrat_bdd['charges'] > 0): ?>
                <p><strong>Charges locatives :</strong> <?= number_format($contrat_bdd['charges'], 0, ',', ' ') ?> $</p>
                <p><strong>Total mensuel (CC) :</strong> <span style="color: var(--primary); font-weight:700;"><?= number_format($contrat_bdd['montant'] + $contrat_bdd['charges'], 0, ',', ' ') ?> $</span></p>
                <?php endif; ?>
                <p><strong>Date de prise d'effet :</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($contrat_bdd['date_debut']))) ?></p>
                <p><strong>Date de fin de bail :</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($contrat_bdd['date_fin']))) ?></p>
                <p><strong>Statut administratif :</strong>
                    <span style="color: #10b981; font-weight: 600; background: var(--success-soft); padding: 4px 8px; border-radius: 20px; font-size: 12px; border: 1px solid #a7f3d0;">
                        <?= htmlspecialchars($contrat_bdd['statut']) ?>
                    </span>
                </p>
            </div>

            <div class="card">
                <h3><i class="fas fa-scale-balanced"></i> Répartition des charges & impôts</h3>
                <table class="repartition-table">
                    <thead>
                        <tr>
                            <th>Poste</th>
                            <th>Responsable du paiement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><i class="fa fa-droplet" style="color:#3b82f6; margin-right:8px;"></i> Charge eau</td>
                            <td><?= labelRepartition($contrat_bdd['charge_eau'] ?? 'Locataire') ?></td>
                        </tr>
                        <tr>
                            <td><i class="fa fa-bolt" style="color:#f59e0b; margin-right:8px;"></i> Charge électricité</td>
                            <td><?= labelRepartition($contrat_bdd['charge_electricite'] ?? 'Locataire') ?></td>
                        </tr>
                        <tr>
                            <td><i class="fa fa-landmark" style="color:#6366f1; margin-right:8px;"></i> Impôts & taxes</td>
                            <td><?= labelRepartition($contrat_bdd['impot_locataire'] ?? 'Locataire') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h3><i class="fas fa-eye"></i> Consultation du bail officiel</h3>
                <p style="margin-bottom: 16px;">Visualisez directement l'intégralité de votre document de bail signé en cliquant sur le bouton ci-dessous.</p>
                <button onclick="ouvrirApercuContrat()" class="btn-view"><i class="fas fa-file-invoice"></i> Voir et Consulter le contrat</button>
            </div>
        <?php else: ?>
            <div class="no-contrat">
                <i class="fas fa-circle-exclamation"></i> Aucun contrat actif trouvé. Veuillez contacter votre bailleur de toute urgence.
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3><i class="fas fa-paperclip"></i> Ajouter un justificatif ou avenant</h3>
        <input type="file" id="fileInput">
        <button onclick="ajouterDocument()" id="addBtn"><i class="fas fa-plus"></i> Joindre le document</button>
    </div>

    <div class="card">
        <h3><i class="fas fa-folder-open"></i> Vos documents partagés</h3>
        <ul id="listeDocs"></ul>
    </div>

    <footer>
        &copy; 2026 RentMaster - Tous droits réservés
    </footer>
</div>

<?php if ($contrat_bdd): ?>
<div class="modal-overlay" id="contratModal">
    <div class="modal-container">
        <div class="modal-header">
            <h2><i class="fas fa-file-contract" style="color: var(--primary);"></i> Aperçu avant impression du bail</h2>
            <div class="header-actions">
                <button onclick="fermerApercuContrat()" class="btn-modal-close"><i class="fas fa-xmark"></i> Fermer</button>
                <button onclick="telechargerContrat()" id="downloadBtn" class="btn-modal-download"><i class="fas fa-download"></i> Télécharger en PDF</button>
            </div>
        </div>
        <div class="modal-body-scroll">
            
            <div class="paper-page" id="pdf-template">

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #1e40af; padding-bottom: 20px;">
                    <div>
                        <h1 style="color: #1e40af; font-size: 24px; text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">Contrat de Bail</h1>
                        <p style="font-size: 13px; color: #64748b;">Référence officielle : <strong>#Rent-<?= htmlspecialchars($contrat_bdd['id_contrat']) ?></strong></p>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 18px; font-weight: 800; color: #1e40af; letter-spacing: -0.03em;">⭐ RentMaster</div>
                        <div style="font-size: 10px; color: #64748b; text-transform: uppercase;">Platform Contract</div>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <h3 style="color: #1e40af; font-size: 14px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 10px;">1. Désignation du Bailleur</h3>
                    <p style="font-size: 13px; color: #334155;">Le présent contrat est géré et certifié par l'agence mandataire automatisée <strong>RentMaster Real Estate</strong>, agissant en qualité de gestionnaire légal pour le compte du propriétaire bailleur titulaire du patrimoine.</p>
                </div>

                <div style="margin-bottom: 20px;">
                    <h3 style="color: #1e40af; font-size: 14px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 10px;">2. Désignation du Locataire</h3>
                    <p style="font-size: 13px; color: #334155;">Le preneur certifié titulaire est désigné ci-après : <strong>M. / Mme <?= htmlspecialchars($prenom . ' ' . $nom) ?></strong>, inscrit sous la référence locataire unique #<?= htmlspecialchars($id_locataire) ?>.</p>
                </div>

                <div style="margin-bottom: 20px;">
                    <h3 style="color: #1e40af; font-size: 14px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 10px;">3. Description du Bien Immobilier</h3>
                    <p style="font-size: 13px; color: #334155;">Les locaux à usage exclusif d'habitation comprennent l'unité suivante : <strong><?= htmlspecialchars($contrat_bdd['bien_titre'] ?? 'Lot Privatif') ?></strong> situé à l'adresse : <em><?= htmlspecialchars($contrat_bdd['bien_adresse'] ?? 'Adresse enregistrée sur le système') ?></em>. Le bien répond à toutes les normes légales de décence et de sécurité structurelle en vigueur.</p>
                </div>

                <div style="margin-bottom: 20px;">
                    <h3 style="color: #1e40af; font-size: 14px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 10px;">4. Conditions Financières et Durée</h3>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 16px; border-radius: 8px;">
                        <p style="margin-bottom: 8px; font-size: 13px;"><strong>• Loyer mensuel (net hors charges) :</strong> <?= number_format($contrat_bdd['montant'], 2, ',', ' ') ?> $</p>
                        <?php if (!empty($contrat_bdd['charges']) && $contrat_bdd['charges'] > 0): ?>
                        <p style="margin-bottom: 8px; font-size: 13px;"><strong>• Provisions pour charges :</strong> <?= number_format($contrat_bdd['charges'], 2, ',', ' ') ?> $</p>
                        <p style="margin-bottom: 8px; font-size: 13px;"><strong>• Total mensuel charges comprises :</strong> <?= number_format($contrat_bdd['montant'] + $contrat_bdd['charges'], 2, ',', ' ') ?> $</p>
                        <?php endif; ?>
                        <p style="margin-bottom: 8px; font-size: 13px;"><strong>• Date initiale de prise d'effet :</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($contrat_bdd['date_debut']))) ?></p>
                        <p style="margin-bottom: 8px; font-size: 13px;"><strong>• Date d'échéance du terme :</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($contrat_bdd['date_fin']))) ?></p>
                        <p style="font-size: 13px;"><strong>• Statut de la convention :</strong> Validation juridique active (<?= htmlspecialchars($contrat_bdd['statut']) ?>)</p>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <h3 style="color: #1e40af; font-size: 14px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 10px;">5. Répartition des Charges et Impôts</h3>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 0; border-radius: 8px; overflow: hidden;">
                        <table style="width:100%; border-collapse: collapse; font-size: 13px;">
                            <thead>
                                <tr style="background: #eff6ff;">
                                    <th style="padding: 10px 14px; text-align:left; color:#1e40af; font-size:12px; text-transform:uppercase; letter-spacing:0.04em; border-bottom: 1px solid #e2e8f0;">Poste</th>
                                    <th style="padding: 10px 14px; text-align:left; color:#1e40af; font-size:12px; text-transform:uppercase; letter-spacing:0.04em; border-bottom: 1px solid #e2e8f0;">Responsable du paiement</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="padding: 10px 14px; border-bottom: 1px solid #e2e8f0; color:#334155;">💧 Charge eau</td>
                                    <td style="padding: 10px 14px; border-bottom: 1px solid #e2e8f0; color:#334155; font-weight:600;"><?= htmlspecialchars(labelRepartition($contrat_bdd['charge_eau'] ?? 'Locataire')) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 14px; border-bottom: 1px solid #e2e8f0; color:#334155;">⚡ Charge électricité</td>
                                    <td style="padding: 10px 14px; border-bottom: 1px solid #e2e8f0; color:#334155; font-weight:600;"><?= htmlspecialchars(labelRepartition($contrat_bdd['charge_electricite'] ?? 'Locataire')) ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 14px; color:#334155;">🏛️ Impôts & taxes</td>
                                    <td style="padding: 10px 14px; color:#334155; font-weight:600;"><?= htmlspecialchars(labelRepartition($contrat_bdd['impot_locataire'] ?? 'Locataire')) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div style="margin-bottom: 35px;">
                    <h3 style="color: #1e40af; font-size: 14px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 10px;">6. Obligations Générales</h3>
                    <p style="font-size: 12px; text-align: justify; color: #475569; line-height: 1.5;">Le preneur s'oblige expressément à jouir des locaux en bon père de famille, à ne rien faire qui puisse en troubler la tranquillité publique, et à régler les loyers rubis sur l'ongle à chaque échéance mensuelle. En cas de non-paiement prolongé, le contrat de bail sera rompu unilatéralement conformément aux règles d'exploitation.</p>
                </div>

                <div style="margin-top: 50px; display: flex; justify-content: space-between; align-items: flex-start;">
                    <div style="width: 45%;">
                        <p style="font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 45px;">Signature du Preneur (Locataire) :</p>
                        <div style="font-size: 12px; color: #94a3b8; font-style: italic;">Approuvé en ligne par l'usager</div>
                    </div>
                    <div style="width: 45%; text-align: right;">
                        <p style="font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 15px;">Visa Direction Générale :</p>
                        <div style="display: inline-block; border: 2px dashed #10b981; padding: 6px 12px; border-radius: 6px; color: #10b981; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; background: #ecfdf5;">
                            ✅ Validé Numériquement
                        </div>
                    </div>
                </div>

            </div></div>
    </div>
</div>
<?php endif; ?>

<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle("show");
}

function ouvrirApercuContrat() {
    document.getElementById('contratModal').classList.add('show');
}

function fermerApercuContrat() {
    document.getElementById('contratModal').classList.remove('show');
}

/* DOCUMENTS COMPLÉMENTAIRES */
let documents = [];
try { documents = JSON.parse(localStorage.getItem("documents")) || []; } catch(e){}

function afficherDocs(){
    let ul = document.getElementById("listeDocs");
    ul.innerHTML = "";
    if(documents.length === 0){
        ul.innerHTML = "<p style='color: var(--text-muted); font-size: 14px;'>Aucun document additionnel téléversé.</p>";
        return;
    }
    documents.forEach((doc,i)=>{
        ul.innerHTML += `<li><span><i class="fas fa-file-alt" style="color: var(--primary-light); margin-right: 8px;"></i> ${doc}</span> <i class="fas fa-trash-can delete" onclick="supprimerDoc(${i})"></i></li>`;
    });
}

function ajouterDocument(){
    let file = document.getElementById("fileInput").files[0];
    if(!file){ alert("Veuillez sélectionner un fichier au préalable !"); return; }
    documents.push(file.name);
    try { localStorage.setItem("documents", JSON.stringify(documents)); } catch(e){}
    afficherDocs();
    let btn = document.getElementById("addBtn");
    btn.innerHTML = "<i class='fas fa-check'></i> Document ajouté";
    btn.style.background = "#10b981";
    setTimeout(()=>{ btn.innerHTML = "<i class='fas fa-plus'></i> Joindre le document"; btn.style.background = "var(--primary)"; }, 2000);
}

function supprimerDoc(i){
    documents.splice(i,1);
    try { localStorage.setItem("documents", JSON.stringify(documents)); } catch(e){}
    afficherDocs();
}

function telechargerContrat(){
    let btn = document.getElementById("downloadBtn");
    let element = document.getElementById('pdf-template');
    btn.innerHTML = "<i class='fas fa-spinner fa-spin'></i> Téléchargement...";
    btn.style.opacity = "0.7";
    let opt = {
        margin:       12,
        filename:     'Contrat_Bail_RentMaster_<?= htmlspecialchars($id_locataire) ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).toPdf().get('pdf').then(function() {
        btn.innerHTML = "<i class='fas fa-download'></i> Télécharger en PDF";
        btn.style.opacity = "1";
    }).save();
}

afficherDocs();
</script>
</body>
</html>