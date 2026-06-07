<?php
class LocataireController {
    private $pdo;

    // Connexion automatique à la base de données
    public function __construct() {
        try {
            $this->pdo = new PDO("mysql:host=localhost;dbname=rentmaster;charset=utf8", "root", "", [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (Exception $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    // [READ] Récupérer tous les locataires (Mis à jour : locataires)
    public function getAllLocataires() {
        $stmt = $this->pdo->query("SELECT * FROM locataires ORDER BY id_locataire DESC");
        return $stmt->fetchAll();
    }

    // [READ ONE] Récupérer un locataire spécifique (Mis à jour : locataires)
    public function getLocataireById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM locataires WHERE id_locataire = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // [CREATE / UPDATE] Sauvegarder ou modifier un locataire (Mis à jour : locataires)
    public function saveLocataire($data) {
        // Hachage du mot de passe
        $password = !empty($data['mot_de_passe']) ? password_hash($data['mot_de_passe'], PASSWORD_BCRYPT) : '1111';

        if (!empty($data['id_locataire'])) {
            // MODE UPDATE (Modification)
            $sql = "UPDATE locataires SET 
                    matricule = ?, nom = ?, prenom = ?, date_naissance = ?, sexe = ?, 
                    etat_civil = ?, nationalite = ?, email = ?, telephone = ?, profession = ?, adresse = ?
                    WHERE id_locataire = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['matricule'], $data['nom'], $data['prenom'], $data['date_naissance'], $data['sexe'],
                $data['etat_civil'], $data['nationalite'], $data['email'], $data['telephone'], $data['profession'], 
                $data['adresse'], $data['id_locataire']
            ]);
        } else {
            // MODE INSERT (Ajout)
            $sql = "INSERT INTO locataires (id_bailleur, matricule, nom, prenom, telephone, email, adresse, mot_de_passe, first_login, created_at, date_naissance, sexe, etat_civil, nationalite, profession) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['id_bailleur'] ?? 1,
                $data['matricule'], $data['nom'], $data['prenom'], $data['telephone'], 
                $data['email'], $data['adresse'] ?? '', $password, $data['date_naissance'], 
                $data['sexe'], $data['etat_civil'], $data['nationalite'], $data['profession']
            ]);
        }
    }

    // [DELETE] Supprimer un locataire (Mis à jour : locataires)
    public function deleteLocataire($id) {
        $stmt = $this->pdo->prepare("DELETE FROM locataires WHERE id_locataire = ?");
        $stmt->execute([$id]);
    }
}

// --- ROUTAGE DES ACTIONS CRUD ---
$controller = new LocataireController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save') {
        $controller->saveLocataire($_POST);
        // Redirection vers la vue après l'action
        header('Location: ../views/bailleur/locataire.php');
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $controller->deleteLocataire($_GET['id']);
    // Redirection vers la vue après la suppression
    header('Location: ../views/bailleur/locataire.php');
    exit;
}