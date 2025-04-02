<?php
session_start();

// Nécessaire pour afficher les notifications en cas d'actions spécifiques dans certains fichiers
// (le header étant inclus dans chacun d'eux...)
require_once $_SERVER['DOCUMENT_ROOT'] . '/../php_files/notifications.php';

// Si déjà connecté, rediriger sur page d'accueil
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/php_files/authentification.php';
if (isConnectedAsMember()) {
    header('location: /');
    exit();
}

// Vider les messages d'erreur si c'est la première fois qu'on vient sur la page de connexion
if (!isset($_SESSION['data_en_cours_connexion'])) {
    unset($_SESSION['data_en_cours_totp']);
    unset($_SESSION['data_en_cours_inscription']);
    unset($_SESSION['data_en_cours_reset']);
    unset($_SESSION['error']);
}

// 1ère étape : remplir les informations de connexion
if (empty($_POST)) { ?>
    <!DOCTYPE html>
    <html lang="fr">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <!-- NOS FICHIERS -->
        <link rel="icon" href="/public/images/favicon.png">
        <link rel="stylesheet" href="/styles/style.css">
        <script type="module" src="/scripts/main.js"></script>

        <title>Connexion au compte - PACT</title>
    </head>

    <body class="h-screen bg-white p-4 overflow-hidden">
        <div class="h-full flex flex-col items-center justify-center">
            <div class="relative w-full max-w-96 h-fit flex flex-col items-center justify-center sm:w-96 m-auto">
                <!-- Logo de l'application -->
                <a href="/">
                    <img src="/public/icones/logo.svg" alt="Logo de TripEnArvor : Moine macareux" width="108">
                </a>

                <h2 class="mx-auto text-center text-2xl pt-4 my-4">
                    <?php
                        $from = $_GET["from"];
                        switch ($from) {
                            case 'like':
                                echo "Se connecter pour mettre un \"j'aime\" à un commentaire";
                                break;
                            case 'dislike':
                                echo "Se connecter pour mettre un \"j'aime pas\" à un commentaire";
                                break;
                            case 'avis':
                                echo "Se connecter pour laisser un avis";
                                break;
                            default:
                                echo "Se connecter à la PACT";
                        }
                    ?>
                </h2>

                <form class="bg-white w-full p-5 border-2 border-primary" action="/connexion/<?php echo isset($_GET['id_offre']) ? '?id_offre=' . $_GET['id_offre'] : ''; ?>" method="POST">

                    <!-- Champ pour l'identifiant -->
                    <label class="text-sm" for="id">Identifiant</label>
                    <input class="p-2 bg-base100 w-full h-12 mb-1.5" type="text" id="id" name="id"
                        title="Saisir un de vos identifiants (Pseudonyme, téléphone ou mail)"
                        placeholder="Pseudonyme, téléphone ou mail"
                        value="<?php echo $_SESSION['data_en_cours_connexion']['id'] ?? '' ?>" required>

                    <!-- Champ pour le mot de passe -->
                    <div class="relative w-full">
                        <label class="text-sm" for="mdp">Mot de passe</label>
                        <div class="relative w-full">
                            <input class="p-2 pr-12 bg-base100 w-full h-12 mb-1.5" type="password" id="mdp" name="mdp"
                                pattern="^(?=(.*[A-Z].*))(?=(.*\d.*))[\w\W]{8,}$"
                                title="Saisir votre mot de passe (au moins 8 caractères dont 1 majuscule et 1 chiffre)"
                                value="<?php echo $_SESSION['data_en_cours_connexion']['mdp'] ?? '' ?>" required>
                            <!-- Icône pour afficher/masquer le mot de passe -->
                            <i
                                class="fa-regular fa-eye fa-lg absolute top-1/2 translate-y-1/2 right-4 cursor-pointer eye-toggle-password"></i>
                        </div>
                    </div>

                    <span id="error-message" class="error text-rouge-logo text-sm">
                        <?php echo $_SESSION['error'] ?? '' ?>
                    </span>

                    <!-- Bouton de connexion -->
                    <input type="submit" value="Me connecter"
                        class="cursor-pointer w-full text-sm py-2 px-4 rounded-full h-12 my-1.5 bg-primary text-white inline-flex items-center justify-center border border-transparent focus:scale-[0.97] hover:bg-orange-600 hover:border-orange-600 hover:text-white">

                    <!-- Liens pour mot de passe oublié et création de compte -->
                    <div class="flex items-center flex-nowrap h-12 space-x-1.5">
                        <a href="/connexion/reinitialisation"
                            class="text-sm text-center w-full text-wrap bg-transparent text-primary underline focus:scale-[0.97]">
                            Mot de passe oublié ?
                        </a>
                        <a href="/inscription"
                            class="text-sm py-2 px-4 rounded-full text-center w-full h-full p-1 text-wrap bg-transparent text-primary inline-flex items-center justify-center border border-primary hover:text-white hover:bg-orange-600 focus:scale-[0.97]">
                            Créer un compte
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </body>

    </html>

    <!-- 2ème étape, essayer de se connecter à la base, et inscrire une erreur sinon -->
<?php } else {
    try {
        // Connexion avec la bdd
        require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/php_files/connect_to_bdd.php';

        // Vérifie si la requête est une soumission de formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            //  Pour garder les informations dans le formulaire en cas d'erreur
            $_SESSION['data_en_cours_connexion'] = $_POST;
            $id = $_POST['id'];
            $mdp = $_POST['mdp'];

            // Formatter (avec espaces) si c'est un numéro de téléphone qui est utilisé comme id
            if (preg_match('/^0\d{9}$/', $id)) {
                $id = preg_replace('/(\d{2})(?=\d)/', '$1 ', $id);
            }

            // Prépare une requête SQL pour trouver l'utilisateur par nom, email ou numéro de téléphone
            $stmt = $dbh->prepare("SELECT * FROM sae_db._membre WHERE pseudo = :id OR email = :id OR num_tel = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Vérifie si l'utilisateur existe et si le mot de passe est correct
            if ($user) {
                if (password_verify($mdp, $user['mdp_hash'])) {
                    // Connecte le membre et retire toute éventuelle information de connexion à un compte pro
                    unset($_SESSION['id_pro']);

                    // Vérifier si le membre a besoin d'une connexion OTP
                    if ($user['totp_active'] == true) {
                        $_SESSION['tmp_id_compte_a2f'] = $user['id_compte'];
                        header('Location: /a2f');
                        exit();
                    } else {
                        $_SESSION['id_membre'] = $user['id_compte'];
                        $_SESSION['message_pour_notification'] = 'Connecté(e) en tant que Membre';
                        if ($_GET["id_offre"] != null) {
                            $_SESSION['id_offre'] = $_GET["id_offre"];
                            header('Location: /offre?id=' . $_GET["id_offre"]);
                            exit();
                        }
                        header('Location: /');
                        exit();
                    }

                } else {
                    $_SESSION['error'] = "Mot de passe ou identifiant incorrect";
                    $redirectUrl = '/connexion';
                    if (!empty($_GET)) {
                        $queryParams = http_build_query($_GET);
                        $redirectUrl .= '?' . $queryParams;
                    }
                    header('Location: ' . $redirectUrl);
                    exit();
                }
            } else {
                $_SESSION['error'] = "Mot de passe ou identifiant incorrect";
                $redirectUrl = '/connexion';
                if (!empty($_GET)) {
                    $queryParams = http_build_query($_GET);
                    $redirectUrl .= '?' . $queryParams;
                }
                header('Location: ' . $redirectUrl);
                exit();
            }
        }
    } catch (PDOException $e) {
        echo "Erreur : " . $e->getMessage();
    }
}
?>