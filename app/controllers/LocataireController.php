<?php
class LocataireController {
    private $pdo;

    public function __construct() {
        try {
            // Connexion à la base de données sur le port 3307
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

    public function saveLocataire($data) {
        if (!empty($data['id_locataire'])) {
            // MODE UPDATE
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
            // MODE INSERT SIMPLE : Enregistrement en texte clair (SANS PASSWORD_HASH)
            $password = !empty($data['mot_de_passe']) ? $data['mot_de_passe'] : '1111';

            $sql = "INSERT INTO locataires (id_bailleur, matricule, nom, prenom, telephone, email, adresse, mot_de_passe, first_login, created_at, date_naissance, sexe, etat_civil, nationalite, profession, statut) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), ?, ?, ?, ?, ?, 'actif')";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['id_bailleur'] ?? null,
                $data['matricule'], 
                $data['nom'], 
                $data['prenom'], 
                $data['telephone'], 
                $data['email'], 
                $data['adresse'] ?? '', 
                $password, 
                $data['date_naissance'], 
                $data['sexe'], 
                $data['etat_civil'], 
                $data['nationalite'], 
                $data['profession']
            ]);
        }
    }

    public function deleteLocataire($id) {
        $stmt = $this->pdo->prepare("DELETE FROM locataires WHERE id_locataire = ?");
        // CORRECTION ICI : Ajout du $ manquant devant id
        $stmt->execute([$id]); 
    }
}

// --- SYSTEME DE ROUTAGE INTERNE ---
$controller = new LocataireController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save') {
        $controller->saveLocataire($_POST);
        
        // Redirection vers l'affichage de la liste des locataires
        // NOTE : Si l'erreur 404 persiste, remplacez par le chemin absolu ex: '/rentmaster/views/bailleur/locataire.php'
        header('Location: ../views/bailleur/locataire.php');
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $controller->deleteLocataire($_GET['id']);
    
    // Redirection après suppression
    header('Location: ../views/bailleur/locataire.php');
    exit;
}