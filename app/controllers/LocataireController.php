<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class LocataireController {
    private $pdo;
    private $dossierUploads;

    public function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host=127.0.0.1;port=3307;dbname=rentmaster;charset=utf8mb4",
                "root",
                "",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (Exception $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }

        $this->dossierUploads = __DIR__ . '/../../public/uploads/locataires/';
        if (!is_dir($this->dossierUploads)) {
            mkdir($this->dossierUploads, 0755, true);
        }
    }

    public function getAllLocataires() {
        $stmt = $this->pdo->query("SELECT * FROM locataires ORDER BY id_locataire DESC");
        return $stmt->fetchAll();
    }

    public function getLocataireById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM locataires WHERE id_locataire = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    private function genererMatriculeUnique() {
        do {
            $matricule = "R" . rand(1000, 9999);
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM locataires WHERE matricule = ?");
            $stmt->execute([$matricule]);
            $existe = $stmt->fetchColumn();
        } while ($existe > 0);

        return $matricule;
    }

    private function gererUploadPhoto($file, $ancienPhoto = null) {
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return $ancienPhoto;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Erreur lors de l'envoi de la photo (code " . $file['error'] . ").");
        }

        $extensionsAutorisees = ['jpg', 'jpeg', 'png', 'webp'];
        $tailleMaxOctets = 2 * 1024 * 1024;
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $extensionsAutorisees)) {
            throw new Exception("Format de photo non autorisé. Utilisez JPG, PNG ou WEBP.");
        }
        if ($file['size'] > $tailleMaxOctets) {
            throw new Exception("La photo est trop lourde (2 Mo maximum).");
        }

        $nomFichier = 'loc_' . uniqid() . '.' . $extension;
        $cheminComplet = $this->dossierUploads . $nomFichier;

        if (!move_uploaded_file($file['tmp_name'], $cheminComplet)) {
            throw new Exception("Impossible d'enregistrer la photo sur le serveur.");
        }

        if ($ancienPhoto && file_exists($this->dossierUploads . $ancienPhoto)) {
            @unlink($this->dossierUploads . $ancienPhoto);
        }

        return $nomFichier;
    }

    public function saveLocataire($data, $files = []) {
        if (!empty($data['id_locataire'])) {
            // =================================================================
            // MODE UPDATE
            // =================================================================
            $ancien = $this->getLocataireById($data['id_locataire']);
            $photo = $this->gererUploadPhoto($files['photo'] ?? null, $ancien['photo'] ?? null);

            $sql = "UPDATE locataires SET 
                    nom = ?, prenom = ?, date_naissance = ?, sexe = ?, 
                    etat_civil = ?, nationalite = ?, email = ?, telephone = ?, profession = ?, adresse = ?, photo = ?
                    WHERE id_locataire = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['nom'], $data['prenom'], $data['date_naissance'], $data['sexe'],
                $data['etat_civil'], $data['nationalite'], $data['email'], $data['telephone'],
                $data['profession'], $data['adresse'], $photo, $data['id_locataire']
            ]);
        } else {
            // =================================================================
            // MODE INSERT
            // ✅ CORRECTION : récupérer id_bailleur depuis le POST ou la session
            // =================================================================
            $id_bailleur = $data['id_bailleur'] ?? $_SESSION['id'] ?? null;

            $matriculeAuto = $this->genererMatriculeUnique();
            $motDePasseClair = "1111";
            $photo = $this->gererUploadPhoto($files['photo'] ?? null, null);

            $sql = "INSERT INTO locataires (id_bailleur, matricule, nom, prenom, telephone, email, adresse, mot_de_passe, first_login, created_at, date_naissance, sexe, etat_civil, nationalite, profession, statut, photo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), ?, ?, ?, ?, ?, 'actif', ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $success = $stmt->execute([
                $id_bailleur,
                $matriculeAuto, 
                $data['nom'], 
                $data['prenom'], 
                $data['telephone'], 
                $data['email'], 
                $data['adresse'] ?? '', 
                $motDePasseClair,
                $data['date_naissance'], 
                $data['sexe'], 
                $data['etat_civil'], 
                $data['nationalite'], 
                $data['profession'],
                $photo
            ]);

            if ($success && !empty($data['email'])) {
                $this->envoyerEmailBienvenue($data['email'], $data['prenom'], $matriculeAuto, $motDePasseClair);
            }
        }
    }

    private function envoyerEmailBienvenue($emailLocataire, $prenom, $matricule, $mdpClair) {
        $sujet = "Bienvenue sur RentMaster - Vos identifiants de connexion";
        $lienConnexion = "http://" . $_SERVER['HTTP_HOST'] . "/rentmaster/auth/login-locataire.php";

        $message = "Bonjour " . htmlspecialchars($prenom) . ",\n\n";
        $message .= "Un compte locataire a été créé pour vous sur l'application RentMaster.\n\n";
        $message .= "Voici vos identifiants par défaut pour vous connecter :\n";
        $message .= "----------------------------------------\n";
        $message .= "Matricule : " . $matricule . "\n";
        $message .= "Mot de passe par défaut : " . $mdpClair . "\n";
        $message .= "----------------------------------------\n\n";
        $message .= "Pour des raisons de sécurité, il vous sera demandé de modifier ce mot de passe lors de votre première connexion.\n\n";
        $message .= "Cliquez sur le lien suivant pour accéder à l'interface : \n" . $lienConnexion . "\n\n";
        $message .= "Cordialement,\nL'équipe RentMaster.";

        $headers = "From: no-reply@rentmaster.com\r\n";
        $headers .= "Reply-To: no-reply@rentmaster.com\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        @mail($emailLocataire, $sujet, $message, $headers);
    }

    public function deleteLocataire($id) {
        $locataire = $this->getLocataireById($id);
        if ($locataire && !empty($locataire['photo']) && file_exists($this->dossierUploads . $locataire['photo'])) {
            @unlink($this->dossierUploads . $locataire['photo']);
        }
        $stmt = $this->pdo->prepare("DELETE FROM locataires WHERE id_locataire = ?");
        $stmt->execute([$id]); 
    }
}

// --- SYSTEME DE ROUTAGE INTERNE ---
$controller = new LocataireController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save') {
        try {
            $controller->saveLocataire($_POST, $_FILES);
            header('Location: ../views/bailleur/locataire.php');
        } catch (Exception $e) {
            $_SESSION['erreur_upload'] = $e->getMessage();
            header('Location: ../views/bailleur/locataire.php?error=1');
        }
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $controller->deleteLocataire($_GET['id']);
    header('Location: ../views/bailleur/locataire.php');
    exit;
}