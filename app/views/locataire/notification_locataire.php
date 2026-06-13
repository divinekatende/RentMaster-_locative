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
            --shadow-sm: 0 4px 6px -1px rgba(15, 23, 42, 0.05), 0 2px 4px -2px rgba(15, 23, 42, 0.05);
            --shadow-md: 0 10px 15px -3px rgba(15, 23, 42, 0.08);
            
            /* Statuts Couleurs */
            --danger: #ef4444;
            --danger-soft: #fef2f2;
            --warning: #f59e0b;
            --warning-soft: #fef3c7;
            --success: #10b981;
            --success-soft: #ecfdf5;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        body { background: var(--bg-app); color: var(--text-main); min-height: 100vh; padding: 24px; display: flex; flex-direction: column; }

        /* HEADER ÉPURÉ */
        .header {
            padding: 10px 0;
            font-size: 24px;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.02em;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* NAVIGATION BAR */
        nav { background: var(--surface); padding: 14px 28px; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); display: flex; gap: 28px; margin-bottom: 30px; align-items: center; border: 1px solid var(--border-color); }
        nav a { text-decoration: none; color: var(--text-muted); font-weight: 500; font-size: 14px; transition: all 0.2s ease; display: flex; align-items: center; gap: 8px; }
        nav a:hover, nav a.active { color: var(--primary); font-weight: 600; }

        /* CONTAINER */
        .container {
            max-width: 850px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* NOTIFICATION CARDS */
        .notification {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
        }
        .notification:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .notif-info {
            display: flex;
            align-items: center;
            gap: 18px;
            flex: 1;
        }

        /* DYNAMIC ICON STYLES */
        .notif-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        /* Variants de statut */
        .notif-retard { border-left: 5px solid var(--danger); }
        .notif-retard .notif-icon { background: var(--danger-soft); color: var(--danger); }
        
        .notif-echeance { border-left: 5px solid var(--warning); }
        .notif-echeance .notif-icon { background: var(--warning-soft); color: var(--warning); }
        
        .notif-info-type { border-left: 5px solid var(--primary-light); }
        .notif-info-type .notif-icon { background: var(--primary-soft); color: var(--primary-light); }

        .notif-text-block {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .notif-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-main);
        }
        .notif-badge {
            display: inline-block;
            align-self: flex-start;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            margin-bottom: 2px;
        }
        .badge-retard { background: var(--danger-soft); color: var(--danger); }
        .badge-echeance { background: var(--warning-soft); color: var(--warning); }
        .badge-info { background: var(--primary-soft); color: var(--primary); }

        /* ACTION BUTTONS */
        .notification button {
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #f1f5f9;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .notification button:hover {
            background: var(--danger-soft);
            color: var(--danger);
        }

        /* EMPTY STATE */
        .empty {
            text-align: center;
            background: var(--surface);
            padding: 40px;
            border-radius: var(--radius-lg);
            color: var(--text-muted);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
        }
        .empty i { font-size: 44px; color: #cbd5e1; margin-bottom: 12px; }
        .empty p { font-size: 15px; font-weight: 500; }

        /* RESPONSIVE */
        @media(max-width: 700px){
            body { padding: 16px; }
            .notification { flex-direction: column; gap: 16px; align-items: flex-start; }
            .notification button { width: 100%; height: 44px; }
        }
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
    </nav>

    <div class="container" id="notifContainer"></div>

<script>
// Mock data intégrant les types : 'retard', 'echeance', ou 'info'
let notifications = JSON.parse(localStorage.getItem("notifications")) || [
    {
        id: 1,
        type: "retard",
        badgeText: "Retard de paiement",
        text: "Le loyer du mois de Mai présente un retard de 5 jours. Solde dû : 650.00 €.",
        icon: "fas fa-exclamation-triangle"
    },
    {
        id: 2,
        type: "echeance",
        badgeText: "Prochaine échéance",
        text: "Appel de loyer pour le mois de Juillet disponible. Échéance le 05/07/2026.",
        icon: "fas fa-calendar-alt"
    },
    {
        id: 3,
        type: "info",
        badgeText: "Message",
        text: "Nouveau message officiel disponible concernant l'état des lieux.",
        icon: "fas fa-envelope-open-text"
    }
];

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

    notifications.forEach((n, index) => {
        // Associer la classe CSS spécifique selon le type
        let wrapperClass = "notif-info-type";
        let badgeClass = "badge-info";
        
        if (n.type === "retard") {
            wrapperClass = "notif-retard";
            badgeClass = "badge-retard";
        } else if (n.type === "echeance") {
            wrapperClass = "notif-echeance";
            badgeClass = "badge-echeance";
        }

        container.innerHTML += `
        <div class="notification ${wrapperClass}">
            <div class="notif-info">
                <div class="notif-icon">
                    <i class="${n.icon}"></i>
                </div>
                <div class="notif-text-block">
                    <span class="notif-badge ${badgeClass}">${n.badgeText}</span>
                    <p class="notif-title">${n.text}</p>
                </div>
            </div>
            <button onclick="supprimerNotif(${index})" title="Archiver">
                <i class="fas fa-trash-can"></i>
            </button>
        </div>
        `;
    });
}

function supprimerNotif(index) {
    notifications.splice(index, 1);
    localStorage.setItem("notifications", JSON.stringify(notifications));
    afficherNotif();
}

// Lancement au chargement de la page
afficherNotif();
</script>

</body>
</html>