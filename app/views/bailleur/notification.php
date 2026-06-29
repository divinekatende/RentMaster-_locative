<?php
require_once __DIR__ . '/../../auth/session.php';
verifierConnexion();

require_once __DIR__ . '/../../controllers/NotificationController.php';

$controller = new NotificationController();

$id_user = $_SESSION['id'];
$type_user = $_SESSION['role']; // 'bailleur' ou 'locataire'

// 1. SYNCHRONISATION AUTOMATIQUE : Génère les alertes Calendrier / Paiement si nécessaire
$controller->synchroniserEvenements($id_user, $type_user);

// 2. Marquer automatiquement les notifications existantes comme lues à l'ouverture de la page
$controller->marquerCommeLu($id_user, $type_user);

// 3. Récupération finale des notifications mises à jour
$notifications = $controller->getNotifications($id_user, $type_user);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centre de Notifications - RentMaster</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        :root {
            --bg-app: #f8fafc;          /* Même fond gris-bleu très clair */
            --surface: #ffffff;         /* Blanc pur */
            --primary: #1e40af;         /* Bleu Cobalt Royal */
            --primary-light: #3b82f6;   /* Bleu accent */
            --primary-soft: #eff6ff;    /* AJOUTÉ : Pour le fond du count-badge */
            --text-main: #0f172a;       /* Noir confort */
            --text-muted: #64748b;      /* Gris ardoise */
            --border-color: #e2e8f0;    /* Lignes de séparation fines */
            --radius-lg: 16px;
            --radius-md: 12px;
            --shadow-sm: 0 4px 6px -1px rgba(15, 23, 42, 0.05);
            --shadow-md: 0 10px 15px -3px rgba(15, 23, 42, 0.08);

            /* Couleurs spécifiques aux statuts d'alertes adoucis */
            --danger-bg: #fef2f2;
            --danger-border: #fee2e2;
            --danger-text: #991b1b;
            --danger-icon: #ef4444;

            --success-bg: #f0fdf4;
            --success-border: #dcfce7;
            --success-text: #166534;
            --success-icon: #22c55e;

            --info-bg: #f0f9ff;
            --info-border: #e0f2fe;
            --info-text: #075985;
            --info-icon: #0ea5e9;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        body { background: var(--bg-app); color: var(--text-main); height: 100vh; display: flex; flex-direction: column; padding: 24px; }

        /* NAV BAR PROFESSIONNELLE */
        nav { background: var(--surface); padding: 14px 28px; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: flex; gap: 28px; margin-bottom: 24px; align-items: center; border: 1px solid var(--border-color); shrink: 0; }
        nav a { text-decoration: none; color: var(--text-muted); font-weight: 500; font-size: 14px; transition: all 0.2s ease; display: flex; align-items: center; gap: 8px; }
        nav a:hover, nav a.active { color: var(--primary); font-weight: 600; }

        /* CONTENEUR CENTRAL ÉLAGUÉ (ÉVITE LE VIDE) */
        .notification-wrapper {
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            flex: 1;
            overflow: hidden;
        }

        .header-panel {
            padding: 24px 30px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-panel h2 { font-size: 20px; font-weight: 700; letter-spacing: -0.02em; }
        .header-panel .count-badge { background: var(--primary-soft); color: var(--primary); font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 20px; border: 1px solid #dbeafe; }

        /* LISTE DÉROULANTE DES ALERTES */
        .notification-list {
            flex: 1;
            overflow-y: auto;
            padding: 20px 30px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            background: #fafafa;
        }

        /* CARD INDIVIDUELLE */
        .notif-card {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 16px 20px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            background: var(--surface);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .notif-card:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03); }

        /* VARIATIONS STYLES SELON LE CONTENU DU TITRE/MESSAGE */
        .notif-card.retard { background: var(--danger-bg); border-color: var(--danger-border); color: var(--danger-text); }
        .notif-card.paiement { background: var(--success-bg); border-color: var(--success-border); color: var(--success-text); }
        .notif-card.message-recu { background: var(--info-bg); border-color: var(--info-border); color: var(--info-text); }

        /* ICÔNES ASSIGNÉES AVEC PRÉCISION */
        .notif-icon-box {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
            background: rgba(15, 23, 42, 0.05);
        }
        .retard .notif-icon-box { background: rgba(239, 68, 68, 0.1); color: var(--danger-icon); }
        .paiement .notif-icon-box { background: rgba(34, 197, 94, 0.1); color: var(--success-icon); }
        .message-recu .notif-icon-box { background: rgba(14, 165, 233, 0.1); color: var(--info-icon); }

        .notif-content { flex: 1; min-width: 0; }
        .notif-content .notif-title { font-weight: 600; font-size: 15px; margin-bottom: 4px; color: inherit; }
        .notif-content .notif-body { font-size: 13.5px; opacity: 0.9; line-height: 1.5; }
        
        .notif-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: space-between;
            gap: 12px;
            flex-shrink: 0;
        }
        .notif-meta .time { font-size: 11px; opacity: 0.6; font-weight: 500; white-space: nowrap; }

        /* BOUTON SUPPRIMER ÉPURÉ */
        .btn-action-delete {
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .btn-action-delete:hover { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        /* EMPTY STATE PROFESSIONNEL */
        .empty-notifications {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            color: var(--text-muted);
            gap: 12px;
            padding: 40px;
        }
        .empty-notifications i { font-size: 52px; color: #cbd5e1; }
        .empty-notifications h3 { font-size: 18px; color: var(--text-main); font-weight: 600; }

        /* MOBILE */
        @media (max-width: 768px) {
            body { padding: 12px; }
            nav { flex-wrap: wrap; gap: 12px; padding: 14px; }
            .header-panel { padding: 16px 20px; }
            .notification-list { padding: 16px; }
            .notif-card { flex-direction: column; gap: 10px; }
            .notif-meta { flex-direction: row; width: 100%; align-items: center; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 10px; }
        }
    </style>
</head>
<body>

    <nav>
        <a href="dashboard.php"><i class="fa-solid fa-chart-pie"></i> Tableau de bord</a>
        <a href="contrat.php"><i class="fa-solid fa-file-signature"></i> Contrats</a>
        <a href="message.php"><i class="fa-solid fa-envelope"></i> Centre de messagerie</a>
        <a href="notification.php" class="active"><i class="fa-solid fa-bell"></i> Notifications</a>
    </nav>

    <div class="notification-wrapper">
        
        <div class="header-panel">
            <h2>Flux de notifications</h2>
            <span class="count-badge"><?= count($notifications) ?> alerte(s)</span>
        </div>

        <div class="notification-list">
            <?php if (empty($notifications)): ?>
                <div class="empty-notifications">
                    <i class="fa-regular fa-bell-slash"></i>
                    <h3>Votre journal est à jour</h3>
                    <p>Aucune notification ni alerte critique n'a été signalée récemment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $n): ?>
                    <?php 
                        // Détection intelligente du type de notification basé sur le contenu ou le titre
                        $class_type = 'info';
                        $icon = 'fa-info-circle';

                        if (stripos($n['titre'], 'retard') !== false || stripos($n['contenu'], 'retard') !== false) {
                            $class_type = 'retard';
                            $icon = 'fa-triangle-exclamation';
                        } elseif (stripos($n['titre'], 'paiement') !== false || stripos($n['titre'], 'reçu') !== false) {
                            $class_type = 'paiement';
                            $icon = 'fa-circle-check';
                        } elseif (stripos($n['titre'], 'message') !== false || stripos($n['contenu'], 'messagerie') !== false) {
                            $class_type = 'message-recu';
                            $icon = 'fa-comments';
                        }
                    ?>
                    
                    <div class="notif-card <?= $class_type ?>">
                        <div class="notif-icon-box">
                            <i class="fa-solid <?= $icon ?>"></i>
                        </div>
                        
                        <div class="notif-content">
                            <div class="notif-title"><?= htmlspecialchars($n['titre']) ?></div>
                            <div class="notif-body"><?= htmlspecialchars($n['contenu']) ?></div>
                        </div>

                        <div class="notif-meta">
                            <span class="time"><?= date('d/m H:i', strtotime($n['created_at'])) ?></span>
                            <button class="btn-action-delete" onclick="supprimerNotification(<?= $n['id'] ?>)" title="Supprimer l'alerte">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <script>
    /* ==========================================================================
       SUPPRESSION ASYNCHRONE D'UNE NOTIFICATION
       ========================================================================== */
    function supprimerNotification(id) {
        if (!confirm("Voulez-vous archiver et supprimer définitivement cette alerte ?")) return;
        
        fetch("delete_notification.php?id=" + id)
        .then(() => {
            location.reload(); 
        })
        .catch(err => console.error("Erreur de suppression :", err));
    }
    </script>
</body>
</html>