<?php
session_start();
require_once('../../config/database.php');
require_once('../../controllers/HistoriqueController.php');

$id_bailleur = $_SESSION['id'] ?? null;
if (!$id_bailleur) {
    die("Accès refusé");
}

$ctrl = new HistoriqueController();

// Gestion des actions (Suppression / Nettoyage)
if (isset($_GET['delete'])) {
    $ctrl->delete((int)$_GET['delete'], $id_bailleur);
    header("Location: historique.php");
    exit();
}

if (isset($_POST['clear_all'])) {
    $ctrl->clearAll($id_bailleur);
    header("Location: historique.php");
    exit();
}

// Récupération de l'historique
$logs = $ctrl->index($id_bailleur);

// Fonction helper pour afficher de jolis badges selon le type d'action
function getBadgeStyle($type) {
    switch ($type) {
        case 'ajout':
            return ['bg' => '#ecfdf5', 'color' => '#047857', 'icon' => 'fa-plus-circle', 'label' => 'Ajout'];
        case 'modification':
            return ['bg' => '#fef3c7', 'color' => '#b45309', 'icon' => 'fa-edit', 'label' => 'Modification'];
        case 'suppression':
            return ['bg' => '#fef2f2', 'color' => '#b91c1c', 'icon' => 'fa-trash', 'label' => 'Suppression'];
        case 'connexion':
            return ['bg' => '#e0f2fe', 'color' => '#0369a1', 'icon' => 'fa-sign-in-alt', 'label' => 'Connexion'];
        default:
            return ['bg' => '#f1f5f9', 'color' => '#475569', 'icon' => 'fa-info-circle', 'label' => 'Info'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique - RentMaster</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --bg-app: #f8fafc;
            --surface: #ffffff;
            --primary: #1e40af;
            --primary-light: #3b82f6;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --radius-lg: 16px;
            --radius-md: 12px;
            --radius-sm: 8px;
            --shadow-md: 0 10px 15px -3px rgba(15, 23, 42, 0.08);
            --danger: #ef4444;
            --danger-soft: #fef2f2;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-app); color: var(--text-main); padding: 20px 10px; }

        .container {
            max-width: 1000px; margin: 40px auto; padding: 30px;
            background: var(--surface); border-radius: var(--radius-lg);
            border: 1px solid var(--border-color); box-shadow: var(--shadow-md);
        }

        /* TOPBAR */
        .top-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 15px; }
        .top-actions h1 { font-size: 24px; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 10px; letter-spacing: -0.02em; }
        
        .action-buttons { display: flex; gap: 10px; }

        /* BOUTONS */
        .btn { padding: 10px 18px; border: none; border-radius: var(--radius-sm); cursor: pointer; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s ease; text-decoration: none; }
        .btn-ghost { background: var(--surface); border: 1px solid var(--border-color); color: var(--text-main); }
        .btn-ghost:hover { background: var(--bg-app); }
        .btn-danger-outline { background: transparent; border: 1px solid #fca5a5; color: var(--danger); }
        .btn-danger-outline:hover { background: var(--danger); color: white; }
        .btn-action-delete { background: var(--danger-soft); color: var(--danger); border: 1px solid #fca5a5; padding: 6px 10px; font-size: 13px; border-radius: 6px; }
        .btn-action-delete:hover { background: var(--danger); color: white; }

        /* TABLEAU PREMIUM & RESPONSIVE */
        .table-responsive { width: 100%; overflow-x: auto; border: 1px solid var(--border-color); border-radius: var(--radius-md); background: var(--surface); }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; min-width: 600px; }
        th, td { padding: 16px 20px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        th { background: #f8fafc; font-weight: 600; color: var(--text-muted); text-transform: uppercase; font-size: 12px; letter-spacing: 0.05em; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }

        /* BADGES STYLE */
        .badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 50px; font-size: 12px; font-weight: 600; text-transform: capitalize; }
        
        .log-date { font-size: 13px; color: var(--text-muted); font-weight: 500; }
        .log-action { font-weight: 500; color: var(--text-main); }

        /* VIDE */
        .empty-state { text-align: center; padding: 40px 20px; color: var(--text-muted); font-style: italic; }

        @media(max-width: 768px) {
            .top-actions { flex-direction: column; align-items: flex-start; }
            .action-buttons { width: 100%; justify-content: space-between; }
            .container { padding: 16px; margin: 20px auto; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="top-actions">
        <h1><i class="fa fa-clock"></i> Historique des actions</h1>
        <div class="action-buttons">
            <?php if (!empty($logs)): ?>
                <form method="POST" action="historique.php" onsubmit="return confirm('Voulez-vous vraiment vider tout l\'historique ?');">
                    <button type="submit" name="clear_all" class="btn btn-danger-outline">
                        <i class="fa fa-dumpster"></i> Tout vider
                    </button>
                </form>
            <?php endif; ?>
            <a href="dashboard.php" class="btn btn-ghost">
                <i class="fa fa-arrow-left"></i> Retour au Dashboard
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">Type</th>
                    <th style="width: 55%;">Action effectuée</th>
                    <th style="width: 20%;">Date & Heure</th>
                    <th style="width: 10%; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="4" class="empty-state">
                            <i class="fa fa-folder-open" style="font-size: 24px; margin-bottom: 10px; display: block; color: var(--text-muted);"></i>
                            Aucune action enregistrée pour le moment.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): 
                        $badge = getBadgeStyle($log['type_action']);
                        $formattedDate = date('d/m/Y à H:i', strtotime($log['created_at']));
                    ?>
                        <tr>
                            <td>
                                <span class="badge" style="background-color: <?= $badge['bg'] ?>; color: <?= $badge['color'] ?>;">
                                    <i class="fa <?= $badge['icon'] ?>"></i> <?= $badge['label'] ?>
                                </span>
                            </td>
                            <td class="log-action"><?= htmlspecialchars($log['action']) ?></td>
                            <td class="log-date"><?= $formattedDate ?></td>
                            <td style="text-align: center;">
                                <a href="historique_bailleur.php?delete=<?= $log['id_historique'] ?>" 
                                   class="btn-action-delete" 
                                   title="Supprimer cette ligne" 
                                   onclick="return confirm('Supprimer cette entrée de l\'historique ?')">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>