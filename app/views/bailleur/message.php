<?php
require_once __DIR__ . '/../../auth/session.php';
verifierConnexion();

require_once __DIR__ . '/../../controllers/MessageController.php';

$controller = new MessageController();

$id_user = $_SESSION['id'];
$type_user = $_SESSION['role']; 

/* ==========================================================================
   TRAITEMENT DE L'ENVOI (POST)
   ========================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destinataire_id = $_POST['destinataire_id'] ?? '';
    $contenu = trim($_POST['contenu'] ?? '');

    if (!empty($contenu) && !empty($destinataire_id)) {
        $dest_type = ($type_user === 'bailleur') ? 'locataire' : 'bailleur';
        $controller->envoyer($id_user, $type_user, $destinataire_id, $dest_type, $contenu);
    }

    header("Location: message.php?active_contact=" . urlencode($destinataire_id));
    exit;
}

/* ==========================================================================
   RÉCUPÉRATION DES DONNÉES
   ========================================================================== */
$messages = $controller->getMessages($id_user, $type_user);
$contacts = $controller->getContacts($id_user, $type_user);

// Déterminer quel contact est ouvert par défaut
$active_contact_id = $_GET['active_contact'] ?? ($contacts[0]['id'] ?? null);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie Professionnelle - RentMaster</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        :root {
            --bg-app: #f8fafc;          /* Fond de page gris-bleu très clair rafraîchissant */
            --surface: #ffffff;         /* Surfaces blanches des composants */
            --primary: #1e40af;         /* Bleu Cobalt Royal (Sérieux & Corporate) */
            --primary-light: #3b82f6;   /* Bleu d'accentuation moderne */
            --primary-soft: #eff6ff;    /* Fond bleu très doux pour les éléments sélectionnés */
            --text-main: #0f172a;       /* Texte principal presque noir pour le confort visuel */
            --text-muted: #64748b;      /* Texte secondaire gris ardoise */
            --border-color: #e2e8f0;    /* Lignes de séparation fines et discrètes */
            --bubble-received: #f1f5f9; /* Bulle grise épurée pour les messages reçus */
            --radius-lg: 16px;
            --radius-md: 12px;
            --shadow-sm: 0 4px 6px -1px rgba(15, 23, 42, 0.05), 0 2px 4px -2px rgba(15, 23, 42, 0.05);
            --shadow-md: 0 10px 15px -3px rgba(15, 23, 42, 0.08);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        body { background: var(--bg-app); color: var(--text-main); height: 100vh; display: flex; flex-direction: column; padding: 24px; }

        /* NAV BAR PROFESSIONNELLE */
        nav { background: var(--surface); padding: 14px 28px; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: flex; gap: 28px; margin-bottom: 24px; align-items: center; border: 1px solid var(--border-color); }
        nav a { text-decoration: none; color: var(--text-muted); font-weight: 500; font-size: 14px; transition: all 0.2s ease; display: flex; align-items: center; gap: 8px; }
        nav a:hover, nav a.active { color: var(--primary); font-weight: 600; }

        /* ARCHITECTURE DE LA MESSAGERIE */
        .messaging-container {
            display: flex;
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            flex: 1;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        /* PANNEAU LATÉRAL : LISTE DE CONTACTS */
        .sidebar-contacts {
            width: 350px;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            background: #fcfdfe;
        }
        .sidebar-search-area {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            background: var(--surface);
        }
        .sidebar-search-area h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }
        
        .contacts-scrollable {
            flex: 1;
            overflow-y: auto;
        }
        .contact-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px;
            cursor: pointer;
            border-bottom: 1px solid #f8fafc;
            transition: all 0.2s ease;
        }
        .contact-card:hover { background: #f8fafc; }
        .contact-card.active { background: var(--primary-soft); border-left: 4px solid var(--primary); }
        
        /* AVATAR PRO */
        .avatar-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            color: #334155;
            flex-shrink: 0;
        }
        .contact-card.active .avatar-circle {
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            color: white;
        }

        .contact-meta { flex: 1; min-width: 0; }
        .contact-meta .full-name { font-weight: 600; font-size: 14.5px; margin-bottom: 3px; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .contact-meta .user-role { font-size: 11px; color: var(--text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: 0.03em; }

        /* ESPACE DE DISCUSSION (DROITE) */
        .chat-view {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fafafa;
        }

        /* EN-TÊTE DU CHAT */
        .chat-view-header {
            padding: 16px 24px;
            background: var(--surface);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-user-info { display: flex; align-items: center; gap: 12px; }
        .header-user-info .active-title { font-weight: 600; font-size: 16px; color: var(--text-main); }
        .status-tag { font-size: 12px; color: #10b981; display: flex; align-items: center; gap: 6px; font-weight: 500; }

        /* ZONE DES BULLES */
        .chat-body-timeline {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .bubble {
            padding: 12px 18px;
            border-radius: 14px;
            max-width: 60%;
            word-wrap: break-word;
            font-size: 14px;
            line-height: 1.5;
            position: relative;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }
        .bubble-time-info { display: flex; align-items: center; justify-content: flex-end; gap: 8px; font-size: 10px; margin-top: 6px; opacity: 0.7; }

        /* MESSAGE ENVOYÉ (BLEU) */
        .bubble.outgoing {
            background: var(--primary);
            color: #ffffff;
            align-self: flex-end;
            border-top-right-radius: 4px;
        }
        /* MESSAGE REÇU (GRIS) */
        .bubble.incoming {
            background: var(--bubble-received);
            color: var(--text-main);
            align-self: flex-start;
            border-top-left-radius: 4px;
            border: 1px solid #e2e8f0;
        }

        .action-delete { background: transparent; border: none; color: #f87171; font-size: 10px; cursor: pointer; text-decoration: none; font-weight: 500; }
        .action-delete:hover { color: #ef4444; text-decoration: underline; }

        /* BARRE DE TEXTE EN PIED DE PAGE */
        .chat-view-footer {
            padding: 18px 24px;
            background: var(--surface);
            border-top: 1px solid var(--border-color);
        }
        #formMessage {
            display: flex;
            gap: 14px;
            align-items: center;
        }
        #messageText {
            flex: 1;
            padding: 14px 20px;
            border-radius: 30px;
            border: 1px solid var(--border-color);
            background: #f8fafc;
            height: 48px;
            outline: none;
            font-size: 14px;
            color: var(--text-main);
            transition: all 0.2s;
        }
        #messageText:focus {
            border-color: var(--primary-light);
            background: var(--surface);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        /* BOUTON ENVOYER CYLINDRIQUE */
        #formMessage button {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: none;
            background: var(--primary);
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(30, 64, 175, 0.2);
        }
        #formMessage button:hover { background: var(--primary-light); transform: translateY(-1px); }

        /* INTERFACE PAR DÉFAUT VIDE */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            color: var(--text-muted);
            gap: 12px;
            background: #f8fafc;
        }
        .empty-state i { font-size: 48px; color: #cbd5e1; }
        .empty-state h3 { font-size: 18px; color: var(--text-main); font-weight: 600; }

        /* RESPONSIVE FLUIDE */
        @media (max-width: 768px) {
            body { padding: 12px; }
            .messaging-container { flex-direction: column; }
            .sidebar-contacts { width: 100%; height: 240px; border-right: none; border-bottom: 1px solid var(--border-color); }
            .chat-view { height: calc(100vh - 360px); }
        }
    </style>
</head>
<body>

    <nav>
        <a href="dashboard.php"><i class="fa-solid fa-chart-pie"></i> Tableau de bord</a>
        <a href="contrat.php"><i class="fa-solid fa-file-signature"></i> Contrats</a>
        <a href="message.php" class="active"><i class="fa-solid fa-envelope"></i> Centre de messagerie</a>
        <a href="notification.html"><i class="fa-solid fa-bell"></i> Notifications</a>
    </nav>

    <div class="messaging-container">
        
        <div class="sidebar-contacts">
            <div class="sidebar-search-area">
                <h2>Discussions</h2>
            </div>
            <div class="contacts-scrollable">
                <?php if (empty($contacts)): ?>
                    <p style="padding: 24px; font-size: 13px; color: var(--text-muted); text-align: center;">Aucun collaborateur trouvé.</p>
                <?php else: ?>
                    <?php foreach ($contacts as $c): ?>
                        <?php $initials = strtoupper(substr($c['prenom'], 0, 1) . substr($c['nom'], 0, 1)); ?>
                        <div class="contact-card <?= ($c['id'] == $active_contact_id) ? 'active' : '' ?>" 
                             onclick="selectContact(<?= $c['id'] ?>)">
                            <div class="avatar-circle"><?= $initials ?></div>
                            <div class="contact-meta">
                                <div class="full-name"><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></div>
                                <div class="user-role"><?= $type_user === 'bailleur' ? 'Locataire' : 'Propriétaire' ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="chat-view">
            <?php if ($active_contact_id): ?>
                
                <div class="chat-view-header">
                    <div class="header-user-info">
                        <div class="avatar-circle" id="activeAvatar" style="background: var(--primary); color: white;">??</div>
                        <div>
                            <div class="active-title" id="activeName">Chargement...</div>
                            <div class="status-tag"><i class="fa-solid fa-circle" style="font-size: 7px;"></i> Dossier Actif</div>
                        </div>
                    </div>
                </div>

                <div class="chat-body-timeline" id="messagesContainer">
                    <?php foreach ($messages as $m): ?>
                        <?php 
                        $is_sender = ($m['expediteur_id'] == $id_user && $m['expediteur_type'] === $type_user);
                        $is_related = ($is_sender && $m['destinataire_id'] == $active_contact_id) || 
                                      (!$is_sender && $m['expediteur_id'] == $active_contact_id);
                        
                        if (!$is_related) continue; 
                        ?>
                        
                        <div class="bubble <?= $is_sender ? 'outgoing' : 'incoming' ?>">
                            <?= htmlspecialchars($m['contenu']) ?>
                            <div class="bubble-time-info">
                                <span><?= date('H:i', strtotime($m['date_message'])) ?></span>
                                <?php if ($is_sender): ?>
                                    <span>•</span>
                                    <button class="action-delete" onclick="supprimerMessage(<?= $m['id_message'] ?>)">Supprimer</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="chat-view-footer">
                    <form id="formMessage" method="POST">
                        <input type="hidden" name="destinataire_id" id="destinataire_id" value="<?= $active_contact_id ?>">
                        <input type="text" name="contenu" id="messageText" placeholder="Rédiger votre message officiel..." required autocomplete="off">
                        <button type="submit"><i class="fa-solid fa-paper-plane"></i></button>
                    </form>
                </div>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-regular fa-comments"></i>
                    <h3>Vos correspondances</h3>
                    <p>Sélectionnez un profil dans le volet de gauche pour charger l'historique des échanges.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <script>
    const activeContactId = <?= json_encode($active_contact_id) ?>;

    function scrollToBottom() {
        const container = document.getElementById("messagesContainer");
        if(container) container.scrollTop = container.scrollHeight;
    }

    function selectContact(id) {
        window.location.href = "message.php?active_contact=" + id;
    }

    document.addEventListener("DOMContentLoaded", () => {
        const activeItem = document.querySelector(".contact-card.active");
        if(activeItem) {
            const name = activeItem.querySelector(".full-name").innerText;
            const avatarTxt = activeItem.querySelector(".avatar-circle").innerText;
            
            document.getElementById("activeName").innerText = name;
            document.getElementById("activeAvatar").innerText = avatarTxt;
        }
        scrollToBottom();
    });

    function supprimerMessage(id) {
        if (!confirm("Confirmez-vous la suppression irréversible de cette entrée ?")) return;
        fetch("delete_message.php?id=" + id).then(() => location.reload());
    }
    </script>
</body>
</html>