<!-- 
    POUR APPELER LA VUE AVIS, DÉFINIR LES VARIABLES SUIVANTES EN AMONT :
    - $id_avis
    - $mode         : soit 'avis', soit 'mon_avis' pour un affichage différent
    - $is_reference : soit true soit false pour savoir si l'on peut cliquer sur une flèche menant à l'offre correspondant à l'avis
-->
<?php
session_start();

// Connexion à la BDD
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/php_files/connect_to_bdd.php';

if ($mode == 'avis') {
    $is_mon_avis = false;
} else if ($mode == 'mon_avis') {
    $is_mon_avis = true;
}

// Import des controllers
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/membre_controller.php';
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/pro_prive_controller.php';
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/pro_public_controller.php';
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/avis_controller.php';
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/restauration_controller.php';
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/controller/image_controller.php';

$membreController = new MembreController();
$proPublicController = new ProPublicController();
$proPriveController = new ProPriveController();
$avisController = new avisController();
$restaurationController = new RestaurationController();
$controllerImage = new ImageController();

// Obtenir la variables regroupant les infos majeures
$avis = $avisController->getAvisById($id_avis);
$membre = $membreController->getInfosMembre($avis['id_membre']);
$restauration = $restaurationController->getInfosRestauration($avis['id_offre']);
$images = $controllerImage->getImagesAvis($id_avis);

// Vérifier si on est connecté avec le compte du pro qui peut répondre
$stmt = $dbh->prepare("SELECT id_pro FROM sae_db._offre WHERE id_offre = :id_offre");
$stmt->bindParam(':id_offre', $avis['id_offre']);
$stmt->execute();
$id_pro_must_have = $stmt->fetch(PDO::FETCH_ASSOC)['id_pro'];
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/php_files/authentification.php';
$pro_can_answer = (isConnectedAsPro() && $id_pro_must_have == $_SESSION['id_pro']) ? true : false;

// Savoir si l'avis actuel est blacklisté
$is_blacklisted = (isset($avis['fin_blacklistage']) && $avis['fin_blacklistage'] != null) ? true : false;

// FONCTION UTILES
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/php_files/fonctions.php';
?>
<!-- CARTE DE L'AVIS COMPORTANT TOUTES LES INFORMATIONS NÉCESSAIRES (MEMBRE) -->
<div
    class="avis w-full <?php echo $is_mon_avis ? 'border-primary border-2' : '' ?> p-2 gap-1 flex flex-col text-sm <?php echo $is_blacklisted ? 'bg-slate-100' : '' ?> <?php echo ($pro_can_answer === true && !$avis['est_lu']) ? 'border-y border-secondary' : '' ?>">
    <?php
    // Vérifier si celui qui consulte l'avis est le pro lié à l'offre correspondant à l'avis -> mettre l'attribut est_lu à true
    if ($pro_can_answer) {
        $stmt = $dbh->prepare("UPDATE sae_db._avis SET est_lu = TRUE WHERE id_avis = ?");
        $stmt->bindParam(1, $id_avis);
        $stmt->execute();
    }

    // Possiblité de blacklister : type_offre = premium, tickets blacklistage restants et pro_can_answer
    $pro_can_blacklist = false;
    $stmt = $dbh->prepare(
        "
        SELECT * FROM sae_db._avis
        JOIN sae_db._offre ON sae_db._offre.id_offre = sae_db._avis.id_offre
        WHERE sae_db._offre.id_offre = :id_offre;
    "
    );
    $stmt->bindParam(':id_offre', $avis['id_offre']);
    $stmt->execute();
    $id_type_offre = $stmt->fetch(PDO::FETCH_ASSOC)['id_type_offre'];

    $stmt = $dbh->prepare("SELECT * FROM sae_db.vue_offre_blacklistes_en_cours WHERE id_offre = :id_offre");
    $stmt->bindParam(':id_offre', $avis['id_offre']);
    $stmt->execute();
    $nb_blacklistes_en_cours = $stmt->rowCount();
    if ($stmt->rowCount() < 3 && $pro_can_answer && $id_type_offre == '2') {
        $pro_can_blacklist = true;
    }
    ?>

    <!-- Première ligne du haut -->
    <div class="flex gap-3 items-center">
        <div class="flex">
            <!-- Prénom, nom -->
            <?php
            if ($avis['titre']) { ?>
                <p class="font-medium"><?php echo $avis['titre'] ?>&nbsp;</p>
                <?php
            }
            ?>
            <p class="text-gray-500">de</p>
            <!-- // Titre de l'avis s'il y en a un -->
            <p class="text-gray-500">&nbsp;<?php echo $membre['pseudo'] ?></p>
            <!-- Date de publication (2ème ligne) -->
            <?php
            if ($avis['date_publication']) { ?>
                <?php
                $date_publication = new DateTime($avis['date_publication']);
                $now = new DateTime();
                $interval = $date_publication->diff($now);

                if ($interval->y > 0) {
                    $time_ago = $interval->y . ' an' . ($interval->y > 1 ? 's' : '');
                } elseif ($interval->m > 0) {
                    $time_ago = $interval->m . ' mois';
                } elseif ($interval->d > 7) {
                    $weeks = floor($interval->d / 7);
                    $time_ago = $weeks . ' semaine' . ($weeks > 1 ? 's' : '');
                } elseif ($interval->d > 0) {
                    $time_ago = $interval->d . ' jour' . ($interval->d > 1 ? 's' : '');
                } else {
                    $time_ago = 'aujourd\'hui';
                }
                ?>
                <p class="grow text-gray-500">
                    &nbsp;<?php echo ($time_ago == 'aujourd\'hui') ? $time_ago : 'il y a ' . $time_ago ?></p>
                <?php
            }
            ?>
        </div>
        <!-- TAB PC Note sur 5 -->
        <div class="flex gap-1 grow shrink-0 hidden md:flex">
            <?php
            // Note s'il y en a une
            $note = floatval($avis['note']);
            for ($i = 0; $i < 5; $i++) {
                if ($note >= 1) {
                    ?>
                    <img class="w-3" src="/public/icones/egg-full.svg" alt="1 point de note">
                    <?php
                } else if ($note > 0) {
                    ?>
                        <img class="w-3" src="/public/icones/egg-half.svg" alt="0.5 point de note">
                    <?php
                } else {
                    ?>
                        <img class="w-3" src="/public/icones/egg-empty.svg" alt="0 point de note">
                    <?php
                }
                $note--;
            }
            ?>
        </div>
        <div class="flex items-center self-end ml-auto gap-5">
            <?php
            // Le pro peut-il blacklister ?
            $duree_blacklistage = parse_config_file('DUREE_BLACKLISTAGE');
            // Décomposer la durée de blacklistage en jours, heures... etc
            list($jours, $heures, $minutes, $secondes) = explode(' ', $duree_blacklistage);
            $jours = isset($jours) ? (int) $jours : 0;
            $heures = isset($heures) ? (int) $heures : 0;
            $minutes = isset($minutes) ? (int) $minutes : 0;
            $secondes = isset($secondes) ? (int) $secondes : 0;

            // Nécessaire de faire un form pour envoyer en POST car il ne faut pas pouvoir modifier les durées à la main
            if (!$is_blacklisted && $pro_can_blacklist) { ?>
                <form id="blacklistForm" action="/scripts/blacklister_avis.php" method="POST"
                    onsubmit="return confirmBlacklist()">
                    <input type="hidden" name="id_avis" value="<?php echo $id_avis; ?>" />
                    <input type="hidden" name="jours" value="<?php echo $jours; ?>" />
                    <input type="hidden" name="heures" value="<?php echo $heures; ?>" />
                    <input type="hidden" name="minutes" value="<?php echo $minutes; ?>" />
                    <input type="hidden" name="secondes" value="<?php echo $secondes; ?>" />
                    <button type="submit" class="no-style-btn">
                        <i title="blacklister l'avis" class="text-xl fa-solid fa-ban hover:text-rouge-logo"></i>
                    </button>
                </form>
            <?php } ?>

            <script>
                function confirmBlacklist() {
                    return confirm('Voulez-vous vraiment blacklister cet avis définitivement ? Cela coute un ticket (il vous en reste <?php echo 3 - $nb_blacklistes_en_cours ?>) qui vous sera restitué dans <?php echo $jours ?> jour(s), <?php echo $heures ?> heure(s), <?php echo $minutes ?> minute(s) et <?php echo $secondes ?> seconde(s)');
                }
            </script>

            <?php
            // Flèche de référence vers l'offre correspondante
            if (isset($is_reference) && $is_reference) { ?>
                <a title="voir l'offre correspondante" class="hover:text-primary"
                    href="/offre?id_offre=<?php echo $avis['id_offre'] ?>">
                    <i class="text-xl fa-solid fa-arrow-up-right-from-square"></i>
                </a>
            <?php }

            if (!$is_mon_avis && !$is_blacklisted) {
                ?>
                <!-- Drapeau de signalement -->
                <i class="fa-regular fa-flag text-xl hover:text-primary hover:cursor-pointer"
                    onclick="document.getElementById('pop-up-signalement-<?php echo $id_avis ?>').classList.remove('hidden')"></i>
                <div id="pop-up-signalement-<?php echo $id_avis ?>"
                    class="z-30 fixed top-0 left-0 h-full w-full flex hidden items-center justify-center">
                    <!-- Background blur -->
                    <div class="fixed top-0 left-0 w-full h-full bg-blur/25 backdrop-blur"
                        onclick="document.getElementById('pop-up-signalement-<?php echo $id_avis ?>').classList.add('hidden');">
                    </div>
                    <!-- La pop-up (vue)-->
                    <?php
                    require dirname($_SERVER['DOCUMENT_ROOT']) . '/view/pop_up_signalement_view.php';
                    ?>
                </div>
                <?php
            } else if ($is_mon_avis) {
                ?>
                    <!-- Poubelle de suppression d'avis -->
                    <a href="/scripts/delete_avis.php?id_avis=<?php echo $id_avis ?>&id_offre=<?php echo $avis['id_offre'] ?>"
                        onclick="return confirm('Supprimer votre avis ?')">
                        <i class="fa-solid fa-trash text-xl"></i>
                    </a>
                <?php
            }
            ?>
        </div>
    </div>

    <!-- TEL Note sur 5 -->
    <div class="flex gap-1 grow shrink-0 md:hidden">
        <?php
        // Note s'il y en a une
        $note = floatval($avis['note']);
        for ($i = 0; $i < 5; $i++) {
            if ($note >= 1) {
                ?>
                <img class="w-3" src="/public/icones/egg-full.svg" alt="1 point de note">
                <?php
            } else if ($note > 0) {
                ?>
                    <img class="w-3" src="/public/icones/egg-half.svg" alt="0.5 point de note">
                <?php
            } else {
                ?>
                    <img class="w-3" src="/public/icones/egg-empty.svg" alt="0 point de note">
                <?php
            }
            $note--;
        }
        ?>
    </div>

    <?php
    // Notes pour les restaurants
    if ($restauration) { ?>
        <div class='flex md:flex-row flex-col justify-between flex-wrap'>
            <?php
            $stmt = $dbh->prepare("SELECT * FROM sae_db._avis_restauration_note WHERE id_avis = :id_avis AND id_restauration = :id_restauration");
            $stmt->bindParam(":id_avis", $id_avis);
            $stmt->bindParam(":id_restauration", $restauration['id_offre']);
            $stmt->execute();
            $notes_restauration = $stmt->fetch();

            foreach (['note_ambiance', 'note_service', 'note_cuisine', 'rapport_qualite_prix'] as $nom_note) {
                ?>
                <div class='flex'>
                    <p class="text-gray-500"><?php echo ucfirst(to_nom_note(nom_attribut_note: $nom_note)) ?> :&nbsp;</p>
                    <p><?php echo $notes_restauration[$nom_note] ?>/5</p>
                </div>
                <?php
            } ?>
        </div>
        <?php
    }
    ?>

    <!-- Date d'expérience + contexte de passage -->
    <?php
    if ($avis['date_experience']) { ?>
        <div class="flex justify-start gap-3">
            <p class="text-gray-500">Vécu le
                <?php
                $date_experience = date('d/m/Y', strtotime($avis['date_experience']));
                echo $date_experience;
                ?>,
                <?php echo (isset($avis['contexte_passage'])) ? $avis['contexte_passage'] : '' ?>
                <?php
                setlocale(LC_TIME, 'fr_FR.UTF-8');
                ?>
            </p>
        </div>
    <?php } ?>

    <!-- Commentaire de l'avis s'il y en a un -->
    <div class="flex flex-col gap-2">
        <p class="text-justify"><?php echo $avis['commentaire'] ?></p>
    </div>

    <!-- Images de l'avis -->
    <?php
    if ($images) { ?>
        <!-- Variables d'image de de curseur pour chaque slider de chaque avis -->
        <script>
            var imagesAvis<?php echo $id_avis ?> = <?php echo json_encode($images); ?>;
            var etatImageModal = {
                currentIndex: 0
            }
        </script>

        <div class="flex space-x-1 py-2">
            <?php foreach ($images as $index => $image): ?>
                <?php if (count($images) === 1): ?>
                    <img src="/public/images/avis/<?php echo $image; ?>" class="object-cover w-12 h-16 rounded-lg"
                        onclick="openImageModal(<?php echo $id_avis ?>, etatImageModal, <?php echo $index; ?>, imagesAvis<?php echo $id_avis ?>)">
                <?php elseif ($index < 4): ?>
                    <img src="/public/images/avis/<?php echo $image; ?>"
                        class="object-cover w-12 h-16 <?php echo $index === 0 ? 'rounded-tl-lg rounded-bl-lg' : ''; ?> <?php echo $index === count($images) - 1 ? 'rounded-tr-lg rounded-br-lg' : ''; ?>"
                        onclick="openImageModal(<?php echo $id_avis ?>, etatImageModal, <?php echo $index; ?>, imagesAvis<?php echo $id_avis ?>)">
                <?php elseif ($index === 4): ?>
                    <div class="bg-base100 w-12 h-16 rounded-tr-lg rounded-br-lg">
                        <a href="javascript:void(0);" class="w-full h-full flex justify-center items-center text-small text-gris"
                            onclick="openImageModal(<?php echo $id_avis ?>, etatImageModal, 4, imagesAvis<?php echo $id_avis ?>)">+<?php echo count($images) - 4 ?></a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php } ?>

    <!-- Modal pour le slider d'images de l'avis -->
    <?php if ($images) { ?>
        <div id="imageModal<?php echo $id_avis ?>"
            class="fixed inset-0 hidden flex bg-black bg-opacity-75 items-center justify-center z-50">
            <div class="relative" onclick="event.stopPropagation();">
                <button class="absolute top-0 right-0 m-3 text-white bg-primary py-2 px-2"
                    onclick="closeImageModal(<?php echo $id_avis ?>)">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <img id="modalImage<?php echo $id_avis ?>" class="max-w-screen max-h-screen">
                <button class="absolute left-0 top-1/2 transform -translate-y-1/2 text-white bg-primary px-2 py-2 ml-2"
                    onclick="prevImage(<?php echo $id_avis ?>, etatImageModal, imagesAvis<?php echo $id_avis ?>)">
                    <i class="fa-solid fa-less-than"></i>
                </button>
                <button class="absolute right-0 top-1/2 transform -translate-y-1/2 text-white bg-primary px-2 py-2 mr-2"
                    onclick="nextImage(<?php echo $id_avis ?>, etatImageModal, imagesAvis<?php echo $id_avis ?>)">
                    <i class="fa-solid fa-greater-than"></i>
                </button>
            </div>
        </div>
    <?php } ?>

    <!-- Réponse du pro s'il y en a une -->
    <?php if (!is_null($avis['reponse'])) { ?>
        <div class="flex gap-8 items-center text-gris">
            <div class="p-4">
                <div class="flex gap-8 items-center text-gris">

                    <!-- Bouton pour afficher la réponse -->
                    <div class="flex gap-2 hover:cursor-pointer"
                        onclick="this.querySelector('i').classList.toggle('rotate-90'); document.getElementById('reponse-avis-<?php echo $id_avis ?>').classList.toggle('hidden');">
                        <i class="fa-solid fa-angle-right"></i>
                        <p><?php echo $pro_can_answer ? 'Votre réponse' : 'Réponse du pro' ?></p>
                    </div>
                    <?php
                    if ($pro_can_answer) { ?>
                        <a
                            onclick=" if (confirm('Voulez-vous vraiment supprimer votre réponse ?')) {
                                    window.location.href='/scripts/delete_reponse.php?id_avis=<?php echo $id_avis ?>' }">
                            <svg width="15" height="18" viewBox="0 0 10 12" fill="none"
                                class="stroke-black hover:!stroke-primary hover:cursor-pointer">
                                <path
                                    d="M3.46444 0.619944L3.46445 0.619949L3.46589 0.61705C3.50119 0.545792 3.57425 0.5 3.65625 0.5H6.34375C6.42575 0.5 6.49881 0.545792 6.53411 0.61705L6.5341 0.617055L6.53556 0.619945L6.69627 0.939141L6.83481 1.21429H7.14286H9.28571C9.40466 1.21429 9.5 1.30962 9.5 1.42857C9.5 1.54752 9.40466 1.64286 9.28571 1.64286H0.714286C0.595339 1.64286 0.5 1.54752 0.5 1.42857C0.5 1.30962 0.595339 1.21429 0.714286 1.21429H2.85714H3.1652L3.30373 0.939141L3.46444 0.619944ZM1.6865 10.3925L1.24653 3.35714H8.75347L8.3135 10.3925C8.3135 10.3926 8.31349 10.3926 8.31349 10.3926C8.29439 10.6941 8.04399 10.9286 7.7433 10.9286H2.2567C1.95606 10.9286 1.70568 10.6942 1.68652 10.3927C1.68651 10.3927 1.68651 10.3926 1.6865 10.3925Z" />
                            </svg>
                        </a>
                        <?php
                    }
                    ?>
                </div>
            </div>

            <!-- Texte de la réponse -->
            <p id="reponse-avis-<?php echo $id_avis ?>" class="hidden"> <?php echo $avis['reponse'] ?></p>
        </div>

        <!-- Sinon formulaire de reponse pour le pro s'il est bien connecté -->
    <?php } else if ($pro_can_answer) { ?>
            <div class="p-4 flex flex-col gap-2 justify-start">
                <!-- Bouton de rédaction de réponse -->
                <div class="flex gap-4 items-center">
                    <a class="px-2 py-1 rounded-full hover:cursor-pointer self-start border border-secondary hover:bg-secondary hover:text-white"
                        onclick="document.getElementById('formulaire-reponse-avis-<?php echo $id_avis ?>').classList.toggle('hidden')">Répondre</a>
                    <a id="send-reponse-avis-<?php echo $id_avis ?>" class="hidden">
                        <i class="fa-regular fa-paper-plane hover:cursor-pointer" title="Envoyer" onclick="let content = document.getElementById('formulaire-reponse-avis-<?php echo $id_avis ?>').value; let encodedContent = encodeURIComponent(content); if (encodedContent.length > 0) {
                                    window.location.href = '/scripts/send_reponse.php?id_avis=<?php echo $id_avis ?>&reponse=' + encodedContent;
                                }">
                        </i>
                    </a>
                </div>

                <!-- Champ de rédaction -->
                <textarea id="formulaire-reponse-avis-<?php echo $id_avis ?>" class="hidden border border-gris p-1"></textarea>
                <!-- Proposer d'envoyer la réponse que quand il y a du texte rentré -->
                <script>
                    $("#formulaire-reponse-avis-<?php echo $id_avis ?>").on('input', function () {
                        let send_button = document.getElementById('send-reponse-avis-<?php echo $id_avis ?>');
                        let longeur_message = document.getElementById('formulaire-reponse-avis-<?php echo $id_avis ?>').value.length;
                        if (longeur_message > 0) {
                            send_button.classList.remove('hidden');
                        } else {
                            send_button.classList.add('hidden');
                        }
                    });
                </script>
            </div>
    <?php } ?>

    <!-- POUCES -->
    <?php
    $statement = $dbh->prepare("SELECT * FROM sae_db.vue_avis_reaction_counter WHERE id_avis = ?");
    $statement->bindParam(1, $id_avis);
    $statement->execute();
    $nb_reactions = $statement->fetch(PDO::FETCH_ASSOC); ?>
    <div>
        <div class="flex flex-row-reverse gap-3 items-center">
            <?php
            ?>

            <!-- AFFICHER LES POUCES VITRINES POUR LE PRO -->
            <?php if (isset($_SESSION['id_pro'])) { ?>

                <!-- Nombre de pouces rouges -->
                <p class="font-bold w-2 text-center">
                    <?php echo (!empty($nb_reactions)) ? $nb_reactions['nb_dislikes'] : 0; ?>
                </p>
                <i class="fa-regular fa-thumbs-down text-2xl mt-1 text-rouge-logo"
                    onclick="sendReaction(<?php echo $id_avis; ?>, 'upTOdown')"></i>

                <!-- Nombre de pouces bleus -->
                <p class="font-bold w-2 text-center">
                    <?php echo (!empty($nb_reactions)) ? $nb_reactions['nb_likes'] : 0; ?>
                </p>
                <i class="fa-regular fa-thumbs-up text-2xl mb-1 text-secondary"
                    onclick="sendReaction(<?php echo $id_avis; ?>, 'upTOnull')"></i>

                <!-- AFFICHER LES POUCES INTERACTIFS POUR LE MEMBRE -->
            <?php } else if (isset($_SESSION['id_membre'])) {

                $query = "SELECT type_de_reaction FROM sae_db._avis_reactions WHERE id_avis = ? AND id_membre = ?";
                $statement = $dbh->prepare($query);
                $statement->bindParam(1, $id_avis);
                $statement->bindParam(2, $_SESSION['id_membre']);

                if ($statement->execute()) {
                    $reaction = $statement->fetch(PDO::FETCH_ASSOC);
                } else {
                    echo "ERREUR : Impossible d'obtenir cette réaction";
                    return -1;
                }
                if ($statement->execute()) {
                    $reaction = $statement->fetch(PDO::FETCH_ASSOC);
                } else {
                    echo "ERREUR : Impossible d'obtenir cette réaction";
                    return -1;
                }

                if ($reaction) { ?>
                        <!-- Pouce bleu pour le membre -->
                    <?php if ($reaction['type_de_reaction'] == true) { ?>
                            <p class="font-bold w-2 text-center" id="dislike-count-<?php echo $id_avis; ?>">
                            <?php echo (!empty($nb_reactions)) ? $nb_reactions['nb_dislikes'] : 0; ?>
                            </p>
                            <i class="cursor-pointer fa-regular fa-thumbs-down text-2xl mt-1" id="thumb-down-<?php echo $id_avis; ?>"
                                onclick="sendReaction(<?php echo $id_avis; ?>, 'upTOdown')"></i>
                            <p class="font-bold w-2 text-center" id="like-count-<?php echo $id_avis; ?>">
                            <?php echo (!empty($nb_reactions)) ? $nb_reactions['nb_likes'] : 0; ?>
                            </p>
                            <i class="cursor-pointer fa-solid fa-thumbs-up text-2xl mb-1 text-secondary"
                                id="thumb-up-<?php echo $id_avis; ?>" onclick="sendReaction(<?php echo $id_avis; ?>, 'upTOnull')"></i>
                            <!-- Pouce rouge pour le membre -->
                    <?php } else { ?>
                            <p class="font-bold w-2 text-center" id="dislike-count-<?php echo $id_avis; ?>">
                            <?php echo (!empty($nb_reactions)) ? $nb_reactions['nb_dislikes'] : 0; ?>
                            </p>
                            <i class="cursor-pointer fa-solid fa-thumbs-down text-2xl mt-1 text-rouge-logo"
                                id="thumb-down-<?php echo $id_avis; ?>"
                                onclick="sendReaction(<?php echo $id_avis; ?>, 'downTOnull')"></i>
                            <p class="font-bold w-2 text-center" id="like-count-<?php echo $id_avis; ?>">
                            <?php echo (!empty($nb_reactions)) ? $nb_reactions['nb_likes'] : 0; ?>
                            </p>
                            <i class="cursor-pointer fa-regular fa-thumbs-up text-2xl mb-1" id="thumb-up-<?php echo $id_avis; ?>"
                                onclick="sendReaction(<?php echo $id_avis; ?>, 'downTOup')"></i>
                    <?php } ?>
                        <!-- Aucun pouce pour le membre -->
                <?php } else { ?>
                        <p class="font-bold w-2 text-center" id="dislike-count-<?php echo $id_avis; ?>">
                        <?php echo (!empty($nb_reactions)) ? $nb_reactions['nb_dislikes'] : 0; ?>
                        </p>
                        <i class="cursor-pointer fa-regular fa-thumbs-down text-2xl mt-1" id="thumb-down-<?php echo $id_avis; ?>"
                            onclick="sendReaction(<?php echo $id_avis; ?>, 'down')"></i>
                        <p class="font-bold w-2 text-center" id="like-count-<?php echo $id_avis; ?>">
                        <?php echo (!empty($nb_reactions)) ? $nb_reactions['nb_likes'] : 0; ?>
                        </p>
                        <i class="cursor-pointer fa-regular fa-thumbs-up text-2xl mb-1" id="thumb-up-<?php echo $id_avis; ?>"
                            onclick="sendReaction(<?php echo $id_avis; ?>, 'up')"></i>
                <?php }
            } else {
                ?>
                    <!-- POUCES POUR LES VISITEURS -->
                    <p class="font-bold w-2 text-center" id="dislike-count-<?php echo $id_avis; ?>">
                    <?php echo (!empty($nb_reactions)) ? $nb_reactions['nb_dislikes'] : 0; ?>
                    </p>
                    <a href="/connexion/?from=dislike&id_offre=<?php echo $avis['id_offre'] ?>">
                        <i class="cursor-pointer fa-regular fa-thumbs-down text-2xl mt-1"></i>
                    </a>
                    <p class="font-bold w-2 text-center" id="like-count-<?php echo $id_avis; ?>">
                    <?php echo (!empty($nb_reactions)) ? $nb_reactions['nb_likes'] : 0; ?>
                    </p>
                    <a href="/connexion/?from=like&id_offre=<?php echo $avis['id_offre'] ?>">
                        <i class="cursor-pointer fa-regular fa-thumbs-up text-2xl mb-1"></i>
                    </a>
            <?php } ?>
        </div>
    </div>
</div>