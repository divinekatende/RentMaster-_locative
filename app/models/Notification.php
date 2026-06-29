<?php
// 1. Initialisation de la session et sécurisation de l'accès
require_once __DIR__ . '/../../auth/session.php';
verifierConnexion();

require_once __DIR__ . '/../../controllers/NotificationController.php';

// On s'assure que l'ID de la notification est bien présent dans l'URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_notification = intval($_GET['id']);
    $id_user = $_SESSION['id']; // Sécurité : Seul le propriétaire de la notif peut la supprimer

    $controller = new NotificationController();
    
    // Appel de la méthode du Controller (qui exécute le DELETE avec vérification de l'utilisateur)
    $succes = $controller->deleteNotification($id_notification, $id_user);

    if ($succes) {
        // Envoi d'une réponse HTTP 200 (Succès) pour le fetch() JavaScript
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Notification supprimée."]);
    } else {
        // Erreur lors de la suppression (ex: la notif n'appartient pas à cet utilisateur)
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Action non autorisée ou introuvable."]);
    }
} else {
    // Mauvaise requête (pas d'ID fourni)
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "ID manquant."]);
}
exit;