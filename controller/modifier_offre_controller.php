<?php
require_once dirname($_SERVER['DOCUMENT_ROOT']) . "/model/modifier_offre.php";

class ModifierOffreController
{

    private $model;

    function __construct()
    {
        $this->model = 'ModifierOffre';
    }

    public function getOffreById($id)
    {
        $offre = $this->model::getOffreById($id);
        $this->model::log("Les informations de l'offre $id ont été lues.");
        return $offre;
    }

    public function updateOffre($id, $titre, $description, $resume, $prix_mini, $date_creation, $date_mise_a_jour, $date_suppression, $est_en_ligne, $id_type_offre, $id_pro, $id_adresse, $accessibilite)
    {
        $updatedOffreId = $this->model::updateOffre($id, $titre, $description, $resume, $prix_mini, $date_creation, $date_mise_a_jour, $date_suppression, $est_en_ligne, $id_type_offre, $id_pro, $id_adresse, $accessibilite);
        $this->model::log("L'offre $id a été modifiée.");

        // Si l'offre est passée hors ligne. Clore toute période 'en_ligne' en cours.
        require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/periodes_en_ligne_controller.php';
        $periodes_en_ligne_controller = new PeriodesEnLigneController();
        $periodes_en_ligne_controller->clorePeriodeByIdOffre($id_offre);

        return $updatedOffreId;
    }
}
