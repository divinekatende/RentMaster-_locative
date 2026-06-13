<?php
session_start();
 
// 1. Sécurité : locataire connecté obligatoire
if (!isset($_SESSION['id_locataire'])) {
    header('Location: ../../auth/login-locataire.php');
    exit;
}
 
$id_locataire = $_SESSION['id_locataire'];
$nom     = $_SESSION['nom']    ?? '';
$prenom = $_SESSION['prenom'] ?? '';

// ===========================================================
// CONNEXION BDD POUR RÉCUPÉRER L'HISTORIQUE DES PAIEMENTS
// ===========================================================
require_once(__DIR__ . '/../../config/database.php');
 
$liste_paiements = [];

try {
    $database = new Database();
    $conn = $database->connect();
 
    // Récupérer tous les paiements du locataire via son ou ses contrats
    $stmt = $conn->prepare("
        SELECT p.* FROM paiements p
        INNER JOIN contrats c ON p.id_contrat = c.id_contrat
        WHERE c.id_locataire = :id_locataire
        ORDER BY p.id_paiement DESC
    ");
    $stmt->execute([':id_locataire' => $id_locataire]);
    $liste_paiements = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $liste_paiements = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Paiements - RentMaster</title>
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
            --warning: #f59e0b;
            --warning-soft: #fffbeb;
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
        .top { display:flex; justify-content:space-between; align-items:center; margin-bottom:32px; }
        .top h1 { color:var(--text-main); font-size:24px; font-weight: 700; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; }
        .menu-btn { display:none; width:40px; height:40px; border:1px solid var(--border-color); border-radius:var(--radius-sm); background:var(--surface); color:var(--text-main); font-size:16px; cursor: pointer; }
        
        /* CONTAINER DES TRANSACTIONS */
        .table-container { background: var(--surface); padding: 8px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); }
        
        /* ITEM TRANSACTION */
        .transaction-item { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 20px; border-bottom: 1px solid var(--border-color); transition: all 0.2s ease;
            border-radius: var(--radius-md); margin-bottom: 4px;
        }
        .transaction-item:last-child { border-bottom: none; margin-bottom: 0; }
        .transaction-item:hover { background: var(--bg-app); transform: translateX(4px); }
        
        .transaction-info { display: flex; align-items: center; gap: 20px; }
        .transaction-icon { 
            width: 48px; height: 48px; background: var(--primary-soft); color: var(--primary); 
            border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 18px;
            border: 1px solid #dbeafe;
        }
        .transaction-details h4 { color: var(--text-main); font-size: 15px; font-weight: 600; }
        .transaction-details p { color: var(--text-muted); font-size: 13px; margin-top: 4px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        
        .meta-separator { color: var(--border-color); }

        /* BADGES DE STATUT ACCORDÉS */
        .status-badge {
            font-weight: 600; font-size: 11px; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.02em;
        }
        .status-complet { background: var(--success-soft); color: var(--success); border: 1px solid #a7f3d0; }
        .status-acompte { background: var(--warning-soft); color: var(--warning); border: 1px solid #fde68a; }
        .status-refuse { background: var(--danger-soft); color: var(--danger); border: 1px solid #fca5a5; }
        
        /* MONTANT FIXE À DROITE */
        .transaction-amount { font-size: 16px; font-weight: 700; color: var(--text-main); text-align: right; }

        /* EMPTY STATE */
        .empty { text-align: center; padding: 60px 20px; color: var(--text-muted); }
        .empty i { font-size: 48px; color: var(--primary-light); margin-bottom: 20px; background: var(--primary-soft); width: 80px; height: 80px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; }
        .empty h2 { color: var(--text-main); font-size: 16px; font-weight: 600; margin-bottom: 6px; }
        .empty p { font-size: 14px; max-width: 400px; margin: 0 auto; }

        footer { text-align:center; padding: 24px 0; color: var(--text-muted); font-size: 13px; border-top: 1px solid var(--border-color); margin-top: auto; }

        @media(max-width:900px){
            aside { left:-280px; }
            aside.show { left:0; box-shadow: var(--shadow-md); }
            .content { margin-left:0; padding:24px 16px; }
            .menu-btn { display:flex; align-items: center; justify-content: center; }
            .top h1 { font-size:22px; }
            .transaction-item { flex-direction: column; align-items: flex-start; gap: 14px; padding: 16px; }
            .transaction-item:hover { transform: none; }
            .transaction-amount { width: 100%; text-align: left; font-size: 15px; border-top: 1px dashed var(--border-color); padding-top: 10px; }
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
        <a href="contrat_locataire.php"><i class="fas fa-file-contract"></i> Contrat</a>
        <a href="paiement_locataire.php"><i class="fas fa-receipt"></i> Reçus</a>
        <a href="historique.php" class="active"><i class="fas fa-history"></i> Historique</a>
        <a href="profil_locataire.php"><i class="fas fa-user"></i> Profil</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../../controllers/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</aside>

<div class="content">
    <div class="top">
        <h1><i class="fas fa-history" style="color: var(--primary)"></i> Historique des Paiements</h1>
        <button class="menu-btn" onclick="toggleMenu()">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <div class="table-container" id="historiqueContainer">
        <?php if (empty($liste_paiements)): ?>
            <div class="empty">
                <i class="fas fa-receipt"></i>
                <h2>Aucun paiement enregistré</h2>
                <p>Vos transactions passées et règlements de loyer s'afficheront ici.</p>
            </div>
        <?php else: ?>
            <?php foreach ($liste_paiements as $p): 
                // Gestion sémantique et visuelle des statuts
                $classe_statut = match($p['statut']) {
                    'Complet', 'Payé' => 'status-complet',
                    'Acompte'         => 'status-acompte',
                    default           => 'status-refuse',
                };
            ?>
                <div class="transaction-item">
                    <div class="transaction-info">
                        <div class="transaction-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="transaction-details">
                            <h4>Loyer — Période de <?= htmlspecialchars($p['mois_annee']) ?></h4>
                            <p>
                                <span><strong>Méthode :</strong> <?= htmlspecialchars($p['moyen_paiement'] ?? 'Non spécifié') ?></span>
                                <span class="meta-separator">|</span>
                                <span><strong>Date :</strong> <?= isset($p['date_paiement']) ? htmlspecialchars(date('d/m/Y à H:i', strtotime($p['date_paiement']))) : 'N/A' ?></span>
                                <span class="meta-separator">|</span>
                                <span class="status-badge <?= $classe_statut ?>"><?= htmlspecialchars($p['statut']) ?></span>
                            </p>
                        </div>
                    </div>
                    <div class="transaction-amount">
                        <?= number_format($p['montant_verse'], 0, ',', ' ') ?> $
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <footer>
        &copy; 2026 RentMaster - Tous droits réservés
    </footer>
</div>

<script>
function toggleMenu(){
    document.getElementById('sidebar').classList.toggle("show");
}
</script>
</body>
</html>