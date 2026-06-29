<?php
require_once __DIR__ . "/../app/config/database.php";

class Paiement
{
    private $conn;
    private $table = "paiements"; // Ajusté selon l'orthographe standard (ou paiments si tu as fait une coquille en BDD)

    public function __construct()
    {
        $db = new Database();
        $this->conn = $db->connect();
    }

    // Historique des paiements liés aux contrats d'un bailleur (pour le dashboard bailleur)
    public function getByBailleur($id_bailleur)
    {
        $sql = "SELECT p.*, b.titre AS titre_bien, l.nom, l.prenom 
                FROM " . $this->table . " p
                JOIN contrats c ON p.id_contrat = c.id_contrat
                JOIN biens b ON c.id_bien = b.id_bien
                JOIN locataires l ON c.id_locataire = l.id_locataire
                WHERE c.id_bailleur = :id_bailleur ORDER BY p.date_paiement DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_bailleur' => $id_bailleur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}