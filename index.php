
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HMS Home Page</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="header.css">
    <style>
       
/* Styles pour l'image de fond */
        .hero-section {
            position: relative;
            width: 100vw;
            min-height: 100vh; /* Couvre toute la hauteur de la fenêtre */
            background-image: url('img/doc2.jpg'); /* Chemin de l'image */
            background-size: cover; /* Ajuste l'image à la taille de la section */
            background-position: top center; /* Centre l'image */
            background-repeat: no-repeat; /* Empêche la répétition de l'image */
            padding-top: 70px; /* Ajustez cette valeur en fonction de la hauteur de votre navbar */
        }

        /* Styles pour la barre de navigation */
        .navbar-custom {
            position: fixed; /* Rendre la navbar fixe */
            top: 0;
            left: 0;
            width: 100%;
            background: rgba(0, 0, 0, 0.5) !important; /* Fond noir transparent */
            z-index: 1000; /* Assurer que la navbar est au-dessus de tout */
        }

        .navbar-custom .navbar-nav .nav-link {
            color: white !important; /* Couleur du texte en blanc */
        }

        .navbar-custom .navbar-brand {
            color: white !important; /* Couleur du texte en blanc pour le brand */
        }

        .navbar-custom .d-flex a {
            color: white !important; /* Couleur du texte en blanc pour le lien de login */
        }

        /* Ajuster le body pour éviter les chevauchements */
        body {
            padding-top: 70px; /* Ajustez cette valeur en fonction de la hauteur de votre navbar */
        }
        /* Styles pour la barre de navigation */
.navbar {
    background-color: #333;
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: fixed; /* Rend la navbar fixe */
    top: 0; /* Positionne la navbar en haut */
    width: 100%; /* Occupe toute la largeur */
    z-index: 1000; /* Assure que la navbar est au-dessus des autres éléments */
}

/* Ajouter un padding-top au body pour éviter que le contenu ne soit masqué */
body {
    padding-top: 70px; /* Ajustez cette valeur en fonction de la hauteur de votre navbar */
}

/* Styles pour le conteneur du logo et du message */
.logo-message-container {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 120px; /* Ajustez cette valeur pour compenser la navbar fixe */
}

/* Styles pour le message de bienvenue */
.welcome-message {
    text-align: center;
    color: white;
    margin-top: 150px; /* Ajustez cette valeur pour compenser la navbar fixe */
}

/* Le reste de vos styles CSS */
.navbar-brand {
    color: white;
    font-weight: bold;
    display: flex;
    align-items: center;
}

.navbar-brand img {
    height: 50px;
    margin-right: 10px;
}

.navbar-collapse {
    display: flex;
    justify-content: flex-end;
    flex-grow: 1;
}

.navbar-nav {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
}

.nav-item {
    position: relative;
    margin-right: 20px;
}

.nav-link {
    color: white;
    text-decoration: none;
    padding: 10px 15px;
    display: block;
    transition: background-color 0.3s ease;
}

.nav-link:hover {
    background-color: #555;
}

.dropdown-menu {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    background-color: #444;
    list-style: none;
    padding: 0;
    margin: 0;
    min-width: 150px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    z-index: 1000;
}

.dropdown-item {
    color: white;
    text-decoration: none;
    padding: 10px 15px;
    display: block;
    transition: background-color 0.3s ease;
}

.dropdown-item:hover {
    background-color: #666;
}

.nav-item:hover .dropdown-menu {
    display: block;
}

.login-link {
    margin-left: 20px;
}

.login-link a {
    color: white;
    text-decoration: none;
    font-weight: 500;
}

.login-link a:hover {
    text-decoration: underline;
}

    </style>
</head>
<body>
    <!-- Section avec l'image de fond -->
    <div class="hero-section">
        <!-- Barre de navigation -->
       <?php include_once __DIR__ . "/include/header.php"; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>