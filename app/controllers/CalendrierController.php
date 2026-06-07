<?php
class CalendrierController {
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    // Récupérer tous les événements d'un bailleur
    public function index($id_bailleur) {
        $sql = "SELECT e.*, CONCAT(l.nom, ' ', l.prenom) AS locataire_nom 
                FROM evenements e 
                LEFT JOIN locataires l ON e.id_locataire = l.id_locataire 
                WHERE e.id_bailleur = :id_bailleur 
                ORDER BY e.date_evenement ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_bailleur' => $id_bailleur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer les locataires pour le select du formulaire
    public function getLocataires($id_bailleur) {
        $sql = "SELECT id_locataire, nom, prenom FROM locataires WHERE id_bailleur = :id_bailleur ORDER BY nom ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_bailleur' => $id_bailleur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ajouter un événement
    public function create($data) {
        try {
            $sql = "INSERT INTO evenements (id_bailleur, id_locataire, titre, description, date_evenement) 
                    VALUES (:id_bailleur, :id_locataire, :titre, :description, :date_evenement)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id_bailleur'    => $data['id_bailleur'],
                'id_locataire'   => !empty($data['id_locataire']) ? $data['id_locataire'] : null,
                'titre'          => $data['titre'],
                'description'    => $data['description'],
                'date_evenement' => $data['date_evenement']
            ]);
            return ['ok' => true, 'msg' => "Événement ajouté avec succès !"];
        } catch (PDOException $e) {
            return ['ok' => false, 'msg' => "Erreur lors de l'enregistrement : " . $e->getMessage()];
        }
    }

    // Supprimer un événement
    public function delete($id_evenement, $id_bailleur) {
        $sql = "DELETE FROM evenements WHERE id_evenement = :id_evenement AND id_bailleur = :id_bailleur";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id_evenement' => $id_evenement, 'id_bailleur' => $id_bailleur]);
    }
}