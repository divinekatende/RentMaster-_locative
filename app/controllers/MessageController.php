<?php

require_once __DIR__ . '/../config/database.php';

class MessageController
{
    private $conn;

    public function __construct()
    {
        $this->conn = (new Database())->connect();
    }

    /* =========================
        ENVOYER MESSAGE
    ========================= */
    public function envoyer($exp_id, $exp_type, $dest_id, $dest_type, $contenu)
    {
        $sql = "
            INSERT INTO messages 
            (expediteur_id, expediteur_type, destinataire_id, destinataire_type, contenu, date_message) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            $exp_id,
            $exp_type,
            $dest_id,
            $dest_type,
            $contenu
        ]);
    }

    /* =========================
        RÉCUPÉRER MESSAGES
    ========================= */
    public function getMessages($user_id, $user_type)
    {
        $sql = "
            SELECT * FROM messages 
            WHERE 
                (expediteur_id = ? AND expediteur_type = ?) 
                OR 
                (destinataire_id = ? AND destinataire_type = ?) 
            ORDER BY date_message ASC
        ";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            $user_id,
            $user_type,
            $user_id,
            $user_type
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
        CONTACTS AUTORISÉS (CORRIGÉ ET SÉCURISÉ)
    ========================= */
    public function getContacts($user_id, $user_type)
    {
        if ($user_type === 'bailleur') {
            // Le bailleur récupère tous les locataires qui lui sont directement associés
            $sql = "
                SELECT DISTINCT id_locataire AS id, nom, prenom 
                FROM locataires 
                WHERE id_bailleur = ?
            ";
        } else {
            // Le locataire récupère les informations du bailleur lié à son profil
            $sql = "
                SELECT DISTINCT b.id_bailleur AS id, b.nom, b.prenom 
                FROM bailleurs b
                INNER JOIN locataires l ON b.id_bailleur = l.id_bailleur
                WHERE l.id_locataire = ?
            ";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
        SUPPRIMER MESSAGE
    ========================= */
    public function deleteMessage($id_message, $user_id)
    {
        $sql = "
            DELETE FROM messages 
            WHERE id_message = ? 
            AND expediteur_id = ?
        ";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([$id_message, $user_id]);
    }
}