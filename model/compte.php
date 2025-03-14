<?php
require_once dirname($_SERVER['DOCUMENT_ROOT']) . "/model/bdd.php";

abstract class Compte extends BDD
{
    static private $nom_table = "sae_db._compte";

    static function createCompte($email, $mdp, $tel, $adresseId)
    {
        $query = "INSERT INTO (email, mdp_hash, num_tel, id_adresse" . self::$nom_table . "VALUES (?, ?, ?, ?) RETURNING id_compte";
        $statement = self::$db->prepare($query);
        $statement->bindParam(1, $email);
        $statement->bindParam(2, $mdp);
        $statement->bindParam(3, $tel);
        $statement->bindParam(4, $adresseId);

        if ($statement->execute()) {
            return $statement->fetchAll(PDO::FETCH_ASSOC)[0]['id_compte'];
        } else {
            echo "ERREUR: Impossible de créer le compte";
            return -1;
        }
    }

    static function getCompteById($id)
    {
        self::initBDD();
        $query = "SELECT * FROM " . self::$nom_table . " WHERE id_compte = ?";
        $statement = self::$db->prepare($query);
        $statement->bindParam(1, $id);

        if ($statement->execute()) {
            return $statement->fetchAll(PDO::FETCH_ASSOC)[0];
        } else {
            echo "ERREUR";
            return false;
        }
    }

    static function updateCompte($id, $email, $mdp, $tel, $adresseId)
    {
        $query = "UPDATE " . self::$nom_table . " SET email = ?, mdp_hash = ?, num_tel = ?, id_adresse = ? WHERE id_compte = ?";
        $statement = self::$db->prepare($query);
        $statement->bindParam(1, $email);
        $statement->bindParam(2, $mdp);
        $statement->bindParam(3, $tel);
        $statement->bindParam(4, $adresseId);
        $statement->bindParam(5, $id);

        if ($statement->execute()) {
            return $statement->fetchAll(PDO::FETCH_ASSOC)[0]['id_compte'];
        } else {
            echo "ERREUR: Impossible de mettre à jour le compte";
            return -1;
        }
    }
}
