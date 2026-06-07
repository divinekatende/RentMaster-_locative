<?php

require_once __DIR__ . '/../config/database.php';

class BienController {

    private $conn;

    public function __construct() {
        $this->conn = (new Database())->connect();
    }

    /* ================= CREATE ================= */
    public function create($data, $file, $id_bailleur) {

        $image = null;

        if (!empty($file['image']['name'])) {
            $image = time() . "_" . $file['image']['name'];
            move_uploaded_file($file['image']['tmp_name'], "public/uploads/" . $image);
        }

        $sql = "INSERT INTO biens 
        (id_bailleur,image,titre,adresse,type_bien,surface,nombre_pieces,prix,statut,description)
        VALUES
        (:id_bailleur,:image,:titre,:adresse,:type_bien,:surface,:nombre_pieces,:prix,:statut,:description)";

        $stmt = $this->conn->prepare($sql);

        $stmt->execute([
            'id_bailleur' => $id_bailleur,
            'image' => $image ?? '',
            'titre' => $data['titre'],
            'adresse' => $data['adresse'],
            'type_bien' => $data['type_bien'],
            'surface' => $data['surface'],
            'nombre_pieces' => $data['nombre_pieces'],
            'prix' => $data['prix'],
            'statut' => $data['statut'],
            'description' => $data['description']
        ]);
    }

    /* ================= UPDATE ================= */
    public function update($data, $file, $id_bailleur) {

        $imageSql = "";

        $params = [
            'id_bien' => $data['id_bien'],
            'id_bailleur' => $id_bailleur,
            'titre' => $data['titre'],
            'adresse' => $data['adresse'],
            'type_bien' => $data['type_bien'],
            'surface' => $data['surface'],
            'nombre_pieces' => $data['nombre_pieces'],
            'prix' => $data['prix'],
            'statut' => $data['statut'],
            'description' => $data['description']
        ];

        if (!empty($file['image']['name'])) {

            $image = time() . "_" . $file['image']['name'];
            move_uploaded_file($file['image']['tmp_name'], "public/uploads/" . $image);

            $imageSql = ", image=:image";
            $params['image'] = $image;
        }

        $sql = "UPDATE biens SET 
            titre=:titre,
            adresse=:adresse,
            type_bien=:type_bien,
            surface=:surface,
            nombre_pieces=:nombre_pieces,
            prix=:prix,
            statut=:statut,
            description=:description
            $imageSql
            WHERE id_bien=:id_bien AND id_bailleur=:id_bailleur";

        $this->conn->prepare($sql)->execute($params);
    }

    /* ================= DELETE ================= */
    public function delete($id_bien, $id_bailleur) {

        $sql = "DELETE FROM biens 
                WHERE id_bien=:id_bien AND id_bailleur=:id_bailleur";

        $this->conn->prepare($sql)->execute([
            'id_bien' => $id_bien,
            'id_bailleur' => $id_bailleur
        ]);
    }

    /* ================= LIST ================= */
    public function getAll($id_bailleur) {

        $sql = "SELECT * FROM biens 
                WHERE id_bailleur=:id_bailleur 
                ORDER BY id_bien DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id_bailleur' => $id_bailleur]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}