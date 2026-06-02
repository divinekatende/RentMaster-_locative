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
<title>RentMaster Dashboard</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

:root{
    --blue:#2563eb;
    --blue2:#3b82f6;
    --bg:#f4f7fb;
    --card:#ffffff;
    --text:#1e293b;
    --muted:#64748b;
    --border:#e2e8f0;
}

body{
    margin:0;
    font-family:Segoe UI;
    background:var(--bg);
    color:var(--text);
}

/* SIDEBAR */
.sidebar{
    width:240px;
    height:100vh;
    position:fixed;
    background:linear-gradient(180deg,var(--blue),var(--blue2));
    color:white;
    padding:20px;
}

.logo{
    text-align:center;
    margin-bottom:25px;
}

.logo-box{
    width:60px;
    height:60px;
    background:white;
    color:var(--blue);
    margin:auto;
    border-radius:16px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:bold;
}

/* MENU */
.sidebar ul{
    list-style:none;
    padding:0;
}

.sidebar li{
    padding:12px;
    margin-bottom:10px;
    border-radius:10px;
    cursor:pointer;
}

.sidebar li:hover{
    background:rgba(255,255,255,0.2);
}

/* MAIN */
.main{
    margin-left:240px;
    padding:25px;
}

/* HEADER */
.header{
    background:white;
    padding:18px;
    border-radius:16px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
    box-shadow:0 5px 20px rgba(0,0,0,0.05);
}

/* STATS */
.stats{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:15px;
    margin-bottom:20px;
}

.card{
    background:var(--card);
    padding:18px;
    border-radius:16px;
    box-shadow:0 5px 15px rgba(0,0,0,0.05);
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.card i{
    font-size:26px;
    color:var(--blue);
}

/* TABLE */
.table{
    background:white;
    border-radius:16px;
    overflow:hidden;
}

.table h3{
    padding:15px;
    margin:0;
    border-bottom:1px solid var(--border);
}

table{
    width:100%;
    border-collapse:collapse;
}

th,td{
    padding:14px;
    text-align:left;
    border-bottom:1px solid var(--border);
    font-size:14px;
}

.badge{
    padding:5px 10px;
    border-radius:20px;
    font-size:12px;
    color:white;
}

.actif{background:#16a34a;}
.bloque{background:#dc2626;}

.actions i{
    margin-right:10px;
    cursor:pointer;
    color:var(--blue);
}

.small{
    color:var(--muted);
    font-size:13px;
}

</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <div class="logo">
        <div class="logo-box">RM</div>
        <h3>RentMaster</h3>
        <small>Admin Panel</small>
    </div>

    <ul>
        <li onclick="go('dashboard.php')"><i class="fa fa-chart-line"></i> Dashboard</li>
        <li onclick="go('gestion.php')"><i class="fa fa-building"></i> Gestion</li>
        <li onclick="go('settings.php')"><i class="fa fa-gear"></i> Paramètres</li>
        <li onclick="go('../../controllers/logout.php')"><i class="fa fa-right-from-bracket"></i> Déconnexion</li>
    </ul>

</div>

<!-- MAIN -->
<div class="main">

    <div class="header">
        <div>
            <h2>Dashboard Administrateur</h2>
            <div class="small">Vue globale du système RentMaster</div>
        </div>
    </div>

    <!-- STATS -->
    <div class="stats">

        <div class="card">
            <div>
                <h3>Bailleurs</h3>
                <h2><?= $bailleurs ?></h2>
            </div>
            <i class="fa fa-users"></i>
        </div>

        <div class="card">
            <div>
                <h3>Statut système</h3>
                <h2>Actif</h2>
            </div>
            <i class="fa fa-shield"></i>
        </div>

        <div class="card">
            <div>
                <h3>Dernière activité</h3>
                <h2>Temps réel</h2>
            </div>
            <i class="fa fa-clock"></i>
        </div>

        <div class="card">
            <div>
                <h3>Performance</h3>
                <h2>98%</h2>
            </div>
            <i class="fa fa-bolt"></i>
        </div>

    </div>

    <!-- TABLE -->
    <div class="table">

        <h3>Activités récentes des bailleurs</h3>

        <table>

            <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>

            <?php foreach($recent as $r): ?>
            <tr>

                <td><?= htmlspecialchars($r['nom']) ?></td>
                <td><?= htmlspecialchars($r['email']) ?></td>
                <td><?= htmlspecialchars($r['telephone']) ?></td>

                <td>
                    <?php if(($r['statut'] ?? 'actif') == 'bloqué'): ?>
                        <span class="badge bloque">Bloqué</span>
                    <?php else: ?>
                        <span class="badge actif">Actif</span>
                    <?php endif; ?>
                </td>

                <td class="actions">
                    <i class="fa fa-eye"></i>
                    <i class="fa fa-pen"></i>
                    <i class="fa fa-trash"></i>
                </td>

            </tr>
            <?php endforeach; ?>

        </table>

    </div>

</div>

<script>
function go(page){
    window.location.href = page;
}
</script>

</body>
</html>

