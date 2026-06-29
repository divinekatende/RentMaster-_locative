<?php
require_once __DIR__ . '/../../auth/session.php';
verifierConnexion();
verifierRole(['bailleur']);

require_once __DIR__ . '/../../config/database.php';

$conn = (new Database())->connect();
$id_bailleur = $_SESSION['id'];

/* ==========================================================================
   1. RÉCUPÉRER LE PROFIL DU BAILLEUR
   ========================================================================== */
$stmt = $conn->prepare("SELECT * FROM bailleurs WHERE id_bailleur = ? LIMIT 1");
$stmt->execute([$id_bailleur]);
$profil = $stmt->fetch(PDO::FETCH_ASSOC);

// Génération des initiales (Prénom + Nom)
$initiales = strtoupper(substr($profil['prenom'] ?? '', 0, 1) . substr($profil['nom'] ?? '', 0, 1));
if (empty($initiales)) { $initiales = "RM"; }

// Vérification de la photo existante
$has_photo = !empty($profil['photo']) && file_exists(__DIR__ . '/../../public/uploads/' . $profil['photo']);
$avatar_url = $has_photo ? '../../public/uploads/' . $profil['photo'] : '';

$error = null;

/* ==========================================================================
   2. MISE À JOUR DU PROFIL & UPLOAD
   ========================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $nom_photo = $profil['photo']; 

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['photo']['tmp_name'];
        $fileName = $_FILES['photo']['name'];
        $fileSize = $_FILES['photo']['size'];
        
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $extensionsAutorisees = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($fileExtension, $extensionsAutorisees)) {
            if ($fileSize <= 2 * 1024 * 1024) { 
                $uploadFileDir = __DIR__ . '/../../public/uploads/';
                
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }

                $nouveauNomPhoto = 'bailleur_' . $id_bailleur . '_' . time() . '.' . $fileExtension;
                $dest_path = $uploadFileDir . $nouveauNomPhoto;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    if (!empty($profil['photo']) && file_exists($uploadFileDir . $profil['photo'])) {
                        unlink($uploadFileDir . $profil['photo']);
                    }
                    $nom_photo = $nouveauNomPhoto;
                } else {
                    $error = "Erreur lors de l'enregistrement de l'image.";
                }
            } else {
                $error = "L'image est trop lourde (max 2 Mo).";
            }
        } else {
            $error = "Format invalide (JPG, JPEG, PNG, WEBP uniquement).";
        }
    }

    if (!$error) {
        if (!empty($password)) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE bailleurs SET nom = ?, email = ?, mot_de_passe = ?, photo = ? WHERE id_bailleur = ?");
            $stmt->execute([$nom, $email, $passwordHash, $nom_photo, $id_bailleur]);
        } else {
            $stmt = $conn->prepare("UPDATE bailleurs SET nom = ?, email = ?, photo = ? WHERE id_bailleur = ?");
            $stmt->execute([$nom, $email, $nom_photo, $id_bailleur]);
        }

        header("Location: parametres.php?success=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - RentMaster</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg: #f4f7fb;
            --white: #ffffff;
            --blue: #2563eb;
            --blue-light: #3b82f6;
            --blue-soft: #eaf1ff;
            --text: #1e293b;
            --text-soft: #64748b;
            --border: #ddd;
            --radius: 14px;
        }

        body.dark {
            --bg: #1e293b;
            --white: #1f2937;
            --blue: #3b82f6;
            --blue-light: #60a5fa;
            --blue-soft: #334155;
            --text: #f4f7fb;
            --text-soft: #cbd5e1;
            --border: #4b5563;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; min-height: 100vh; transition: background 0.3s; }

        /* MENU MOBILE */
        .menu-btn { display: none; position: fixed; top: 15px; left: 15px; background: var(--blue); color: white; border: none; padding: 10px; border-radius: 8px; z-index: 2000; cursor: pointer; }

        /* SIDEBAR */
        aside { width: 220px; background: var(--white); display: flex; flex-direction: column; padding: 20px; transition: .3s; border-right: 1px solid var(--border); }
        aside img#logo { width: 150px; margin-bottom: 20px; }
        aside a { color: var(--text-soft); text-decoration: none; margin-bottom: 10px; display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 8px; transition: .3s; }
        aside a:hover { background: var(--blue-soft); color: var(--blue); }

        /* MAIN CONTENT */
        main { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; justify-content: space-between; }
        .content-wrap { width: 100%; max-width: 800px; margin: 0 auto; }
        h1 { margin-bottom: 20px; color: var(--text); font-size: 28px; }
        h2 { margin: 20px 0 10px 0; font-size: 20px; }

        /* TABS SYSTEM */
        .tabs-container { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid var(--blue-soft); }
        .tabs { display: flex; gap: 5px; }
        .tab-btn { padding: 12px 20px; border: none; border-radius: 10px 10px 0 0; background: transparent; cursor: pointer; font-weight: 600; transition: 0.3s; color: var(--text-soft); }
        .tab-btn.active { background: var(--blue); color: white; }
        .tab-content { display: none; background: var(--white); padding: 30px; border-radius: 0 0 10px 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .tab-content.active { display: block; }

        /* ZONE AVATAR INTERACTIVE ET DESIGN */
        .profile-image-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
            gap: 10px;
        }

        .avatar-wrapper {
            position: relative;
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: var(--blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: 700;
            letter-spacing: 1px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
            border: 3px solid var(--white);
            outline: 2px solid var(--blue);
            overflow: hidden;
        }

        .avatar-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }

        /* Masquer l'image si elle n'existe pas */
        .avatar-wrapper img[src=""] {
            display: none;
        }

        /* Bouton "Ajouter une photo" stylisé */
        .upload-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--blue);
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .upload-label:hover { color: var(--blue-light); text-decoration: underline; }
        .hidden-input { display: none; }

        /* THEME TOGGLE BUTTON */
        #themeToggle { background: var(--blue); color: white; border: none; padding: 10px 12px; border-radius: 8px; cursor: pointer; font-size: 16px; transition: 0.3s; }
        #themeToggle:hover { background: var(--blue-light); }

        /* FORMS & INPUTS */
        .info-form { display: flex; flex-direction: column; gap: 15px; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group label { font-weight: 600; font-size: 14px; }
        .form-group input, .settings select { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid var(--border); background: var(--white); color: var(--text); font-size: 15px; }
        .info-form button { padding: 12px; background: var(--blue); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 16px; transition: 0.3s; }
        .info-form button:hover { background: var(--blue-light); }

        /* NOTIFICATIONS */
        .success { background: #d1fae5; color: #065f46; padding: 12px; margin-bottom: 15px; border-radius: 8px; font-weight: 600; }
        .error-msg { background: #fee2e2; color: #991b1b; padding: 12px; margin-bottom: 15px; border-radius: 8px; font-weight: 600; }

        .settings { display: flex; flex-direction: column; gap: 15px; }
        .settings label { display: flex; align-items: center; gap: 10px; font-weight: 600; cursor: pointer; }
        .settings input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
        ul { margin-left: 20px; line-height: 1.6; }
        li { margin-bottom: 8px; }

        footer { text-align: center; padding: 20px; background: var(--white); margin-top: 40px; border-radius: 10px; font-size: 0.9em; color: var(--text-soft); border: 1px solid var(--border); }
        footer a { color: var(--blue); text-decoration: none; font-weight: 600; }
        footer a:hover { color: var(--blue-light); }

        @media (max-width: 900px) {
            body { flex-direction: column; }
            .menu-btn { display: block; }
            aside { position: fixed; left: -250px; top: 0; height: 100%; z-index: 1500; width: 220px; }
            aside.show { left: 0; }
            main { padding: 60px 20px 20px 20px; }
            .tabs-container { flex-direction: column; align-items: flex-start; gap: 10px; border-bottom: none; }
            .tabs { width: 100%; flex-direction: column; }
            .tab-btn { border-radius: 8px; text-align: left; }
        }
    </style>
</head>
<body>

    <button class="menu-btn" id="menuBtn"><i class="fas fa-bars"></i></button>

    <aside id="sidebar">
        <img src="../../../public/assets/images/logo.png" id="logo" alt="Logo RentMaster">
        <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
        <a href="bien.php"><i class="fa fa-building"></i> Biens</a>
        <a href="locataire.php"><i class="fa fa-users"></i> Locataires</a>
        <a href="contrat.php"><i class="fa fa-file-contract"></i> Contrats</a>
        <a href="paiement.php"><i class="fa fa-credit-card"></i> Paiements</a>
        <a href="../../../index.html"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </aside>

    <main>
        <div class="content-wrap">
            <h1>Paramètres</h1>

            <div class="tabs-container">
                <div class="tabs">
                    <button class="tab-btn active" data-tab="apropos"><i class="fas fa-info-circle"></i> À propos / Guide</button>
                    <button class="tab-btn" data-tab="info"><i class="fas fa-user"></i> Mon Profil</button>
                    <button class="tab-btn" data-tab="parametres"><i class="fas fa-cog"></i> Préférences</button>
                </div>
                <button id="themeToggle" title="Changer le thème"><i class="fas fa-moon"></i></button>
            </div>

            <div id="apropos" class="tab-content active">
                <h2>À propos</h2>
                <p><strong>RentMaster</strong> est une application de gestion immobilière créée par <strong>Divine Katende</strong>. Elle vous aide à gérer vos biens, locataires, contrats et paiements facilement.</p>
                
                <h2>Guide d'utilisation</h2>
                <ul>
                    <li><strong>Dashboard :</strong> Vue générale et statistiques en temps réel.</li>
                    <li><strong>Biens :</strong> Ajouter, modifier ou supprimer vos propriétés.</li>
                    <li><strong>Locataires :</strong> Gérer les dossiers et informations de vos locataires.</li>
                    <li><strong>Contrats :</strong> Générer et suivre l'état de vos baux.</li>
                    <li><strong>Paiements :</strong> Suivre et enregistrer l'encaissement des loyers.</li>
                </ul>
            </div>

            <div id="info" class="tab-content">
                <h2>Modifier mon profil</h2>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="success">Profil mis à jour avec succès.</div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="error-msg"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="info-form">
                    
                    <div class="profile-image-section">
                        <div class="avatar-wrapper" id="avatarWrapper">
                            <span id="avatarInitials"><?= $initiales ?></span>
                            <img id="avatarImg" src="<?= $avatar_url ?>" alt="Avatar">
                        </div>
                        
                        <label for="photo" class="upload-label">
                            <i class="fas fa-camera"></i> Ajouter ou changer la photo
                        </label>
                        <input type="file" id="photo" name="photo" class="hidden-input" accept="image/png, image/jpeg, image/jpg, image/webp">
                    </div>
                <div class="form-group">
                        <label for="nom">Nom complet</label>
                        <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($profil['nom'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Adresse Email</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($profil['email'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Nouveau mot de passe</label>
                        <input type="password" id="password" name="password" placeholder="Laissez vide si inchangé">
                    </div>

                    <button type="submit">Sauvegarder les modifications</button>
                </form>
            </div>

            <div id="parametres" class="tab-content">
                <h2>Préférences système</h2>
                <div class="settings">
                    <label><input type="checkbox" id="notifCheck"> Activer les notifications push</label>
                    <label><input type="checkbox" id="darkModeSetting"> Mode sombre permanent</label>
                    
                    <div class="form-group" style="margin-top: 10px;">
                        <label for="langue">Langue de l'interface</label>
                        <select id="langue">
                            <option value="fr">Français</option>
                            <option value="en">English</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <footer>
          <p>&copy; 2026 RentMaster. Interface d'administration foncière. | 
            <a href="mailto:divinekatende23@gmail.com">Support client</a> | 
            <a href="https://divinekatende.github.io/DIVKT/" target="_blank">Portfolio Développeur</a>
          </p>
        </footer>
    </main>

    <script>
    // Gestion de l'aperçu dynamique de la photo en JS direct
    const photoInput = document.getElementById('photo');
    const avatarImg = document.getElementById('avatarImg');
    const avatarInitials = document.getElementById('avatarInitials');

    photoInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.addEventListener('load', function() {
                avatarImg.setAttribute('src', this.result);
                avatarImg.style.display = 'block'; // On affiche la balise img
            });
            reader.readAsDataURL(file);
        }
    });

    // Gestion du basculement des onglets
    const tabs = document.querySelectorAll(".tab-btn");
    const contents = document.querySelectorAll(".tab-content");

    tabs.forEach(tab => {
        tab.addEventListener("click", () => {
            tabs.forEach(t => t.classList.remove("active"));
            contents.forEach(c => c.classList.remove("active"));

            tab.classList.add("active");
            const targetId = tab.getAttribute("data-tab");
            document.getElementById(targetId).classList.add("active");
        });
    });

    function toggleDarkMode(isDark) {
        document.body.classList.toggle('dark', isDark);
        darkModeCheckbox.checked = isDark;
        themeToggle.innerHTML = isDark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
    }

    const themeToggle = document.getElementById('themeToggle');
    const darkModeCheckbox = document.getElementById('darkModeSetting');

    themeToggle.addEventListener('click', () => {
        const setDark = !document.body.classList.contains('dark');
        toggleDarkMode(setDark);
    });

    darkModeCheckbox.addEventListener('change', () => {
        toggleDarkMode(darkModeCheckbox.checked);
    });

    const menuBtn = document.getElementById("menuBtn");
    const sidebar = document.getElementById("sidebar");
    menuBtn.addEventListener("click", () => sidebar.classList.toggle("show"));
    </script>
</body>
</html>