<?php
class HistoriqueController {
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    // Récupérer l'historique d'un bailleur
    public function index($id_bailleur) {
        $sql = "SELECT * FROM historique WHERE id_bailleur = :id_bailleur ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_bailleur' => $id_bailleur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ajouter une ligne à l'historique (Méthode globale réutilisable partout dans RentMaster)
    public function logEvent($id_bailleur, $action, $type_action = 'info') {
        $sql = "INSERT INTO historique (id_bailleur, action, type_action) VALUES (:id_bailleur, :action, :type_action)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id_bailleur'  => $id_bailleur,
            'action'       => $action,
            'type_action'  => $type_action
        ]);
    }

    // Supprimer une entrée spécifique
    public function delete($id_historique, $id_bailleur) {
        $sql = "DELETE FROM historique WHERE id_historique = :id_historique AND id_bailleur = :id_bailleur";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id_historique' => $id_historique, 'id_bailleur' => $id_bailleur]);
    }

    // Tout effacer
    public function clearAll($id_bailleur) {
        $sql = "DELETE FROM historique WHERE id_bailleur = :id_bailleur";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id_bailleur' => $id_bailleur]);
    }
}