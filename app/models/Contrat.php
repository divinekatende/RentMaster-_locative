<?php
require_once __DIR__ . "/../config/database.php";

class Contrat
{
    private $conn;
    private $table = "contrats";

    public function __construct()
    {
        $this->conn = (new Database())->connect();
    }

    public function getByBailleur($id_bailleur)
    {
        $stmt = $this->conn->prepare("
            SELECT c.*, 
                   l.nom    AS locataire_nom,
                   l.prenom AS locataire_prenom,
                   b.titre  AS bien_titre,
                   b.prix   AS bien_prix
            FROM {$this->table} c
            LEFT JOIN locataires l ON c.id_locataire = l.id_locataire
            LEFT JOIN biens      b ON c.id_bien      = b.id_bien
            WHERE c.id_bailleur = :id_bailleur
            ORDER BY c.id_contrat DESC
        ");
        $stmt->execute(['id_bailleur' => $id_bailleur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByLocataire($id_locataire)
    {
        $stmt = $this->conn->prepare("
            SELECT c.*, b.titre AS titre_bien, b.adresse 
            FROM {$this->table} c
            LEFT JOIN biens b ON c.id_bien = b.id_bien
            WHERE c.id_locataire = :id_locataire
            ORDER BY c.created_at DESC
        ");
        $stmt->execute(['id_locataire' => $id_locataire]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id_contrat, $id_bailleur)
    {
        $stmt = $this->conn->prepare("
            SELECT c.*, l.nom AS locataire_nom, l.prenom AS locataire_prenom, b.titre AS bien_titre, b.prix AS bien_prix
            FROM {$this->table} c
            LEFT JOIN locataires l ON c.id_locataire = l.id_locataire
            LEFT JOIN biens      b ON c.id_bien      = b.id_bien
            WHERE c.id_contrat = :id_contrat AND c.id_bailleur = :id_bailleur
        ");
        $stmt->execute(['id_contrat' => $id_contrat, 'id_bailleur' => $id_bailleur]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function isBienDisponible($id_bien, $id_bailleur, $id_contrat_exclu = null)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE id_bien = :id_bien AND id_bailleur = :id_bailleur AND statut = 'Actif'";
        $params = ['id_bien' => $id_bien, 'id_bailleur' => $id_bailleur];

        if ($id_contrat_exclu) {
            $sql .= " AND id_contrat != :id_contrat_exclu";
            $params['id_contrat_exclu'] = $id_contrat_exclu;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() === 0;
    }

    public function getPrixBien($id_bien, $id_bailleur)
    {
        $stmt = $this->conn->prepare("SELECT prix FROM biens WHERE id_bien = :id_bien AND id_bailleur = :id_bailleur");
        $stmt->execute(['id_bien' => $id_bien, 'id_bailleur' => $id_bailleur]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateStatutBien($id_bien, $statut)
    {
        $stmt = $this->conn->prepare("UPDATE biens SET statut = :statut WHERE id_bien = :id_bien");
        return $stmt->execute(['statut' => $statut, 'id_bien' => $id_bien]);
    }

    public function insert($data)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table} 
                (id_bailleur, id_locataire, id_bien, date_debut, date_fin, montant, charges,
                 charge_eau, charge_electricite, impot_locataire, statut, created_at)
            VALUES 
                (:id_bailleur, :id_locataire, :id_bien, :date_debut, :date_fin, :montant, :charges,
                 :charge_eau, :charge_electricite, :impot_locataire, :statut, NOW())
        ");
        return $stmt->execute($data);
    }

    public function update($id_contrat, $data, $id_bailleur = null)
    {
        // On injecte manuellement l'id_contrat dans les données
        $data['id_contrat'] = $id_contrat;
        
        // Si l'id_bailleur est fourni à part, on l'ajoute au tableau
        if ($id_bailleur !== null) {
            $data['id_bailleur'] = $id_bailleur;
        }

        // Liste stricte des paramètres attendus dans la requête SQL ci-dessous
        $allowed_keys = [
            'id_locataire', 'id_bien', 'date_debut', 'date_fin', 'montant', 
            'charges', 'charge_eau', 'charge_electricite', 'impot_locataire', 
            'statut', 'id_contrat', 'id_bailleur'
        ];

        // Sécurité : on filtre pour supprimer les clés inutiles (ex: bouton submit du formulaire)
        // et éviter l'erreur de correspondance des paramètres de PDO
        $filtered_data = array_intersect_key($data, array_flip($allowed_keys));

        $stmt = $this->conn->prepare("
            UPDATE {$this->table} SET 
                id_locataire       = :id_locataire,
                id_bien            = :id_bien,
                date_debut         = :date_debut,
                date_fin           = :date_fin,
                montant            = :montant,
                charges            = :charges,
                charge_eau         = :charge_eau,
                charge_electricite = :charge_electricite,
                impot_locataire    = :impot_locataire,
                statut             = :statut
            WHERE id_contrat = :id_contrat AND id_bailleur = :id_bailleur
        ");
        
        return $stmt->execute($filtered_data);
    }

    public function delete($id_contrat, $id_bailleur)
    {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id_contrat = :id_contrat AND id_bailleur = :id_bailleur");
        return $stmt->execute(['id_contrat' => $id_contrat, 'id_bailleur' => $id_bailleur]);
    }

    public function getByStatut($id_bailleur, $statut)
    {
        $stmt = $this->conn->prepare("
            SELECT c.*, l.nom AS locataire_nom, b.titre AS bien_titre
            FROM {$this->table} c
            LEFT JOIN locataires l ON c.id_locataire = l.id_locataire
            LEFT JOIN biens      b ON c.id_bien      = b.id_bien
            WHERE c.id_bailleur = :id_bailleur AND c.statut = :statut
            ORDER BY c.date_fin ASC
        ");
        $stmt->execute(['id_bailleur' => $id_bailleur, 'statut' => $statut]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRevenusMensuels($id_bailleur, $annee)
    {
        $stmt = $this->conn->prepare("
            SELECT MONTH(date_debut) AS mois, SUM(montant) AS total
            FROM {$this->table}
            WHERE id_bailleur = :id_bailleur AND statut = 'Actif' AND YEAR(date_debut) = :annee
            GROUP BY MONTH(date_debut) ORDER BY mois ASC
        ");
        $stmt->execute(['id_bailleur' => $id_bailleur, 'annee' => $annee]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}