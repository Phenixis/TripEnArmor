<?php
session_start();
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/php_files/authentification.php';
$pro = verifyPro();

// FONCTION UTILES
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/php_files/fonctions.php';
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" href="/public/images/favicon.png">
    <link rel="stylesheet" href="/styles/style.css">
    <script type="module" src="/scripts/main.js"></script>

    <!-- POUR LEAFLET ET L'AUTOCOMPLETION -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-geosearch@3.0.0/dist/geosearch.css" />

    <title>Création d'offre - Professionnel - PACT</title>
</head>

<body>
    <?php
    // Partie pour traiter la soumission du formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/model/bdd.php';
        // *********************************************************************************************************************** Définition de fonctions
        // Fonction pour calculer le prix minimum à partir des prix envoyés dans le formulaire
        function calculerPrixMin($prices)
        {
            $minPrice = null;

            foreach ($prices as $price) {
                if (isset($price['value']) && (is_null($minPrice) || $price['value'] < $minPrice)) {
                    $minPrice = $price['value'];
                }
            }

            return $minPrice;
        }

        // ******************************************************************************************************************** Récupération des données du POST
        // Récupération des données du formulaire
        // *** Données standard
        $id_type_offre = $_POST["type_offre"];
        $titre = $_POST['titre'];
        $adresse = $_POST['user_input_autocomplete_address'];
        $code = $_POST['postal_code'];
        $ville = $_POST['locality'];
        $resume = $_POST['resume'];
        $lat = $_POST['lat'];
        $lng = $_POST['lng'];
        $description = $_POST['description'];
        $accessibilite = $_POST['accessibilite'];
        $activityType = $_POST['activityType'];

        // *** Données spécifiques
        $avec_guide = $_POST["guide"] ?? "on"; // VISITE
        $age = $_POST["age"];
        $duree_formatted = sprintf('%02d:%02d:00', $_POST["hours"], $_POST["minutes"]); // ACTIVITE, VISITE, SPECTACLE
        $gamme_prix = $_POST['gamme2prix'];
        $capacite = $_POST['capacite'] ?? '';
        $langues = [
            "Français" => $_POST["langueFR"] ?? "on",
            "Anglais" => $_POST["langueEN"] ?? "on",
            "Espagnol" => $_POST["langueES"] ?? "on",
            "Allemand" => $_POST["langueDE"] ?? "on"
        ]; // VISITE
        $typesRepas = [
            "Petit déjeuner" => $_POST["repasPetitDejeuner"] ?? "on",
            "Brunch" => $_POST["repasBrunch"] ?? "on",
            "Déjeuner" => $_POST["repasDejeuner"] ?? "on",
            "Dîner" => $_POST["repasDiner"] ?? "on",
            "Boissons" => $_POST["repasBoissons"] ?? "on",
        ];
        $nb_attractions = (int) $_POST['nb_attractions'] ?? 0; // PARC_ATTRACTION
        $prices = $_POST['prices'] ?? [];
        $tags = $_POST['tags'][$activityType] ?? [];
        $id_pro = $_SESSION['id_pro'];
        $prestations = $_POST['newPrestationName'] ?? [];
        $horaires = $_POST['horaires'] ?? [];
        $option = $_POST['option'] ?? [];
        $duree_option = $_POST['duration'] ?? [];
        $debut_option = $_POST['start_date'] ?? [];

        // *********************************************************************************************************************** Insertion
        BDD::startTransaction();
        try {
            // Insérer l'adresse dans la base de données
            $realAdresse = extraireInfoAdresse($adresse);
            require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/adresse_controller.php';
            $adresseController = new AdresseController();
            $id_adresse = $adresseController->createAdresse($code, $ville, $realAdresse['numero'], $realAdresse['odonyme'], null, $lat, $lng);
            if (!$id_adresse) {
                echo "Erreur lors de l'insertion de l'adresse.";
                BDD::startTransaction();
                exit;
            }

            // Insérer l'offre dans la base de données
            $prixMin = calculerPrixMin($prices);
            $id_offre;
            switch ($activityType) {
                case 'activite':
                    // Insertion spécifique à l'activité
                    require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/activite_controller.php';
                    $activiteController = new ActiviteController();
                    $id_offre = $activiteController->createActivite($description, $resume, $prixMin, $titre, $id_pro, $id_type_offre, $id_adresse, $duree_formatted, $age, $prestations);

                    if ($id_offre < 0) { // Cas d'erreur
                        echo "Erreur lors de l'insertion de l'activité.";
                        BDD::rollbackTransaction();
                        exit;
                    }
                    break;

                case 'visite':
                    require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/visite_controller.php';

                    $visiteController = new VisiteController();
                    $id_offre = $visiteController->createVisite($description, $resume, $prixMin, $titre, $id_pro, $id_type_offre, $id_adresse, $dureeFormatted, $avec_guide);

                    if ($id_offre < 0) {
                        echo "Erreur lors de l'insertion de la visite.";
                        BDD::rollbackTransaction();
                        exit;
                    }
                    break;

                case 'spectacle':

                    require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/spectacle_controller.php';

                    $spectacleController = new SpectacleController();
                    $id_offre = $spectacleController->createSpectacle($description, $resume, $prixMin, $titre, $id_pro, $id_type_offre, $id_adresse, $capacite, $dureeFormatted);

                    if ($id_offre < 0) {
                        echo "Erreur lors de l'insertion du spectacle.";
                        BDD::rollbackTransaction();
                        exit;
                    }
                    break;

                case 'parc_attraction':

                    require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/parc_attraction_controller.php';

                    $parcAttractionController = new ParcAttractionController();
                    $id_offre = $parcAttractionController->createParcAttraction($description, $resume, $prixMin, $titre, $id_pro, $id_type_offre, $id_adresse, $nb_attractions, $age);

                    if ($id_offre < 0) {
                        echo "Erreur lors de l'insertion du parc d'attraction.";
                        BDD::rollbackTransaction();
                        exit;
                    }
                    break;

                case 'restauration':

                    require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/restauration_controller.php';

                    $restaurationController = new RestaurationController();
                    $id_offre = $restaurationController->createRestauration($description, $resume, $prixMin, $titre, $id_pro, $id_type_offre, $id_adresse, $gamme_prix);

                    if ($id_offre < 0) {
                        echo "Erreur lors de l'insertion de la restauration.";
                        BDD::rollbackTransaction();
                        exit;
                    }
                    break;

                default:
                    echo "Type d'activité inconnu.";
                    BDD::rollbackTransaction();
                    exit;
            }

            // Insérer les liens entre les offres et les tags dans la base de données
            if ($activityType === 'restauration') {
                require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/tag_restaurant_controller.php';
                $tagRestaurationController = new TagRestaurantController();
                require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/tag_restaurant_restauration_controller.php';
                $tagRestaurationRestaurantController = new TagRestaurantRestaurationController();

                foreach ($tags as $tag) {
                    $tags_id = $tagRestaurationController->getTagsRestaurantByName($tag);

                    $tag_id = $tags_id ? $tags_id[0]['id_tag_restaurant'] : $tagRestaurationController->createTag($tag);

                    $tagRestaurationRestaurantController->linkRestaurationAndTag($id_offre, $tag_id);
                }
            } else {
                require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/tag_controller.php';
                $tagController = new TagController();
                require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/tag_offre_controller.php';
                $tagOffreController = new TagOffreController();

                foreach ($tags as $tag) {
                    $tags_id = $tagController->getTagsByName($tag);
                    $tag_id = $tags_id ? $tags_id[0]['id_tag'] : $tagController->createTag($tag);
                    $tagOffreController->linkOffreAndTag($id_offre, $tag_id);
                }
            }

            // Insérer les images dans la base de données
            require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/image_controller.php';
            $imageController = new ImageController();
            // echo"Image de la carte insérée.<br>";
    

            // *** DETAIL
            if ($_FILES['photo-detail']['error'][0] !== 4) {
                for ($i = 0; $i < count($_FILES['photo-detail']['name']); $i++) {
                    if (!$imageController->uploadImage($id_offre, 'detail-' . $i, $_FILES['photo-detail']['tmp_name'][$i], explode('/', $_FILES['photo-detail']['type'][$i])[1])) {
                        echo "Erreur lors de l'upload de l'image détaillée.";
                        var_dump($_FILES['photo-detail']);
                        BDD::rollbackTransaction();
                        exit;
                    }
                }
            }

            if ($activityType === 'parc_attraction') {
                if (!$imageController->uploadImage($id_offre, 'plan', $_FILES['photo-plan']['tmp_name'], explode('/', $_FILES['photo-plan']['type'])[1])) {
                    echo "Erreur lors de l'upload de l'image du plan.";
                    BDD::rollbackTransaction();
                    exit;
                }
                // echo "Image du plan insérée.<br>";
            }

            if ($activityType === 'restauration') {
                if (!$imageController->uploadImage($id_offre, 'photo-resto', $_FILES['photo-resto']['tmp_name'], explode('/', $_FILES['photo-resto']['type'])[1])) {
                    echo "Erreur lors de l'upload de l'image de la carte du restaurant.";
                    BDD::rollbackTransaction();
                    exit;
                }
            }

            if ($activityType === 'visite' && $avec_guide) {
                // Insérer les langues dans la base de données
                require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/langue_controller.php';
                $langueController = new LangueController();
                require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/visite_langue_controller.php';
                $visiteLangueController = new VisiteLangueController();

                for ($i = 1; $i < count($langueController->getInfosAllLangues()) + 1; $i++) { // foreach ($langues as $langue => $isIncluded) {
                    $isIncluded = $_POST['langue' . $i] ?? "on";
                    if ($isIncluded) {
                        $visiteLangueController->linkVisiteAndLangue($id_offre, $i);
                    }
                }
            } elseif ($activityType === 'restauration') {
                require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/type_repas_controller.php';
                $typeRepasController = new TypeRepasController();
                require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/restauration_type_repas_controller.php';
                $restaurationTypeRepasController = new RestaurationTypeRepasController();

                foreach ($typesRepas as $typeRepas => $isIncluded) {
                    if ($isIncluded) {
                        $query = $typeRepasController->getTypeRepasByName($typeRepas);

                        $id_type_repas = $query ? $query[0]['id_type_repas'] : $typeRepasController->createTypeRepas($typeRepas);

                        $restaurationTypeRepasController->linkRestaurantAndTypeRepas($id_offre, $id_type_repas);
                    }
                }
            } elseif ($activityType === 'activite') {
                require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/prestation_controller.php';
                $prestationController = new PrestationController();
                require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/activite_prestation_controller.php';
                $activitePrestationController = new ActivitePrestationController();

                foreach ($prestations as $prestation => $isIncluded) {
                    $id_prestation = $prestationController->getPrestationByName($prestation);
                    if ($id_prestation < 0) {
                        $id_prestation = $prestationController->createPrestation($prestation, $isIncluded);
                    }

                    $activitePrestationController->linkActiviteAndPrestation($id_offre, $id_prestation);
                }
            }

            // Insérer les horaires dans la base de données
            require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/horaire_controller.php';
            $horaireController = new HoraireController();

            foreach ($horaires as $key => $jour) {
                $horaireController->createHoraire($key, $jour['ouverture'], $jour['fermeture'], $jour['pause'], $jour['reprise'], $id_offre);
            }

            // Insérer les prix dans la base de données
            require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/tarif_public_controller.php';
            $tarifController = new TarifPublicController();
            foreach ($prices as $price) {
                if (!isset($price['name']) || !isset($price['value'])) {
                    continue;
                }

                $tarifController->createTarifPublic($price['name'], $price['value'], $id_offre);
            }

            // Insérer les options dans la base de données
            if ($option == "A la une" || $option == "En relief") {
                $prix_ht = $option == "A la une" ? 8.34 : 16.68;
                $prix_ttc = $option == "A la une" ? 10.00 : 20.00;

                require_once dirname(path: $_SERVER["DOCUMENT_ROOT"]) . "/controller/souscription_controller.php";
                $souscription_controller = new SouscriptionController();
                $souscription_controller->createSouscription($id_offre, $option, $prix_ht, $prix_ttc, $debut_option, $duree_option);
            }

            BDD::commitTransaction();

            // Tout s'est bien passé (ouf !)
            header('location: /pro');
            $_SESSION['message_pour_notification'] = 'Votre offre a été créée';
        } catch (Exception $e) {
            echo "Erreur lors de l'insertion : " . $e->getMessage();
            BDD::rollbackTransaction();
            exit;
        }
    } else {
        require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/type_offre_controller.php';

        $typeOffreController = new TypeOffreController();
        $typesOffre = $typeOffreController->getAllTypeOffre();
        array_multisort($typesOffre, SORT_DESC);
        ?>

        <!-- Map à afficher pour choisir l'adresse -->
        <div id="map-container" class="z-30 fixed top-0 left-0 h-full w-full flex hidden items-center justify-center">
            <!-- Background blur -->
            <div class="fixed top-0 left-0 w-full h-full bg-blur/25 backdrop-blur"
                onclick="document.getElementById('map-container').classList.add('hidden');">
            </div>

            <div id="map" class="border border-black max-h-[500px] h-full max-w-[500px] w-full"></div>
        </div>

        <!-- LEAFLET JS -->
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <!-- GEOSEARCH JS -->
        <script src="https://unpkg.com/leaflet-geosearch@latest/dist/bundle.min.js"></script>
        <!-- CONFIGURER LA MAP -->
        <script src="/scripts/selectOnMap.js" type="module"></script>

        <!-- Conteneur principal pour le contenu -->
        <div class="flex flex-col w-full justify-between items-center align-baseline min-h-screen">

            <div class="w-full">
                <!-- Inclusion du header -->
                <?php
                include_once dirname($_SERVER['DOCUMENT_ROOT']) . '/view/header.php';
                ?>
            </div>

            <div class="grow w-full max-w-[1280px] mt-20 flex flex-col items-center justify-center p-2 ">
                <!-- Lien de retour avec une icône et un titre -->
                <div class="w-full flex">
                    <h1 class="text-3xl">Création d'offre</h1>
                </div>
                <!-- Section de sélection de l'offre -->
                <form id="formulaire" action="/pro/offre/creer/" method="POST" class="grow block w-full space-y-8"
                    enctype="multipart/form-data">
                    <div class="
                    <?php if ($pro['data']['type'] === 'prive') {
                        echo "grid grid-cols-2";
                    } ?>
                    justify-around items-evenly gap-6 w-full md:space-y-0 md:flex-nowrap">
                        <!-- Carte de l'offre gratuite -->
                        <?php
                        foreach ($typesOffre as $i => $typeOffre) {
                            $cardColor = $i % 2 == 0 ? 'secondary' : 'primary';
                            $cardVisible = $pro['data']['type'] == 'prive' ? ($typeOffre['id_type_offre'] == 1 ? 'hidden' : '') : ($typeOffre['id_type_offre'] == 1 ? '' : 'hidden');
                            $subTitle = "Pour les entreprises et organismes privés";
                            $avantages = [
                                "Jusqu’à 10 photos de présentations",
                                "Réponse aux avis des membres"
                            ];

                            if ($typeOffre['id_type_offre'] == 1) { // Gratuit
                                $subTitle = "Pour les associations et les organismes publics";
                            } else if ($typeOffre['id_type_offre'] == 2) { // Premium
                                $avantages[] = "Possibilité de remplir une grille tarifaire";
                                $avantages[] = "Possibilité de souscrire aux options “À la Une” et “En relief”";
                                $avantages[] = "<span class=''>Mise sur liste noire de 3 commentaires</span>";
                            } else if ($typeOffre['id_type_offre'] == 3) { // Standard
                                $avantages[] = "<span class=''>Possibilité de remplir une grille tarifaire</span>";
                                $avantages[] = "<span class=''>Possibilité de souscrire aux options “À la Une” et “En relief”</span>";
                            }
                            ?>
                            <div
                                class="border border-<?php echo $cardColor; ?>  flex-col justify-center w-full text-<?php echo $cardColor; ?> p-4 has-[:checked]:bg-<?php echo $cardColor; ?> has-[:checked]:text-white md:h-full <?php echo $cardVisible; ?>">

                                <input type="radio" name="type_offre" id="type_offre_<?php echo $typeOffre['id_type_offre']; ?>"
                                    value="<?php echo $typeOffre['id_type_offre']; ?>" class="hidden">

                                <label for="type_offre_<?php echo $typeOffre['id_type_offre']; ?>"
                                    class="divide-y divide-current cursor-pointer flex flex-col justify-between h-full">
                                    <div class="h-full divide-y divide-current">
                                        <div>
                                            <h1 class="text-3xl leading-none mt-1 text-center">
                                                <?php echo ucfirst($typeOffre['nom']) ?>
                                            </h1>
                                            <h1 class="text-center ">
                                                <?php echo $subTitle ?>
                                            </h1>
                                        </div>
                                        <div>
                                            <div class="ml-8">
                                                <ul class="list-disc text-left text-sm my-2">
                                                    <?php
                                                    foreach ($avantages as $avantage) {
                                                        echo "<li>$avantage</li>";
                                                    }
                                                    ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <h1 class="text-3xl leading-none mt-1 text-center py-2">
                                            <?php
                                            if ($typeOffre["prix_ht"] == 0) {
                                                echo "0€/jour en ligne";
                                            } else { ?>
                                                HT <?php echo $typeOffre['prix_ht']; ?> €/jour en ligne<br>
                                                <span class="text-2xl">
                                                    TTC <?php echo $typeOffre['prix_ttc']; ?> €/jour en ligne
                                                </span>
                                            <?php } ?>
                                        </h1>
                                    </div>
                                </label>
                            </div>
                            <?php
                        } ?>

                        <!-- <div class="border border-secondary  flex-col justify-center w-full text-secondary p-4 has-[:checked]:bg-secondary has-[:checked]:text-white md:h-full <?php if ($pro['data']['type'] === "prive") {
                            echo "hidden";
                        } ?>">
                            <input type="radio" name="type_offre" id="type_offre_1" value="1" class="hidden">
                            <label for="type_offre_1"
                                class="divide-y divide-current cursor-pointer flex flex-col justify-between h-full">
                                <div class="h-full divide-y divide-current">
                                    <div>
                                        <h1 class="text-3xl leading-none mt-1 text-center">Gratuite</h1>
                                        <h1 class="text-center ">Pour les associations et les organismes publics
                                        </h1>
                                    </div>
                                    <div>
                                        <div class="ml-8">
                                            <ul class="list-disc text-left text-sm my-2">
                                                <li>Jusqu’à 10 photos de présentations</li>
                                                <li>Réponse aux avis des membres</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <h1 class="text-3xl leading-none mt-1 text-center py-2">0€/mois</h1>
                                </div>
                            </label>
                        </div>
                        <div class="border border-primary  flex-col justify-center w-full text-primary p-4 has-[:checked]:bg-primary has-[:checked]:text-white md:h-full <?php if ($pro['data']['type'] === "public") {
                            echo "hidden";
                        } ?>">
                            <input type="radio" name="type_offre" id="type_offre_2" value="2" class="hidden">
                            <label for="type_offre_2"
                                class="divide-y divide-current cursor-pointer flex flex-col justify-between h-full">
                                <div class="h-full divide-y divide-current">
                                    <div>
                                        <h1 class="text-3xl leading-none mt-1 text-center">Standard</h1>
                                        <h1 class="text-center ">Pour les entreprises et organismes privés</h1>
                                    </div>
                                    <div class="h-full">
                                        <div class="ml-8">
                                            <ul class="list-disc text-left text-sm my-2">
                                                <li>Jusqu’à 10 photos de présentations</li>
                                                <li>Réponse aux avis des membres</li>
                                                <li>Possibilité de souscrire aux options “À la Une” et “En relief”</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <h1 class="text-3xl leading-none mt-1 text-center py-2">12€/mois</h1>
                                </div>
                            </label>
                        </div>
                        <div class="border border-secondary  flex-col justify-center w-full text-secondary p-4 has-[:checked]:bg-secondary has-[:checked]:text-white md:h-full <?php if ($pro['data']['type'] === "public") {
                            echo "hidden";
                        } ?>">
                            <input type="radio" name="type_offre" id="type_offre_3" value="3" class="hidden">
                            <label for="type_offre_3"
                                class="divide-y divide-current cursor-pointer flex flex-col justify-between h-full">
                                <div class="h-full divide-y divide-current">
                                    <div>
                                        <h1 class="text-3xl leading-none mt-1 text-center">Premium</h1>
                                        <h2 class="text-center ">Pour les entreprises et organismes privés</h2>
                                    </div>
                                    <div class="h-full">
                                        <p class="mt-2 text-sm ">Standard +</p>
                                        <div class="ml-8">
                                            <ul class="list-disc text-left text-sm">
                                                <li>Mise sur liste noire de 3 commentaires</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-3xl leading-none mt-1 text-center py-2">19€/mois</p>
                                </div>
                            </label>
                        </div> -->
                    </div>
                    <div class="w-full flex space-x-12">
                        <div class="w-full">
                            <div class="w-full flex flex-col justify-center items-center space-y-4 part1 hidden">
                                <h2 class="w-full text-2xl text-secondary">Informations</h2>

                                <!-- Titre -->
                                <div class="flex flex-col justify-center w-full">
                                    <label for="titre" class="text-nowrap">Titre :</label>
                                    <input type="text" id="titre" class="border border-secondary  p-2 bg-white w-full"
                                        name="titre" placeholder="Escapade En Arvor" required>
                                </div>

                                <!-- Auteur -->
                                <div class="flex flex-col w-full">
                                    <label for="auteur" class="text-nowrap">Auteur :</label>
                                    <input id="auteur" name="auteur"
                                        class="border border-secondary  p-2 bg-gray-200 w-full text-gray-600">
                                    <?php
                                    if ($pro) {
                                        echo $pro['nom_pro'];
                                    } else {
                                        echo "Nom du compte";
                                    } ?>
                                </div>

                                <!-- Champs pour l'adresse -->
                                <p id="select-on-map"
                                    class="text-sm p-2 border rounded-full text-center border-black self-start cursor-pointer hover:border-secondary hover:text-white hover:bg-secondary"
                                    onclick="showMap();">Trouver mon adresse</p>

                                <!-- Champs cachés pour les coordonnées -->
                                <input class='hidden' id='lat' name='lat' value="0">
                                <input class='hidden' id='lng' name='lng' value="0">

                                <!-- Adresse -->
                                <div class="justify-between items-center w-full mb-2">
                                    <label for="user_input_autocomplete_address" class="text-nowrap">Adresse :</label>
                                    <input type="text" id="user_input_autocomplete_address"
                                        name="user_input_autocomplete_address" placeholder="21, rue de la Paix"
                                        class="border border-secondary  p-2 bg-white w-full" required>
                                </div>

                                <div class="justify-between items-center w-full">
                                    <label for="locality" class="text-nowrap">Ville :</label>
                                    <input id="locality" name="locality" type="text"
                                        pattern="^[a-zA-Zéèêëàâôûç\-'\s]+(?:\s[A-Z][a-zA-Zéèêëàâôûç\-']+)*$"
                                        title="Saisir votre ville" placeholder="Rennes"
                                        class="border border-secondary  p-2 bg-white w-full" required>

                                    <label for="postal_code" class="text-nowrap mt-2">Code postal :</label>
                                    <input id="postal_code" name="postal_code" type="number" title="Format : 12345"
                                        placeholder="12345" class="border border-secondary p-2 mt-2 bg-white w-24 w-full"
                                        required>
                                </div>

                                <div class="w-full justify-between space-y-4">
                                    <!-- Photo principale -->
                                    <div class="flex flex-col justify-between w-full">
                                        <label for="photo-upload-carte" class="text-nowrap w-full">Photo de la carte
                                            :</label>
                                        <input type="file" name="photo-upload-carte" id="photo-upload-carte" class="text-center text-secondary block w-full
                                    border-dashed border-2 border-secondary  p-2
                                    file:mr-5 file:py-3 file:px-10
                                    file:
                                    file:text-sm file:  file:text-secondary
                                    file:border file:border-secondary
                                    hover:file:cursor-pointer hover:file:bg-secondary hover:file:text-white"
                                            accept=".svg,.png,.jpg,.jpeg,.webp" required>
                                    </div>

                                    <!-- Photos détaillée -->
                                    <div class="flex flex-col justify-between w-full">
                                        <label for="photo-detail[]" class="text-nowrap w-full">Photos de l'offre
                                            détaillée:
                                        </label>
                                        <input type="file" name="photo-detail[]" id="photo-detail[]" class="text-center text-secondary block w-full
                                            border-dashed border-2 border-secondary  p-2
                                            file:mr-5 file:py-3 file:px-10
                                            file:
                                            file:text-sm file:  file:text-secondary
                                            file:border file:border-secondary
                                            hover:file:cursor-pointer hover:file:bg-secondary hover:file:text-white"
                                            accept=".svg,.png,.jpg,.jpeg,.webp" multiple>
                                    </div>
                                </div>

                                <!-- Résumé -->
                                <div class="flex flex-col items-center w-full max-w-full">
                                    <label for="resume" class="text-nowrap w-full">Résumé :</label>
                                    <textarea id="resume" name="resume" class="border border-secondary  p-2 bg-white w-full"
                                        rows="4" placeholder="Le résumé visible sur la carte de l'offre."
                                        required></textarea>

                                </div>

                                <!-- Description -->
                                <div class="flex flex-col items-center w-full">
                                    <label for="description" class="text-nowrap w-full">Description :</label>
                                    <textarea id="description" name="description"
                                        class="border border-secondary  p-2 bg-white w-full" rows="11"
                                        placeholder="La description visible dans les détails de l'offre."
                                        required></textarea>
                                </div>

                                <!-- Accessibilité -->
                                <div class="flex flex-col justify-between items-center w-full">
                                    <label for="accessibilite" class="text-nowrap w-full">Accessibilité :</label>
                                    <textarea id="accessibilite" name="accessibilite"
                                        class="border border-secondary  p-2 bg-white w-full" rows="5"
                                        placeholder="Une description de l'accessibilité pour les personnes en situation de handicap, visible dans les détails de l'offre."></textarea>
                                </div>
                            </div>
                            <div class="w-full flex flex-col justify-center items-center space-y-4 part2 hidden pt-4">
                                <h2 class="w-full text-2xl text-secondary">Informations supplémentaires</h2>

                                <!-- Sélection du type d'activité -->
                                <div class="w-full">
                                    <label for="activityType" class="block text-nowrap">Type d'activité:</label>
                                    <select id="activityType" name="activityType"
                                        class="bg-white text-black py-2 px-4 border border-black  w-full" required>
                                        <option value="" selected hidden>Quel type d'activité ?</option>
                                        <option value="activite" id="activite">Activité</option>
                                        <option value="visite" id="visite">Visite</option>
                                        <option value="spectacle" id="spectacle">Spectacle</option>
                                        <option value="parc_attraction" id="parc_attraction">Parc d'attraction</option>
                                        <option value="restauration" id="restauration">Restauration</option>
                                    </select>
                                </div>

                                <div
                                    class="flex flex-col w-full optionActivite optionVisite optionSpectacle optionRestauration optionParcAttraction hidden">
                                    <label for="tag-input" class="block text-nowrap">Tags :</label>
                                    <select id="tag-input"
                                        class="bg-white text-black py-2 px-4 border border-black  w-full">
                                        <option value="" class="hidden" selected>Rechercher un tag</option>
                                    </select>
                                </div>

                                <div>
                                    <div class="tag-container flex flex-wrap p-2  optionActivite hidden" id="activiteTags">
                                    </div>
                                    <div class="tag-container flex flex-wrap p-2  optionVisite hidden" id="visiteTags">
                                    </div>
                                    <div class="tag-container flex flex-wrap p-2  optionSpectacle hidden"
                                        id="spectacleTags"></div>
                                    <div class="tag-container flex flex-wrap p-2  optionParcAttraction hidden"
                                        id="parcAttractionTags"></div>
                                    <div class="tag-container flex flex-wrap p-2  optionRestauration hidden"
                                        id="restaurationTags"></div>
                                </div>

                                <!-- PARAMÈTRES DÉPENDANT DE LA CATÉGORIE DE L'OFFRE -->
                                <!-- Visite guidée -->
                                <!-- Visite -->
                                <div class="flex justify-between items-center w-full space-x-2 optionVisite hidden">
                                    <div class="inline-flex items-center space-x-4" onclick="toggleCheckbox('guide')">
                                        <p>Visite guidée :</p>
                                        <input type="checkbox" name="guide" id="guide" class="sr-only peer">
                                        <div
                                            class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800  peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after: after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                                        </div>
                                        <div class="space-x-2 w-fit flex items-center invisible peer-checked:visible">
                                            <p>
                                                Langues parlées :
                                            </p>
                                            <?php
                                            require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/langue_controller.php';
                                            $langueController = new LangueController();

                                            $langues = $langueController->getInfosAllLangues();

                                            foreach ($langues as $langue) { ?>
                                                <div class="w-fit p-2  border border-transparent hover:border-secondary"
                                                    onclick="toggleCheckbox('<?php echo 'langue' . $langue['id_langue']; ?>')">
                                                    <input type="checkbox" name="<?php echo 'langue' . $langue['id_langue']; ?>"
                                                        id="<?php echo 'langue' . $langue['id_langue']; ?>" class="hidden">
                                                    <label
                                                        for="<?php echo 'langue' . $langue['id_langue']; ?>"><?php echo $langue['nom']; ?></label>
                                                </div>
                                            <?php }
                                            ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Âge requis -->
                                <!-- Activité, Parc d'attractions -->
                                <div
                                    class="flex justify-start items-center w-full space-x-2 optionActivite optionParcAttraction hidden">
                                    <label for="age" class="text-nowrap">Âge requis :</label>
                                    <input type="number" id="age" min="0" max="125" name="age"
                                        class="border border-secondary  p-2 bg-white w-fit text-right">
                                    <p>an(s)</p>
                                </div>

                                <!-- Durée (HEURE & MIN) -->
                                <!-- Activité, Visite, Spectacle -->
                                <div
                                    class="flex justify-start items-center w-full space-x-1 optionActivite optionVisite optionSpectacle hidden">
                                    <label for="duree" class="text-nowrap">Durée :</label>
                                    <input type="number" name="hours" id="duree" min="0" max="23"
                                        class="border border-secondary  p-2 bg-white w-fit text-right">
                                    <p>h </p>
                                    <input type="number" name="minutes" id="minute" min="0" max="59"
                                        class="border border-secondary  p-2 bg-white w-fit text-right">
                                    <p>min</p>
                                </div>

                                <!-- Gamme de prix -->
                                <!-- Restauration -->
                                <div class="flex justify-start items-center w-full space-x-4 optionRestauration hidden">
                                    <label for="gamme2prix" class="text-nowrap">Gamme de prix :</label>
                                    <select name="gamme2prix" id="gamme2prix">
                                        <option value="€">&lt;25€</option>
                                        <option value="€€" selected>&lt;40€</option>
                                        <option value="€€€">&gt;40€</option>
                                    </select>
                                </div>

                                <!-- <div class="flex justify-start items-center w-full space-x-4 optionRestauration hidden">
                                        <label for="gamme2prix" class="text-nowrap">Gamme de prix :</label>
                                        <div class="flex space-x-6">
                                            <div>
                                                <input type="radio" name="gamme2prix" value="€">
                                                <p>€ (&lt;25€)</p>
                                            </div>
                                            <div>
                                                <input type="radio" name="gamme2prix" value="€€" checked>
                                                <p>€€ (&lt;40€)</p>
                                            </div>
                                            <div>
                                                <input type="radio" name="gamme2prix" value="€€€">
                                                <p>€€€ (&gt;40€)</p>
                                            </div>
                                        </div>
                                    </div> -->

                                <!-- Capacité d'accueil -->
                                <!-- Spectacle -->
                                <div class="flex justify-start items-center w-full space-x-2 optionSpectacle hidden">
                                    <label for="capacite" class="text-nowrap">Capacité d'accueil :</label>
                                    <input type="number" name="capacite" id="capacite" onchange="" min="0"
                                        class="border border-secondary  p-2 bg-white w-fit text-right">
                                    <p>personnes</p>
                                </div>

                                <!-- Nombre d'attractions -->
                                <!-- Parc d'attractions -->
                                <div class="flex justify-start items-center w-full space-x-2 optionParcAttraction hidden">
                                    <label for="nb_attractions" class="text-nowrap">Nombre d'attraction :</label>
                                    <input type="number" name="nb_attractions" id="nb_attractions" onchange="" min="0"
                                        class="border border-secondary  p-2 bg-white w-fit text-right">
                                    <p>attractions</p>
                                </div>

                                <!-- Repas servis -->
                                <div class="space-x-2 w-full flex justify-start items-center optionRestauration hidden">
                                    <p>
                                        Repas servis :
                                    </p>
                                    <div class="w-fit p-2  border border-transparent"
                                        onclick="toggleCheckbox('repasPetitDejeuner')">
                                        <input type="checkbox" name="repasPetitDejeuner" id="repasPetitDejeuner"
                                            class="hidden">
                                        <label for="repasPetitDejeuner">Petit-déjeuner</label>
                                    </div>
                                    <div class="w-fit p-2  border border-transparent"
                                        onclick="toggleCheckbox('repasBrunch')">
                                        <input type="checkbox" name="repasBrunch" id="repasBrunch" class="hidden">
                                        <label for="repasBrunch">Brunch</label>
                                    </div>
                                    <div class="w-fit p-2  border border-transparent"
                                        onclick="toggleCheckbox('repasDejeuner')">
                                        <input type="checkbox" name="repasDejeuner" id="repasDejeuner" class="hidden">
                                        <label for="repasDejeuner">Déjeuner</label>
                                    </div>
                                    <div class="w-fit p-2  border border-transparent"
                                        onclick="toggleCheckbox('repasDiner')">
                                        <input type="checkbox" name="repasDiner" id="repasDiner" class="hidden">
                                        <label for="repasDiner">Dîner</label>
                                    </div>
                                    <div class="w-fit p-2  border border-transparent"
                                        onclick="toggleCheckbox('repasBoissons')">
                                        <input type="checkbox" name="repasBoissons" id="repasBoissons" class="hidden">
                                        <label for="repasBoissons">Boissons</label>
                                    </div>
                                </div>

                                <!-- Plan du parc d'attraction -->
                                <!-- Parc d'attraction -->
                                <div class="flex flex-col justify-between w-full optionParcAttraction hidden">
                                    <label for="photo-plan" class="text-nowrap w-full">Plan du parc d'attraction
                                        :</label>
                                    <input type="file" name="photo-plan" id="photo-plan" class="text-center text-secondary block w-full
                            border-dashed border-2 border-secondary  p-2
                            file:mr-5 file:py-3 file:px-10
                            file:f
                            file:text-sm file:  file:text-secondary
                            file:border file:border-secondary
                            hover:file:cursor-pointer hover:file:bg-secondary hover:file:text-white"
                                        accept=".svg,.png,.jpg">
                                </div>

                                <!-- Carte du restaurant -->
                                <div class="flex flex-col justify-between w-full optionRestauration hidden">
                                    <label for="photo-resto" class="text-nowrap w-full">Carte du restaurant :</label>
                                    <input type="file" name="photo-resto" id="photo-resto" class="text-center text-secondary block w-full
                            border-dashed border-2 border-secondary  p-2
                            file:mr-5 file:py-3 file:px-10
                            file:
                            file:text-sm file:  file:text-secondary
                            file:border file:border-secondary
                            hover:file:cursor-pointer hover:file:bg-secondary hover:file:text-white"
                                        accept=".svg,.png,.jpg">
                                </div>

                                <!-- Services -->
                                <!-- Formulaire pour entrer les informations -->
                                <div class="flex flex-col justify-center items-center w-full space-y-4">
                                    <!-- PRESTATIONS -->
                                    <div class="w-full optionActivite hidden">
                                        <h2 class="text-2xl text-secondary">Prestation</h2>
                                        <table class="w-full">
                                            <thead>
                                                <tr>
                                                    <th>
                                                        Nom
                                                    </th>
                                                    <th class="text-nowrap">
                                                        Est incluse ?
                                                    </th>
                                                    <th>
                                                        Actions
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody id="prestations">

                                            </tbody>
                                            <tr>
                                                <td class="w-full">
                                                    <input type="text" id="newPrestationName"
                                                        class="border border-secondary  p-2 bg-white w-full">
                                                </td>
                                                <td class="w-fit group">
                                                    <input type="checkbox" id="newPrestationInclude" class="hidden peer">
                                                    <label for="newPrestationInclude"
                                                        class="h-max w-full cursor-pointer flex justify-center items-center stroke-rouge-logo peer-checked:hidden">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                            viewBox="0 0 32 32" fill="none" stroke-width="2.5"
                                                            stroke-linecap="round" stroke-linejoin="round"
                                                            class="lucide lucide-square-x">
                                                            <rect width="28" height="28" x="2" y="2" rx="4" ry="4" />
                                                            <path d="m24 8-16 16" />
                                                            <path d="m8 8 16 16" />
                                                        </svg>
                                                    </label>
                                                    <label for="newPrestationInclude"
                                                        class="hidden h-max w-full cursor-pointer justify-center items-center fill-secondary peer-checked:flex">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                            viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                                            <path
                                                                d="M64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l320 0c35.3 0 64-28.7 64-64l0-320c0-35.3-28.7-64-64-64L64 32zM337 209L209 337c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47L303 175c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z" />
                                                        </svg>
                                                    </label>
                                                </td>
                                                <td class="w-fit">
                                                    <div class="h-max w-full cursor-pointer flex justify-center items-center"
                                                        id="addPrestationButton">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="fill-secondary  border border-transparent border-box p-1"
                                                            width="32" height="32"
                                                            viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                                            <path
                                                                d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 144L48 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l144 0 0 144c0 17.7 14.3 32 32 32s32-14.3 32-32l0-144 144 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-144 0 0-144z" />
                                                        </svg>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>

                                    <!-- HORAIRES -->
                                    <div
                                        class="w-full optionActivite optionVisite optionSpectacle optionParcAttraction optionRestauration hidden">

                                        <h2 class="text-2xl text-secondary">Horaires</h2>
                                        <table class="w-full table-auto">
                                            <thead>
                                                <tr>
                                                    <th>
                                                    </th>
                                                    <th>
                                                        Lundi
                                                    </th>
                                                    <th>
                                                        Mardi
                                                    </th>
                                                    <th>
                                                        Mercredi
                                                    </th>
                                                    <th>
                                                        Jeudi
                                                    </th>
                                                    <th>
                                                        Vendredi
                                                    </th>
                                                    <th>
                                                        Samedi
                                                    </th>
                                                    <th>
                                                        Dimanche
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        Ouverture
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[lundi][ouverture]"
                                                            id="horaires[lundi][ouverture]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[mardi][ouverture]"
                                                            id="horaires[mardi][ouverture]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[mercredi][ouverture]"
                                                            id="horaires[mercredi][ouverture]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[jeudi][ouverture]"
                                                            id="horaires[jeudi][ouverture]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[vendredi][ouverture]"
                                                            id="horaires[vendredi][ouverture]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[samedi][ouverture]"
                                                            id="horaires[samedi][ouverture]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[dimanche][ouverture]"
                                                            id="horaires[dimanche][ouverture]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        Pause
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[lundi][pause]"
                                                            id="horaires[lundi][pause]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[mardi][pause]"
                                                            id="horaires[mardi][pause]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[mercredi][pause]"
                                                            id="horaires[mercredi][pause]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[jeudi][pause]"
                                                            id="horaires[jeudi][pause]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[vendredi][pause]"
                                                            id="horaires[vendredi][pause]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[samedi][pause]"
                                                            id="horaires[samedi][pause]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[dimanche][pause]"
                                                            id="horaires[dimanche][pause]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        Reprise
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[lundi][reprise]"
                                                            id="horaires[lundi][reprise]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[mardi][reprise]"
                                                            id="horaires[mardi][reprise]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[mercredi][reprise]"
                                                            id="horaires[mercredi][reprise]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[jeudi][reprise]"
                                                            id="horaires[jeudi][reprise]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[vendredi][reprise]"
                                                            id="horaires[vendredi][reprise]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[samedi][reprise]"
                                                            id="horaires[samedi][reprise]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[dimanche][reprise]"
                                                            id="horaires[dimanche][reprise]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        Fermeture
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[lundi][fermeture]"
                                                            id="horaires[lundi][fermeture]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[mardi][fermeture]"
                                                            id="horaires[mardi][fermeture]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[mercredi][fermeture]"
                                                            id="horaires[mercredi][fermeture]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[jeudi][fermeture]"
                                                            id="horaires[jeudi][fermeture]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[vendredi][fermeture]"
                                                            id="horaires[vendredi][fermeture]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[samedi][fermeture]"
                                                            id="horaires[samedi][fermeture]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                    <td class="relative">
                                                        <input type="time" name="horaires[dimanche][fermeture]"
                                                            id="horaires[dimanche][fermeture]"
                                                            class="border border-secondary  p-2 bg-white mx-auto block">
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <p>
                                            <span class="">Pro Tip :</span> Lorsque vous remplissez les horaires du
                                            lundi, elles mettent à jour les horaires des autres jours de la semaine.
                                        </p>
                                    </div>

                                    <!-- GRILLE TARIFAIRE -->
                                    <div class="w-full <?php if ($pro['data']['type'] === 'prive') {
                                        echo "optionActivite optionVisite optionSpectacle optionParcAttraction";
                                    } ?> hidden">
                                        <h2 class="text-2xl text-secondary">Grille tarifaire</h2>
                                        <table class="w-full">
                                            <thead>
                                                <th>
                                                    Titre
                                                </th>
                                                <th>
                                                    Prix<br>en €
                                                </th>
                                                <th>
                                                    Actions
                                                </th>
                                            </thead>
                                            <tbody id="grilleTarifaire">

                                            </tbody>
                                            <tr>
                                                <td class="w-full">
                                                    <input type="text" id="newPrixName"
                                                        class="border border-secondary  p-2 bg-white w-full">
                                                </td>
                                                <td class="w-fit">
                                                    <input type="number" id="newPrixValeur" min="0"
                                                        class="border border-secondary  p-2 bg-white">
                                                </td>
                                                <td class="w-fit">
                                                    <div class="h-max w-full cursor-pointer flex justify-center items-center"
                                                        id="addPriceButton">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="fill-secondary  border border-transparent hover:border-secondary border-box p-1"
                                                            width="32" height="32"
                                                            viewBox="0 0 448 512"><!--!Font Awesome Free 6.6.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                                                            <path
                                                                d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 144L48 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l144 0 0 144c0 17.7 14.3 32 32 32s32-14.3 32-32l0-144 144 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-144 0 0-144z" />
                                                        </svg>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                <div class="<?php if ($pro['data']['type'] === 'prive') {
                                    echo "optionActivite optionVisite optionSpectacle optionRestauration optionParcAttraction";
                                } ?> hidden w-full">
                                    <h1 class="text-2xl text-secondary">Les options</h1>

                                    <!-- CGU -->
                                    <a href="/cgu" class="text-sm underline text-secondary"> Voir les CGU</a>

                                    <!-- Radio button -->
                                    <div
                                        class="flex flex-row mb-4 content-center justify-between items-center text-secondary w-full">
                                        <!-- Sans option -->
                                        <div class="w-fit p-2  border border-transparent hover:border-secondary has-[:checked]:bg-secondary has-[:checked]:text-white  text-lg"
                                            id="option-rien-div">
                                            <input type="radio" id="option-rien" name="option" value="1" class="hidden"
                                                checked>
                                            <label for="option-rien">Sans option</label>
                                        </div>
                                        <?php
                                        require_once dirname($_SERVER["DOCUMENT_ROOT"]) . "/php_files/connect_to_bdd.php";

                                        $stmt = $dbh->prepare('SELECT * FROM sae_db._option ORDER BY prix_ht ASC');
                                        $stmt->execute();
                                        $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        foreach ($options as $option) {
                                            $nom_option = str_contains($option['nom'], 'relief') ? "option-relief" : "option-a-la-une";
                                            ?>
                                            <div class="w-fit p-2  border border-transparent hover:border-secondary has-[:checked]:bg-secondary has-[:checked]:text-white  text-center text-lg"
                                                id="<?php echo $nom_option; ?>-div">
                                                <input type="radio" id="<?php echo $nom_option; ?>" name="option"
                                                    value="<?php echo $option['nom']; ?>" class="hidden" />
                                                <label
                                                    for="<?php echo $nom_option; ?>"><?php echo ucwords($option['nom']); ?><br>
                                                    <span class="font-normal text-base">HT
                                                        <?php echo $option['prix_ht']; ?> €/semaine<br>(TTC
                                                        <?php echo $option['prix_ttc']; ?> €/semaine)</span>
                                                </label>
                                            </div>
                                        <?php }
                                        ?>
                                        <script>
                                            document.getElementById('option-rien').addEventListener('change', function () {
                                                if (document.getElementById('option-rien').checked === 'true') {
                                                    document.getElementById('duration').removeAttribute('required');
                                                    document.getElementById('start_date').removeAttribute('required');
                                                } else {
                                                    document.getElementById('duration').setAttribute('required', 'required');
                                                    document.getElementById('start_date').setAttribute('required', 'required');
                                                }
                                            });
                                        </script>
                                    </div>

                                    <div class="flex items-start hidden" id="option-data">
                                        <div class="flex flex-col justify-center w-full">
                                            <label for="start_date" class="text-nowrap">Début de la souscription
                                                :</label>
                                            <input type="date" id="start_date" name="start_date"
                                                class="border border-secondary  p-2 bg-white w-min"
                                                oninput="validateMonday(this)">
                                            <script>
                                                function validateMonday(input) {
                                                    const date = new Date(input.value);
                                                    if (date.getDay() !== 1) {
                                                        const nextMonday = new Date(date.setDate(date.getDate() + (1 + 7 - date.getDay()) % 7));
                                                        input.value = nextMonday.toISOString().split('T')[0];
                                                    }
                                                }

                                                document.getElementById('start_date').addEventListener('focus', function (e) {
                                                    e.target.setAttribute('min', getNextMonday());
                                                    e.target.value = getNextMonday();
                                                });

                                                function getNextMonday() {
                                                    const today = new Date();
                                                    const nextMonday = new Date(today.setDate(today.getDate() + (1 + 7 - today.getDay()) % 7));
                                                    return nextMonday.toISOString().split('T')[0];
                                                }
                                            </script>
                                            <p>
                                                Votre souscription doit commencer un lundi.
                                            </p>
                                        </div>

                                        <div class="flex flex-col justify-center w-full">
                                            <label for="duration" class="text-nowrap">Durée de la souscription :</label>
                                            <input type="number" id="duration" name="duration" min="1" max="4" value="1"
                                                class="border border-secondary  p-2 bg-white w-min">
                                            <script>
                                                document.getElementById('duration').addEventListener('change', function (event) {
                                                    const value = parseInt(event.target.value, 10);
                                                    if (value < 1) {
                                                        event.target.value = 1;
                                                    } else if (value > 4) {
                                                        event.target.value = 4;
                                                    }
                                                });
                                            </script>
                                            <p>
                                                La durée se compte en semaines.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Créer l'offre -->
                            <div
                                class="w-full flex justify-center items-center optionActivite optionVisite optionSpectacle optionRestauration optionParcAttraction hidden">
                                <input type="submit" value="Créer l'offre" id="submitPart3"
                                    class="bg-secondary text-white font-medium py-2 px-4  inline-flex items-center border border-transparent hover:bg-secondary/90 hover:border-secondary/90 focus:scale-[0.97] w-1/2 m-1 disabled:bg-gray-300 disabled:border-gray-300"
                                    disabled="true">
                            </div>
                        </div>
                        <!-- Mettre la preview à droite du fleuve -->
                        <div
                            class="w-full min-w-[450px] max-w-[450px] h-screen flex justify-center items-center sticky top-0 part1 hidden">
                            <div class="h-fit w-full">
                                <!-- Affiche de la carte en fonction de l'option choisie et des informations rentrées au préalable. -->
                                <!-- Script > listener sur "change" sur les inputs radios (1 sur chaque) ; si input en relief ou À la Une, ajouter(.add('active')) à la classlist(.classList) du div {card-preview} "active", sinon l'enlever(.remove('active')) -->
                                <div class="card relative bg-base100 flex flex-col w-full" id="card-preview">
                                    <script>
                                        // Fonction pour activer ou désactiver la carte en fonction de l'option choisie
                                        function toggleCardPreview(option) {
                                            // Récupérer l'élément de la carte
                                            const cardPreview = document.getElementById("card-preview");
                                            // Ajouter ou retirer la classe active en fonction de l'option choisie
                                            if (option === "option-rien") {
                                                cardPreview.classList.remove("active");
                                            } else {
                                                cardPreview.classList.add("active");
                                            }
                                        }
                                        // Ajouter un EventListener pour détecter les changements dans les options
                                        optionData = document.getElementById("option-data");
                                        document.getElementById("option-rien-div").addEventListener("click", function () {
                                            toggleRadio("option-rien");
                                            toggleCardPreview("option-rien");
                                            optionData.classList.add('hidden');
                                        });
                                        document.getElementById("option-relief-div").addEventListener("click", function () {
                                            toggleRadio("option-relief");
                                            toggleCardPreview("option-relief");
                                            optionData.classList.remove('hidden');
                                        });
                                        document.getElementById("option-a-la-une-div").addEventListener("click", function () {
                                            toggleRadio("option-a-la-une");
                                            toggleCardPreview("option-a-la-une");
                                            optionData.classList.remove('hidden');
                                        });
                                    </script>
                                    <!-- En tête -->
                                    <div
                                        class="en-tete absolute top-0 w-72 max-w-full bg-blur/50 backdrop-blur left-1/2 -translate-x-1/2 ">
                                        <!-- Mise à jour du titre en temps réel -->
                                        <h3 class="text-center " id="preview-titre"></h3>
                                        <script>
                                            document.getElementById("preview-titre").textContent = document.getElementById("titre").value ?
                                                document.getElementById("titre").value :
                                                // Si le titre est vide, afficher le placeholder du titre
                                                document.getElementById("titre").placeholder;
                                            document
                                                .getElementById("titre")
                                                .addEventListener("input", function () {
                                                    document.getElementById("preview-titre").textContent = document.getElementById("titre").value ?
                                                        document.getElementById("titre").value :
                                                        // Si le titre est vide, afficher le placeholder du titre
                                                        document.getElementById("titre").placeholder;
                                                });
                                        </script>
                                        <div class="flex w-full justify-between px-2">
                                            <!-- Mise à jour de l'auteur en temps réel -->
                                            <p class="text-sm" id="preview-auteur"></p>
                                            <script>
                                                document.getElementById("preview-auteur").textContent =
                                                    document.getElementById("auteur").innerText;
                                            </script>
                                            <p class="text-sm" id="preview-activite"></p>
                                            <!-- Mise à jour de l'activité en fonction de la sélection -->
                                            <script>
                                                // Fonction pour mettre à jour la sélection d'activité
                                                function updateActivite() {
                                                    // Récupérer la valeur sélectionnée dans le sélecteur
                                                    const selectedActivite =
                                                        document.getElementById("activityType").value;
                                                    // Transforme la value en texte propre
                                                    switch (selectedActivite) {
                                                        case "activite":
                                                            document.getElementById(
                                                                "preview-activite"
                                                            ).textContent = "Activité";
                                                            break;
                                                        case "visite":
                                                            document.getElementById(
                                                                "preview-activite"
                                                            ).textContent = "Visite";
                                                            break;
                                                        case "spectacle":
                                                            document.getElementById(
                                                                "preview-activite"
                                                            ).textContent = "Spectacle";
                                                            break;
                                                        case "parc_attraction":
                                                            document.getElementById(
                                                                "preview-activite"
                                                            ).textContent = "Parc d'attraction";
                                                            break;
                                                        case "restauration":
                                                            document.getElementById(
                                                                "preview-activite"
                                                            ).textContent = "Restauration";
                                                            break;
                                                        default:
                                                            document.getElementById(
                                                                "preview-activite"
                                                            ).textContent = "Type d'activité";
                                                    }
                                                }
                                                // Ajouter un EventListener pour détecter les changements dans le sélecteur
                                                document
                                                    .getElementById("activityType")
                                                    .addEventListener("change", updateActivite);
                                                // Appeler la fonction une première fois pour l'initialisation avec la valeur par défaut
                                                updateActivite();
                                            </script>
                                        </div>
                                    </div>
                                    <!-- Image de fond -->
                                    <img class="h-48 w-full object-cover" src="/public/images/image-test.png"
                                        id="preview-image" />
                                    <script>
                                        document
                                            .getElementById("photo-upload-carte")
                                            .addEventListener("change", function (event) {
                                                const file = event.target.files[0]; // Récupérer le fichier sélectionné
                                                const previewImage =
                                                    document.getElementById("preview-image"); // Élément d'image à mettre à jour

                                                if (file) {
                                                    const reader = new FileReader(); // Créer un nouvel objet FileReader
                                                    reader.onload = function (e) {
                                                        previewImage.src = e.target.result; // Mettre à jour la source de l'image avec le fichier
                                                    };
                                                    reader.readAsDataURL(file); // Lire le fichier comme une URL de données
                                                } else {
                                                    previewImage.src = "#"; // Image par défaut ou vide si aucun fichier
                                                }
                                            });
                                    </script>
                                    <!-- Infos principales -->
                                    <div class="infos flex items-center justify-around gap-2 px-2 w-full max-w-full">
                                        <!-- Localisation -->
                                        <div
                                            class="localisation flex flex-col gap-2 flex-shrink-0 justify-center items-center">
                                            <i class="fa-solid fa-location-dot"></i>
                                            <!-- Mise à jour de la ville en temps réel -->
                                            <p class="text-sm" id="preview-locality"></p>
                                            <!-- Mise à jour du code postal en temps réel -->
                                            <p class="text-sm" id="preview-postal_code"></p>
                                            <script>
                                                setInterval(function () {
                                                    const locality = document.getElementById("locality").value;
                                                    const postalCode = document.getElementById("postal_code").value;
                                                    document.getElementById("preview-locality").textContent = locality ? locality : document.getElementById("locality").placeholder;
                                                    document.getElementById("preview-postal_code").textContent = postalCode ? postalCode : document.getElementById("postal_code").placeholder;
                                                }, 100);
                                            </script>
                                        </div>
                                        <!-- Résumé de l'offre -->
                                        <div
                                            class="description py-2 flex flex-col gap-2 justify-center w-full max-w-[300px]">
                                            <div class="p-1 w-full flex justify-center items-center">
                                                <!-- Mise à jour du tag en temps réel -->
                                                <p class="tags text-white text-center  bg-secondary  w-fit p-2"
                                                    id="preview-tag-input">
                                                    Ajouter un tag...
                                                </p>
                                                <script>
                                                    function refreshTagPreview() {
                                                        const tagPreview = document.getElementById(
                                                            "preview-tag-input"
                                                        )

                                                        document.querySelectorAll('.tag-container')?.forEach(container => {
                                                            if (!container.classList.contains('hidden')) {
                                                                const tags = Array.from(container.children).map(tag => tag.childNodes[0].nodeValue).join(', ');
                                                                tagPreview.textContent = tags !== '' ? (tags.length > 30 ? tags.slice(0, 30) + "..." : tags) : "Ajouter un tag...";
                                                            }
                                                        });
                                                    }
                                                    refreshTagPreview();

                                                    Array.from(document
                                                        .getElementsByClassName("tag-container")).forEach(
                                                            (container) => {
                                                                const observer = new MutationObserver(refreshTagPreview);
                                                                observer.observe(container, {
                                                                    childList: true
                                                                });
                                                            }
                                                        )
                                                </script>
                                            </div>
                                            <!-- Mise à jour du résumé en temps réel -->
                                            <p class="line-clamp-2 text-sm text-center break-words max-w-full"
                                                id="preview-resume"></p>
                                            <script>
                                                document.getElementById("preview-resume").textContent =
                                                    document.getElementById("resume").value ? document.getElementById("resume").value : document.getElementById("resume").placeholder
                                                document
                                                    .getElementById("resume")
                                                    .addEventListener("input", function () {
                                                        document.getElementById("preview-resume").textContent =
                                                            document.getElementById("resume").value ? document.getElementById("resume").value : document.getElementById("resume").placeholder;
                                                    });
                                            </script>
                                        </div>
                                        <!-- Notation et Prix -->
                                        <div
                                            class="localisation flex flex-col flex-shrink-0 gap-2 justify-center items-center">
                                            <p class="text-sm" id="preview-prix-diff">€</p>
                                            <!-- Valeur par défaut -->
                                        </div>
                                        <!-- Mise à jour de la gamme de prix -->
                                        <script>
                                            // Fonction pour mettre à jour la gamme de prix
                                            function updatePrixDiff() {
                                                // Récupérer la valeur du bouton radio sélectionné
                                                const selectedPrix = document.querySelector(
                                                    'input[name="gamme2prix"]:checked'
                                                ).value;
                                                // Mettre à jour le texte dans la prévisualisation
                                                document.getElementById("preview-prix-diff").textContent =
                                                    selectedPrix;
                                            }

                                            // Ajouter un EventListener pour détecter les changements dans le select de la gamme de prix
                                            document.getElementById('gamme2prix').addEventListener('change', updatePrixDiff);

                                            // Appeler la fonction une première fois pour l'initialisation avec la valeur par défaut
                                            updatePrixDiff();
                                        </script>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
            </form>
        </div>

        <!-- FOOTER -->
        <div class="w-full">
            <?php
            include_once dirname($_SERVER['DOCUMENT_ROOT']) . '/view/footer.php';
            ?>
        </div>
        </div>

        <script src="/scripts/tagManager.js"></script>
        <script>
            const tagManager = new TagManager('tag-input', []);
        </script>
        <script src="/scripts/priceManager.js"></script>
        <script>
            const priceManager = new PriceManager('grilleTarifaire', []);
        </script>
        <script src="/scripts/prestationManager.js"></script>
        <script src="/scripts/optionToggler.js"></script>
        <script>
            // Lors de l'appui sur entrer, ne pas soumettre le formulaire
            document.getElementById('formulaire').addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                }
            });

            // Fonction pour afficher la partie 1 du formulaire
            function showPart1() {
                // Récupérer les éléments à afficher
                const elements = document.getElementsByClassName("part1");
                // Afficher les éléments
                for (let i = 0; i < elements.length; i++) {
                    elements[i].classList.remove("hidden");
                }
            }

            // Fonction pour afficher la partie 2 du formulaire
            function showPart2() {
                // Récupérer les éléments à afficher
                const part2 = document.querySelector(".part2");
                // Afficher les éléments
                part2.classList.remove("hidden");
            }

            function showPart3() {
                document.getElementById("submitPart3").removeAttribute("disabled");
            }

            function hidePart3() {
                document.getElementById("submitPart3").setAttribute("disabled", "true");
            }

            function checkPart1Validity() {
                const offreRadios = document.querySelectorAll('input[name="type_offre"]');
                let isValid = false;

                offreRadios.forEach((radio) => {
                    if (radio.checked) {
                        isValid = true;
                    }
                });

                if (isValid) {
                    showPart1();
                }

                return isValid;
            }

            function checkPart2Validity(fieldChanged) {
                if (!checkPart1Validity()) {
                    return false;
                }

                const requiredFields = document.querySelectorAll('.part1 input[required], .part1 textarea[required]');
                let isValid = true;

                requiredFields.forEach((field) => {
                    if (field.nodeName === 'INPUT' && field.attributes['type'].value === 'number') { // Locality
                        if (field.value === '' || RegExp('^((22)|(29)|(35)|(56))[0-9]{3}$').test(field.value) === false) {
                            if (fieldChanged.compareDocumentPosition(field) & Node.DOCUMENT_POSITION_PRECEDING || fieldChanged.compareDocumentPosition(field) === 0) {
                                field.classList.remove("border-secondary")
                                field.classList.add('border-red-500');
                            }
                            isValid = false;
                        } else {
                            field.classList.remove("border-red-500");
                            field.classList.add('border-secondary');
                        }
                    } else {
                        if (field.value.trim() === '') {
                            if (fieldChanged.compareDocumentPosition(field) & Node.DOCUMENT_POSITION_PRECEDING || fieldChanged.compareDocumentPosition(field) === 0) {
                                field.classList.remove("border-secondary")
                                field.classList.add('border-red-500');
                            }
                            isValid = false;
                        } else {
                            field.classList.remove("border-red-500");
                            field.classList.add('border-secondary');
                        }
                    }
                });

                if (isValid) {
                    showPart2();
                }

                return isValid;
            }

            function checkPart3Validity(fieldChanged) {
                if (!checkPart2Validity(fieldChanged)) {
                    return false;
                }

                const requiredFields = document.querySelectorAll('.part2 [required]');
                let isValid = true;

                requiredFields.forEach((field) => {
                    if (field.nodeName === 'INPUT' && field.attributes['type'].value === 'number') {
                        if (fieldChanged.compareDocumentPosition(field) & Node.DOCUMENT_POSITION_PRECEDING || fieldChanged.compareDocumentPosition(field) === 0) {
                            if (field.value.trim() === '' || field.value < 0 || RegExp('^[0-9]+$').test(field.value) === false) {
                                field.classList.remove("border-secondary")
                                field.classList.add('border-red-500');
                                isValid = false;
                            } else {
                                field.classList.remove("border-red-500");
                                field.classList.add('border-secondary');
                            }
                        }
                    }
                });

                if (isValid) {
                    showPart3();
                } else {
                    hidePart3();
                }
            }

            function toggleCheckbox(id) {
                const checkbox = document.getElementById(id);
                checkbox.checked = !checkbox.checked;
            }

            function toggleRadio(id) {
                const radio = document.getElementById(id);
                radio.checked = true;
            }

            document.querySelectorAll('input[name="type_offre"]').forEach((radio) => {
                radio.addEventListener("change", () => {
                    checkPart1Validity();
                });
            });

            const fields = document.querySelectorAll('input, textarea, select');

            fields.forEach((field) => {
                field.addEventListener('input', (e) => {
                    checkPart3Validity(field);
                    if (field.nodeName === 'INPUT' && field.attributes['type'].value === 'number') {
                        field.value = field.value.replace(/[^0-9]/g, '');
                    }
                });
            });
        </script>
        <script>
            // TODO: à fix : Lors de la suppression du lundi, suppression du reste

            for (const field of ['ouverture', 'pause', 'reprise', 'fermeture']) {
                const lundi = document.getElementById(`horaires[lundi][${field}]`);
                lundi.addEventListener('change', () => {
                    for (const jour of ['mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche']) {
                        const element = document.getElementById(`horaires[${jour}][${field}]`);
                        element.value = lundi.value;
                    }
                });
            }
        </script>

    <?php } ?>
</body>

</html>