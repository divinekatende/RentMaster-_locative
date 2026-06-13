<?php
session_start();
require_once(__DIR__ . '/../../config/database.php');

// 1. Déterminer qui est connecté (Bailleur ou Locataire)
$id_utilisateur = null;
$type_utilisateur = null;

if (isset($_SESSION['id_bailleur'])) {
    $id_utilisateur = $_SESSION['id_bailleur'];
    $type_utilisateur = 'bailleur';
} elseif (isset($_SESSION['id_locataire'])) {
    $id_utilisateur = $_SESSION['id_locataire'];
    $type_utilisateur = 'locataire';
} else {
    die("Accès refusé. Veuillez vous connecter.");
}

$conn = (new Database())->connect();

/* ==========================================================================
   TRAITEMENT DE L'ENVOI (POST)
   ========================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'envoyer') {
    $destinataire_id = intval($_POST['destinataire_id'] ?? 0);
    $destinataire_type = ($type_utilisateur === 'bailleur') ? 'locataire' : 'bailleur';
    $contenu = trim($_POST['contenu'] ?? '');

    if (!empty($contenu) && $destinataire_id > 0) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO messages (expediteur_id, expediteur_type, destinataire_id, destinataire_type, contenu, date_message)
                VALUES (:exp_id, :exp_type, :dest_id, :dest_type, :contenu, NOW())
            ");
            $stmt->execute([
                ':exp_id' => $id_utilisateur,
                ':exp_type' => $type_utilisateur,
                ':dest_id' => $destinataire_id,
                ':dest_type' => $destinataire_type,
                ':contenu' => $contenu
            ]);
            header("Location: messagerie.php?contact_id=" . $destinataire_id);
            exit;
        } catch (PDOException $e) {
            $erreur = "Erreur d'envoi : " . $e->getMessage();
        }
    }
}

/* ==========================================================================
   RÉCUPÉRATION DES CONTACTS (Pour la liste déroulante)
   ========================================================================== */
$contacts = [];
try {
    if ($type_utilisateur === 'bailleur') {
        $stmt = $conn->prepare("
            SELECT DISTINCT l.id_locataire AS id, l.nom, l.prenom 
            FROM locataires l
            INNER JOIN contrats c ON l.id_locataire = c.id_locataire
            INNER JOIN biens b ON c.id_bien = b.id_bien
            WHERE b.id_bailleur = :id_user
        ");
    } else {
        $stmt = $conn->prepare("
            SELECT DISTINCT b.id_bailleur AS id, ba.nom, ba.prenom 
            FROM bailleurs ba
            INNER JOIN biens b ON ba.id_bailleur = b.id_bailleur
            INNER JOIN contrats c ON b.id_bien = c.id_bien
            WHERE c.id_locataire = :id_user
        ");
    }
    $stmt->execute([':id_user' => $id_utilisateur]);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Erreur silencieuse
}

// Contact actif
$contact_id_selectionne = isset($_GET['contact_id']) ? intval($_GET['contact_id']) : ($contacts[0]['id'] ?? 0);

/* ==========================================================================
   RÉCUPÉRATION DE LA DISCUSSION SÉLECTIONNÉE
   ========================================================================== */
$discussion = [];
if ($contact_id_selectionne > 0) {
    try {
        $destinataire_type_recherche = ($type_utilisateur === 'bailleur') ? 'locataire' : 'bailleur';
        
        $stmt = $conn->prepare("
            SELECT * FROM messages 
            WHERE 
                (expediteur_id = :id_user AND expediteur_type = :type_user AND destinataire_id = :contact_id AND destinataire_type = :dest_type)
                OR 
                (expediteur_id = :contact_id AND expediteur_type = :dest_type AND destinataire_id = :id_user AND destinataire_type = :type_user)
            ORDER BY date_message ASC
        ");
        $stmt->execute([
            ':id_user' => $id_utilisateur,
            ':type_user' => $type_utilisateur,
            ':contact_id' => $contact_id_selectionne,
            ':dest_type' => $destinataire_type_recherche
        ]);
        $discussion = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Erreur
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie - RentMaster</title>
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
            --bubble-received: #f1f5f9;
            --radius-lg: 16px;
            --radius-md: 12px;
            --shadow-sm: 0 4px 6px -1px rgba(15, 23, 42, 0.05), 0 2px 4px -2px rgba(15, 23, 42, 0.05);
            --shadow-md: 0 10px 15px -3px rgba(15, 23, 42, 0.08);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        body { background: var(--bg-app); color: var(--text-main); min-height: 100vh; padding: 24px; display: flex; flex-direction: column; }

        /* NAVIGATION BAR */
        nav { background: var(--surface); padding: 14px 28px; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: flex; gap: 28px; margin-bottom: 24px; align-items: center; border: 1px solid var(--border-color); }
        nav a { text-decoration: none; color: var(--text-muted); font-weight: 500; font-size: 14px; transition: all 0.2s ease; display: flex; align-items: center; gap: 8px; }
        nav a:hover, nav a.active { color: var(--primary); font-weight: 600; }

        /* CONTENEUR PRINCIPAL (MONO-BLOC PLEINE LARGEUR) */
        .chat-window-box {
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* BANDEAU TOP : SÉLECTEUR DE CONTACT */
        .chat-top-bar {
            padding: 20px 24px;
            background: var(--surface);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }
        .chat-top-bar h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }
        
        /* STYLE DU SELECT DROPDOWN PRO */
        .select-contact-dropdown {
            padding: 10px 16px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-main);
            background-color: var(--bg-app);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            outline: none;
            cursor: pointer;
            transition: all 0.2s;
            min-width: 250px;
        }
        .select-contact-dropdown:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* THREAD DES MESSAGES */
        .chat-timeline {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-height: 400px;
        }

        /* BULLES DU NOUVEAU DESIGN */
        .bubble {
            padding: 12px 18px;
            border-radius: 14px;
            max-width: 65%;
            word-wrap: break-word;
            font-size: 14px;
            line-height: 1.5;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            animation: pop 0.2s ease;
        }
        @keyframes pop { from { transform: scale(0.97); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        
        .bubble-time-info { display: flex; align-items: center; justify-content: flex-end; gap: 6px; font-size: 10px; margin-top: 6px; opacity: 0.7; }

        /* OUTGOING (Moi, Bleu) */
        .bubble.outgoing {
            background: var(--primary);
            color: #ffffff;
            align-self: flex-end;
            border-top-right-radius: 4px;
        }
        /* INCOMING (L'autre, Gris clair) */
        .bubble.incoming {
            background: var(--bubble-received);
            color: var(--text-main);
            align-self: flex-start;
            border-top-left-radius: 4px;
            border: 1px solid var(--border-color);
        }

        /* BARRE D'ACTION BASSE (FORMULAIRE) */
        .chat-bottom-bar {
            padding: 18px 24px;
            background: var(--surface);
            border-top: 1px solid var(--border-color);
        }
        .message-form-layout {
            display: flex;
            gap: 14px;
            align-items: center;
        }
        .message-input-field {
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
        .message-input-field:focus {
            border-color: var(--primary-light);
            background: var(--surface);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .btn-send-message {
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
        .btn-send-message:hover { background: var(--primary-light); transform: translateY(-1px); }

        /* ÉTAT VIDE */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            color: var(--text-muted);
            gap: 12px;
            padding: 60px 24px;
        }
        .empty-state i { font-size: 44px; color: #cbd5e1; }
        .empty-state h3 { font-size: 16px; color: var(--text-main); font-weight: 600; }
    </style>
</head>
<body>

    <!-- NAVIGATION -->
    <nav>
        <a href="dashboard_locataire.php"><i class="fa-solid fa-chart-pie"></i> Tableau de bord</a>
        <a href="contrat_locataire.php"><i class="fa-solid fa-file-signature"></i> Contrats</a>
        <a href="messagerie.php" class="active"><i class="fa-solid fa-envelope"></i> Centre de messagerie</a>
    </nav>

    <!-- FENÊTRE DE DISCUSSION PLEINE LARGEUR -->
    <div class="chat-window-box">
        
        <!-- EN-TÊTE AVEC LA LISTE DÉROULANTE INTERACTIVE -->
        <div class="chat-top-bar">
            <h2><i class="fa-regular fa-comments" style="color: var(--primary); margin-right: 8px;"></i> Messagerie sécurisée</h2>
            
            <select class="select-contact-dropdown" onchange="changerDiscussion(this.value)">
                <?php if (empty($contacts)): ?>
                    <option value="">Aucun contact disponible</option>
                <?php else: ?>
                    <?php foreach ($contacts as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($c['id'] == $contact_id_selectionne) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?> (<?= $type_utilisateur === 'bailleur' ? 'Locataire' : 'Propriétaire' ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <!-- LISTE DES MESSAGES -->
        <div class="chat-timeline" id="chatTimeline">
            <?php if ($contact_id_selectionne > 0): ?>
                <?php if (empty($discussion)): ?>
                    <div class="empty-state">
                        <i class="fa-regular fa-paper-plane"></i>
                        <h3>Aucun échange enregistré</h3>
                        <p>Envoyez un message ci-dessous pour démarrer la discussion officielle.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($discussion as $msg): ?>
                        <?php $estMoi = ($msg['expediteur_id'] == $id_utilisateur && $msg['expediteur_type'] === $type_utilisateur); ?>
                        
                        <div class="bubble <?= $estMoi ? 'outgoing' : 'incoming' ?>">
                            <?= htmlspecialchars($msg['contenu']) ?>
                            <div class="bubble-time-info">
                                <span><?= date('H:i', strtotime($msg['date_message'])) ?></span>
                                <?php if ($estMoi): ?>
                                    <span>•</span>
                                    <i class="fas fa-check-double" style="color: #60a5fa;"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-user-slash"></i>
                    <h3>Aucun contact sélectionné</h3>
                    <p>Veuillez choisir un destinataire en haut à droite.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ZONE DE SAISIE DE TEXTE -->
        <?php if ($contact_id_selectionne > 0): ?>
            <div class="chat-bottom-bar">
                <form id="formMessage" method="POST" action="messagerie.php?contact_id=<?= $contact_id_selectionne ?>" class="message-form-layout">
                    <input type="hidden" name="action" value="envoyer">
                    <input type="hidden" name="destinataire_id" value="<?= $contact_id_selectionne ?>">
                    
                    <input type="text" name="contenu" id="messageInput" class="message-input-field" placeholder="Écrivez votre message officiel ici..." required autocomplete="off">
                    <button type="submit" class="btn-send-message"><i class="fa-solid fa-paper-plane"></i></button>
                </form>
            </div>
        <?php endif; ?>

    </div>

    <script>
    // Redirection au changement de contact dans le select
    function changerDiscussion(contactId) {
        if(contactId) {
            window.location.href = "messagerie.php?contact_id=" + contactId;
        }
    }

    // Garder le défilement automatique vers le bas
    function scrollTimelineToBottom() {
        const timeline = document.getElementById("chatTimeline");
        if(timeline) {
            timeline.scrollTop = timeline.scrollHeight;
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        scrollTimelineToBottom();
    });
    </script>
</body>
</html>