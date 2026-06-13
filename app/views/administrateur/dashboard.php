<?php

require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/database.php';

verifierConnexion();
verifierRole(['admin']);

$database = new Database();
$conn = $database->connect();

/*
========================
STATS RÉELLES
========================
*/

function countTable($conn, $table){
    try{
        $stmt = $conn->query("SELECT COUNT(*) as total FROM $table");
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }catch(Exception $e){
        return 0;
    }
}

$bailleurs = countTable($conn, "bailleurs");

/*
========================
RECENT BAILLEURS
========================
*/
try{
    $stmt = $conn->query("
        SELECT id_bailleur, nom, email, telephone, statut, created_at
        FROM bailleurs
        ORDER BY id_bailleur DESC
        LIMIT 6
    ");
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
}catch(Exception $e){
    $recent = [];
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentMaster — Admin Dashboard</title>
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
            --danger: #ef4444;
            --danger-soft: #fef2f2;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        body { background: var(--bg-app); color: var(--text-main); min-height: 100vh; display: flex; }

        /* SIDEBAR */
        aside {
            width: 260px; background: var(--surface);
            border-right: 1px solid var(--border-color); padding: 24px 16px; position: fixed; height: 100%;
            display: flex; flex-direction: column; transition: .3s ease; z-index: 99;
        }
        .logo { text-align: center; margin-bottom: 32px; display: flex; flex-direction: column; align-items: center; gap: 8px; }
        .logo-box { width: 52px; height: 52px; background: var(--primary); color: white; font-weight: 800; font-size: 18px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(30, 64, 175, 0.2); }
        .logo h3 { color: var(--primary); font-size: 1.2rem; font-weight: 700; letter-spacing: -0.02em; margin-top: 4px; }
        .logo small { color: var(--text-muted); font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
        
        aside nav { flex: 1; display: flex; flex-direction: column; gap: 4px; }
        aside button {
            display: flex; align-items: center; gap: 12px; padding: 12px 14px; width: 100%; border: none; background: transparent;
            border-radius: var(--radius-md); text-decoration: none; color: var(--text-muted); font-weight: 500; font-size: 14px; cursor: pointer; transition: .2s ease;
        }
        aside button:hover { background: var(--primary-soft); color: var(--primary); }
        aside button.active { background: var(--primary); color: white; font-weight: 600; }
        aside button i { width: 20px; text-align: center; font-size: 16px; }
        
        .sidebar-footer { border-top: 1px solid var(--border-color); padding-top: 16px; }
        .sidebar-footer button { color: var(--danger); background: var(--danger-soft); }
        .sidebar-footer button:hover { background: var(--danger); color: white; }

        /* MAIN CONTENT */
        .main { margin-left: 260px; width: 100%; padding: 40px; display: flex; flex-direction: column; min-height: 100vh; }
        
        /* HEADER */
        .header {
            background: var(--surface); padding: 24px 32px; border-radius: var(--radius-lg);
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 32px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-color);
        }
        .header h2 { font-size: 22px; font-weight: 700; letter-spacing: -0.02em; color: var(--text-main); }
        .small { color: var(--text-muted); font-size: 13px; margin-top: 4px; }

        /* GRID STATS */
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .card {
            background: var(--surface); padding: 24px; border-radius: var(--radius-lg);
            border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);
            display: flex; justify-content: space-between; align-items: center; transition: transform 0.2s ease;
        }
        .card:hover { transform: translateY(-2px); }
        .card h4 { font-size: 13px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.02em; }
        .card h2 { font-size: 28px; font-weight: 700; color: var(--text-main); margin-top: 8px; letter-spacing: -0.02em; }
        .card-icon { width: 48px; height: 48px; border-radius: var(--radius-md); background: var(--primary-soft); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 20px; }

        /* TABLE CONTAINER SYSTEM */
        .table-container { background: var(--surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); overflow: hidden; }
        .table-container h3 { padding: 24px 32px; font-size: 16px; font-weight: 700; color: var(--text-main); border-bottom: 1px solid var(--border-color); }
        
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #f8fafc; padding: 14px 32px; font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); border-bottom: 1px solid var(--border-color); letter-spacing: 0.05em; }
        td { padding: 16px 32px; font-size: 14px; border-bottom: 1px solid var(--border-color); color: var(--text-main); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }

        /* BADGES */
        .badge { display: inline-flex; font-weight: 600; font-size: 11px; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.02em; }
        .badge.actif { background: var(--success-soft); color: var(--success); border: 1px solid #a7f3d0; }
        .badge.bloque { background: var(--danger-soft); color: var(--danger); border: 1px solid #fca5a5; }

        /* ACTIONS BUTTONS */
        .actions { display: flex; gap: 8px; }
        .actions button {
            width: 32px; height: 32px; border: 1px solid var(--border-color); background: var(--surface);
            border-radius: var(--radius-sm); color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s ease;
        }
        .actions button:hover { color: var(--primary); border-color: var(--primary-light); background: var(--primary-soft); }
        .actions button.btn-delete:hover { color: var(--danger); border-color: var(--danger); background: var(--danger-soft); }

        @media(max-width: 1024px) {
            aside { width: 70px; padding: 24px 8px; }
            aside .logo h3, aside .logo small, aside button span { display: none; }
            .main { margin-left: 70px; padding: 24px 16px; }
            .header { padding: 20px; }
            th, td { padding: 14px 16px; }
        }
    </style>
</head>
<body>

<aside>
    <div class="logo">
        <div class="logo-box">RM</div>
        <h3>RentMaster</h3>
        <small>Admin Panel</small>
    </div>

    <nav>
        <button class="active" onclick="go('dashboard.php')"><i class="fa fa-chart-line"></i> <span>Dashboard</span></button>
        <button onclick="go('gestion.php')"><i class="fa fa-building"></i> <span>Gestion</span></button>
        <button onclick="go('settings.php')"><i class="fa fa-gear"></i> <span>Paramètres</span></button>
    </nav>

    <div class="sidebar-footer">
        <button onclick="go('../../controllers/logout.php')"><i class="fa fa-right-from-bracket"></i> <span>Déconnexion</span></button>
    </div>
</aside>

<div class="main">

    <div class="header">
        <div>
            <h2>Dashboard Administrateur</h2>
            <div class="small">Vue globale du système RentMaster</div>
        </div>
    </div>

    <div class="stats">
        <div class="card">
            <div>
                <h4>Bailleurs</h4>
                <h2><?= $bailleurs ?></h2>
            </div>
            <div class="card-icon"><i class="fa fa-users"></i></div>
        </div>

        <div class="card">
            <div>
                <h4>Statut système</h4>
                <h2 style="color: var(--success); font-size: 22px; margin-top: 15px;">Opérationnel</h2>
            </div>
            <div class="card-icon" style="background: var(--success-soft); color: var(--success);"><i class="fa fa-shield-solid fa-shield"></i></div>
        </div>

        <div class="card">
            <div>
                <h4>Flux d'activité</h4>
                <h2 style="font-size: 22px; margin-top: 15px;">Temps réel</h2>
            </div>
            <div class="card-icon"><i class="fa fa-clock"></i></div>
        </div>

        <div class="card">
            <div>
                <h4>Performance</h4>
                <h2>98%</h2>
            </div>
            <div class="card-icon" style="background: #fff7ed; color: #ea580c;"><i class="fa fa-bolt"></i></div>
        </div>
    </div>

    <div class="table-container">
        <h3>Activités récentes des bailleurs</h3>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nom complet</th>
                        <th>Adresse Email</th>
                        <th>Téléphone</th>
                        <th>Statut du Compte</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($recent)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 32px;">Aucun bailleur enregistré dans le système.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach($recent as $r): ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($r['nom']) ?></td>
                            <td style="color: var(--text-muted);"><?= htmlspecialchars($r['email']) ?></td>
                            <td><?= htmlspecialchars($r['telephone'] ?? '—') ?></td>
                            <td>
                                <?php if(($r['statut'] ?? 'actif') == 'bloqué'): ?>
                                    <span class="badge bloque">Bloqué</span>
                                <?php else: ?>
                                    <span class="badge actif">Actif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions" style="justify-content: flex-end;">
                                    <button title="Voir le profil" onclick="go('voir_bailleur.php?id=<?= $r['id_bailleur'] ?>')"><i class="fa fa-eye"></i></button>
                                    <button title="Modifier" onclick="go('edit_bailleur.php?id=<?= $r['id_bailleur'] ?>')"><i class="fa fa-pen"></i></button>
                                    <button title="Supprimer" class="btn-delete" onclick="if(confirm('Supprimer ce bailleur ?')) go('delete_bailleur.php?id=<?= $r['id_bailleur'] ?>')"><i class="fa fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
function go(page){
    window.location.href = page;
}
</script>

</body>
</html>