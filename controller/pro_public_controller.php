<?php
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/model/pro_public.php';

class ProPublicController
{
    private $model;

    function __construct()
    {
        $this->model = 'ProPublic';
    }

    public function createProPublic($email, $mdp, $tel, $adresseId, $nom_pro, $type_orga)
    {
        $proPublicID = $this->model::createProPublic($email, $mdp, $tel, $adresseId, $nom_pro, $type_orga);
        return $proPublicID;
    }
    public function getInfosProPublic($id)
    {
        $result = $this->model::getProPublicById($id);
        return $result;
    }

    public function getMdpProPublic($id)
    {
        $proPrive = $this->model::getMdpById($id);

        if ($proPrive) {
            $result = $proPrive["mdp_hash"];
        } else {
            return false;
        }

        return $result;
    }



    public function updateProPublic($id, $email = false, $mdp = false, $tel = false, $adresseId = false, $nom_pro = false, $type_orga = false)
    {
        if ($email === false && $mdp === false && $tel === false && $adresseId === false && $nom_pro === false && $type_orga === false) {
            echo "ERREUR: Aucun champ à modifier";
            return -1;
        } else {
            $proPublic = $this->model::getProPublicById($id);

            $updatedProPublicId = $this->model::updateProPublic(
                $id,
                $email !== false ? $email : $proPublic["email"],
                $mdp !== false ? $mdp : $proPublic["mdp_hash"],
                $tel !== false ? $tel : $proPublic["num_tel"],
                $adresseId !== false ? $adresseId : $proPublic["id_adresse"],
                $nom_pro !== false ? $nom_pro : $proPublic["nom_pro"],
                $type_orga !== false ? $type_orga : $proPublic["type_orga"]
            );
            return $updatedProPublicId;
        }
    }

    public function deleteProPublic($id)
    {
        $proPublic = $this->model::deleteProPublic($id);

        return $proPublic;
    }
}