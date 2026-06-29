<?php
require_once __DIR__ . '/../models/Contrat.php';

class ContratController
{
    private $contratModel;

    public function __construct()
    {
        $this->contratModel = new Contrat();
    }

    public function index($id_bailleur)
    {
        return $this->contratModel->getByBailleur($id_bailleur);
    }

    public function create($data)
    {
        $required = ['id_bailleur','id_locataire','id_bien','date_debut','date_fin','statut'];
        foreach ($required as $field) {
            if (empty($data[$field])) return ['ok' => false, 'msg' => "Champ manquant : $field"];
        }

        if ($data['date_fin'] <= $data['date_debut']) {
            return ['ok' => false, 'msg' => "La date de fin doit être après la date de début."];
        }

        if (!$this->contratModel->isBienDisponible($data['id_bien'], $data['id_bailleur'])) {
            return ['ok' => false, 'msg' => "Ce bien est déjà sous contrat actif."];
        }

        $bien = $this->contratModel->getPrixBien($data['id_bien'], $data['id_bailleur']);
        if (!$bien) return ['ok' => false, 'msg' => "Bien introuvable."];

        $ok = $this->contratModel->insert([
            'id_bailleur'        => $data['id_bailleur'],
            'id_locataire'       => $data['id_locataire'],
            'id_bien'            => $data['id_bien'],
            'date_debut'         => $data['date_debut'],
            'date_fin'           => $data['date_fin'],
            'montant'            => $bien['prix'],
            'charges'            => $data['charges'] ?? 0,
            'charge_eau'         => $data['charge_eau'] ?? 0,
            'charge_electricite' => $data['charge_electricite'] ?? 0,
            'impot_locataire'    => $data['impot_locataire'] ?? 0,
            'statut'             => $data['statut']
        ]);

        if ($ok && $data['statut'] === 'Actif') {
            $this->contratModel->updateStatutBien($data['id_bien'], 'Loué');
        }

        return ['ok' => $ok, 'msg' => $ok ? '' : "Erreur lors de la création."];
    }

    public function update($id_contrat, $data)
    {
        // 1. Vérification de l'id_bailleur dans les données reçues de la vue
        if (empty($data['id_bailleur'])) {
            return ['ok' => false, 'msg' => "ID Bailleur manquant pour la modification."];
        }

        $id_bailleur = $data['id_bailleur'];

        $existing = $this->contratModel->find($id_contrat, $id_bailleur);
        if (!$existing) return ['ok' => false, 'msg' => "Contrat introuvable."];

        if ($existing['id_bien'] != $data['id_bien']) {
            if (!$this->contratModel->isBienDisponible($data['id_bien'], $id_bailleur, $id_contrat)) {
                return ['ok' => false, 'msg' => "Ce bien est déjà sous contrat actif."];
            }
        }

        $bien = $this->contratModel->getPrixBien($data['id_bien'], $id_bailleur);
        if (!$bien) return ['ok' => false, 'msg' => "Bien introuvable."];

        // 2. Préparation complète des données attendues par le Model et sa requête UPDATE
        $updateData = [
            'id_locataire'       => $data['id_locataire'],
            'id_bien'            => $data['id_bien'],
            'date_debut'         => $data['date_debut'],
            'date_fin'           => $data['date_fin'],
            'montant'            => $bien['prix'],
            'charges'            => $data['charges'] ?? 0,
            'charge_eau'         => $data['charge_eau'] ?? 0,
            'charge_electricite' => $data['charge_electricite'] ?? 0,
            'impot_locataire'    => $data['impot_locataire'] ?? 0,
            'statut'             => $data['statut']
        ];

        // Envoi au modèle avec l'id_bailleur transmis séparément en 3ème argument
        $ok = $this->contratModel->update($id_contrat, $updateData, $id_bailleur);

        if ($ok) {
            if ($existing['id_bien'] != $data['id_bien']) {
                $this->contratModel->updateStatutBien($existing['id_bien'], 'Disponible');
            }

            $nouveauStatutBien = ($data['statut'] === 'Actif') ? 'Loué' : 'Disponible';
            $this->contratModel->updateStatutBien($data['id_bien'], $nouveauStatutBien);
        }

        return ['ok' => $ok, 'msg' => $ok ? '' : "Erreur lors de la modification."];
    }

    public function delete($id_contrat, $id_bailleur)
    {
        $existing = $this->contratModel->find($id_contrat, $id_bailleur);
        if (!$existing) return false;

        $ok = $this->contratModel->delete($id_contrat, $id_bailleur);

        if ($ok && $existing['statut'] === 'Actif') {
            $this->contratModel->updateStatutBien($existing['id_bien'], 'Disponible');
        }

        return $ok;
    }

    public function find($id_contrat, $id_bailleur)
    {
        return $this->contratModel->find($id_contrat, $id_bailleur);
    }

    public function getByStatut($id_bailleur, $statut)
    {
        return $this->contratModel->getByStatut($id_bailleur, $statut);
    }

    public function getRevenusMensuels($id_bailleur, $annee = null)
    {
        return $this->contratModel->getRevenusMensuels($id_bailleur, $annee ?? date('Y'));
    }
}