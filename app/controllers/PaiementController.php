Paiementcontroller · PHP
<?php
 
require_once __DIR__ . '/../config/database.php';
 
class PaiementController
{
    private $conn;
 
    public function __construct()
    {
        $this->conn = (new Database())->connect();
    }
 
    /* ==============================================
       LISTE PAIEMENTS (avec infos contrat/bien/loc)
    ============================================== */
    public function index($id_bailleur)
    {
        $stmt = $this->conn->prepare("
            SELECT p.*,
                   c.montant        AS loyer_mensuel,
                   b.titre          AS bien_nom,
                   l.nom            AS loc_nom,
                   l.prenom         AS loc_prenom
            FROM paiements p
            JOIN contrats   c ON p.id_contrat   = c.id_contrat
            JOIN biens      b ON c.id_bien       = b.id_bien
            JOIN locataires l ON c.id_locataire  = l.id_locataire
            WHERE c.id_bailleur = :id_bailleur
            ORDER BY p.id_paiement DESC
        ");
        $stmt->execute(['id_bailleur' => $id_bailleur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
 
    /* ==============================================
       TOTAL DÉJÀ PAYÉ POUR UN MOIS DONNÉ
    ============================================== */
    public function totalPayePourMois($id_contrat, $mois_annee)
    {
        // mois_annee format : "2026-03"
        $stmt = $this->conn->prepare("
            SELECT COALESCE(SUM(montant_verse), 0) AS total
            FROM paiements
            WHERE id_contrat  = :id_contrat
            AND   mois_annee  = :mois_annee
        ");
        $stmt->execute([
            'id_contrat' => $id_contrat,
            'mois_annee' => $mois_annee
        ]);
        return (float) $stmt->fetchColumn();
    }
 
    /* ==============================================
       CRÉER UN PAIEMENT (acompte ou complet)
    ============================================== */
    public function create($data)
    {
        // Récupérer le loyer mensuel du contrat
        $stmt = $this->conn->prepare("
            SELECT montant FROM contrats
            WHERE id_contrat = :id_contrat AND id_bailleur = :id_bailleur
        ");
        $stmt->execute([
            'id_contrat'  => $data['id_contrat'],
            'id_bailleur' => $data['id_bailleur']
        ]);
        $contrat = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$contrat) return ['ok' => false, 'msg' => "Contrat introuvable."];
 
        $loyer_mensuel = (float) $contrat['montant'];
        $mois_annee    = $data['mois_annee']; // ex: "2026-03"
        $montant_verse = (float) $data['montant_verse'];
 
        if ($montant_verse <= 0)
            return ['ok' => false, 'msg' => "Le montant doit être supérieur à 0."];
 
        // Total déjà payé pour ce mois
        $deja_paye = $this->totalPayePourMois($data['id_contrat'], $mois_annee);
 
        if ($deja_paye >= $loyer_mensuel)
            return ['ok' => false, 'msg' => "Le loyer du mois " . $this->nomMois($mois_annee) . " est déjà entièrement payé."];
 
        $reste_avant = $loyer_mensuel - $deja_paye;
 
        // On ne peut pas payer plus que ce qui reste
        if ($montant_verse > $reste_avant)
            return ['ok' => false, 'msg' => "Montant trop élevé. Il reste " . number_format($reste_avant, 2) . " $ à payer pour ce mois."];
 
        $total_apres = $deja_paye + $montant_verse;
 
        // Statut : Complet si loyer entièrement payé, sinon Acompte
        $statut = ($total_apres >= $loyer_mensuel) ? 'Complet' : 'Acompte';
 
        $stmt = $this->conn->prepare("
            INSERT INTO paiements
                (id_contrat, mois_annee, montant_verse, date_paiement, statut, created_at)
            VALUES
                (:id_contrat, :mois_annee, :montant_verse, :date_paiement, :statut, NOW())
        ");
 
        $ok = $stmt->execute([
            'id_contrat'    => $data['id_contrat'],
            'mois_annee'    => $mois_annee,
            'montant_verse' => $montant_verse,
            'date_paiement' => $data['date_paiement'],
            'statut'        => $statut
        ]);
 
        if (!$ok) return ['ok' => false, 'msg' => "Erreur lors de l'enregistrement."];
 
        $msg = $statut === 'Complet'
            ? "✅ Loyer de " . $this->nomMois($mois_annee) . " entièrement payé !"
            : "⚠️ Acompte enregistré. Reste " . number_format($loyer_mensuel - $total_apres, 2) . " $ pour " . $this->nomMois($mois_annee) . ".";
 
        return ['ok' => true, 'msg' => $msg, 'statut' => $statut];
    }
 
    /* ==============================================
       SUPPRIMER UN PAIEMENT
    ============================================== */
    public function delete($id_paiement, $id_bailleur)
    {
        // Vérifier que le paiement appartient au bailleur via le contrat
        $stmt = $this->conn->prepare("
            SELECT p.id_paiement FROM paiements p
            JOIN contrats c ON p.id_contrat = c.id_contrat
            WHERE p.id_paiement = :id_paiement AND c.id_bailleur = :id_bailleur
        ");
        $stmt->execute(['id_paiement' => $id_paiement, 'id_bailleur' => $id_bailleur]);
        if (!$stmt->fetch()) return false;
 
        return $this->conn->prepare("DELETE FROM paiements WHERE id_paiement = :id")
                          ->execute(['id' => $id_paiement]);
    }
 
    /* ==============================================
       LISTE CONTRATS ACTIFS DU BAILLEUR
    ============================================== */
    public function getContratsActifs($id_bailleur)
    {
        $stmt = $this->conn->prepare("
            SELECT c.id_contrat, c.montant,
                   b.titre   AS bien_nom,
                   l.nom     AS loc_nom,
                   l.prenom  AS loc_prenom,
                   c.date_debut, c.date_fin
            FROM contrats c
            JOIN biens      b ON c.id_bien      = b.id_bien
            JOIN locataires l ON c.id_locataire = l.id_locataire
            WHERE c.id_bailleur = :id_bailleur
            AND   c.statut      = 'Actif'
            ORDER BY l.nom ASC
        ");
        $stmt->execute(['id_bailleur' => $id_bailleur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
 
    /* ==============================================
       RÉSUMÉ PAR MOIS POUR UN CONTRAT
       (pour afficher ce qui est payé / reste)
    ============================================== */
    public function resumeMoisContrat($id_contrat, $mois_annee)
    {
        $stmt = $this->conn->prepare("
            SELECT
                COALESCE(SUM(montant_verse), 0) AS total_verse,
                COUNT(*) AS nb_versements
            FROM paiements
            WHERE id_contrat = :id_contrat
            AND   mois_annee = :mois_annee
        ");
        $stmt->execute(['id_contrat' => $id_contrat, 'mois_annee' => $mois_annee]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
 
    /* ==============================================
       HELPER : NOM DU MOIS
    ============================================== */
    private function nomMois($mois_annee)
    {
        $mois = [
            '01'=>'Janvier','02'=>'Février','03'=>'Mars','04'=>'Avril',
            '05'=>'Mai','06'=>'Juin','07'=>'Juillet','08'=>'Août',
            '09'=>'Septembre','10'=>'Octobre','11'=>'Novembre','12'=>'Décembre'
        ];
        [$annee, $m] = explode('-', $mois_annee);
        return ($mois[$m] ?? $m) . ' ' . $annee;
    }
 
    /* ==============================================
       REVENUS TOTAUX PAR MOIS (dashboard)
    ============================================== */
    public function revenusMensuels($id_bailleur, $annee = null)
    {
        $annee = $annee ?? date('Y');
        $stmt  = $this->conn->prepare("
            SELECT
                SUBSTRING(p.mois_annee, 1, 7)  AS mois,
                SUM(p.montant_verse)            AS total
            FROM paiements p
            JOIN contrats c ON p.id_contrat = c.id_contrat
            WHERE c.id_bailleur = :id_bailleur
            AND   YEAR(p.date_paiement) = :annee
            GROUP BY mois
            ORDER BY mois ASC
        ");
        $stmt->execute(['id_bailleur' => $id_bailleur, 'annee' => $annee]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}