<?php

if (isset($_POST['id_pro'])) {
    require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/avis_controller.php';
    $avisController = new AvisController();

    $avisController->marquerTousLesAvisCommeLus($_POST['id_pro']);

    header('Location: /pro/');
}