<?php

require_once "app/config/database.php";

class User
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    public function loginAdmin($email)
    {
        $sql = "SELECT * FROM administrateurs WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function loginBailleur($email)
    {
        $sql = "SELECT * FROM bailleurs WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function loginLocataire($matricule)
    {
        $sql = "SELECT * FROM locataires WHERE matricule = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$matricule]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}