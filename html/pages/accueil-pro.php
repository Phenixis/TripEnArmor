<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script type="module" src="../scripts/loaddetailsmenus.js"></script>
    <link rel="stylesheet" href="../styles/output.css">
    <script type="module" src="../scripts/main.js"></script>
    <title>PACT - Accueil</title>
</head>
<body class="flex flex-col">
    
    <div id="menu-pro" class="1"></div>
    <div id="header-pro" class="mb-40"></div>
    
    <main class="bg-base200 mx-10 self-center rounded-lg p-2 max-w-[1024px]">
        <!-- TOUTES LES OFFRES (offre & détails) -->
        <div class="tablette p-4 flex flex-col gap-4">
            <h1 class="text-4xl">Mes offres</h1>
    
            <!--
            ### CARD COMPONENT POUR LES PROS ! ###
            Composant dynamique (généré avec les données en php)
            Impossible d'en faire un composant pur (statique), donc écrit en HTML pur (copier la forme dans le php)
            -->
            <div class="card active relative bg-base300 rounded-lg flex">
                <!-- Partie gauche -->
                <div class="gauche relative shrink-0 basis-1/2 h-[420px] overflow-hidden">
                    <!-- En tête -->
                    <div class="en-tete flex justify-around absolute top-0 w-full">
                        <div class="bg-bgBlur/75 backdrop-blur rounded-b-lg w-3/5">
                            <h3 class="text-center font-bold">Restaurant le Brélévenez</h3>
                            <div class="flex w-full justify-between px-2">
                                <p class="text-small">Le brélévenez</p>
                                <p class="text-small">Restauration</p>
                            </div>
                        </div>
                        <a href="" onclick="confirm('Mettre XXX hors ligne ?')">
                            <div class="bg-bgBlur/75 absolute right-4 backdrop-blur flex justify-center items-center p-1 rounded-b-lg">
                                <i class="fa-solid fa-wifi text-title"></i>
                            </div>
                        </a>
                    </div>
                    <!-- Image de fond -->
                    <img class="rounded-l-lg w-full h-full object-cover object-center" src="../public/images/image-test.jpg" alt="Image promotionnelle de l'offre">
                </div>
                <!-- Partie droite (infos principales) -->
                <div class="infos flex flex-col items-center self-stretch px-5 py-3 justify-between">
                    <!-- Description -->
                    <div class="description py-2 flex flex-col gap-2 w-full">
                        <div class="flex justify-center relative">
                            <div class="p-2 rounded-lg bg-secondary self-center">
                                <p class="text-white text-center font-bold">Petit déjeuner, Dîner, Boissons</p>
                            </div>
                            <a href="">
                                <div class="flex justify-center items-center rounded-lg absolute top-1/2 right-0 -translate-y-1/2">
                                    <i class="px-2 rounded-lg border border-primary text-primary hover:bg-primary h-full duration-100 text-title hover:text-white details-menu-toggle fa-solid fa-ellipsis"></i>
                                </div>
                            </a>
                            <div class="details-menu hidden rounded-lg absolute right-0 bg-white">
                                <ul class="rounded-lg flex flex-col">
                                    <a href="./details.html">
                                        <li class="rounded-t-lg p-2 hover:bg-primary hover:text-white duration-200" >Details</li>
                                    </a>
                                    <a href="">
                                        <li class="rounded-b-lg p-2 hover:bg-primary hover:text-white border-solid border-t-2 border-black duration-200">Modifier</li>
                                    </a>
                                </ul>
                            </div>
                        </div>
                        <p class="line-clamp-6">
                            Priscilla en salle, son mari Christophe chef de cuisine et toute l'équipe vous accueillent dans leur restaurant,
                            ouvert depuis Janvier 2018, dans le quartier Historique De Lannion :" Brélévenez"
                            Quartier célèbre pour son église avec son escalier de 142 marches pour y accéder.
                            Christophe vous propose une cuisine de produits locaux et de saisons.
                            Restaurant ouvert à l'année.
                            Fermé mardi et mercredi toute la journée et le samedi midi.
                            (Parking privé) 
                        </p>
                    </div>
                    <!-- A droite, en bas -->
                    <div class="self-stretch flex flex-col shrink-0 gap-2">
                        <hr class="border-black w-full">
                        <div class="flex justify-around self-stretch">
                            <!-- Localisation -->
                            <div class="localisation flex flex-col gap-2 flex-shrink-0 justify-center items-center">
                                <i class="fa-solid fa-location-dot"></i>
                                <p class="text-small">Lannion</p>
                                <p class="text-small">22300</p>
                            </div>
                            <!-- Notation et Prix -->
                            <div class="localisation flex flex-col flex-shrink-0 gap-2 justify-center items-center">
                                <p class="text-small">€€</p>
                            </div>
                        </div>

                        <!-- Infos supplémentaires pour le pro -->
                        <div class="bg-veryGris p-3 rounded-lg flex flex-col gap-1">

                            <!-- Avis et type d'offre -->
                            <div class="flex justify-between">
                                <div class="flex italic justify-start gap-4">
                                    <!-- Non vus -->
                                     <a href="" class="hover:text-primary">
                                        <i class=" fa-solid fa-exclamation text-rouge-logo"></i>
                                        (12)
                                     </a>
                                     <!-- Non répondus -->
                                     <a href="" class="hover:text-primary">
                                        <i class="fa-solid fa-reply-all text-rouge-logo"></i>
                                        (53)
                                     </a>
                                     <!-- Blacklistés -->
                                     <a href="" class="hover:text-primary">
                                        <i class="fa-regular fa-eye-slash text-rouge-logo"></i>
                                        (2)
                                     </a>
                                </div>
                                <p class="text-center grow">Standard</p>
                            </div>

                            <!-- Dates de mise à jour -->
                            <div class="flex justify-between text-small">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-rotate text-xl"></i>
                                    <p class="italic">Modifiée le XX/XX/XX</p>
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-gears text-xl"></i>
                                    <div>
                                        <p>‘A la Une’ 10/09/24-17/09/24</p>
                                        <p>‘En relief' 10/09/24-17/09/24</p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Bouton de création d'offre -->
            <a href="" class="font-bold p-4 self-end bg-transparent text-primary py-2 px-4 rounded-lg inline-flex items-center border border-primary hover:text-white hover:bg-primary hover:border-primary m-1 
            focus:scale-[0.97] duration-100">
                + Nouvelle offre
            </a>
        </div>

    </main>

    <div id="footer-pro"></div>
<script src="ajout.js"></script>
</body>
</html>