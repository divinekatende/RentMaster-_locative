<?php

class Locataire
{
    private $db;
    private $table = "locataires";

    // On passe la connexion PDO au constructeur
    public function __construct($dbConnection)
    {
        $this->db = $dbConnection;
    }

    /**
     * Trouver un locataire par son matricule
     */
    public function findByMatricule($matricule)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE matricule = :matricule LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':matricule', $matricule, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Mettre à jour le mot de passe d'un locataire et désactiver le premier login
     */
    public function updatePasswordAndActivate($id_locataire, $new_password)
    {
        // PAS DE HACHAGE : On garde le mot de passe en texte clair
        $password_to_save = trim($new_password);
        
        $query = "UPDATE " . $this->table . " 
                  SET mot_de_passe = :mot_de_passe, first_login = 0 
                  WHERE id_locataire = :id_locataire";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':mot_de_passe', $password_to_save, PDO::PARAM_STR);
        $stmt->bindParam(':id_locataire', $id_locataire, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
}