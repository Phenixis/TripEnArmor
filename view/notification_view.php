<?php
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/avis_controller.php';

$avisController = new avisController();

print_r($_SESSION);

$avis = $avisController->getAvisByIdPro($_SESSION['id_pro']);

if (count($avis) !== 0) {
?>
<div class="h-full p-2">
    <!-- lien vers l'offre -->
    <div class="w-full flex justify-between items-center">
        <h3 class="text-gray-600"><span class="text-black">Titre</span> posté par
            <span class="text-black">Auteur</span> Il y a <span class="text-black">Date de publication</span>
        </h3>
        <div class="h-6 w-24 bg-primary">
            Note
        </div>
    </div>
    <p>Vécu le Date de passage</p>
    <p>
        Description
    </p>
    <hr />
</div>
<?php
} else {
?>
<div class="h-full p-2">
    <p class="text-xl">
        Vous n'avez aucune notification.
    </p>
</div>
<?php
}?>