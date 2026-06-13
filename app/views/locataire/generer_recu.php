<?php
session_start();
require_once(__DIR__ . '/../../config/database.php');

// 1. Sécurité : Vérifier si le locataire est connecté
if (!isset($_SESSION['id_locataire'])) {
    die("Accès refusé. Veuillez vous connecter.");
}

// 2. Vérifier si l'ID du paiement est fourni
if (!isset($_GET['id_paiement']) || empty($_GET['id_paiement'])) {
    die("ID du paiement manquant.");
}

$id_locataire = $_SESSION['id_locataire'];
$id_paiement = intval($_GET['id_paiement']);

$conn = (new Database())->connect();

try {
    // 3. Récupérer les détails avec tes vraies colonnes (prix/loyer de la table biens)
    $stmt = $conn->prepare("
        SELECT 
            p.id_paiement, p.montant_verse, p.mois_annee, p.date_paiement, p.statut AS paiement_statut,
            l.nom AS locataire_nom, l.prenom AS locataire_prenom,
            b.titre AS nom_bien, b.adresse AS bien_adresse,
            ba.nom AS bailleur_nom, ba.prenom AS bailleur_prenom
        FROM paiements p
        INNER JOIN contrats c ON p.id_contrat = c.id_contrat
        INNER JOIN locataires l ON c.id_locataire = l.id_locataire
        INNER JOIN biens b ON c.id_bien = b.id_bien
        INNER JOIN bailleurs ba ON b.id_bailleur = ba.id_bailleur
        WHERE p.id_paiement = :id_paiement AND l.id_locataire = :id_locataire
        LIMIT 1
    ");
    
    $stmt->execute([
        ':id_paiement' => $id_paiement,
        ':id_locataire' => $id_locataire
    ]);
    
    $recu = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si aucun reçu ne correspond (Sécurité)
    if (!$recu) {
        die("Reçu introuvable ou vous n'avez pas l'autorisation de le consulter.");
    }

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// Formatage de la date
$date_facture = date('d/m/Y', strtotime($recu['date_paiement']));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recu_RentMaster_#<?= $recu['id_paiement'] ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #1e40af;
            --primary-soft: #eff6ff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            
            --success: #10b981;
            --success-soft: #ecfdf5;
            --warning: #d97706;
            --warning-soft: #fffbeb;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        body { background: #f8fafc; padding: 40px 20px; color: var(--text-main); display: flex; flex-direction: column; align-items: center; min-height: 100vh; }
        
        /* BARRE D'ACTIONS ÉCRAN */
        .actions-bar { width: 100%; max-width: 800px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .btn { padding: 12px 20px; border: 1px solid var(--border-color); border-radius: 12px; cursor: pointer; font-weight: 600; text-decoration: none; font-size: 14px; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 8px; }
        .btn-print { background: var(--primary); color: white; border-color: var(--primary); box-shadow: 0 4px 10px rgba(30, 64, 175, 0.15); }
        .btn-print:hover { background: #3b82f6; transform: translateY(-1px); }
        .btn-back { background: white; color: var(--text-main); }
        .btn-back:hover { background: var(--bg-app); border-color: var(--text-muted); }

        /* RECU / QUITTANCE CONTAINER */
        .invoice-box { 
            background: white; width: 100%; max-width: 800px; padding: 50px; 
            border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.04); 
            border: 1px solid var(--border-color); position: relative; overflow: hidden;
        }
        
        /* EN-TÊTE AVEC LOGO */
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--border-color); padding-bottom: 32px; margin-bottom: 35px; }
        .logo-area { display: flex; align-items: center; gap: 14px; }
        .logo-area img { width: 64px; height: 64px; border-radius: 50%; border: 3px solid var(--primary-soft); object-fit: cover; }
        .logo-text h1 { color: var(--primary); font-size: 24px; font-weight: 800; letter-spacing: -0.02em; }
        .logo-text p { color: var(--text-muted); font-size: 13px; font-weight: 500; margin-top: 1px; }
        
        .invoice-title { text-align: right; }
        .invoice-title h2 { font-size: 20px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; color: var(--text-main); }
        .invoice-title .ref-badge { display: inline-block; background: var(--bg-app); border: 1px solid var(--border-color); padding: 6px 12px; border-radius: 8px; color: var(--text-main); font-size: 13px; margin-top: 10px; }

        /* METADONNÉES PARTIES */
        .details-section { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 40px; }
        .details-block { background: #fafafa; padding: 16px; border-radius: 12px; border: 1px solid var(--border-color); }
        .details-block h3 { font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--primary); letter-spacing: 0.05em; margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px dashed var(--border-color); }
        .details-block p { font-size: 13px; line-height: 1.5; color: var(--text-main); }
        .details-block p strong { font-weight: 600; color: var(--text-main); }

        /* ZONE DU BIEN IMMOBILIER */
        .property-block { background: var(--primary-soft); border: 1px solid #bfdbfe; padding: 20px; border-radius: 12px; margin-bottom: 35px; display: flex; align-items: center; gap: 16px; }
        .property-icon { width: 44px; height: 44px; background: white; border-radius: 10px; color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 18px; border: 1px solid #93c5fd; }
        .property-info h4 { font-size: 14px; font-weight: 600; color: var(--primary); }
        .property-info p { font-size: 13px; color: var(--text-main); margin-top: 2px; }

        /* TABLEAU COMPTABLE */
        table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        table th { background: #f8fafc; text-align: left; padding: 14px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid var(--border-color); letter-spacing: 0.02em; }
        table td { padding: 18px 16px; font-size: 14px; border-bottom: 1px solid var(--border-color); color: var(--text-main); }
        
        .total-row td { border-top: 2px solid var(--border-color); font-weight: 700; background: #f8fafc; font-size: 15px; }
        .total-amount { color: var(--primary) !important; font-size: 16px; }

        /* FOOTER DE CONFORMITÉ */
        .footer-note { text-align: center; font-size: 13px; color: var(--text-muted); border-top: 1px solid var(--border-color); padding-top: 24px; line-height: 1.6; }
        .footer-note p:last-child { margin-top: 8px; font-size: 11px; color: #94a3b8; }
        
        /* TAMPON DYNAMIQUE REVISE ET PROPRE */
        .stamp { 
            position: absolute; bottom: 120px; right: 50px; 
            border: 3px double <?= $recu['paiement_statut'] === 'Acompte' ? 'var(--warning)' : 'var(--success)' ?>; 
            color: <?= $recu['paiement_statut'] === 'Acompte' ? 'var(--warning)' : 'var(--success)' ?>; 
            background: <?= $recu['paiement_statut'] === 'Acompte' ? 'var(--warning-soft)' : 'var(--success-soft)' ?>;
            font-weight: 800; text-transform: uppercase; padding: 8px 18px; font-size: 16px; letter-spacing: 0.05em;
            transform: rotate(-12deg); border-radius: 8px; opacity: 0.9; pointer-events: none;
        }

        /* CONFIGURATION IMPRESSION / PDF */
        @media print {
            body { background: white; padding: 0; }
            .actions-bar { display: none; }
            .invoice-box { border: none; box-shadow: none; padding: 0; width: 100%; max-width: 100%; }
            .details-block { background: #fff !important; }
            .property-block { background: #fff !important; border: 1px solid var(--border-color) !important; }
        }
    </style>
</head>
<body>

    <div class="actions-bar">
        <a href="paiement_locataire.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Retour aux reçus</a>
        <button onclick="window.print()" class="btn btn-print"><i class="fas fa-print"></i> Imprimer / Exporter PDF</button>
    </div>

    <div class="invoice-box">
        
        <div class="stamp"><?= htmlspecialchars($recu['paiement_statut']) ?></div>

        <div class="header">
            <div class="logo-area">
                <img src="../../../public/assets/images/logo.png" alt="Logo RentMaster">
                <div class="logo-text">
                    <h1>RentMaster</h1>
                    <p>Gestion locative automatisée</p>
                </div>
            </div>
            <div class="invoice-title">
                <h2>Quittance de Loyer</h2>
                <div class="ref-badge">Référence : <strong>#REC-2026-<?= $recu['id_paiement'] ?></strong></div>
            </div>
        </div>

        <div class="details-section">
            <div class="details-block">
                <h3>Propriétaire / Bailleur</h3>
                <p><strong><?= htmlspecialchars($recu['bailleur_prenom'] . ' ' . $recu['bailleur_nom']) ?></strong></p>
                <p style="color: var(--text-muted); margin-top: 4px;">Émetteur du présent reçu</p>
            </div>
            <div class="details-block">
                <h3>Locataire</h3>
                <p><strong><?= htmlspecialchars($recu['locataire_prenom'] . ' ' . $recu['locataire_nom']) ?></strong></p>
                <p style="color: var(--text-muted); margin-top: 4px;">Occupant désigné du bien</p>
            </div>
            <div class="details-block">
                <h3>Émission</h3>
                <p><strong>Date d'encaissement :</strong></p>
                <p><?= $date_facture ?></p>
            </div>
        </div>

        <div class="property-block">
            <div class="property-icon">
                <i class="fas fa-building"></i>
            </div>
            <div class="property-info">
                <h4>Désignation du bien immobilier concerné</h4>
                <p><strong><?= htmlspecialchars($recu['nom_bien']) ?></strong> — <?= htmlspecialchars($recu['bien_adresse']) ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Désignation des prestations libellées</th>
                    <th>Période concernée</th>
                    <th style="text-align: right;">Montant Validé</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Versement au titre du paiement partiel ou total du loyer charges comprises</td>
                    <td style="color: var(--text-muted);">Mois de <?= htmlspecialchars($recu['mois_annee']) ?></td>
                    <td style="text-align: right; font-weight: 600;"><?= number_format($recu['montant_verse'], 2, ',', ' ') ?> $</td>
                </tr>
                <tr class="total-row">
                    <td>Total net perçu</td>
                    <td></td>
                    <td style="text-align: right;" class="total-amount"><?= number_format($recu['montant_verse'], 2, ',', ' ') ?> $</td>
                </tr>
            </tbody>
        </table>

        <div class="footer-note">
            <p>Cette quittance certifie le paiement des sommes indiquées pour la période mentionnée ci-dessus.<br>Le présent document vaut reçu sous réserve d'encaissement définitif de la provision bancaire.</p>
            <p>Document généré électroniquement par l'application RentMaster le <?= date('d/m/Y à H:i') ?></p>
        </div>

    </div>

</body>
</html>