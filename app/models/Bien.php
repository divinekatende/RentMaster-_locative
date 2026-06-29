<?php
require_once __DIR__ . "/../app/config/database.php";

class Bien
{
    private $conn;
    private $table = "biens";

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Trouver tous les biens d'un bailleur
    public function getByBailleur($id_bailleur)
    {
        $sql = "SELECT * FROM " . $this->table . " WHERE id_bailleur = :id_bailleur ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_bailleur' => $id_bailleur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}