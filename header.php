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

            <div class="nav_auth">
                <?php if (isset($_SESSION['id_utilisateur'])): ?>
                    <a href="logout.php" class="btn_auth btn_deconnexion">Se déconnecter</a>
                <?php else: ?>
                    <a href="login.php"
                        class="btn_auth btn_connexion <?= ($page_active ?? '') === 'login' ? 'actif' : '' ?>">Se connecter</a>
                <?php endif; ?>
            </div>

        </nav>
    </header>

    <!-- ==========================================================================
         MODAL ZOOM GLOBAL — présent sur toutes les pages, activé au clic d'une carte
         ========================================================================== -->
    <div id="zoom-overlay" class="zoom-overlay" style="display:none;" onclick="fermerZoom(event)">
        <div class="zoom-carte" id="zoom-carte">
            <button class="zoom-fermer" onclick="fermerZoom()" title="Fermer">×</button>
            <div class="zoom-image">
                <img id="zoom-img" src="" alt="">
            </div>
            <div class="zoom-infos">
                <div class="zoom-nom"    id="zoom-nom"></div>
                <div class="zoom-rarete" id="zoom-rarete"></div>
                <div class="zoom-valeur" id="zoom-valeur"></div>
            </div>
        </div>
    </div>

    <script>
    // ==========================================================================
    // ZOOM GLOBAL — délégation d'événement sur toutes les .card du document
    // ==========================================================================
    document.addEventListener('click', function(e) {
        // Ignorer les clics sur le bouton × et son formulaire parent
        if (e.target.closest('.card-btn-supprimer') || e.target.closest('form')) return;

        const card = e.target.closest('.card');
        if (!card) return;

        const nom    = card.dataset.nom    || card.querySelector('.card_nom')?.textContent?.trim()    || '';
        const image  = card.dataset.image  || card.querySelector('.card_image img')?.getAttribute('src') || '';
        const rarete = card.dataset.rarete || '';
        const valeur = card.dataset.valeur || card.querySelector('.card_valeur')?.textContent?.trim() || '';

        ouvrirZoom(nom, image, rarete, valeur);
    });

    function ouvrirZoom(nom, image, rarete, valeur) {
        document.getElementById('zoom-img').src           = image;
        document.getElementById('zoom-img').alt           = nom;
        document.getElementById('zoom-nom').textContent   = nom;
        document.getElementById('zoom-rarete').textContent = rarete;
        document.getElementById('zoom-valeur').textContent = valeur;

        const couleurs = {
            'commune'     : '#718096',
            'peu commune' : '#2d6a4f',
            'rare'        : '#1a5276',
            'ultra rare'  : '#6c3483'
        };
        document.getElementById('zoom-rarete').style.backgroundColor = couleurs[rarete] || '#718096';

        document.getElementById('zoom-overlay').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function fermerZoom(e) {
        if (e && e.target.closest && e.target.closest('.zoom-carte')) return;
        document.getElementById('zoom-overlay').style.display = 'none';
        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') fermerZoom();
    });
    </script>