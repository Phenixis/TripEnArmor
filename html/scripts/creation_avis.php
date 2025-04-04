<?php
session_start();
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/avis_controller.php';
$avisController = new AvisController;

// Obtenir les informations nécessaires pour la création de l'avis
$titre = isset($_POST['titre']) ? $_POST['titre'] : 'Avis';
$commentaire = isset($_POST['commentaire']) ? $_POST['commentaire'] : '';

$note = isset($_POST['note_globale']) ? floatval($_POST['note_globale']) : 2.5;

$date_experience = isset($_POST['date_experience']) ? $_POST['date_experience'] : date('Y-m-d H:i:s');
$date_experience = date('Y-m-d H:i:s', strtotime($date_experience));
$contexte_passage = isset($_POST['contexte_passage']) ? $_POST['contexte_passage'] : '';

$id_membre = isset($_POST['id_membre']) ? $_POST['id_membre'] : null;
$id_offre = isset($_POST['id_offre']) ? intval($_POST['id_offre']) : null;

$photo_avis = isset($_POST['photo_avis']) ? $_POST['photo_avis'] : null;

// Créer l'avis dans la BDD
if ($titre && isset($note) && $date_experience && $id_membre && $id_offre) {
    $id_avis_inserted = $avisController->createAvis($titre, $date_experience, $id_membre, $id_offre, floatval($note), $contexte_passage, $commentaire)['id_avis'];
}

// Si c'est pour un restaurant, prendre les notes supplémentaires
$note_ambiance = isset($_POST['note_ambiance']) ? floatval($_POST['note_ambiance']) : null;
$note_service = isset($_POST['note_service']) ? floatval($_POST['note_service']) : null;
$note_cuisine = isset($_POST['note_cuisine']) ? floatval($_POST['note_cuisine']) : null;
$note_rapport = isset($_POST['note_rapport']) ? floatval($_POST['note_rapport']) : null;

// Si c'est un restaurant
if ($id_avis_inserted && $note_cuisine) {
    require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/php_files/connect_to_bdd.php';
    $stmt = $dbh->prepare("INSERT INTO sae_db._avis_restauration_note (id_avis, id_restauration, note_ambiance, note_service, note_cuisine, rapport_qualite_prix)
    VALUES (:id_avis, :id_restauration, :note_ambiance, :note_service, :note_cuisine, :rapport_qualite_prix)");
    $stmt->bindParam(':id_avis', $id_avis_inserted);
    $stmt->bindParam(':id_restauration', $id_offre);
    $stmt->bindParam(':note_ambiance', $note_ambiance);
    $stmt->bindParam(':note_service', $note_service);
    $stmt->bindParam(':note_cuisine', $note_cuisine);
    $stmt->bindParam(':rapport_qualite_prix', $note_rapport);
    $stmt->execute();
}

// Gestion de l'upload des images pour l'avis
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/image_controller.php';
$imageController = new ImageController();
if (isset($_FILES['photo_avis']) && isset($id_avis_inserted)) {
    $photoAvisFiles = $_FILES['photo_avis'];
    for ($i = 0; $i < count($photoAvisFiles["name"]); $i++) {
        $tmpName = $photoAvisFiles["tmp_name"][$i];
        $fileType = explode('/', $photoAvisFiles['type'][$i])[1];
        $imageController->uploadImage($id_avis_inserted, "photo-avis_" . $i, $tmpName, $fileType, "avis");
    }
}

// Tout s'est bien passé
$_SESSION['message_pour_notification'] = 'Votre avis a été créé et publié';
if (isset($id_avis_inserted)) {
    header('Location: /offre?id_offre=' . $id_offre);
}