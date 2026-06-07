<?php
require_once __DIR__ . '/../config/database.php';

class NotificationController
{
    private $conn;

    public function __construct()
    {
        $this->conn = (new Database())->connect();
    }

    /* ==========================================================================
       RÉCUPÉRER LES NOTIFICATIONS D'UN UTILISATEUR
       ========================================================================== */
    public function getNotifications($user_id, $user_type)
    {
        $sql = "
            SELECT * FROM notifications 
            WHERE utilisateur_id = ? AND type_utilisateur = ?
            ORDER BY created_at DESC
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$user_id, $user_type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ==========================================================================
       AJOUTER UNE NOTIFICATION (Retard de paiement, Message reçu, etc.)
       ========================================================================== */
    public function ajouter($user_id, $user_type, $titre, $contenu, $type_notif)
    {
        // $type_notif prendra la valeur : 'retard', 'paiementRecu' ou 'info'
        $sql = "
            INSERT INTO notifications (utilisateur_id, type_utilisateur, titre, contenu, lu, created_at) 
            VALUES (?, ?, ?, ?, 0, NOW())
        ";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$user_id, $user_type, $titre, $contenu]);
        // Envoyer une notification au destinataire du message
              $sql_notif = "INSERT INTO notifications (utilisateur_id, type_utilisateur, titre, contenu, lu, created_at) VALUES (?, ?, 'Nouveau message reçu', 'Vous avez reçu un nouveau message de la part de votre correspondant.', 0, NOW())";
              $this->conn->prepare($sql_notif)->execute([$dest_id, $dest_type]);
    }

    /* ==========================================================================
       SUPPRIMER UNE NOTIFICATION
       ========================================================================== */
    public function deleteNotification($id_notification, $user_id)
    {
        $sql = "
            DELETE FROM notifications 
            WHERE id_notification = ? AND utilisateur_id = ?
        ";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id_notification, $user_id]);
    }

    /* ==========================================================================
       MARQUER TOUT COMME LU (Bonus utile pour le design)
       ========================================================================== */
    public function marquerCommeLu($user_id, $user_type)
    {
        $sql = "
            UPDATE notifications SET lu = 1 
            WHERE utilisateur_id = ? AND type_utilisateur = ?
        ";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$user_id, $user_type]);
    }
}