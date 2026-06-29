<?php

class Administrateur
{
    private $db;
    private $table = "administrateurs"; // Adapté selon ta table sql

    public function __construct($dbConnection)
    {
        $this->db = $dbConnection;
    }

    /**
     * Trouver un administrateur par son email
     */
    public function findByEmail($email)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}