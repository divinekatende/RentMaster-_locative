<?php
session_start();
require_once('../../config/database.php');

$conn = (new Database())->connect();

$id = $_SESSION['id'];

/* Récupération locataire */
$sql = "SELECT * FROM locataires WHERE id_locataire = :id";
$stmt = $conn->prepare($sql);
$stmt->execute(['id' => $id]);

$locataire = $stmt->fetch(PDO::FETCH_ASSOC);

/* UPDATE PROFIL */
if (isset($_POST['update'])) {

    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $current = $_POST['currentPassword'] ?? '';
    $new = $_POST['newPassword'] ?? '';

    if (!empty($new)) {

        if ($current !== $locataire['mot_de_passe']) {
            $error = "Mot de passe actuel incorrect";
        } else {

            $sql = "UPDATE locataires 
                    SET nom = :nom,
                        email = :email,
                        mot_de_passe = :mdp,
                        first_login = 0
                    WHERE id_locataire = :id";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'nom' => $nom,
                'email' => $email,
                'mdp' => $new,
                'id' => $id
            ]);

            header("Location: ../locataire/dashboard_locataire.php");
            exit;
        }

    } else {

        $sql = "UPDATE locataires 
                SET nom = :nom,
                    email = :email
                WHERE id_locataire = :id";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'nom' => $nom,
            'email' => $email,
            'id' => $id
        ]);

        header("Location: profil_locataire.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil - RentMaster</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>



*{

    margin:0;

    padding:0;

    box-sizing:border-box;

    font-family:Segoe UI,sans-serif;

}



:root{

    --blue:#2563eb;

    --light:#f4f8ff;

    --white:#fff;

    --dark:#0f172a;

    --gray:#64748b;

}



body{

    background:linear-gradient(135deg,#eef5ff,#ffffff);

    display:flex;

    min-height:100vh;

}



/* SIDEBAR */



aside{

    width:240px;

    background:rgba(255,255,255,.9);

    backdrop-filter:blur(10px);

    padding:20px;

    position:fixed;

    height:100%;

    border-right:1px solid #dbeafe;

    transition:.3s;

}



.logo{

    text-align:center;

    margin-bottom:30px;

}



.logo img{

    width:110px;

    border-radius:50%;

    border:4px solid #dbeafe;

}



.logo h2{

    color:var(--blue);

    margin-top:10px;

}



aside a{

    display:flex;

    align-items:center;

    gap:12px;

    padding:14px;

    margin-bottom:10px;

    text-decoration:none;

    color:var(--dark);

    border-radius:14px;

    transition:.3s;

    font-weight:600;

}



aside a:hover,

.active{

    background:linear-gradient(135deg,var(--blue),#3b82f6);

    color:white;

    transform:translateX(5px);

}



/* CONTENT */



.content{

    margin-left:240px;

    width:100%;

    padding:35px;

}



.top{

    margin-bottom:25px;

}



.top h1{

    color:var(--blue);

    font-size:32px;

}



.top p{

    color:var(--gray);

}



/* CARD */



.card{

    background:rgba(255,255,255,.95);

    padding:30px;

    border-radius:24px;

    box-shadow:0 10px 30px rgba(37,99,235,.08);

}



.title{

    display:flex;

    align-items:center;

    gap:10px;

    margin-bottom:25px;

}



.title i{

    color:var(--blue);

    font-size:24px;

}



/* PROFILE */



.profile{

    display:flex;

    align-items:center;

    gap:20px;

    margin-bottom:25px;

    flex-wrap:wrap;

}



.profile img{

    width:120px;

    height:120px;

    object-fit:cover;

    border-radius:50%;

    border:5px solid #dbeafe;

}



.profile h3{

    font-size:24px;

    color:var(--dark);

}



.profile p{

    color:var(--gray);

}



/* FORM */



.form{

    display:grid;

    grid-template-columns:1fr 1fr;

    gap:18px;

}



.group{

    display:flex;

    flex-direction:column;

}



.full{

    grid-column:1/-1;

}



label{

    margin-bottom:8px;

    font-weight:600;

    color:var(--dark);

}



input,

select{

    height:50px;

    border:1px solid #dbeafe;

    border-radius:14px;

    padding:0 15px;

    background:#f8fbff;

    transition:.3s;

}



input:focus,

select:focus{

    outline:none;

    border-color:var(--blue);

    background:white;

    box-shadow:0 0 0 4px rgba(37,99,235,.1);

}



/* CHECK */



.check{

    display:flex;

    align-items:center;

    gap:10px;

    margin-top:18px;

    background:#f8fbff;

    padding:14px;

    border-radius:14px;

}



.check input{

    width:18px;

    height:18px;

}



/* BUTTON */



button{

    margin-top:22px;

    border:none;

    background:linear-gradient(135deg,var(--blue),#3b82f6);

    color:white;

    padding:15px 22px;

    border-radius:14px;

    cursor:pointer;

    font-size:15px;

    font-weight:700;

    transition:.3s;

    position:relative;

    overflow:hidden;

}



button:hover{

    transform:translateY(-3px);

    box-shadow:0 8px 20px rgba(37,99,235,.3);

}



button:active{

    transform:scale(.97);

}



/* MENU */



.menu-btn{

    display:none;

    position:fixed;

    top:15px;

    left:15px;

    width:45px;

    height:45px;

    border:none;

    border-radius:12px;

    background:var(--blue);

    color:white;

    z-index:1000;

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



    .menu-btn{

        display:block;

    }



    .content{

        margin-left:0;

        padding:80px 15px 20px;

    }



    .form{

        grid-template-columns:1fr;

    }

}



@media(max-width:600px){



    .profile{

        flex-direction:column;

        text-align:center;

    }



    .card{

        padding:20px;

    }



    .top h1{

        font-size:25px;

    }

}



</style>
</head>

<body>

<button class="menu-btn" onclick="toggleMenu()">
<i class="fas fa-bars"></i>
</button>

<!-- SIDEBAR -->
<aside id="sidebar">

<div class="logo">
<img src="../../../public/assets/images/logo.png">
<h2>RentMaster</h2>
</div>

<a href="dashboard_locataire.php"><i class="fas fa-chart-line"></i> Dashboard</a>
<a href="contrat_locataire.php"><i class="fas fa-file-contract"></i> Contrat</a>
<a href="paiement_locataire.php"><i class="fas fa-credit-card"></i> Paiement</a>
<a href="historique.php"><i class="fas fa-history"></i> Historique</a>
<a class="active" href="profil_locataire.php"><i class="fas fa-user"></i> Profil</a>
<a href="../../controllers/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>

</aside>

<!-- CONTENT -->
<div class="content">

<div class="top">
<h1>Mon Profil</h1>
<p>Gérez vos informations personnelles</p>
</div>

<!-- ERROR -->
<?php if(isset($error)) : ?>
<div style="background:#fee2e2;padding:10px;color:#b91c1c;border-radius:8px;margin-bottom:15px;">
    <?= $error ?>
</div>
<?php endif; ?>

<div class="card">

<div class="title">
<i class="fas fa-user-circle"></i>
<h2>Informations</h2>
</div>

<!-- FORMULAIRE REEL -->
<form method="POST">

<div class="profile">

<img src="default-avatar.png">

<div>
<h3><?= $locataire['nom'] . ' ' . $locataire['postnom'] ?></h3>
<p><?= $locataire['email'] ?></p>
</div>

</div>

<div class="form">

<div class="group">
<label>Nom</label>
<input type="text" name="nom" value="<?= $locataire['nom'] ?>" required>
</div>

<div class="group">
<label>Email</label>
<input type="email" name="email" value="<?= $locataire['email'] ?>" required>
</div>

<div class="group">
<label>Mot de passe actuel</label>
<input type="password" name="currentPassword">
</div>

<div class="group">
<label>Nouveau mot de passe</label>
<input type="password" name="newPassword">
</div>

</div>

<div class="check">
<input type="checkbox">
<label>Notifications</label>
</div>

<button type="submit" name="update">
<i class="fas fa-save"></i> Enregistrer
</button>

</form>

</div>
</div>

<script>
function toggleMenu(){
    document.getElementById("sidebar").classList.toggle("show");
}
</script>

</body>
</html>