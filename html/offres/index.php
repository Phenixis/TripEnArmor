<?php
session_start();
// Connexion avec la bdd
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/php_files/connect_to_bdd.php';
// Authentification
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/php_files/authentification.php';
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Toutes les offres - PACT</title>
    
    <!-- NOS FICHIERS -->
    <link rel="icon" href="/public/images/favicon.png">
    <link rel="stylesheet" href="/styles/style.css">
    <script type="module" src="/scripts/main.js"></script>
    
    <!-- LEAFLET -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster/dist/leaflet.markercluster.js"></script>
</head>

<body class="min-h-screen flex flex-col justify-between">

    <!-- Inclusion du header -->
    <?php
    include_once dirname($_SERVER['DOCUMENT_ROOT']) . '/view/header.php';
    ?>

    <?php
    // Obtenez l'ensemble des offres avec le tri approprié
    $stmt = $dbh->prepare("SELECT * FROM sae_db._offre WHERE est_en_ligne = true");
    $stmt->execute();
    $toutesLesOffres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (isset($_GET['sort'])) {
        // Récupérer toutes les moyennes en une seule requête
        $stmt = $dbh->query("SELECT id_offre, avg FROM sae_db.vue_moyenne");
        $notesMoyennes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Associer les moyennes aux offres
        $notesAssociees = [];
        foreach ($notesMoyennes as $note) {
            $notesAssociees[$note['id_offre']] = floatval($note['avg']);
        }

        // Créer un tableau temporaire enrichi
        $offresAvecNotes = array_map(function ($offre) use ($notesAssociees) {
            $offre['note_moyenne'] = $notesAssociees[$offre['id_offre']] ?? null; // Note null si non trouvée
            return $offre;
        }, $toutesLesOffres);

        // Effectuer le tri
        if ($_GET['sort'] === 'note-ascending') {
            usort($offresAvecNotes, function ($a, $b) {
                if (is_null($a['note_moyenne']) && is_null($b['note_moyenne'])) {
                    return 0;
                }
                if (is_null($a['note_moyenne'])) {
                    return 1;
                }
                if (is_null($b['note_moyenne'])) {
                    return -1;
                }

                return $a['note_moyenne'] <=> $b['note_moyenne'];
            });

            // Réassigner les offres triées
            $toutesLesOffres = $offresAvecNotes;
        } else if ($_GET['sort'] === 'note-descending') {
            usort($offresAvecNotes, function ($a, $b) {
                if (is_null($a['note_moyenne']) && is_null($b['note_moyenne'])) {
                    return 0;
                }
                if (is_null($a['note_moyenne'])) {
                    return 1;
                }
                if (is_null($b['note_moyenne'])) {
                    return -1;
                }

                return $b['note_moyenne'] <=> $a['note_moyenne'];
            });

            // Réassigner les offres triées
            $toutesLesOffres = $offresAvecNotes;
        } else if ($_GET['sort'] == 'price-ascending') {
            usort($toutesLesOffres, function ($a, $b) {
                if (is_null($a['prix_mini']) && is_null($b['prix_mini'])) {
                    return 0;
                }
                if (is_null($a['prix_mini'])) {
                    return -1;
                }
                if (is_null($b['prix_mini'])) {
                    return 1;
                }
                return $a['prix_mini'] <=> $b['prix_mini'];
            });
        } else if ($_GET['sort'] == 'price-descending') {
            usort($toutesLesOffres, function ($a, $b) {
                if (is_null($a['prix_mini']) && is_null($b['prix_mini'])) {
                    return 0;
                }
                if (is_null($a['prix_mini'])) {
                    return -1;
                }
                if (is_null($b['prix_mini'])) {
                    return 1;
                }
                return $b['prix_mini'] <=> $a['prix_mini'];
            });
        }
    }

    $prix_mini_max = 0;

    foreach ($toutesLesOffres as $offre) {
        $prix_mini = $offre['prix_mini'];
        if ($prix_mini !== null && $prix_mini !== '') {
            if ($prix_mini_max === 0) {
                $prix_mini_max = $prix_mini;
            } else {
                $prix_mini_max = max($prix_mini_max, $prix_mini);
            }
        }
    }
    ?>

    <!-- MAIN (TABLETTE et TÉLÉPHONE) -->
    <div class="w-full grow flex items-start justify-center mx-auto p-2 md:max-w-[1280px]">

        <!-- Inclusion du menu et de l'interface de filtres (tablette et +) -->
        <div id="menu">
            <?php
            $pagination = 5;
            $menu_avec_filtres = true;
            require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/view/menu.php';
            ?>
        </div>

        <main class="grow m-2 md:p-2 flex flex-col md:mx-10">

            <!-- Conteneur des tags (!!! RECHERCHE) -->
            <div class="flex flex-wrap gap-4" id="tags-container"></div>

            <!-- BOUTONS DE FILTRES ET DE TRIS TABLETTE -->
            <div class="flex justify-between items-end mb-2 mt-6">
                <h1 class="text-3xl ">Toutes les offres</h1>

                <div class="hidden md:flex gap-4">
                    <a class="self-end flex items-center gap-2 hover:text-primary duration-100" id="sort-button-tab"
                        tabindex="0">
                        <i class="text xl fa-solid fa-sort"></i>
                        <p>Trier par</p>
                    </a>
                </div>
            </div>

            <!-- Inclusion des interfaces de tris (tablette et +) -->
            <?php
            include_once dirname($_SERVER['DOCUMENT_ROOT']) . '/view/tris_tab.php';
            ?>

            <div id="map" class="w-full h-[400px] border border-gray-300 mb-4"></div>

            <script>
                window.mapConfig = {
                    center: [48.1, -2.5],
                    zoom: 8
                };
            </script>
            <script src="/scripts/map.js"></script>

            <?php
            // Obtenir les informations de toutes les offres et les ajouter dans les mains du tel ou de la tablette
            if (!$toutesLesOffres) { ?>
                <div class="md:min-w-full flex flex-col gap-4">
                    <?php echo "<p class='mt-4  text-2xl'>Il n'existe aucune offre...</p>"; ?>
                </div>
            <?php } else { ?>
                <div class="md:min-w-full flex flex-col gap-4" id="no-matches">
                    <?php $i = 0;
                    foreach ($toutesLesOffres as $offre) {
                        if ($i > -1) {
                            // Afficher la carte (!!! défnir la variable $mode_carte !!!)
                            $mode_carte = 'membre';
                            require dirname($_SERVER['DOCUMENT_ROOT']) . '/view/carte_offre.php';
                            $i++;
                        }
                    } ?>
                </div>
            <?php } ?>
        </main>
    </div>

    <!-- Inclusion des interfaces de filtres/tris (téléphone) -->
    <?php
    include_once dirname($_SERVER['DOCUMENT_ROOT']) . '/view/filtres_tris_tel.php';
    ?>

    <!-- FOOTER -->
    <?php
    include_once dirname($_SERVER['DOCUMENT_ROOT']) . '/view/footer.php';
    ?>
</body>

</html>