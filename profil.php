<?php
// 1. Démarrage de la session et inclusion de la BDD
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';

// 2. Sécurité : redirection si non connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

$id_utilisateur = $_SESSION['id_utilisateur'];

// =========================================
// TRAITEMENT DES ACTIONS (POST)
// =========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ACTION : AJOUTER UNE CARTE
    if (isset($_POST['action']) && $_POST['action'] === 'ajouter_collection') {
        $id_carte = $_POST['id_carte'];
        if (!empty($id_carte)) {
            $stmt = $pdo->prepare("INSERT INTO inventaire (id_utilisateur, id_carte, date_acquisition) VALUES (?, ?, CURDATE())");
            $stmt->execute([$id_utilisateur, $id_carte]);
            $_SESSION['flash_success'] = "La carte a été ajoutée à votre collection !";
        }
        header("Location: profil.php");
        exit;
    }

    // ACTION : SUPPRIMER UNE CARTE (une seule occurrence)
    if (isset($_POST['action']) && $_POST['action'] === 'supprimer_collection') {
        $id_carte = $_POST['id_carte'];
        if (!empty($id_carte)) {
            // On supprime seulement la ligne la plus ancienne si l'utilisateur a des doublons
            $stmt = $pdo->prepare("DELETE FROM inventaire WHERE id_utilisateur = ? AND id_carte = ? LIMIT 1");
            $stmt->execute([$id_utilisateur, $id_carte]);
            $_SESSION['flash_info'] = "La carte a été retirée de votre collection.";
        }
        header("Location: profil.php");
        exit;
    }
}

// =========================================
// RÉCUPÉRATION DES DONNÉES (AFFICHAGE)
// =========================================

// Récupération des messages flash
$success = $_SESSION['flash_success'] ?? '';
$info = $_SESSION['flash_info'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_info']);

// Infos de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE id = ?");
$stmt->execute([$id_utilisateur]);
$utilisateur = $stmt->fetch();

// Cartes possédées (pour la grille et le menu de suppression)
$stmt = $pdo->prepare("
    SELECT c.*, inv.date_acquisition 
    FROM inventaire inv 
    JOIN carte c ON c.id = inv.id_carte 
    WHERE inv.id_utilisateur = ? 
    ORDER BY inv.date_acquisition DESC
");
$stmt->execute([$id_utilisateur]);
$mes_cartes = $stmt->fetchAll();

// Toutes les cartes existantes (pour le menu d'ajout)
$stmt_all = $pdo->query("SELECT id, nom FROM carte ORDER BY nom ASC");
$toutes_les_cartes = $stmt_all->fetchAll();

// Calculs stats
$nb_cartes = count($mes_cartes);
$valeur_total = array_sum(array_column($mes_cartes, 'valeur'));

$page_active = 'profil';
$titre_page = 'Mon Profil - CARTE A COLLECTIONNER';
require_once 'includes/header.php';
?>

<main>
    <?php if ($success): ?>
        <div
            style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; text-align: center; margin: 20px auto; max-width: 800px; border: 1px solid #c3e6cb;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($info): ?>
        <div
            style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; text-align: center; margin: 20px auto; max-width: 800px; border: 1px solid #ffeeba;">
            <?= htmlspecialchars($info) ?>
        </div>
    <?php endif; ?>

    <div class="profil_bloc">
        <div class="profil_photo">
            <img src="<?= htmlspecialchars($utilisateur['photo_url'] ?? 'img/default_avatar.png') ?>" alt="Avatar">
        </div>
        <div class="profil_infos">
            <h2 class="profil_nom"><?= htmlspecialchars($utilisateur['nom_profil']) ?></h2>
            <div class="profil_stats">
                <p><span class="profil_label">Rôle :</span> <?= htmlspecialchars($utilisateur['role']) ?></p>
                <p><span class="profil_label">Nombre de cartes :</span> <?= $nb_cartes ?></p>
                <p><span class="profil_label">Valeur collection :</span>
                    <?= number_format($valeur_total, 2, ',', ' ') ?> €</p>
            </div>
        </div>
    </div>

    <div
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; max-width: 1000px; margin: 0 auto 40px auto; padding: 0 20px;">

        <div
            style="background: var(--couleur_card_fond); padding: 20px; border-radius: 8px; border: 1px solid var(--couleur_card_bordure);">
            <h3 style="margin-bottom: 15px;">➕ Ajouter une carte</h3>
            <form method="POST">
                <input type="hidden" name="action" value="ajouter_collection">
                <select class="gestion_input" name="id_carte" required style="margin-bottom: 10px; width: 100%;">
                    <option value="">-- Choisir une carte --</option>
                    <?php foreach ($toutes_les_cartes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn_ajouter" style="width: 100%;">Ajouter</button>
            </form>
        </div>

        <div
            style="background: var(--couleur_card_fond); padding: 20px; border-radius: 8px; border: 1px solid var(--couleur_card_bordure);">
            <h3 style="margin-bottom: 15px;">🗑️ Retirer une carte</h3>
            <form method="POST" onsubmit="return confirm('Retirer cette carte de votre collection ?');">
                <input type="hidden" name="action" value="supprimer_collection">
                <select class="gestion_input" name="id_carte" required style="margin-bottom: 10px; width: 100%;">
                    <option value="">-- Choisir une carte --</option>
                    <?php
                    $uniques = [];
                    foreach ($mes_cartes as $mc) {
                        $uniques[$mc['id']] = $mc['nom'];
                    }
                    foreach ($uniques as $id => $nom): ?>
                        <option value="<?= $id ?>"><?= htmlspecialchars($nom) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn_supprimer" style="width: 100%;">Supprimer</button>
            </form>
        </div>
    </div>

    <div class="profil_cartes_header">
        <span class="filtre_label">Ma Collection</span>
        <hr class="profil_separateur">
    </div>

    <div class="grille_cartes">
        <?php if (empty($mes_cartes)): ?>
            <p style="grid-column: 1/-1; text-align: center; color: var(--couleur_texte_secondaire);">Votre collection est
                vide.</p>
        <?php else: ?>
            <?php foreach ($mes_cartes as $carte): ?>
                <div class="card">
                    <div class="card_nom"><?= htmlspecialchars($carte['nom']) ?></div>
                    <div class="card_image">
                        <img src="<?= htmlspecialchars($carte['image_url'] ?? 'img/default_card.png') ?>"
                            alt="<?= htmlspecialchars($carte['nom']) ?>">
                    </div>
                    <div class="card_valeur"><?= number_format($carte['valeur'], 2, ',', ' ') ?> €</div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>