<?php

require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/database.php';

verifierConnexion();
verifierRole(['admin']);

$conn = (new Database())->connect();

$message = "";

/* =========================
REDIRECTION
========================= */
function redirect(){
    header("Location: gestion.php");
    exit();
}

/* =========================
ACTIONS
========================= */
if(isset($_GET['action'], $_GET['type'], $_GET['id'])){

    $type = $_GET['type'];
    $id = (int)$_GET['id'];

    if($type === "bailleur"){
        if($_GET['action'] === "block"){
            $conn->prepare("UPDATE bailleurs SET statut='bloqué' WHERE id_bailleur=?")->execute([$id]);
        }
        if($_GET['action'] === "delete"){
            $conn->prepare("DELETE FROM bailleurs WHERE id_bailleur=?")->execute([$id]);
        }
    }

    if($type === "locataire"){
        if($_GET['action'] === "block"){
            $conn->prepare("UPDATE locataires SET statut='bloqué' WHERE id_locataire=?")->execute([$id]);
        }
        if($_GET['action'] === "delete"){
            $conn->prepare("DELETE FROM locataires WHERE id_locataire=?")->execute([$id]);
        }
    }

    if($type === "bien"){
        if($_GET['action'] === "delete"){
            $conn->prepare("DELETE FROM biens WHERE id_bien=?")->execute([$id]);
        }
    }

    redirect();
}

/* =========================
AJOUTS
========================= */
if(isset($_POST['ajouter_bailleur'])){
    $conn->prepare("INSERT INTO bailleurs(nom,email,telephone,mot_de_passe,statut)
    VALUES(?,?,?,?, 'actif')")->execute([
        $_POST['nom'],
        $_POST['email'],
        $_POST['telephone'],
        password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT)
    ]);
    $message="Bailleur ajouté";
}

if(isset($_POST['ajouter_locataire'])){
    $conn->prepare("INSERT INTO locataires(nom,email,telephone,statut)
    VALUES(?,?,?, 'actif')")->execute([
        $_POST['nom'],
        $_POST['email'],
        $_POST['telephone']
    ]);
    $message="Locataire ajouté";
}

if(isset($_POST['ajouter_bien'])){
    $conn->prepare("INSERT INTO biens(titre,adresse,loyer,statut)
    VALUES(?,?,?, 'disponible')")->execute([
        $_POST['titre'],
        $_POST['adresse'],
        $_POST['loyer']
    ]);
    $message="Bien ajouté";
}

/* =========================
DATA
========================= */
$bailleurs = $conn->query("SELECT * FROM bailleurs ORDER BY id_bailleur DESC")->fetchAll(PDO::FETCH_ASSOC);
$locataires = $conn->query("SELECT * FROM locataires ORDER BY id_locataire DESC")->fetchAll(PDO::FETCH_ASSOC);
$biens = $conn->query("SELECT * FROM biens ORDER BY id_bien DESC")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>RentMaster - Gestion</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

:root{
    --blue:#2563eb;
    --dark:#1e40af;
    --bg:#f3f6ff;
}

body{
margin:0;
font-family:Segoe UI;
background:var(--bg);
}

/* SIDEBAR */
.sidebar{
position:fixed;
top:0;
left:0;
width:250px;
height:100vh;
background:linear-gradient(180deg,var(--blue),var(--dark));
color:white;
padding:20px;
}

.logo{
text-align:center;
margin-bottom:30px;
}

.logo-box{
width:55px;
height:55px;
background:white;
color:var(--blue);
border-radius:14px;
display:flex;
align-items:center;
justify-content:center;
font-weight:bold;
margin:auto;
}

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
background:rgba(255,255,255,0.15);
}

/* MAIN */
.main{
margin-left:270px;
padding:25px;
}

/* NAV MINI PAGES */
.nav{
display:flex;
gap:10px;
margin-top:15px;
}

.nav-btn{
padding:10px 15px;
border:none;
border-radius:10px;
cursor:pointer;
background:#e5e7eb;
font-weight:500;
}

.nav-btn.active{
background:var(--blue);
color:white;
}

/* PAGE */
.page{
margin-top:20px;
}

/* CARD */
.card{
background:white;
padding:20px;
border-radius:14px;
margin-top:15px;
box-shadow:0 6px 18px rgba(0,0,0,0.08);
}

/* TABLE */
table{
width:100%;
border-collapse:collapse;
margin-top:10px;
}

th{
background:#f1f5f9;
text-align:left;
padding:12px;
}

td{
padding:12px;
border-bottom:1px solid #eee;
}

tr:hover{
background:#f9fbff;
}

/* BUTTONS */
.btn{
padding:8px 12px;
border:none;
border-radius:8px;
cursor:pointer;
color:white;
}

.green{background:#10b981;}
.red{background:#ef4444;}
.gray{background:#6b7280;}
.blue{background:var(--blue);}

/* MODAL */
.modal{
display:none;
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(0,0,0,0.55);
justify-content:center;
align-items:center;
}

.modal-content{
background:white;
padding:20px;
border-radius:14px;
width:400px;
}

.modal-content input{
width:100%;
padding:10px;
margin:5px 0;
}

</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

<div class="logo">
<div class="logo-box">RM</div>
<h3>RentMaster</h3>
</div>

<ul>
<li onclick="location.href='dashboard.php'"><i class="fa fa-chart-line"></i> Dashboard</li>
<li onclick="location.href='gestion.php'"><i class="fa fa-building"></i> Gestion</li>
<li onclick="location.href='settings.php'"><i class="fa fa-gear"></i> Paramètres</li>
<li onclick="location.href='../../controllers/logout.php'"><i class="fa fa-right-from-bracket"></i> Déconnexion</li>
</ul>

</div>

<div class="main">

<h2>Gestion complète</h2>

<?php if($message): ?>
<div class="card"><?= $message ?></div>
<?php endif; ?>

<!-- NAV -->
<div class="nav">
<button class="nav-btn active" onclick="showPage('bailleurs',this)">
Bailleurs
</button>

<button class="nav-btn" onclick="showPage('locataires',this)">
Locataires
</button>

<button class="nav-btn" onclick="showPage('biens',this)">
Biens
</button>
</div>

<!-- BAILLEURS -->
<div id="bailleurs" class="page">

<div class="card">
<h3>Bailleurs</h3>

<table>
<tr><th>Nom</th><th>Email</th><th>Tél</th><th>Action</th></tr>

<?php foreach($bailleurs as $b): ?>
<tr>
<td><?= $b['nom'] ?></td>
<td><?= $b['email'] ?></td>
<td><?= $b['telephone'] ?></td>
<td>
<button class="btn green"
onclick="openModal('bailleur',<?= $b['id_bailleur'] ?>,'<?= $b['nom'] ?>','<?= $b['email'] ?>','<?= $b['telephone'] ?>')">
Action
</button>
</td>
</tr>
<?php endforeach; ?>

</table>
</div>

</div>

<!-- LOCATAIRES -->
<div id="locataires" class="page" style="display:none;">

<div class="card">
<h3>Locataires</h3>

<table>
<tr><th>Nom</th><th>Email</th><th>Tél</th><th>Action</th></tr>

<?php foreach($locataires as $l): ?>
<tr>
<td><?= $l['nom'] ?></td>
<td><?= $l['email'] ?></td>
<td><?= $l['telephone'] ?></td>
<td>
<button class="btn green"
onclick="openModal('locataire',<?= $l['id_locataire'] ?>,'<?= $l['nom'] ?>','<?= $l['email'] ?>','<?= $l['telephone'] ?>')">
Action
</button>
</td>
</tr>
<?php endforeach; ?>

</table>
</div>

</div>

<!-- BIENS -->
<div id="biens" class="page" style="display:none;">

<div class="card">
<h3>Biens</h3>

<table>
<tr><th>Titre</th><th>Adresse</th><th>Loyer</th><th>Action</th></tr>

<?php foreach($biens as $b): ?>
<tr>
<td><?= $b['titre'] ?></td>
<td><?= $b['adresse'] ?></td>
<td><?= $b['loyer'] ?></td>
<td>
<button class="btn green"
onclick="openModal('bien',<?= $b['id_bien'] ?>,'<?= $b['titre'] ?>','<?= $b['adresse'] ?>','<?= $b['loyer'] ?>')">
Action
</button>
</td>
</tr>
<?php endforeach; ?>

</table>
</div>

</div>

</div>

<!-- MODAL -->
<div class="modal" id="modal">
<div class="modal-content">

<h3>Action</h3>

<input type="hidden" id="id">
<input type="hidden" id="type">

<input id="f1">
<input id="f2">
<input id="f3">

<br>

<button class="btn gray" onclick="closeModal()">Annuler</button>
<button class="btn red" onclick="supprimer()">Supprimer</button>
<button class="btn blue" onclick="bloquer()">Bloquer</button>

</div>
</div>

<script>

function showPage(id,btn){

document.querySelectorAll(".page").forEach(p=>p.style.display="none");
document.getElementById(id).style.display="block";

document.querySelectorAll(".nav-btn").forEach(b=>b.classList.remove("active"));
btn.classList.add("active");
}

function openModal(type,id,f1,f2,f3){
document.getElementById("modal").style.display="flex";
document.getElementById("type").value=type;
document.getElementById("id").value=id;
document.getElementById("f1").value=f1;
document.getElementById("f2").value=f2;
document.getElementById("f3").value=f3;
}

function closeModal(){
document.getElementById("modal").style.display="none";
}

function supprimer(){
let id=document.getElementById("id").value;
let type=document.getElementById("type").value;
window.location="?action=delete&type="+type+"&id="+id;
}

function bloquer(){
let id=document.getElementById("id").value;
let type=document.getElementById("type").value;
window.location="?action=block&type="+type+"&id="+id;
}

</script>

</body>
</html>