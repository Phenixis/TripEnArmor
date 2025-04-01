<?php
print_r($_POST);
session_start();

// Se connecter à la BDD
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/php_files/connect_to_bdd.php';

// Réinistialiser les messages d'erreur quand on arrive pour la première fois sur la page
if (!isset($_SESSION['data_en_cours_inscription'])) {
    unset($_SESSION['data_en_cours_connexion']);
    unset($_SESSION['error']);
}

// FONCTION UTILES
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/php_files/fonctions.php';

// 1ère étape de la création
if (!isset($_POST['mail']) && !isset($_GET['valid_mail'])) {

    // Supprimer les messages d'erreur si on revient de l'étape 2 à l'étape 1
    if (isset($_SESSION['data_en_cours_inscription']['num_tel'])) {
        $_SESSION['error'] = '';
    }
    ?>

    <!DOCTYPE html>
    <html lang="fr">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <!-- NOS FICHIERS -->
        <link rel="icon" href="/public/images/favicon.png">
        <link rel="stylesheet" href="/styles/style.css">
        <script type="module" src="/scripts/main.js"></script>

        <title>Création de compte- PACT</title>
    </head>

    <body class="h-screen bg-white p-4 overflow-hidden">
        <div class="h-full flex flex-col items-center justify-center">
            <div class="relative w-full max-w-96 h-fit flex flex-col items-center justify-center sm:w-96 m-auto">
                <!-- Logo de l'application -->
                <a href="/">
                    <img src="/public/icones/logo.svg" alt="Logo de TripEnArvor : Moine macareux" width="108">
                </a>

                <h2 class="mx-auto text-center text-2xl pt-4 my-4">Se créer un compte PACT</h2>

                <form class="bg-white w-full p-5 border-2 border-primary" action="/inscription/" method="POST"
                    onsubmit="return validateForm()">
                    <!-- Champs pour le prénom et le nom -->
                    <div class="flex flex-nowrap space-x-3 mb-1.5">
                        <div class="w-full">
                            <label class="text-sm" for="prenom">Prénom</label>
                            <input class="p-2 bg-base100 w-full h-12" type="text" id="prenom" name="prenom"
                                title="Saisir votre prénom (max 50 caractères)"
                                value="<?php echo $_SESSION['data_en_cours_inscription']['prenom'] ?? '' ?>" required>
                        </div>
                        <div class="w-full">
                            <label class="text-sm" for="nom">Nom</label>
                            <input class="p-2 bg-base100 w-full h-12" type="text" id="nom" name="nom"
                                title="Saisir votre nom (max 50 caractères)"
                                value="<?php echo $_SESSION['data_en_cours_inscription']['nom'] ?? '' ?>" required>
                        </div>
                    </div>

                    <!-- Champ pour l'adresse mail -->
                    <label class="text-sm" for="mail">Adresse mail</label>
                    <input class="p-2 bg-base100 w-full h-12 mb-1.5" type="email" id="mail" name="mail"
                        title="L'adresse mail doit comporter un '@' et un '.'" placeholder="exemple@gmail.com"
                        value="<?php echo $_SESSION['data_en_cours_inscription']['mail'] ?? '' ?>" required>
                    <!-- Message d'erreur pour l'adresse mail -->
                    <span class="error text-rouge-logo text-sm"><?php echo $_SESSION['error'] ?? '' ?></span>

                    <!-- Champ pour le mot de passe -->
                    <div class="relative w-full">
                        <label class="text-sm" for="mdp">Mot de passe</label>
                        <div class="relative w-full">
                            <input class="p-2 pr-12 bg-base100 w-full h-12 mb-1.5" type="password" id="mdp" name="mdp"
                                pattern="^(?=(.*[A-Z].*))(?=(.*\d.*))[\w\W]{8,}$"
                                title="Saisir un mot de passe valide (au moins 8 caractères dont 1 majuscule et 1 chiffre)"
                                value="<?php echo $_SESSION['data_en_cours_inscription']['mdp'] ?? '' ?>" required>
                            <!-- Oeil pour afficher le mot de passe -->
                            <i
                                class="fa-regular fa-eye fa-lg absolute top-1/2 translate-y-1/2 right-4 cursor-pointer eye-toggle-password"></i>
                        </div>
                    </div>

                    <!-- Champ pour confirmer le mot de passe -->
                    <div class="relative w-full">
                        <label class="text-sm" for="confMdp">Confirmer le mot de passe</label>
                        <div class="relative w-full">
                            <input class="p-2 pr-12 bg-base100 w-full h-12 mb-1.5" type="password" id="confMdp" name="confMdp"
                                pattern="^(?=(.*[A-Z].*))(?=(.*\d.*))[\w\W]{8,}$"
                                title="Confirmer le mot de passe saisit ci-dessus"
                                value="<?php echo $_SESSION['data_en_cours_inscription']['confMdp'] ?? '' ?>" required>
                            <!-- Oeil pour afficher le mot de passe -->
                            <i
                                class="fa-regular fa-eye fa-lg absolute top-1/2 translate-y-1/2 right-4 cursor-pointer eye-toggle-password"></i>
                        </div>
                    </div>

                    <!-- Mots de passe ne correspondent pas -->
                    <span id="error-message" class="error text-rouge-logo text-sm"></span>

                    <!-- Bouton pour continuer -->
                    <input type="submit" value="Continuer"
                        class="text-sm py-2 px-4 rounded-full cursor-pointer w-full h-12 my-1.5 bg-primary text-white   inline-flex items-center justify-center border border-transparent focus:scale-[0.97] hover:bg-orange-600 hover:border-orange-600 hover:text-white">

                    <!-- Lien vers la page de connexion -->
                    <a href="/connexion"
                        class="text-sm py-2 px-4 rounded-full w-full h-12 p-1 bg-transparent text-primary   inline-flex items-center justify-center border border-primary hover:text-white hover:bg-orange-600 hover:border-orange-600 focus:scale-[0.97]">
                        J'ai déjà un compte
                    </a>
                </form>
            </div>
        </div>

        <script>
            // Gestion des icônes pour afficher/masquer le mot de passe
            const togglePassword1 = document.getElementById('togglePassword1');
            const togglePassword2 = document.getElementById('togglePassword2');
            const mdp = document.getElementById('mdp');
            const confMdp = document.getElementById('confMdp');

            if (togglePassword1) {
                togglePassword1.addEventListener('click', function () {
                    if (mdp.type === 'password') {
                        mdp.type = 'text';
                        this.classList.remove('fa-eye');
                        this.classList.add('fa-eye-slash');
                    } else {
                        mdp.type = 'password';
                        this.classList.remove('fa-eye-slash');
                        this.classList.add('fa-eye');
                    }
                });
            }

            if (togglePassword2) {
                togglePassword2.addEventListener('click', function () {
                    if (confMdp.type === 'password') {
                        confMdp.type = 'text';
                        this.classList.remove('fa-eye');
                        this.classList.add('fa-eye-slash');
                    } else {
                        confMdp.type = 'password';
                        this.classList.remove('fa-eye-slash');
                        this.classList.add('fa-eye');
                    }
                });
            }

            // Fonction de validation du formulaire
            function validateForm() {
                var mdp = document.getElementById("mdp").value;
                var confMdp = document.getElementById("confMdp").value;
                var errorMessage = document.getElementById("error-message");

                // Vérifie si les mots de passe correspondent
                if (mdp !== confMdp) {
                    errorMessage.textContent = "Les mots de passe ne correspondent pas."; // Affiche un message d'erreur
                    return false; // Empêche l'envoi du formulaire
                }

                errorMessage.textContent = ""; // Réinitialise le message d'erreur
                return true; // Permet l'envoi du formulaire
            }
        </script>

    </body>

    <!-- 2ème étape de l'inscription -->
<?php } elseif (!isset($_POST['num_tel'])) {
    // Garder les informations remplies par l'utilisateur
    if (!empty($_POST)) {
        $_SESSION['data_en_cours_inscription'] = $_POST;
    }

    // Est-ce que cette adresse mail est déjà utilisée ?
    $stmt = $dbh->prepare("SELECT * FROM sae_db._compte WHERE email = :mail");
    $stmt->bindParam(":mail", $_POST['mail']);
    $stmt->execute();

    // Si il y a au moins un compte déjà avec cette adresse mail
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Cette adresse mail est déjà utilisée";
        // Revenir sur sur l'inscription comme au début
        header("location: /inscription");
    } elseif (!isset($_SESSION['data_en_cours_inscription']['num_tel'])) {
        $_SESSION['error'] = '';
    }
    ?>

    <!DOCTYPE html>
    <html lang="fr">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <!-- POUR LEAFLET ET L'AUTOCOMPLETION -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet-geosearch@3.0.0/dist/geosearch.css" />

        <!-- NOS FICHIERS -->
        <link rel="icon" href="/public/images/favicon.png">
        <link rel="stylesheet" href="/styles/style.css">
        <script src="/scripts/formats.js" defer></script>

        <title>Création de compte - PACT</title>
    </head>

    <body class="h-screen bg-white pt-4 px-4 overflow-x-hidden">
        <!-- Inclusion -->
        <?php
        include_once dirname($_SERVER['DOCUMENT_ROOT']) . '/view/fiches_inscription.php';
        ?>

        <!-- Logo de l'application -->
        <a href="/" class="absolute left-0 top-0 p-4">
            <img src="/public/icones/logo.svg" alt="Logo de TripEnArvor : Moine macareux" width="81">
        </a>

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

        <div class="w-full max-w-96 h-fit flex flex-col items-end sm:w-96 m-auto">

            <h2 class="mx-auto text-center text-2xl pt-12 sm:pt-0 my-4">Dites-nous en plus !</h2>

            <form class="mb-4 bg-white w-full p-5 border-2 border-primary" action="/inscription/" method="POST">
                <!-- Champs pour le prénom et le nom (en lecture seule) -->
                <div class="flex flex-nowrap space-x-3 mb-1.5">
                    <div class="w-full">
                        <label class="text-sm" for="prenom">Prénom</label>
                        <input class="p-2 bg-base100 w-full h-12" type="text" id="prenom" name="prenom"
                            title="Saisir votre prénom (max 50 caractères)"
                            value="<?php echo $_SESSION['data_en_cours_inscription']['prenom'] ?? '' ?>" readonly>
                    </div>
                    <div class="w-full">
                        <label class="text-sm" for="nom">Nom</label>
                        <input class="p-2 bg-base100 w-full h-12" type="text" id="nom" name="nom"
                            title="Saisir votre nom (max 50 caractères)"
                            value="<?php echo $_SESSION['data_en_cours_inscription']['nom'] ?? '' ?>" readonly>
                    </div>
                </div>

                <!-- Champ pour l'adresse mail (en lecture seule) -->
                <label class="text-sm" for="mail">Adresse mail</label>
                <input class="p-2 bg-base100 w-full h-12 mb-1.5" type="email" id="mail" name="mail"
                    title="L'adresse mail doit comporter un '@' et un '.'" placeholder="exemple@gmail.com"
                    value="<?php echo $_SESSION['data_en_cours_inscription']['mail'] ?? '' ?>" readonly>
                <!-- Champ pour le pseudonyme -->
                <label class="text-sm" for="pseudo">Pseudonyme</label>
                <input class="p-2 bg-base100 w-full h-12 mb-1.5" type="text" id="pseudo" name="pseudo"
                    title="Saisir un pseudonyme"
                    value="<?php echo $_SESSION['data_en_cours_inscription']['pseudo'] ?? '' ?>" required>
                <!-- Message d'erreur pour le pseudonyme déjà utilisé -->
                <?php
                if (isset($_GET['invalid_pseudo'])) { ?>
                    <span class="error text-rouge-logo text-sm"><?php echo $_SESSION['error'] ?? '' ?></span><br>
                    <?php
                }
                ?>

                <!-- Champs pour l'adresse -->
                <p id="select-on-map"
                    class="text-sm p-2 border rounded-full text-center border-black self-start cursor-pointer hover:border-secondary hover:text-white hover:bg-secondary"
                    onclick="showMap();">Trouver mon adresse</p>

                <!-- Champs cachés pour les coordonnées -->
                <input class='hidden' id='lat' name='lat'
                    value="<?php echo $_SESSION['data_en_cours_inscription']['lat'] ?? '0' ?>">
                <input class='hidden' id='lng' name='lng'
                    value="<?php echo $_SESSION['data_en_cours_inscription']['lng'] ?? '0' ?>">

                <label class="text-sm" for="user_input_autocomplete_address">Adresse</label>
                <input class="p-2 bg-slate-200 w-full h-12 mb-1.5" type="text" id="user_input_autocomplete_address"
                    name="user_input_autocomplete_address" placeholder="Ex : 10 Rue des Fleurs" title="Saisir votre adresse"
                    value="<?php echo $_SESSION['data_en_cours_inscription']['user_input_autocomplete_address'] ?? '' ?>"
                    required>

                <div class="flex flex-nowrap space-x-3 mb-1.5">
                    <div class="w-28">
                        <label class="text-sm" for="postal_code">Code postal</label>
                        <input class="text-right p-2 bg-slate-200 w-28 h-12" id="postal_code" name="postal_code"
                            pattern="^(0[1-9]|[1-8]\d|9[0-5]|2A|2B)\d{3}$" title="Format : 12345" placeholder="12345"
                            value="<?php echo $_SESSION['data_en_cours_inscription']['postal_code'] ?? '' ?>" required>
                    </div>
                    <div class="w-full">
                        <label class="text-sm" for="locality">Ville</label>
                        <input class="p-2 bg-slate-200 w-full h-12" id="locality" name="locality"
                            pattern="^[a-zA-Zéèêëàâôûç\-'\s]+(?:\s[A-Z][a-zA-Zéèêëàâôûç\-']+)*$" title="Saisir votre ville"
                            placeholder="Rennes"
                            value="<?php echo $_SESSION['data_en_cours_inscription']['locality'] ?? '' ?>" required>
                    </div>
                </div>

                <label class="text-sm" for="complement">Complément d'adresse</label>
                <input class="p-2 bg-base100 w-full h-12 mb-1.5" type="text" id="complement" name="complement"
                    placeholder="Bâtiment A, Appartement 5" title="Saisir un complément d'adresse ?"
                    value="<?php echo $_SESSION['data_en_cours_inscription']['complement'] ?? '' ?>">

                <!-- Champ pour le numéro de téléphone -->
                <div class="w-full flex flex-col">
                    <label class="text-sm" for="num_tel">Téléphone</label>
                    <input class="text-center p-2 bg-base100 w-36 h-12 mb-3" id="num_tel" name="num_tel"
                        pattern="^0\d( \d{2}){4}" title="Le numéro doit commencer par un 0 et comporter 10 chiffres"
                        placeholder="01 23 45 67 89"
                        value="<?php echo $_SESSION['data_en_cours_inscription']['num_tel'] ?? '' ?>" required>
                </div>

                <!-- Message d'erreur pour le téléphone -->
                <?php
                if (isset($_GET['invalid_phone_number'])) { ?>
                    <span class="error text-rouge-logo text-sm"><?php echo $_SESSION['error'] ?? '' ?></span>
                    <?php
                }
                ?>

                <!-- Choix d'acceptation des termes et conditions -->
                <div class="mb-1.5 text-sm flex items-start gap-1">
                    <input class="mt-0.5 mr-1.5" type="checkbox" id="termes" name="termes" title="Accepter pour continuer"
                        required>
                    <label for="termes">J’accepte les <span onclick="toggleCGU()"
                            class="underline cursor-pointer">Conditions générales d'utilisation</span> et je confirme avoir
                        lu la <span onclick="togglePolitique()" class="underline cursor-pointer">Politique de
                            confidentialité et d'utilisation des cookies</span>.</label>
                </div>

                <!-- Bouton pour créer le compte -->
                <input type="submit" value="Créer mon compte"
                    class="text-sm py-2 px-4 rounded-full mt-1.5 cursor-pointer w-full h-12 bg-primary text-white   inline-flex items-center justify-center border border-transparent focus:scale-[0.97] hover:bg-orange-600 hover:border-orange-600 hover:text-white">

                <!-- Garder le mdp en mémoire mais le cacher -->
                <input type="hidden" name="mdp" value="<?php echo $_SESSION['data_en_cours_inscription']['mdp'] ?? '' ?>">
                <input type="hidden" name="confMdp"
                    value="<?php echo $_SESSION['data_en_cours_inscription']['mdp'] ?? '' ?>">
            </form>
        </div>

        <script>
            // Synchroniser les cases à cocher
            function syncCheckboxes() {
                // Récupère toutes les cases à cocher ayant le même name "termes"
                const checkboxes = document.querySelectorAll('input[name="termes"]');

                checkboxes.forEach((checkbox) => {
                    // Ajoute un écouteur sur le changement d'état
                    checkbox.addEventListener('change', () => {
                        // Applique le même état à toutes les cases
                        checkboxes.forEach((cb) => cb.checked = checkbox.checked);
                    });
                });
            }

            syncCheckboxes();

            function toggleCheckbox() {
                const checkbox = document.querySelector('#termes');
                checkbox.checked = (checkbox.checked) ? false : true; // Cocher la case
            }

            // Fonction pour afficher ou masquer les cgu pendant l'inscription
            function toggleCGU() {
                const cgu = document.querySelector('#cgu');

                if (!cgu.classList.contains('active')) {
                    toggleCheckbox();
                }

                // Toggle la classe 'active' pour le CGU
                cgu.classList.toggle('active');
            }

            // Fonction pour afficher ou masquer la politique de confidentialité et d'utilisation des cookies pendant l'inscription
            function togglePolitique() {
                const politique = document.querySelector('#politique');

                if (!politique.classList.contains('active')) {
                    toggleCheckbox();
                }

                // Toggle la classe 'active' pour la politique
                politique.classList.toggle('active');
            }

            // Fonction pour afficher ou masquer les mentions légales pendant l'inscription
            function toggleMentions() {
                const mentions = document.querySelector('#mentions');

                if (!mentions.classList.contains('active')) {
                    toggleCheckbox();
                }

                // Toggle la classe 'active' pour les mentions légales
                mentions.classList.toggle('active');
            }

            function versCGU() {
                document.querySelector('#cgu')?.classList.toggle('active'); // Alterne la classe 'active'
                document.querySelector('#politique')?.classList.remove('active'); // Supprime la classe 'active'
                document.querySelector('#mentions')?.classList.remove('active'); // Supprime la classe 'active'
            }

            function versPolitique() {
                document.querySelector('#politique')?.classList.toggle('active'); // Alterne la classe 'active'
                document.querySelector('#cgu')?.classList.remove('active'); // Supprime la classe 'active'
                document.querySelector('#mentions')?.classList.remove('active'); // Supprime la classe 'active'
            }

            function versMentions() {
                document.querySelector('#mentions')?.classList.toggle('active'); // Alterne la classe 'active'
                document.querySelector('#cgu')?.classList.remove('active'); // Supprime la classe 'active'
                document.querySelector('#politique')?.classList.remove('active'); // Supprime la classe 'active'
            }
        </script>

    </body>

    <!-- 3ème étape de l'inscription (écriture dans la base de données) -->
<?php } else {
    // Garder les informations remplies par l'utilisateur
    if (!empty($_POST)) {
        $_SESSION['data_en_cours_inscription'] = $_POST;
    }

    // Est-ce que ce pseudo a déjà été utilisé ?
    $stmt = $dbh->prepare("SELECT * FROM sae_db._membre WHERE pseudo = :pseudo");
    $stmt->bindParam(":pseudo", $_POST['pseudo']);
    $stmt->execute();
    // Si il y a au moins un compte déjà avec ce numéro de téléphone
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Ce pseudonyme est déjà utilisé";
        // Revenir sur sur l'inscription comme au début
        header("location: /inscription?valid_mail=true&invalid_pseudo=true");
        exit();
    }

    // Est-ce que le numéro de téléphone renseigné a déjà été utilisé ?
    $stmt = $dbh->prepare("SELECT * FROM sae_db._compte WHERE num_tel = :num_tel");
    $stmt->bindParam(":num_tel", $_POST['num_tel']);
    $stmt->execute();
    // Si il y a au moins un compte déjà avec ce numéro de téléphone
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Ce numéro de téléphone est déjà utilisé";
        // Revenir sur sur l'inscription comme au début
        header("location: /inscription?valid_mail=true&invalid_phone_number=true");
        exit();
    }

    // Partie pour traiter la soumission du second formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['num_tel'])) {

        // Transaction (sécur adresse)
        $dbh->beginTransaction();

        // Assurer que tous les champs obligatoires sont remplis
        $adresse = $_POST['user_input_autocomplete_address'];
        $infosSupAdresse = extraireInfoAdresse($adresse);
        $complement = $_POST['complement'];
        $code = $_POST['postal_code'];
        $ville = $_POST['locality'];
        $lat = $_POST['lat'];
        $lng = $_POST['lng'];

        // Exécuter la requête pour l'adresse
        require_once $_SERVER['DOCUMENT_ROOT'] . '/../controller/adresse_controller.php';
        $adresseController = new AdresseController();
        try {
            $id_adresse = $adresseController->createAdresse($code, $ville, $infosSupAdresse['numero'], $infosSupAdresse['odonyme'], $complement, $lat, $lng);
        } catch (Exception $e) {
            $dbh->rollBack();
        }

        // Infos pour insérer dans le membre
        $prenom = $_POST['prenom'];
        $nom = $_POST['nom'];
        $mail = $_POST['mail'];
        $mdp = $_POST['mdp'];
        $pseudo = $_POST['pseudo'];
        $tel = $_POST['num_tel'];
        $mdp_hash = password_hash($mdp, PASSWORD_DEFAULT);

        // Préparer l'insertion dans la table _membre
        try {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/../controller/membre_controller.php';
            $membreController = new MembreController();
            $id_membre = $membreController->createMembre($mail, $mdp_hash, $tel, $id_adresse, $pseudo, $prenom, $nom);
            $dbh->commit();
        } catch (Exception $e) {
            $dbh->rollBack();
        }
    }

    // Quand tout est bien réalisé, rediriger vers l'accueil en étant connecté
    unset($_SESSION['id_pro']);
    $_SESSION['id_membre'] = $id_membre;
    $_SESSION['message_pour_notification'] = 'Votre compte Membre a été créé';
    header("location: /");
}
?>

</html>