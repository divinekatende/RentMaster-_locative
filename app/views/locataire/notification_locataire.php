<?php
require_once __DIR__ . '/../../auth/session.php';
verifierConnexion(); 

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controllers/NotificationController.php';

$id_locataire = $_SESSION['id']; 

$controller = new NotificationController();

// 1. Lance le nettoyage automatique (3 jours) + Génération des rappels de loyer basés sur le contrat
$controller->synchroniserEvenements($id_locataire, 'locataire');

// 2. Marque les notifications comme lues à l'ouverture de la page
$controller->marquerCommeLu($id_locataire, 'locataire');

// 3. Récupère les notifications à jour depuis la BDD
$notifications_bdd = $controller->getNotifications($id_locataire, 'locataire');

$alertes_dynamiques = [];
foreach ($notifications_bdd as $notif) {
    $type_style = "info";
    $icone = "fas fa-envelope-open-text";
    $badge = "Notification";

    // Si la notification concerne un rappel automatique de loyer
    if (stripos($notif['titre'], 'Rappel Échéance') !== false) {
        $type_style = "echeance";
        $icone = "fas fa-calendar-alt";
        $badge = "Échéance Loyer";
    }

    $alertes_dynamiques[] = [
        "id" => $notif['id'],
        "type" => $type_style,
        "badgeText" => $badge,
        "text" => $notif['contenu'],
        "icon" => $icone
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications & Échéances - RentMaster</title>
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
            --shadow-sm: 0 4px 6px -1px rgba(15, 23, 42, 0.05);
            --shadow-md: 0 10px 15px -3px rgba(15, 23, 42, 0.08);
            
            --warning: #f59e0b;
            --warning-soft: #fef3c7;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-app); color: var(--text-main); min-height: 100vh; padding: 24px; display: flex; flex-direction: column; }

        .header { padding: 10px 0; font-size: 24px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        nav { background: var(--surface); padding: 14px 28px; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: flex; gap: 28px; margin-bottom: 30px; border: 1px solid var(--border-color); }
        nav a { text-decoration: none; color: var(--text-muted); font-weight: 500; font-size: 14px; display: flex; align-items: center; gap: 8px; }
        nav a:hover, nav a.active { color: var(--primary); font-weight: 600; }

        .container { max-width: 850px; width: 100%; margin: 0 auto; display: flex; flex-direction: column; gap: 16px; }

        .notification { background: var(--surface); border-radius: var(--radius-lg); padding: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); transition: all 0.2s ease; }
        .notification:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }

        .notif-info { display: flex; align-items: center; gap: 18px; flex: 1; }
        .notif-icon { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
        
        .notif-echeance { border-left: 5px solid var(--warning); }
        .notif-echeance .notif-icon { background: var(--warning-soft); color: var(--warning); }
        
        .notif-info-type { border-left: 5px solid var(--primary-light); }
        .notif-info-type .notif-icon { background: var(--primary-soft); color: var(--primary-light); }

        .notif-text-block { display: flex; flex-direction: column; gap: 4px; }
        .notif-title { font-size: 14.5px; font-weight: 500; color: var(--text-main); line-height: 1.5; }
        .notif-badge { display: inline-block; align-self: flex-start; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 20px; text-transform: uppercase; }
        
        .badge-echeance { background: var(--warning-soft); color: var(--warning); }
        .badge-info { background: var(--primary-soft); color: var(--primary); }

        .notification button { border: none; width: 40px; height: 40px; border-radius: 10px; background: #f1f5f9; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .notification button:hover { background: #fee2e2; color: #ef4444; }

        .empty { text-align: center; background: var(--surface); padding: 40px; border-radius: var(--radius-lg); color: var(--text-muted); border: 1px solid var(--border-color); }
        .empty i { font-size: 44px; color: #cbd5e1; margin-bottom: 12px; }
    </style>
</head>
<body>

    <div class="header">
        <i class="fa-regular fa-bell" style="color: var(--primary);"></i> Notifications & Échéances
    </div>

    <nav>
        <a href="dashboard_locataire.php"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="contrat_locataire.php"><i class="fas fa-file-contract"></i> Contrats</a>
        <a href="message_locataire.php"><i class="fas fa-comment"></i> Messages</a>
        <a href="notification_locataire.php" class="active"><i class="fas fa-bell"></i> Notifications</a>
    </nav>

    <div class="container" id="notifContainer"></div>

<script>
let notifications = <?= json_encode($alertes_dynamiques); ?>;

function afficherNotif() {
    let container = document.getElementById("notifContainer");
    container.innerHTML = "";

    if (notifications.length === 0) {
        container.innerHTML = `
            <div class="empty">
                <i class="fa-regular fa-bell-slash"></i>
                <p>Aucune notification ni échéance en cours.</p>
            </div>
        `;
        return;
    }

    notifications.forEach((n) => {
        let wrapperClass = n.type === "echeance" ? "notif-echeance" : "notif-info-type";
        let badgeClass = n.type === "echeance" ? "badge-echeance" : "badge-info";

        container.innerHTML += `
        <div class="notification ${wrapperClass}" id="notif-card-${n.id}">
            <div class="notif-info">
                <div class="notif-icon"><i class="${n.icon}"></i></div>
                <div class="notif-text-block">
                    <span class="notif-badge ${badgeClass}">${n.badgeText}</span>
                    <p class="notif-title">${n.text}</p>
                </div>
            </div>
            <button onclick="supprimerNotif('${n.id}')"><i class="fas fa-trash-can"></i></button>
        </div>
        `;
    });
}

function supprimerNotif(id) {
    if (confirm("Voulez-vous masquer cette notification ?")) {
        // Suppression visuelle immédiate
        let element = document.getElementById("notif-card-" + id);
        if (element) element.remove();
        
        // Envoi de la requête de suppression directement au contrôleur centralisé
        fetch("../../controllers/NotificationController.php?action=delete&id=" + id)
        .then(response => {
            if (!response.ok) {
                console.error("Erreur lors de la suppression côté serveur.");
            }
        })
        .catch(err => console.error("Erreur réseau :", err));
    }
}

// Lancement au chargement du DOM
afficherNotif();
</script>
</body>
</html>