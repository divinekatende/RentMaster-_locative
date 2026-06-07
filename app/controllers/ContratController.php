<?php

require_once __DIR__ . '/../config/database.php';

class ContratController
{
    private $conn;

    public function __construct()
    {
        $this->conn = (new Database())->connect();
    }

    /* =========================
       LISTE CONTRATS
    ========================= */
    public function index($id_bailleur)
    {
        $stmt = $this->conn->prepare("
            SELECT c.*, 
                   l.nom    AS locataire_nom,
                   l.prenom AS locataire_prenom,
                   b.titre  AS bien_titre,
                   b.prix   AS bien_prix
            FROM contrats c
            LEFT JOIN locataires l ON c.id_locataire = l.id_locataire
            LEFT JOIN biens      b ON c.id_bien       = b.id_bien
            WHERE c.id_bailleur = :id_bailleur
            ORDER BY c.id_contrat DESC
        ");
        $stmt->execute(['id_bailleur' => $id_bailleur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       VERIFIER SI BIEN DISPONIBLE
    ========================= */
    public function isBienDisponible($id_bien, $id_bailleur, $id_contrat_exclu = null)
    {
        // Un bien est dispo s'il n'a aucun contrat Actif (hors le contrat en cours de modif)
        $sql = "SELECT COUNT(*) FROM contrats 
                WHERE id_bien = :id_bien 
                AND id_bailleur = :id_bailleur 
                AND statut = 'Actif'";

        $params = ['id_bien' => $id_bien, 'id_bailleur' => $id_bailleur];

        if ($id_contrat_exclu) {
            $sql .= " AND id_contrat != :id_contrat_exclu";
            $params['id_contrat_exclu'] = $id_contrat_exclu;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() === 0; // true = disponible
    }

    /* =========================
       METTRE A JOUR STATUT BIEN
    ========================= */
    private function updateStatutBien($id_bien, $statut)
    {
        $this->conn->prepare("UPDATE biens SET statut = :statut WHERE id_bien = :id_bien")
                   ->execute(['statut' => $statut, 'id_bien' => $id_bien]);
    }

    /* =========================
       CREATE CONTRAT
    ========================= */
    public function create($data)
    {
        $required = ['id_bailleur','id_locataire','id_bien','date_debut','date_fin','statut'];
        foreach ($required as $field) {
            if (empty($data[$field])) return ['ok' => false, 'msg' => "Champ manquant : $field"];
        }

        if ($data['date_fin'] <= $data['date_debut'])
            return ['ok' => false, 'msg' => "La date de fin doit être après la date de début."];

        // ✅ Vérifier que le bien est disponible
        if (!$this->isBienDisponible($data['id_bien'], $data['id_bailleur']))
            return ['ok' => false, 'msg' => "Ce bien est déjà sous contrat actif. Terminez d'abord le contrat existant."];

        // Récupérer le prix depuis biens.prix
        $stmt = $this->conn->prepare("SELECT prix FROM biens WHERE id_bien = :id_bien AND id_bailleur = :id_bailleur");
        $stmt->execute(['id_bien' => $data['id_bien'], 'id_bailleur' => $data['id_bailleur']]);
        $bien = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$bien) return ['ok' => false, 'msg' => "Bien introuvable."];

        $stmt = $this->conn->prepare("
            INSERT INTO contrats 
                (id_bailleur, id_locataire, id_bien, date_debut, date_fin, montant, statut, created_at)
            VALUES 
                (:id_bailleur, :id_locataire, :id_bien, :date_debut, :date_fin, :montant, :statut, NOW())
        ");

        $ok = $stmt->execute([
            'id_bailleur'  => $data['id_bailleur'],
            'id_locataire' => $data['id_locataire'],
            'id_bien'      => $data['id_bien'],
            'date_debut'   => $data['date_debut'],
            'date_fin'     => $data['date_fin'],
            'montant'      => $bien['prix'],
            'statut'       => $data['statut']
        ]);

        // ✅ Si contrat Actif → bien devient "Loué"
        if ($ok && $data['statut'] === 'Actif') {
            $this->updateStatutBien($data['id_bien'], 'Loué');
        }

        return ['ok' => $ok, 'msg' => $ok ? '' : "Erreur lors de la création."];
    }

    /* =========================
       UPDATE CONTRAT
    ========================= */
    public function update($id_contrat, $data)
    {
        $existing = $this->find($id_contrat, $data['id_bailleur']);
        if (!$existing) return ['ok' => false, 'msg' => "Contrat introuvable."];

        // ✅ Vérifier dispo seulement si le bien change
        if ($existing['id_bien'] != $data['id_bien']) {
            if (!$this->isBienDisponible($data['id_bien'], $data['id_bailleur'], $id_contrat))
                return ['ok' => false, 'msg' => "Ce bien est déjà sous contrat actif."];
        }

        // Récupérer le prix du nouveau bien
        $stmt = $this->conn->prepare("SELECT prix FROM biens WHERE id_bien = :id_bien AND id_bailleur = :id_bailleur");
        $stmt->execute(['id_bien' => $data['id_bien'], 'id_bailleur' => $data['id_bailleur']]);
        $bien = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$bien) return ['ok' => false, 'msg' => "Bien introuvable."];

        $stmt = $this->conn->prepare("
            UPDATE contrats SET 
                id_locataire = :id_locataire,
                id_bien      = :id_bien,
                date_debut   = :date_debut,
                date_fin     = :date_fin,
                montant      = :montant,
                statut       = :statut
            WHERE id_contrat  = :id_contrat 
            AND   id_bailleur = :id_bailleur
        ");

        $ok = $stmt->execute([
            'id_contrat'   => $id_contrat,
            'id_bailleur'  => $data['id_bailleur'],
            'id_locataire' => $data['id_locataire'],
            'id_bien'      => $data['id_bien'],
            'date_debut'   => $data['date_debut'],
            'date_fin'     => $data['date_fin'],
            'montant'      => $bien['prix'],
            'statut'       => $data['statut']
        ]);

        if ($ok) {
            // ✅ Ancien bien libéré si on change de bien
            if ($existing['id_bien'] != $data['id_bien']) {
                $this->updateStatutBien($existing['id_bien'], 'Disponible');
            }

            // ✅ Statut du bien selon statut du contrat
            if ($data['statut'] === 'Actif') {
                $this->updateStatutBien($data['id_bien'], 'Loué');
            } else {
                // Contrat Terminé → bien redevient Disponible
                $this->updateStatutBien($data['id_bien'], 'Disponible');
            }
        }

        return ['ok' => $ok, 'msg' => $ok ? '' : "Erreur lors de la modification."];
    }

    /* =========================
       DELETE CONTRAT
    ========================= */
    public function delete($id_contrat, $id_bailleur)
    {
        $existing = $this->find($id_contrat, $id_bailleur);
        if (!$existing) return false;

        $stmt = $this->conn->prepare("
            DELETE FROM contrats 
            WHERE id_contrat  = :id_contrat 
            AND   id_bailleur = :id_bailleur
        ");
        $ok = $stmt->execute(['id_contrat' => $id_contrat, 'id_bailleur' => $id_bailleur]);

        // ✅ Si contrat supprimé était Actif → bien redevient Disponible
        if ($ok && $existing['statut'] === 'Actif') {
            $this->updateStatutBien($existing['id_bien'], 'Disponible');
        }

        return $ok;
    }

    /* =========================
       GET ONE CONTRAT
    ========================= */
    public function find($id_contrat, $id_bailleur)
    {
        $stmt = $this->conn->prepare("
            SELECT c.*,
                   l.nom    AS locataire_nom,
                   l.prenom AS locataire_prenom,
                   b.titre  AS bien_titre,
                   b.prix   AS bien_prix
            FROM contrats c
            LEFT JOIN locataires l ON c.id_locataire = l.id_locataire
            LEFT JOIN biens      b ON c.id_bien       = b.id_bien
            WHERE c.id_contrat  = :id_contrat 
            AND   c.id_bailleur = :id_bailleur
        ");
        $stmt->execute(['id_contrat' => $id_contrat, 'id_bailleur' => $id_bailleur]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* =========================
       CONTRATS PAR STATUT
    ========================= */
    public function getByStatut($id_bailleur, $statut)
    {
        $stmt = $this->conn->prepare("
            SELECT c.*,
                   l.nom   AS locataire_nom,
                   b.titre AS bien_titre
            FROM contrats c
            LEFT JOIN locataires l ON c.id_locataire = l.id_locataire
            LEFT JOIN biens      b ON c.id_bien       = b.id_bien
            WHERE c.id_bailleur = :id_bailleur
            AND   c.statut      = :statut
            ORDER BY c.date_fin ASC
        ");
        $stmt->execute(['id_bailleur' => $id_bailleur, 'statut' => $statut]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================
       REVENUS MENSUELS
    ========================= */
    public function getRevenusMensuels($id_bailleur, $annee = null)
    {
        $annee = $annee ?? date('Y');
        $stmt = $this->conn->prepare("
            SELECT MONTH(date_debut) AS mois, SUM(montant) AS total
            FROM contrats
            WHERE id_bailleur = :id_bailleur
            AND statut = 'Actif'
            AND YEAR(date_debut) = :annee
            GROUP BY MONTH(date_debut)
            ORDER BY mois ASC
        ");
        $stmt->execute(['id_bailleur' => $id_bailleur, 'annee' => $annee]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}