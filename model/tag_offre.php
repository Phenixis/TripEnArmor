<?php

class TagOffre extends BDD
{
    private $nom_table = "sae_db._tag_offre";

    static function getTagsByIdOffre($id_offre)
    {
        $query = "SELECT * FROM " . self::$nom_table . " WHERE id_offre = ?";

        $statement = self::$db->prepare($query);
        $statement->bindValue(1, $id_offre);

        if ($statement->execute()) {
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return -1;
        }
    }

    static function getOffresByIdTag($id_tag)
    {
        $query = "SELECT * FROM " . self::$nom_table . " WHERE id_tag = ?";

        $statement = self::$db->prepare($query);
        $statement->bindValue(1, $id_tag);

        if ($statement->execute()) {
            return !empty($statement->fetchAll(PDO::FETCH_ASSOC));
        } else {
            return false;
        }
    }

    static function checkIfLinkExists($id_offre, $id_tag)
    {
        $query = "SELECT * FROM " . self::$nom_table . " WHERE id_offre = ? AND id_tag = ?";

        $statement = self::$db->prepare($query);
        $statement->bindValue(1, $id_offre);
        $statement->bindValue(2, $id_tag);

        if ($statement->execute()) {
            return $statement->fetchAll(PDO::FETCH_ASSOC)[0];
        } else {
            return -1;
        }
    }

    static function linkOffreAndTag($id_offre, $id_tag)
    {
        $query = "INSERT INTO " . self::$nom_table . "VALUES (?, ?) RETURNING *";

        $statement = self::$db->prepare($query);
        $statement->bindValue(1, $id_offre);
        $statement->bindValue(2, $id_tag);

        if ($statement->execute()) {
            return $statement->fetchAll(PDO::FETCH_ASSOC)[0];
        } else {
            return -1;
        }
    }

    static function unlinkOffreAndTag($id_offre, $id_tag)
    {
        $query = "DELETE FROM " . self::$nom_table . " WHERE id_offre = ? AND id_tag = ?";

        $statement = self::$db->prepare($query);
        $statement->bindValue(1, $id_offre);
        $statement->bindValue(2, $id_tag);

        return $statement->execute();
    }


}