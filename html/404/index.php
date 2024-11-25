<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/styles/input.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="/styles/config.js"></script>
    <link rel="icon" type="image" href="/public/images/favicon.png">

    <link rel="stylesheet" href="/styles/output.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="/scripts/loadComponents.js" defer=""></script>
    <script type="module" src="/scripts/main.js" defer=""></script>
    
    <title>Page non trouvée</title>
</head>

<body class="min-h-screen flex flex-col">
    <div id="header"></div>
    <main class="md:w-full mt-0 m-auto flex max-w-[1280px] p-2">
        <div id="menu" class="absolute md:block"></div>
        <div class="m-auto text-center">
            <h1 class="font-cormorant text-[10rem]">404</h1>
            <p>Ce n'est pas la page que vous recherchez.</p>
            <img src="https://i.pinimg.com/originals/e0/5a/70/e05a70b23f36987ff395063a1e193db7.gif" class="mt-10 rounded-lg m-auto" alt="tottereau" width="250">
        </div>
    </main>
    <div id="footer" class=""></div>
</body>

</html>