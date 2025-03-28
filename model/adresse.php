<?php
require_once dirname($_SERVER['DOCUMENT_ROOT']) . "/model/bdd.php";

class Adresse extends BDD
{
    // Nom de la table utilisée dans les requêtes
    static private $nom_table = "sae_db._adresse";

    /**
     * Récupère une adresse par son ID.
     * @param int $id L'identifiant de l'adresse à récupérer.
     * @return array|int Retourne un tableau contenant les données de l'adresse ou -1 en cas d'erreur.
     */
    static function getAdresseById($id)
    {
        self::initBDD();
        // Requête SQL pour sélectionner une adresse par son ID
        $query = "SELECT * FROM " . self::$nom_table . " WHERE id_adresse = ?";

        // Prépare la requête SQL
        $statement = self::$db->prepare($query);
        $statement->bindParam(1, $id);

        // Exécute la requête et retourne les résultats ou une erreur
        if ($statement->execute()) {
            return $statement->fetchAll(PDO::FETCH_ASSOC)[0];
        } else {
            echo "ERREUR : Impossible d'obtenir cette adresse";
            return -1;
        }
    }

    /**
     * Crée une nouvelle adresse.
     * @param string $code_postal Le code postal de l'adresse.
     * @param string $ville La ville de l'adresse.
     * @param int $numero Le numéro de l'adresse.
     * @param string $odonyme L'odonyme (nom de la rue ou voie).
     * @param string|null $complement Le complément d'adresse (facultatif).
     * @param string $lat La latitude.
     * @param string $lng La longitude.
     * @return int Retourne l'identifiant de la nouvelle adresse ou -1 en cas d'erreur.
     */
    static function createAdresse($code_postal, $ville, $numero, $odonyme, $complement, $lat, $lng)
    {
        self::initBDD();
        // Requête SQL pour insérer une nouvelle adresse
        $query = "INSERT INTO " . self::$nom_table . " (code_postal, ville, numero, odonyme, complement, lat, lng) VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id_adresse";

        // Prépare la requête SQL
        $statement = self::$db->prepare($query);
        $statement->bindParam(1, $code_postal);
        $statement->bindParam(2, $ville);
        $statement->bindParam(3, $numero);
        $statement->bindParam(4, $odonyme);
        $statement->bindParam(5, $complement);
        $statement->bindParam(6, $lat);
        $statement->bindParam(7, $lng);

        // Exécute la requête et retourne les résultats ou une erreur
        if ($statement->execute()) {
            return $statement->fetchAll(PDO::FETCH_ASSOC)[0]['id_adresse'];
        } else {
            echo "ERREUR : Impossible de créer l'adresse";
            return -1;
        }
    }

    /**
     * Met à jour une adresse existante.
     * @param string $code_postal Le nouveau code postal.
     * @param string $ville La nouvelle ville.
     * @param int $numero Le nouveau numéro.
     * @param string $odonyme Le nouvel odonyme.
     * @param string|null $complement Le nouveau complément d'adresse.
     * @return array|int Retourne un tableau contenant l'identifiant de l'adresse mise à jour ou -1 en cas d'erreur.
     */
    static function updateAdresse($id_adresse, $code_postal, $ville, $numero, $odonyme, $complement, $lat, $lng)
    {
        self::initBDD();
        // Requête SQL pour mettre à jour une adresse existante
        $query = "UPDATE " . self::$nom_table . " SET code_postal = ?, ville = ?, numero = ?, odonyme = ?, complement = ?, lat = ?, lng = ? WHERE id_adresse = ? RETURNING id_adresse";

        // Prépare la requête SQL
        $statement = self::$db->prepare($query);
        $statement->bindParam(1, $code_postal);
        $statement->bindParam(2, $ville);
        $statement->bindParam(3, $numero);
        $statement->bindParam(4, $odonyme);
        $statement->bindParam(5, $complement);
        $statement->bindParam(6, $lat);
        $statement->bindParam(7, $lng);
        $statement->bindParam(8, $id_adresse);

        // Exécute la requête et retourne les résultats ou une erreur
        if ($statement->execute()) {
            return $statement->fetchAll(PDO::FETCH_ASSOC)[0]['id_adresse'];
        } else {
            echo "ERREUR : Impossible de mettre à jour l'adresse";
            return -1;
        }
    }

    /**
     * Supprime une adresse par son ID.
     * @param int $id L'identifiant de l'adresse à supprimer.
     * @return array|int Retourne un tableau vide si la suppression réussit ou -1 en cas d'erreur.
     */
    static function deleteAdresse($id)
    {
        self::initBDD();
        // Requête SQL pour supprimer une adresse par son ID
        $query = "DELETE FROM " . self::$nom_table . " WHERE id_adresse = ?";

        // Prépare la requête SQL
        $statement = self::$db->prepare($query);
        $statement->bindParam(1, $id);

        // Exécute la requête et retourne les résultats ou une erreur
        return $statement->execute();
    }
}
