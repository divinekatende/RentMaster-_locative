<?php

require_once __DIR__ . '/../../auth/session.php';

verifierConnexion();
verifierRole(['locataire']);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - RentMaster</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

:root{
    --blue:#2563eb;
    --blue2:#3b82f6;
    --light:#f4f8ff;
    --white:#fff;
    --dark:#0f172a;
    --gray:#64748b;
}

body{
    background:linear-gradient(135deg,#eef5ff,#ffffff);
    min-height:100vh;
    display:flex;
}

/* SIDEBAR */

aside{
    width:240px;
    background:rgba(255,255,255,.9);
    backdrop-filter:blur(10px);
    border-right:1px solid #dbeafe;
    padding:20px;
    position:fixed;
    height:100%;
    transition:.3s;
}

.logo{
    text-align:center;
    margin-bottom:30px;
}

.logo img{
    width:100px;
    border-radius:50%;
    border:4px solid #dbeafe;
}

.logo h2{
    margin-top:10px;
    color:var(--blue);
}

aside a{
    display:flex;
    align-items:center;
    gap:12px;
    padding:14px;
    margin-bottom:10px;
    border-radius:14px;
    text-decoration:none;
    color:var(--dark);
    font-weight:600;
    transition:.3s;
}

aside a:hover,
.active{
    background:linear-gradient(135deg,var(--blue),var(--blue2));
    color:white;
    transform:translateX(5px);
}

/* CONTENT */

.content{
    margin-left:240px;
    width:100%;
    padding:30px;
}

/* HEADER */

.top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.top h1{
    color:var(--blue);
    font-size:32px;
}

.menu-btn{
    display:none;
    width:45px;
    height:45px;
    border:none;
    border-radius:12px;
    background:var(--blue);
    color:white;
    font-size:18px;
}

/* WELCOME */

.welcome{
    background:linear-gradient(135deg,var(--blue),var(--blue2));
    color:white;
    padding:25px;
    border-radius:24px;
    margin-bottom:25px;
    box-shadow:0 10px 25px rgba(37,99,235,.2);
}

.welcome h2{
    margin-bottom:10px;
}

.welcome p{
    opacity:.9;
}

/* CARDS */

.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
}

.card{
    background:rgba(255,255,255,.95);
    padding:25px;
    border-radius:22px;
    box-shadow:0 10px 25px rgba(37,99,235,.08);
    transition:.3s;
}

.card:hover{
    transform:translateY(-5px);
}

.card i{
    width:55px;
    height:55px;
    background:#dbeafe;
    color:var(--blue);
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:16px;
    font-size:22px;
    margin-bottom:15px;
}

.card h3{
    color:var(--gray);
    margin-bottom:10px;
}

.card p{
    color:var(--dark);
    font-size:24px;
    font-weight:700;
}

/* BUTTON */

.btn{
    margin-top:30px;
    border:none;
    background:linear-gradient(135deg,var(--blue),var(--blue2));
    color:white;
    padding:16px 24px;
    border-radius:16px;
    font-size:16px;
    font-weight:700;
    cursor:pointer;
    transition:.3s;
}

.btn:hover{
    transform:translateY(-3px);
    box-shadow:0 8px 20px rgba(37,99,235,.25);
}

/* QUICK */

.quick{
    margin-top:35px;
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(150px,1fr));
    gap:18px;
}

.quick-btn{
    background:white;
    border:none;
    padding:25px;
    border-radius:20px;
    box-shadow:0 8px 20px rgba(37,99,235,.08);
    cursor:pointer;
    transition:.3s;
    color:var(--blue);
    font-weight:700;
}

.quick-btn i{
    font-size:28px;
    margin-bottom:10px;
}

.quick-btn:hover{
    background:linear-gradient(135deg,var(--blue),var(--blue2));
    color:white;
    transform:translateY(-5px);
}

/* FOOTER */

footer{
    margin-top:40px;
    text-align:center;
    color:var(--gray);
}

/* RESPONSIVE */

@media(max-width:900px){

    aside{
        left:-100%;
        z-index:999;
    }

    aside.show{
        left:0;
    }

    .content{
        margin-left:0;
        padding:20px;
    }

    .menu-btn{
        display:block;
    }

    .top h1{
        font-size:24px;
    }
}

</style>
</head>

<body>

<!-- SIDEBAR -->

<aside id="sidebar">

<div class="logo">

<img src="../../../public/assets/images/logo.png">

<h2>RentMaster</h2>

</div>

<a class="active" href="dashboard.php">
<i class="fas fa-home"></i>
Dashboard
</a>

<a href="contrat_locataire.php">
<i class="fas fa-file-contract"></i>
Contrat
</a>

<a href="paiement_locataire.php">
<i class="fas fa-credit-card"></i>
Paiement
</a>

<a href="historique.php">
<i class="fas fa-history"></i>
Historique
</a>

<a href="profil_locataire.php">
<i class="fas fa-user"></i>
Profil
</a>

<a href="../../controllers/logout.php">
    <i class="fas fa-sign-out-alt"></i>
    Déconnexion
</a>

</aside>

<!-- CONTENT -->

<div class="content">

<div class="top">

<h1>Dashboard</h1>

<button class="menu-btn" onclick="toggleSidebar()">
<i class="fas fa-bars"></i>
</button>

</div>

<!-- WELCOME -->

<div class="welcome">

<h2>

<h2>
Bienvenue
<?= $_SESSION['prenom'] . ' ' . $_SESSION['nom']; ?>
</h2>

</h2>

<p>
Sur votre espace locataire RentMaster.
Suivez vos paiements et vos contrats facilement.
</p>

</div>

<!-- CARDS -->

<div class="cards">

<div class="card">

<i class="fas fa-money-bill-wave"></i>

<h3>Loyer à payer</h3>

<p>500$</p>

</div>

<div class="card">

<i class="fas fa-calendar-alt"></i>

<h3>Échéance</h3>

<p>05 Avril</p>

</div>

<div class="card">

<i class="fas fa-circle-xmark"></i>

<h3>Statut</h3>

<p style="color:red;">Non payé</p>

</div>

</div>

<!-- BUTTON -->

<button class="btn" onclick="payer()" id="payBtn">

<i class="fas fa-credit-card"></i>

Payer maintenant

</button>

<!-- QUICK ACCESS -->

<div class="quick">

<button class="quick-btn" onclick="goPage('message_locataire.php')">

<i class="fas fa-envelope"></i>

<br>Messages

</button>

<button class="quick-btn" onclick="goPage('notification_locataire.php')">

<i class="fas fa-bell"></i>

<br>Notifications

</button>

<button class="quick-btn" onclick="goPage('guide.php')">

<i class="fas fa-book"></i>

<br>Guide

</button>

<button class="quick-btn" onclick="goPage('calendrier_locataire.php')">

<i class="fas fa-calendar"></i>

<br>Calendrier

</button>

</div>

<footer>

© 2026 RentMaster - Tous droits réservés

</footer>

</div>

<script>

/* MENU */

function toggleSidebar(){

    document.getElementById("sidebar").classList.toggle("show");
}

/* PAYMENT BUTTON */

function payer(){

    let btn = document.getElementById("payBtn");

    btn.innerHTML = "✔ Redirection...";

    btn.style.background =
    "linear-gradient(135deg,#16a34a,#22c55e)";

    setTimeout(()=>{

        window.location.href =
        "paiement_locataire.php";

    },1000);
}

/* QUICK ACCESS */

function goPage(page){

    window.location.href = page;
}

</script>

</body>
</html>