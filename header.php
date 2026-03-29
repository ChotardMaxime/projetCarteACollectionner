<?php
// La variable $page_active doit être définie avant d'inclure ce fichier.
// Valeurs possibles : 'index', 'profil', 'gestion'

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <title><?= $titre_page ?? 'CARTE A COLLECTIONNER' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600&family=Lato:wght@300;400;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="style.css">
</head>

<body>
    <header>
        <div class="titre">
            <h1>Carte a collectionner.com</h1>
        </div>
        <nav class="barre_navigation">

            <!-- LIENS PRINCIPAUX (centrés) -->
            <div class="nav_liens">
                <div class="mon_profil <?= ($page_active ?? '') === 'profil' ? 'actif' : '' ?>">
                    <a href="profil.php">Mon profil</a>
                </div>
                <div class="voir_les_cartes <?= ($page_active ?? '') === 'index' ? 'actif' : '' ?>">
                    <a href="index.php">Voir les cartes</a>
                </div>
                <div class="gestion_des_cartes <?= ($page_active ?? '') === 'gestion' ? 'actif' : '' ?>">
                    <a href="gestion.php">Gestion des cartes</a>
                </div>
            </div>

            <!-- BOUTON CONNEXION / DECONNEXION (tout à droite) -->
            <div class="nav_auth">
                <?php if (isset($_SESSION['id_utilisateur'])): ?>
                    <a href="logout.php" class="btn_auth btn_deconnexion">Se déconnecter</a>
                <?php else: ?>
                    <a href="login.php"
                        class="btn_auth btn_connexion <?= ($page_active ?? '') === 'login' ? 'actif' : '' ?>">Se
                        connecter</a>
                <?php endif; ?>
            </div>

        </nav>
    </header>