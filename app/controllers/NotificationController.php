<?php
require_once __DIR__ . '/../config/database.php';

// Interception de l'action de suppression asynchrone (Fetch)
if (basename($_SERVER['PHP_SELF']) === 'NotificationController.php' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    require_once __DIR__ . '/../auth/session.php';
    verifierConnexion();
    
    if (!empty($_GET['id'])) {
        $controller = new NotificationController();
        // Sécurité : on passe l'ID de la notif et l'ID de l'utilisateur connecté
        $succes = $controller->deleteNotification($_GET['id'], $_SESSION['id']);
        
        http_response_code($succes ? 200 : 403);
        echo json_encode(["status" => $succes ? "success" : "error"]);
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "ID manquant."]);
    }
    exit;
}

class NotificationController
{
    private $conn;

    public function __construct()
    {
        $this->conn = (new Database())->connect();
    }

    /* ==========================================================================
       SYNCHRONISATION GLOBALE + NETTOYAGE AUTO (3 JOURS)
       ========================================================================== */
    public function synchroniserEvenements($user_id, $user_type)
    {
        try {
            // 1. NETTOYAGE : Supprime les notifications de plus de 3 jours
            $sqlClean = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 3 DAY)";
            $this->conn->query($sqlClean);

            // 2. LOGIQUE LOCATAIRE : Rappel de loyer automatique (Déclenché dès le 29 du mois)
            if ($user_type === 'locataire') {
                // Récupération du contrat actif
                $sqlContrat = "SELECT id_contrat, montant FROM contrats WHERE id_locataire = ? AND statut = 'Actif' LIMIT 1";
                $stmtContrat = $this->conn->prepare($sqlContrat);
                $stmtContrat->execute([$user_id]);
                $contrat = $stmtContrat->fetch(PDO::FETCH_ASSOC);

                if ($contrat) {
                    $id_contrat = $contrat['id_contrat'];
                    $loyer_total = (float)$contrat['montant'];
                    $mois_courant = date('Y-m'); // Ex: '2026-06'
                    
                    // Vérification du jour du mois (Aujourd'hui nous sommes le 29, donc ça passe !)
                    if ((int)date('d') >= 29) {
                        
                        // Calcul des montants déjà payés pour ce mois
                        $sqlCheckPay = "SELECT SUM(montant_verse) as total FROM paiements WHERE id_contrat = ? AND mois_annee = ? AND statut = 'Complet'";
                        $stmtCheckPay = $this->conn->prepare($sqlCheckPay);
                        $stmtCheckPay->execute([$id_contrat, $mois_courant]);
                        $paiement = $stmtCheckPay->fetch(PDO::FETCH_ASSOC);
                        $deja_paye = $paiement['total'] ? (float)$paiement['total'] : 0;

                        // Si le loyer n'est pas totalement réglé
                        if ($deja_paye < $loyer_total) {
                            $reste_a_payer = $loyer_total - $deja_paye;
                            $titreNotif = "Rappel Échéance : Loyer " . date('m/Y');
                            $contenuNotif = "Votre loyer du mois courant doit être régularisé. Reste dû : " . number_format($reste_a_payer, 2, ',', ' ') . " €.";

                            // Sécurité Anti-doublon : Évite de recréer la même notification aujourd'hui
                            $sqlCheckNotif = "SELECT COUNT(*) FROM notifications WHERE utilisateur_id = ? AND type_utilisateur = ? AND titre = ? AND DATE(created_at) = CURDATE()";
                            $stmtCheckNotif = $this->conn->prepare($sqlCheckNotif);
                            $stmtCheckNotif->execute([$user_id, $user_type, $titreNotif]);

                            if ($stmtCheckNotif->fetchColumn() == 0) {
                                $this->ajouter($user_id, $user_type, $titreNotif, $contenuNotif);
                            }
                        }
                    }
                }
            }

            // 3. LOGIQUE BAILLEUR : Synchronisation et alertes basées sur la table `evenements`
            if ($user_type === 'bailleur') {
                // Récupère les événements prévus pour aujourd'hui ou demain
                $sqlEvents = "SELECT * FROM evenements WHERE (date_evenement = CURDATE() OR date_evenement = DATE_ADD(CURDATE(), INTERVAL 1 DAY))";
                $events = $this->conn->query($sqlEvents)->fetchAll(PDO::FETCH_ASSOC);

                foreach ($events as $event) {
                    $titreNotif = "Rappel Événement : " . $event['titre'];
                    $contenuNotif = "Le rappel pour l'événement '" . $event['description'] . "' est planifié le " . date('d/m/Y', strtotime($event['date_evenement'])) . ".";

                    // Anti-doublon journalier pour le bailleur
                    $sqlCheckNotif = "SELECT COUNT(*) FROM notifications WHERE utilisateur_id = ? AND type_utilisateur = ? AND titre = ? AND DATE(created_at) = CURDATE()";
                    $stmtCheckNotif = $this->conn->prepare($sqlCheckNotif);
                    $stmtCheckNotif->execute([$user_id, $user_type, $titreNotif]);

                    if ($stmtCheckNotif->fetchColumn() == 0) {
                        $this->ajouter($user_id, $user_type, $titreNotif, $contenuNotif);
                    }
                }
            }

        } catch (PDOException $e) {
            // Échec silencieux pour éviter de bloquer l'application
        }
    }

    /* ==========================================================================
       MÉTHODES CRUD
       ========================================================================== */
    
    // Récupérer les notifications d'un utilisateur
    public function getNotifications($user_id, $user_type)
    {
        $sql = "SELECT * FROM notifications WHERE utilisateur_id = ? AND type_utilisateur = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$user_id, $user_type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Insérer une nouvelle notification
    public function ajouter($user_id, $user_type, $titre, $contenu)
    {
        $sql = "INSERT INTO notifications (utilisateur_id, type_utilisateur, titre, contenu, lu, created_at) VALUES (?, ?, ?, ?, 0, NOW())";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$user_id, $user_type, $titre, $contenu]);
    }

    // Supprimer/Masquer une notification avec vérification de propriété
    public function deleteNotification($id_notification, $id_user)
    {
        // Si l'id contient des lettres (anciennes alertes dynamiques JS), on valide directement sans toucher à la BDD
        if (!is_numeric($id_notification)) {
            return true;
        }

        $sql = "DELETE FROM notifications WHERE id = ? AND utilisateur_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([intval($id_notification), $id_user]);
    }

    // Marquer toutes les notifications d'un utilisateur comme lues
    public function marquerCommeLu($user_id, $user_type)
    {
        $sql = "UPDATE notifications SET lu = 1 WHERE utilisateur_id = ? AND type_utilisateur = ? AND lu = 0";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$user_id, $user_type]);
    }
}