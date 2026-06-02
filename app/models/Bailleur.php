<?php

require_once "app/config/database.php";

class Bailleur
{
    private $conn;

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    // =========================
    // AJOUTER UN BAILLEUR
    // =========================
    public function create($nom, $email, $password)
    {
        $sql = "INSERT INTO bailleurs (nom, email, mot_de_passe)
                VALUES (:nom, :email, :password)";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':nom' => $nom,
            ':email' => $email,
            ':password' => password_hash($password, PASSWORD_DEFAULT)
        ]);
    }

    // =========================
    // TROUVER UN BAILLEUR
    // =========================
    public function findByEmail($email)
    {
        $sql = "SELECT * FROM bailleurs WHERE email = :email";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':email' => $email]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // =========================
    // SUPPRIMER BAILLEUR
    // =========================
    public function delete($id)
    {
        $sql = "DELETE FROM bailleurs WHERE id_bailleur = :id";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([':id' => $id]);
    }

    // =========================
    // BLOQUER BAILLEUR
    // =========================
    public function block($id)
    {
        $sql = "UPDATE bailleurs SET statut = 'bloqué'
                WHERE id_bailleur = :id";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([':id' => $id]);
    }
}