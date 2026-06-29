<?php
class Message {
    private $db;

    public function __construct($database_connection) {
        $this->db = $database_connection;
    }

    // Récupérer la discussion entre un bailleur et un locataire
    public function getConversation($bailleur_id, $locataire_id) {
        $query = "SELECT * FROM messages 
                  WHERE (expediteur_id = :b_id AND expediteur_type = 'bailleur' AND destinataire_id = :l_id AND destinataire_type = 'locataire')
                     OR (expediteur_id = :l_id AND expediteur_type = 'locataire' AND destinataire_id = :b_id AND destinataire_type = 'bailleur')
                  ORDER BY date_message ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'b_id' => intval($bailleur_id),
            'l_id' => intval($locataire_id)
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Envoyer un nouveau message
    public function envoyer($expediteur_id, $expediteur_type, $destinataire_id, $destinataire_type, $contenu) {
        $query = "INSERT INTO messages (expediteur_id, expediteur_type, destinataire_id, destinataire_type, contenu, date_message) 
                  VALUES (:exp_id, :exp_type, :dest_id, :dest_type, :contenu, NOW())";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            'exp_id'   => intval($expediteur_id),
            'exp_type' => trim($expediteur_type),
            'dest_id'  => intval($destinataire_id),
            'dest_type'=> trim($destinataire_type),
            'contenu'  => $contenu
        ]);
    }

    // Récupérer tous les locataires liés à un bailleur spécifique (Correction du statut)
    public function getLocatairesDuBailleur($bailleur_id) {
        // Utilisation de LOWER() pour éviter les problèmes de majuscules/minuscules sur 'actif'
        $query = "SELECT id_locataire, matricule, nom, prenom, photo FROM locataires WHERE id_bailleur = :b_id AND LOWER(statut) = 'actif'";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['b_id' => intval($bailleur_id)]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer les infos d'un locataire précis
    public function getInfosLocataire($locataire_id) {
        $query = "SELECT id_locataire, nom, prenom, matricule FROM locataires WHERE id_locataire = :l_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['l_id' => intval($locataire_id)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Récupérer les infos du bailleur d'un locataire donné
    public function getBailleurDuLocataire($locataire_id) {
        $query = "SELECT b.id_bailleur, b.nom, b.prenom 
                  FROM bailleurs b
                  JOIN locataires l ON l.id_bailleur = b.id_bailleur
                  WHERE l.id_locataire = :l_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['l_id' => intval($locataire_id)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}