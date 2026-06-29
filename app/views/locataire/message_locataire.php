<?php
session_start();
require_once(__DIR__ . '/../../config/database.php');

// 1. Déterminer qui est connecté de manière stricte et étanche
$id_utilisateur = null;
$type_utilisateur = null;

if (isset($_SESSION['id_locataire'])) {
    $id_utilisateur = $_SESSION['id_locataire'];
    $type_utilisateur = 'locataire';
} elseif (isset($_SESSION['id_bailleur'])) { 
    $id_utilisateur = $_SESSION['id_bailleur'];
    $type_utilisateur = 'bailleur';
} elseif (isset($_SESSION['id']) && isset($_SESSION['type_utilisateur'])) {
    $id_utilisateur = $_SESSION['id'];
    $type_utilisateur = $_SESSION['type_utilisateur'];
} else {
    die("Accès refusé. Veuillez vous connecter.");
}

$conn = (new Database())->connect();

/* ==========================================================================
   TRAITEMENT DE L'ENVOI (POST)
   ========================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'envoyer') {
    $destinataire_id = $_POST['destinataire_id'] ?? '';
    $destinataire_type = ($type_utilisateur === 'bailleur') ? 'locataire' : 'bailleur';
    $contenu = trim($_POST['contenu'] ?? '');

    if (!empty($contenu) && !empty($destinataire_id)) {
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
            
            header("Location: " . $_SERVER['PHP_SELF'] . "?contact_id=" . urlencode($destinataire_id));
            exit;
        } catch (PDOException $e) {
            $erreur = "Erreur d'envoi : " . $e->getMessage();
        }
    }
}

/* ==========================================================================
   RÉCUPÉRATION DES CONTACTS
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
            SELECT DISTINCT ba.id_bailleur AS id, ba.nom, ba.prenom 
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

$contact_id_selectionne = isset($_GET['contact_id']) ? $_GET['contact_id'] : ($contacts[0]['id'] ?? '');

// Récupérer le nom du contact actif
$nom_contact_actif = "Sélectionnez un fil de discussion";
foreach ($contacts as $c) {
    if ($c['id'] == $contact_id_selectionne) {
        $nom_contact_actif = htmlspecialchars($c['prenom'] . ' ' . $c['nom']);
        break;
    }
}

/* ==========================================================================
   RÉCUPÉRATION DE LA DISCUSSION SÉLECTIONNÉE
   ========================================================================== */
$discussion = [];
if (!empty($contact_id_selectionne)) {
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
        // Erreur silencieuse
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
            --bg-app: #f1f5f9;
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
            --shadow-sm: 0 4px 6px -1px rgba(15, 23, 42, 0.05);
            --shadow-md: 0 10px 15px -3px rgba(15, 23, 42, 0.06);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        body { background: var(--bg-app); color: var(--text-main); height: 100vh; padding: 20px; display: flex; flex-direction: column; overflow: hidden; }

        /* NAVIGATION BAR */
        nav { background: var(--surface); padding: 14px 28px; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: flex; gap: 28px; margin-bottom: 16px; align-items: center; border: 1px solid var(--border-color); flex-shrink: 0; }
        nav a { text-decoration: none; color: var(--text-muted); font-weight: 500; font-size: 14px; transition: all 0.2s ease; display: flex; align-items: center; gap: 8px; }
        nav a:hover, nav a.active { color: var(--primary); font-weight: 600; }

        /* ARY LAYOUT EN DEUX COLONNES */
        .messaging-wrapper {
            display: flex;
            flex: 1;
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        /* SIDEBAR DE GAUCHE : LES CONTACTS */
        .contacts-sidebar {
            width: 320px;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            background: #fafafa;
        }
        .sidebar-title {
            padding: 24px;
            font-size: 16px;
            font-weight: 700;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
        }
        .contacts-list {
            flex: 1;
            overflow-y: auto;
        }
        .contact-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        .contact-item:hover { background: #f1f5f9; }
        .contact-item.active { background: var(--primary-soft); border-left: 4px solid var(--primary); }
        
        .contact-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #cbd5e1;
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
        }
        .contact-item.active .contact-avatar { background: var(--primary); color: white; }
        
        .contact-details { display: flex; flex-direction: column; gap: 2px; }
        .contact-name { font-size: 14px; font-weight: 600; color: var(--text-main); }
        .contact-role { font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 500; }

        /* ZONE DE DROITE : LE CHAT COMPLET */
        .chat-main-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--surface);
        }

        .chat-top-bar {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chat-top-bar h2 { font-size: 16px; font-weight: 600; color: var(--text-main); }

        /* SCROLL TIMELINE */
        .chat-timeline {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        /* STYLE DES BULLES */
        .bubble {
            padding: 12px 16px;
            border-radius: 16px;
            max-width: 60%;
            word-wrap: break-word;
            font-size: 14px;
            line-height: 1.5;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
            position: relative;
        }
        .bubble-time-info { display: flex; align-items: center; gap: 4px; font-size: 10px; margin-top: 5px; opacity: 0.6; justify-content: flex-end; }

        .bubble.cote-droite {
            background: var(--primary);
            color: #ffffff;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }
        .bubble.cote-gauche {
            background: var(--bubble-received);
            color: var(--text-main);
            align-self: flex-start;
            border-bottom-left-radius: 4px;
            border: 1px solid var(--border-color);
        }

        /* FORMULAIRE D'ENVOI */
        .chat-bottom-bar { padding: 16px 24px; background: var(--surface); border-top: 1px solid var(--border-color); }
        .message-form-layout { display: flex; gap: 12px; align-items: center; }
        .message-input-field {
            flex: 1;
            padding: 12px 20px;
            border-radius: 24px;
            border: 1px solid var(--border-color);
            background: #f8fafc;
            outline: none;
            font-size: 14px;
            color: var(--text-main);
            transition: all 0.2s;
        }
        .message-input-field:focus { border-color: var(--primary-light); background: var(--surface); box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        
        .btn-send-message {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: none;
            background: var(--primary);
            color: white;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 6px rgba(30, 64, 175, 0.15);
        }
        .btn-send-message:hover { background: var(--primary-light); transform: scale(1.03); }

        .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; color: var(--text-muted); gap: 12px; text-align: center; }
        .empty-state i { font-size: 48px; color: #cbd5e1; }
        .empty-state h3 { font-size: 15px; color: var(--text-main); font-weight: 600; }
    </style>
</head>
<body>

    <nav>
        <?php if ($type_utilisateur === 'bailleur'): ?>
            <a href="dashboard_bailleur.php"><i class="fa-solid fa-chart-pie"></i> Tableau de bord</a>
            <a href="contrats_bailleur.php"><i class="fa-solid fa-file-signature"></i> Gestion Contrats</a>
        <?php else: ?>
            <a href="dashboard_locataire.php"><i class="fa-solid fa-chart-pie"></i> Tableau de bord</a>
            <a href="contrat_locataire.php"><i class="fa-solid fa-file-signature"></i> Mes Contrats</a>
        <?php endif; ?>
        <a href="messagerie.php" class="active"><i class="fa-solid fa-envelope"></i> Centre de messagerie</a>
    </nav>

    <?php if (isset($erreur)): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: var(--radius-md); margin-bottom: 12px; border: 1px solid #fca5a5; font-size: 14px;">
            <?= htmlspecialchars($erreur) ?>
        </div>
    <?php endif; ?>

    <div class="messaging-wrapper">
        
        <div class="contacts-sidebar">
            <div class="sidebar-title"><i class="fa-regular fa-comments"></i> Conversations</div>
            <div class="contacts-list">
                <?php if (empty($contacts)): ?>
                    <div style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 13px;">Aucun contact associé à vos contrats.</div>
                <?php else: ?>
                    <?php foreach ($contacts as $c): ?>
                        <?php 
                        $initiales = strtoupper(substr($c['prenom'], 0, 1) . substr($c['nom'], 0, 1));
                        $isActive = ($c['id'] == $contact_id_selectionne);
                        ?>
                        <a href="?contact_id=<?= urlencode($c['id']) ?>" class="contact-item <?= $isActive ? 'active' : '' ?>">
                            <div class="contact-avatar"><?= $initiales ?></div>
                            <div class="contact-details">
                                <span class="contact-name"><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></span>
                                <span class="contact-role"><?= $type_utilisateur === 'bailleur' ? 'Locataire' : 'Propriétaire' ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="chat-main-area">
            <div class="chat-top-bar">
                <h2><?= $nom_contact_actif ?></h2>
                <span style="font-size: 12px; color: var(--text-muted); background: #f1f5f9; padding: 4px 10px; border-radius: 12px; font-weight: 500;">
                    <i class="fa-solid fa-shield-halved"></i> Fil de discussion sécurisé
                </span>
            </div>

            <div class="chat-timeline" id="chatTimeline">
                <?php if (!empty($contact_id_selectionne)): ?>
                    <?php if (empty($discussion)): ?>
                        <div class="empty-state">
                            <i class="fa-regular fa-paper-plane"></i>
                            <h3>Aucun échange enregistré</h3>
                            <p>Envoyez un premier message officiel ci-dessous pour lancer la discussion.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($discussion as $msg): ?>
                            <?php 
                            $estMoi = ($msg['expediteur_id'] == $id_utilisateur && $msg['expediteur_type'] === $type_utilisateur); 
                            $classePosition = $estMoi ? 'cote-droite' : 'cote-gauche';
                            ?>
                            <div class="bubble <?= $classePosition ?>">
                                <?= htmlspecialchars($msg['contenu']) ?>
                                <div class="bubble-time-info">
                                    <span><?= date('H:i', strtotime($msg['date_message'])) ?></span>
                                    <?php if ($estMoi): ?>
                                        <span>•</span>
                                        <i class="fas fa-check" style="color: rgba(255,255,255,0.7);"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-user-slash"></i>
                        <h3>Aucune conversation active</h3>
                        <p>Veuillez sélectionner un contact dans la liste à gauche pour commencer.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($contact_id_selectionne)): ?>
                <div class="chat-bottom-bar">
                    <form id="formMessage" method="POST" action="?contact_id=<?= urlencode($contact_id_selectionne) ?>" class="message-form-layout">
                        <input type="hidden" name="action" value="envoyer">
                        <input type="hidden" name="destinataire_id" value="<?= htmlspecialchars($contact_id_selectionne) ?>">
                        
                        <input type="text" name="contenu" id="messageInput" class="message-input-field" placeholder="Écrivez votre message officiel ici..." required autocomplete="off">
                        <button type="submit" class="btn-send-message"><i class="fa-solid fa-paper-plane"></i></button>
                    </form>
                </div>
            <?php endif; ?>

        </div>

    </div>

    <script>
    function scrollTimelineToBottom() {
        const timeline = document.getElementById("chatTimeline");
        if(timeline) {
            timeline.scrollTop = timeline.scrollHeight;
        }
    }

    // Lance le scroll au chargement complet de l'interface
    document.addEventListener("DOMContentLoaded", () => {
        scrollTimelineToBottom();
    });
    </script>
</body>
</html>