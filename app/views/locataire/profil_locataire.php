<?php
session_start();
require_once(__DIR__ . '/../../config/database.php');

// 1. Sécurité : locataire connecté obligatoire
if (!isset($_SESSION['id_locataire'])) {
    header('Location: ../../auth/login-locataire.php');
    exit;
}

$id = $_SESSION['id_locataire'];
$conn = (new Database())->connect();

// Dossier de stockage physique sur le serveur
$upload_dir = __DIR__ . '/../../public/uploads/locataires/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Détection automatique du dossier racine pour l'affichage HTML
$project_root = str_replace($_SERVER['DOCUMENT_ROOT'], '', str_replace('\\', '/', __DIR__ . '/../../'));
$project_root = rtrim($project_root, '/') . '/';
$avatar_url_path = $project_root . 'public/uploads/locataires/';

/* Récupération des infos réelles du locataire */
$sql = "SELECT * FROM locataires WHERE id_locataire = :id";
$stmt = $conn->prepare($sql);
$stmt->execute(['id' => $id]);
$locataire = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$locataire) {
    die("Erreur : Profil introuvable.");
}

/* Traitement de la suppression de la photo */
if (isset($_POST['delete_photo'])) {
    if (!empty($locataire['photo'])) {
        $file_path = $upload_dir . $locataire['photo'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        $sql = "UPDATE locataires SET photo = NULL WHERE id_locataire = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        header("Location: profil_locataire.php?success=2");
        exit;
    }
}

/* Traitement de la mise à jour (Formulaire soumis) */
if (isset($_POST['update'])) {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $current = $_POST['currentPassword'] ?? '';
    $new = $_POST['newPassword'] ?? '';
    
    $photo_filename = $locataire['photo']; // On garde l'ancienne photo par défaut

    // Gestion de l'upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['photo']['tmp_name'];
        $file_name = $_FILES['photo']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($file_ext, $allowed_extensions)) {
            // Supprimer l'ancienne photo physique si elle existe
            if (!empty($locataire['photo']) && file_exists($upload_dir . $locataire['photo'])) {
                unlink($upload_dir . $locataire['photo']);
            }
            
            // Génération du nom unique
            $photo_filename = 'loc_' . uniqid() . '.' . $file_ext;
            move_uploaded_file($file_tmp, $upload_dir . $photo_filename);
        } else {
            $error = "Format d'image invalide. Uniquement JPG, JPEG, PNG ou WEBP.";
        }
    }

    if (!isset($error)) {
        if (!empty($new)) {
            $password_valide = false;
            if (password_verify($current, $locataire['mot_de_passe'])) {
                $password_valide = true;
            } elseif ($current === $locataire['mot_de_passe']) {
                $password_valide = true;
            }

            if (!$password_valide) {
                $error = "Le mot de passe actuel est incorrect.";
            } else {
                $new_hash = password_hash($new, PASSWORD_BCRYPT);

                $sql = "UPDATE locataires 
                        SET nom = :nom, prenom = :prenom, email = :email, mot_de_passe = :mdp, photo = :photo, first_login = 1
                        WHERE id_locataire = :id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'nom' => $nom, 'prenom' => $prenom, 'email' => $email, 'mdp' => $new_hash, 'photo' => $photo_filename, 'id' => $id
                ]);

                $_SESSION['nom'] = $nom;
                $_SESSION['prenom'] = $prenom;
                header("Location: dashboard_locataire.php");
                exit;
            }
        } else {
            // Mise à jour simple sans mot de passe
            $sql = "UPDATE locataires 
                    SET nom = :nom, prenom = :prenom, email = :email, photo = :photo
                    WHERE id_locataire = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'nom' => $nom, 'prenom' => $prenom, 'email' => $email, 'photo' => $photo_filename, 'id' => $id
            ]);

            $_SESSION['nom'] = $nom;
            $_SESSION['prenom'] = $prenom;
            header("Location: profil_locataire.php?success=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - RentMaster</title>
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

        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        body { background: var(--bg-app); color: var(--text-main); min-height:100vh; display:flex; }
        
        /* SIDEBAR */
        aside {
            width:260px; background: var(--surface);
            border-right:1px solid var(--border-color); padding:24px 16px; position:fixed; height:100%;
            display: flex; flex-direction: column; transition:.3s ease; z-index: 99;
        }
        .logo { text-align:center; margin-bottom:32px; display: flex; flex-direction: column; align-items: center; gap: 10px; }
        .logo img { width:70px; height:70px; border-radius:50%; border:3px solid var(--primary-soft); object-fit: cover; }
        .logo h2 { color:var(--primary); font-size: 1.25rem; font-weight:700; letter-spacing: -0.02em; }
        aside nav { flex: 1; display: flex; flex-direction: column; gap: 4px; }
        aside a {
            display:flex; align-items:center; gap:12px; padding:12px 14px;
            border-radius:var(--radius-md); text-decoration:none; color:var(--text-muted); font-weight:500; font-size: 14px; transition:.2s ease;
        }
        aside a:hover { background: var(--primary-soft); color: var(--primary); }
        aside a.active { background:var(--primary); color:white; font-weight:600; }
        aside a i { width: 20px; text-align: center; font-size: 16px; }
        .sidebar-footer { border-top: 1px solid var(--border-color); padding-top: 16px; }
        .sidebar-footer a { color: var(--danger); background: var(--danger-soft); }
        .sidebar-footer a:hover { background: var(--danger); color: white; }

        /* CONTENT */
        .content { margin-left:260px; width:100%; padding:40px; display: flex; flex-direction: column; min-height: 100vh; }
        .top { display:flex; justify-content:space-between; align-items:center; margin-bottom:32px; }
        .top h1 { color:var(--text-main); font-size:24px; font-weight: 700; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; }
        .top p { color: var(--text-muted); font-size: 14px; margin-top: 4px; }
        .menu-btn { display:none; width:40px; height:40px; border:1px solid var(--border-color); border-radius:var(--radius-sm); background:var(--surface); color:var(--text-main); font-size:16px; cursor: pointer; }

        /* NOTIFICATIONS / ALERTS */
        .alert { padding:16px 20px; border-radius:var(--radius-md); margin-bottom:24px; font-weight:500; font-size: 14px; display: flex; align-items: center; gap: 12px; }
        .alert-danger { background: var(--danger-soft); color: #b91c1c; border: 1px solid #fca5a5; }
        .alert-success { background: var(--success-soft); color: #047857; border: 1px solid #6ee7b7; }

        /* CARD COMPONENT */
        .card { background: var(--surface); padding: 32px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); }
        .title { display:flex; align-items:center; gap:10px; margin-bottom:28px; padding-bottom: 16px; border-bottom: 1px solid var(--border-color); }
        .title h2 { font-size:18px; font-weight:600; color:var(--text-main); }
        .title i { color:var(--primary); font-size:20px; }

        /* PROFILE AVATAR BLOCK */
        .profile { display:flex; align-items:center; gap:24px; margin-bottom:32px; background: var(--bg-app); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-color); position: relative; }
        .profile-img-container { position: relative; flex-shrink: 0; width: 84px; height: 84px; }
        .profile-img-container img { width:84px; height:84px; object-fit:cover; border-radius:50%; border:3px solid var(--surface); box-shadow: var(--shadow-sm); }
        .profile-info h3 { font-size:18px; font-weight: 600; color:var(--text-main); text-transform: capitalize; }
        .profile-info p { color:var(--text-muted); font-size: 13px; margin-top: 2px; }

        .avatar-actions { display: flex; flex-direction: column; gap: 8px; margin-top: 8px; }
        .btn-upload { background: var(--primary-soft); color: var(--primary); padding: 6px 12px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 600; cursor: pointer; border: 1px solid var(--border-color); display: inline-block; text-align: center; }
        .btn-delete { background: var(--danger-soft); color: var(--danger); padding: 6px 12px; border-radius: var(--radius-sm); font-size: 12px; font-weight: 600; cursor: pointer; border: 1px solid #fca5a5; display: inline-block; text-align: center; font-family: inherit; }

        /* FORM */
        .form { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .group { display:flex; flex-direction:column; }
        .group-full { grid-column: 1 / -1; display:flex; flex-direction:column; }
        
        label { margin-bottom:8px; font-weight:500; font-size: 13px; color:var(--text-main); }
        input[type="text"],
        input[type="email"],
        input[type="password"] { 
            height:46px; border:1px solid var(--border-color); border-radius:var(--radius-md); 
            padding:0 16px; background: var(--bg-app); color: var(--text-main); 
            font-size: 14px; transition: all .2s ease; width: 100%;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus { 
            outline:none; border-color:var(--primary-light); background: var(--surface); 
            box-shadow:0 0 0 4px var(--primary-soft); 
        }
        input::placeholder { color: var(--text-muted); font-size: 13px; opacity: 0.7; }

        /* CHECKBOX */
        .check { display:flex; align-items:center; gap:12px; margin-top:24px; background: var(--bg-app); padding:16px; border-radius:var(--radius-md); border: 1px solid var(--border-color); }
        .check input[type="checkbox"] { width:16px; height:16px; cursor: pointer; accent-color: var(--primary); }
        .check label { margin-bottom: 0; cursor: pointer; color: var(--text-muted); font-size: 13px; font-weight: 500; }

        button[type="submit"].btn-save { 
            margin-top:24px; border:none; background: var(--primary); color:white; padding:14px 28px; 
            border-radius:var(--radius-md); cursor:pointer; font-size:14px; font-weight:600; 
            display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: all .2s ease; 
            font-family: inherit;
        }
        button[type="submit"].btn-save:hover { background: var(--primary-light); transform:translateY(-1px); box-shadow: var(--shadow-md); }

        footer { text-align:center; padding: 24px 0; color: var(--text-muted); font-size: 13px; border-top: 1px solid var(--border-color); margin-top: 40px; }

        @media(max-width:900px){
            aside { left:-280px; }
            aside.show { left:0; box-shadow: var(--shadow-md); }
            .menu-btn { display:flex; align-items: center; justify-content: center; }
            .content { margin-left:0; padding:24px 16px; }
            .top h1 { font-size:22px; }
            .form { grid-template-columns:1fr; gap: 16px; }
            .card { padding: 20px; }
            button[type="submit"].btn-save { width: 100%; }
        }
    </style>
</head>
<body>

<aside id="sidebar">
    <div class="logo">
        <img src="../../../public/assets/images/logo.png" alt="logo">
        <h2>RentMaster</h2>
    </div>
    <nav>
        <a href="dashboard_locataire.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="contrat_locataire.php"><i class="fas fa-file-contract"></i> Contrat</a>
        <a href="paiement_locataire.php"><i class="fas fa-receipt"></i> Reçus</a>
        <a href="historique.php"><i class="fas fa-history"></i> Historique</a>
        <a href="profil_locataire.php" class="active"><i class="fas fa-user"></i> Profil</a>
    </nav>
    <div class="sidebar-footer">
        <a href="../../controllers/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</aside>

<div class="content">
    <div class="top">
        <div>
            <h1><i class="fas fa-user" style="color: var(--primary); font-size: 22px; margin-right: 4px;"></i> Mon Profil</h1>
            <p>Gérer vos informations de compte personnelles</p>
        </div>
        <button class="menu-btn" onclick="toggleMenu()">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <?php if(isset($error)) : ?>
        <div class="alert alert-danger">
            <i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['success'])) : ?>
        <div class="alert alert-success">
            <i class="fas fa-circle-check"></i> 
            <?= $_GET['success'] == 2 ? "Photo supprimée avec succès." : "Votre profil a été mis à jour avec succès." ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="title">
            <i class="fas fa-sliders"></i>
            <h2>Configuration des accès</h2>
        </div>

        <!-- Formulaire séparé pour la suppression de photo -->
        <?php if(!empty($locataire['photo'])): ?>
        <form method="POST" id="form-delete-photo" style="display:none;">
            <input type="hidden" name="delete_photo" value="1">
        </form>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            
            <div class="profile">

                <!-- ✅ PHOTO DU LOCATAIRE -->
                <div class="profile-img-container">
                    <?php if (!empty($locataire['photo'])): ?>
                        <img 
                            id="preview-photo"
                            src="<?= htmlspecialchars($avatar_url_path . $locataire['photo']) ?>" 
                            alt="Photo de profil"
                            onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($locataire['prenom'] . '+' . $locataire['nom']) ?>&background=1e40af&color=fff&size=84'">
                    <?php else: ?>
                        <img 
                            id="preview-photo"
                            src="https://ui-avatars.com/api/?name=<?= urlencode($locataire['prenom'] . '+' . $locataire['nom']) ?>&background=1e40af&color=fff&size=84" 
                            alt="Avatar par défaut">
                    <?php endif; ?>
                </div>

                <div class="profile-info">
                    <h3><?= htmlspecialchars($locataire['prenom'] . ' ' . $locataire['nom']) ?></h3>
                    <p><?= htmlspecialchars($locataire['email']) ?></p>

                    <div class="avatar-actions">
                        <label for="photo-input" class="btn-upload">
                            <i class="fas fa-camera"></i> Modifier la photo
                        </label>
                        <input type="file" id="photo-input" name="photo" accept="image/jpeg, image/png, image/jpg, image/webp" style="display:none;">
                        
                        <?php if(!empty($locataire['photo'])): ?>
                            <button type="button" class="btn-delete" onclick="confirmerSuppression()">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <div class="form">
                <div class="group">
                    <label>Prénom</label>
                    <input type="text" name="prenom" value="<?= htmlspecialchars($locataire['prenom']) ?>" required>
                </div>

                <div class="group">
                    <label>Nom</label>
                    <input type="text" name="nom" value="<?= htmlspecialchars($locataire['nom']) ?>" required>
                </div>

                <div class="group-full">
                    <label>Adresse Email officielle</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($locataire['email']) ?>" required>
                </div>

                <div class="group">
                    <label>Mot de passe actuel</label>
                    <input type="password" name="currentPassword" placeholder="Requis si modification de sécurité">
                </div>

                <div class="group">
                    <label>Nouveau mot de passe</label>
                    <input type="password" name="newPassword" placeholder="Laissez vide pour conserver l'actuel">
                </div>
            </div>

            <div class="check">
                <input type="checkbox" id="notif" checked>
                <label for="notif">Recevoir les alertes de factures et quittances par email</label>
            </div>

            <button type="submit" name="update" class="btn-save">
                <i class="fas fa-floppy-disk"></i> Enregistrer les modifications
            </button>
        </form>
    </div>

    <footer>
        &copy; 2026 RentMaster - Tous droits réservés
    </footer>
</div>

<script>
function toggleMenu(){
    document.getElementById("sidebar").classList.toggle("show");
}

function confirmerSuppression() {
    if (confirm('Supprimer votre photo de profil ?')) {
        document.getElementById('form-delete-photo').submit();
    }
}

document.getElementById('photo-input').onchange = function() {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-photo').src = e.target.result;
        };
        reader.readAsDataURL(this.files[0]);
    }
};
</script>
</body>
</html>