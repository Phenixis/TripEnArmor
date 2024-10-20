<?php if (!isset($_POST['mail'])) { ?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Lien vers le favicon de l'application -->
    <link rel="icon" type="image" href="../public/images/favicon.png">
    <!-- Lien vers le fichier CSS pour le style de la page -->
    <link rel="stylesheet" href="../styles/output.css">
    <title>Création de compte 1/2</title>
    <!-- Inclusion de Font Awesome pour les icônes -->
    <script src="https://kit.fontawesome.com/d815dd872f.js" crossorigin="anonymous"></script>

</head>
<body class="h-screen bg-base100 p-4 overflow-hidden">
    <!-- Icône pour revenir à la page précédente -->
    <i onclick="history.back()" class="fa-solid fa-arrow-left fa-2xl cursor-pointer"></i>
    <div class="h-full flex flex-col items-center justify-center">
        <div class="relative w-full max-w-96 h-fit flex flex-col items-center justify-center sm:w-96 m-auto">
            <!-- Logo de l'application -->
            <img class="absolute -top-24" src="../public/images/logo.svg" alt="moine" width="108">
            <form class="bg-base200 w-full p-5 rounded-lg border-2 border-secondary" action="create-pro.php" method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
                <p class="pb-3">Je créé un compte Professionnel</p>

                <!-- Champ pour la dénomination sociale -->
                <label class="text-small" for="deno">Dénomination sociale*</label>
                <input class="p-2 bg-base100 w-full h-12 mb-1.5 rounded-lg" type="text" id="deno" name="deno" 
                       pattern="^?:(\w+|\w+[\.\-_]?\w+)+$" 
                       title="Saisir la dénomination sociale de l'entreprise" maxlength="100" required>
                
                <!-- Champ pour l'adresse mail -->
                <label class="text-small" for="mail">Adresse mail*</label>
                <input class="p-2 bg-base100 w-full h-12 mb-1.5 rounded-lg" type="email" id="mail" name="mail" 
                       pattern="^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$" 
                       title="Saisir une adresse mail" maxlength="255" required>
                
                <!-- Champ pour le mot de passe -->
                <label class="text-small" for="mdp">Mot de passe</label>
                <div class="relative w-full">
                    <input class="p-2 pr-12 bg-base100 w-full h-12 mb-1.5 rounded-lg" type="password" id="mdp" name="mdp" 
                           pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?&quot;:{}|&lt;&gt;])[A-Za-z\d!@#$%^&*(),.?&quot;:{}|&gt;&lt;]{8,}" 
                           title="Saisir un mot de passe" minlength="8" autocomplete="new-password" required>
                    <!-- Icône pour afficher/masquer le mot de passe -->
                    <i class="fa-regular fa-eye fa-lg absolute top-6 right-4 cursor-pointer" id="togglePassword1"></i>
                </div>

                <!-- Champ pour confirmer le mot de passe -->
                <label class="text-small" for="confMdp">Confirmer le mot de passe</label>
                <div class="relative w-full">
                    <input class="p-2 pr-12 bg-base100 w-full h-12 mb-1.5 rounded-lg" type="password" id="confMdp" name="confMdp" 
                           pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?&quot;:{}|&lt;&gt;])[A-Za-z\d!@#$%^&*(),.?&quot;:{}|&gt;&lt;]{8,}" 
                           title="Saisir le même mot de passe" minlength="8" autocomplete="new-password" required>
                    <i class="fa-regular fa-eye fa-lg absolute top-6 right-4 cursor-pointer" id="togglePassword2"></i>
                </div>

                <!-- Message d'erreur pour le mot de passe -->
                <span id="error-message" class="error text-rouge-logo text-small"></span>

                <!-- Bouton pour continuer -->
                <input type="submit" value="Continuer" class="cursor-pointer w-full h-12 my-1.5 bg-secondary text-white font-bold rounded-lg inline-flex items-center justify-center border border-transparent focus:scale-[0.97] hover:bg-green-900 hover:border-green-900 hover:text-white">
                
                <!-- Lien vers la page de connexion -->
                <a href="login-pro.html" class="w-full h-12 p-1 bg-transparent text-secondary font-bold rounded-lg inline-flex items-center justify-center border border-secondary hover:text-white hover:bg-green-900 hover:border-green-900 focus:scale-[0.97]"> 
                    J'ai déjà un compte
                </a>
            </form>
        </div>
    </div>
</body>
</html>

<script>
// Gestion des icônes pour afficher/masquer le mot de passe
const togglePassword1 = document.getElementById('togglePassword1');
const togglePassword2 = document.getElementById('togglePassword2');
const mdp = document.getElementById('mdp');
const confMdp = document.getElementById('confMdp');

togglePassword1.addEventListener('mousedown', function () {
    mdp.type = 'text'; // Change le type d'input pour afficher le mot de passe
    this.classList.remove('fa-eye'); // Change l'icône
    this.classList.add('fa-eye-slash');
});

togglePassword1.addEventListener('mouseup', function () {
    mdp.type = 'password'; // Masque le mot de passe à nouveau
    this.classList.remove('fa-eye-slash');
    this.classList.add('fa-eye');
});

togglePassword2.addEventListener('mousedown', function () {
    confMdp.type = 'text'; // Change le type d'input pour afficher le mot de passe
    this.classList.remove('fa-eye');
    this.classList.add('fa-eye-slash');
});

togglePassword2.addEventListener('mouseup', function () {
    confMdp.type = 'password'; // Masque le mot de passe à nouveau
    this.classList.remove('fa-eye-slash');
    this.classList.add('fa-eye');
});

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

<?php } else { 
// Si le formulaire a été soumis
$deno = $_POST['deno'];
$mail = strtolower($_POST['mail']);
$mdp = $_POST['mdp'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image" href="../public/images/favicon.png">
    <link rel="stylesheet" href="../styles/output.css">
    <title>Création de compte 2/2</title>
    <script src="https://kit.fontawesome.com/d815dd872f.js" crossorigin="anonymous"></script>
</head>
<body class="h-screen bg-base100 pt-4 px-4 overflow-x-hidden">
    <!-- Icône pour revenir à la page précédente -->
    <i onclick="history.back()" class="absolute top-7 fa-solid fa-arrow-left fa-2xl cursor-pointer"></i>
    <div class="w-full max-w-96 h-fit flex flex-col items-end sm:w-96 m-auto">
        <img class="text mb-4" src="../public/images/logo.svg" alt="moine" width="57">
        <form class="mb-4 bg-base200 w-full p-5 rounded-lg border-2 border-secondary" action="../dockerBDD/connexion/pro/crea_compte_pro.php" method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
            <p class="pb-3">Dites-nous en plus !</p>

            <!-- Champ pour la dénomination sociale (en lecture seule) -->
            <label class="text-small" for="deno">Dénomination sociale</label>
            <input class="p-2 text-gris bg-base100 w-full h-12 mb-1.5 rounded-lg" type="text" id="deno" name="deno" title="Dénomination sociale" value="<?php echo $deno;?>" readonly>
            
            <!-- Champ pour l'adresse mail (en lecture seule) -->
            <label class="text-small" for="mail">Adresse mail</label>
            <input class="p-2 text-gris bg-base100 w-full h-12 mb-1.5 rounded-lg" type="email" id="mail" name="mail" title="Adresse mail" value="<?php echo $mail;?>" readonly>

            <!-- Choix du statut de l'utilisateur -->
            <label class="text-small" for="statut">Je suis un organisme&nbsp;</label>
            <select class="text-small mt-3 mb-1.5 bg-base100 p-1 rounded-lg" id="statut" name="statut" title="" required>
                <option value="" disabled selected> --- </option>
                <option value="public">public</option>
                <option value="private">privé</option>
            </select>
            <label class="text-small" for="statut">&nbsp;.</label></br>
            
            <!-- Champs pour l'adresse -->
            <label class="text-small" for="adresse">Adresse postale*</label>
            <input class="p-2 bg-base100 w-full h-12 mb-1.5 rounded-lg" type="text" id="adresse" name="adresse" 
                   pattern="\d{1,5}\s[\w\s.-]+$" title="" maxlength="255" required>
            
            <div class="flex flex-nowrap space-x-3 mb-1.5">
                <div class="w-28">
                    <label class="text-small" for="code">Code postal*</label>
                    <input class="text-right p-2 bg-base100 w-28 h-12 rounded-lg" type="text" id="code" name="code" 
                           pattern="^(0[1-9]|[1-8]\d|9[0-5]|2A|2B)[0-9]{3}$" title="Saisir un code postal" minlength="5" maxlength="5" oninput="number(this)" required>
                </div>
                <div class="w-full">
                    <label class="text-small" for="ville">Ville*</label>
                    <input class="p-2 bg-base100 w-full h-12 rounded-lg" type="text" id="ville" name="ville" 
                           pattern="^[a-zA-Zéèêëàâôûç\-'\s]+(?:\s[A-Z][a-zA-Zéèêëàâôûç\-']+)*$" title="Saisir une ville" maxlength="50" required>
                </div>
            </div>

            <!-- Champ pour le numéro de téléphone -->
            <label class="text-small" for="num_tel">Téléphone*</label>
            <div class="w-full">
                <input class="text-center p-2 bg-base100 w-36 h-12 mb-3 rounded-lg" type="tel" id="num_tel" name="num_tel" 
                       pattern="^0\d( \d{2}){4}" title="Saisir un numéro de téléphone" minlength="14" maxlength="14" oninput="formatTEL(this)" required>
            </div>

            <!-- Choix de saisie des informations bancaires -->
            <div class="group">
                <div class="mb-1.5 flex items-start">
                    <input class="mt-0.5 mr-1.5" type="checkbox" id="plus" name="plus" onchange="toggleIBAN()">
                    <label class="text-small" for="plus">Je souhaite saisir mes informations bancaires dès maintenant !</label>
                </div>

                <!-- Champ pour l'IBAN -->
                <div id="iban-container" class="hidden">
                    <label class="text-small" for="iban">IBAN</label>
                    <input class="p-2 bg-base100 w-full h-12 mb-3 rounded-lg" type="text" id="iban" name="iban" 
                        pattern="^(FR)\d{2}( \d{4}){5} \d{3}$" title="Saisir un IBAN (FR)" minlength="33" maxlength="33" 
                        oninput="formatIBAN(this)" value="">
                </div>
            </div>  
             <!-- Champ caché pour le mot de passe -->
            <input type="hidden" name="mdp" value="<?php echo htmlspecialchars($mdp); ?>">

            <div class="mb-1.5 flex items-start">
                <input class="mt-0.5 mr-1.5" type="checkbox" id="termes" name="termes" title="" required>
                <label class="text-small" for="termes">J’accepte les <u>conditions d'utilisation</u> et vous confirmez que vous avez lu notre <u>Politique de confidentialité et d'utilisation des cookies</u>.</label>
            </div>
            
            <!-- Bouton pour créer le compte -->
            <input type="submit" value="Créer mon compte" class="cursor-pointer w-full mt-1.5 h-12 bg-secondary text-white font-bold rounded-lg inline-flex items-center justify-center border border-transparent focus:scale-[0.97] hover:bg-green-900 hover:border-green-900 hover:text-white">
        </form>
    </div>
</body>
</html>

<script>
// Fonction pour autoriser uniquement les chiffres dans l'input
function number(input) {
    let value = input.value.replace(/[^0-9]/g, '');
    input.value = value;
}

// Fonction pour formater le numéro de téléphone
function formatTEL(input) {
    let value = input.value.replace(/[^0-9]/g, '');
    const formattedValue = value.match(/.{1,2}/g)?.join(' ') || ''; // Formatage en paires de chiffres
    input.value = formattedValue;
}

// Fonction pour afficher ou masquer le champ IBAN
function toggleIBAN() {
    const ibanContainer = document.getElementById('iban-container');
    const checkbox = document.getElementById('plus');
    ibanContainer.classList.toggle('hidden', !checkbox.checked);
}

// Fonction pour formater l'IBAN
function formatIBAN(input) {
    let value = input.value.replace(/[^0-9]/g, '');
    const prefix = "FR"; // Préfixe de l'IBAN
    const formattedValue = value.length > 0 ? (prefix + value).match(/.{1,4}/g)?.join(' ') : prefix; // Formatage de l'IBAN
    input.value = formattedValue;
}
</script>

<?php } ?>
