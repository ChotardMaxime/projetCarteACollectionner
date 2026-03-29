<?php
require_once 'includes/db.php';

$page_active = 'gestion';
$titre_page = 'Gestion des cartes - CARTE A COLLECTIONNER';

$message = '';
$erreur = '';

// =========================================
// AJOUT D'UNE CARTE
// =========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {

    $nom = trim($_POST['nom'] ?? '');
    $valeur = trim($_POST['valeur'] ?? '');
    $rarete = $_POST['rarete'] ?? 'commune';

    if ($nom === '' || $valeur === '') {
        $erreur = "Veuillez remplir tous les champs obligatoires.";
    } else {
        // Gestion de l'upload d'image
        $image_url = null;
        if (!empty($_FILES['image']['name'])) {
            $dossier = 'img/';
            $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $extensions_autorisees = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($extension, $extensions_autorisees)) {
                $erreur = "Format d'image non autorisé. Utilisez JPG, PNG, GIF ou WEBP.";
            } else {
                $nom_fichier = strtolower(str_replace(' ', '_', $nom)) . '.' . $extension;
                $chemin_complet = $dossier . $nom_fichier;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $chemin_complet)) {
                    $image_url = $chemin_complet;
                } else {
                    $erreur = "Erreur lors de l'upload de l'image.";
                }
            }
        }

        if ($erreur === '') {
            $stmt = $pdo->prepare("INSERT INTO carte (nom, valeur, image_url, rarete) VALUES (:nom, :valeur, :image_url, :rarete)");
            $stmt->execute([
                ':nom' => $nom,
                ':valeur' => $valeur,
                ':image_url' => $image_url,
                ':rarete' => $rarete
            ]);
            $message = "La carte \"$nom\" a été ajoutée avec succès.";
        }
    }
}

// =========================================
// SUPPRESSION D'UNE CARTE
// =========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'supprimer') {
    $id = intval($_POST['id_carte'] ?? 0);

    if ($id > 0) {
        // Suppression de l'image associée si elle existe
        $stmt = $pdo->prepare("SELECT image_url FROM carte WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $carte = $stmt->fetch();

        if ($carte && $carte['image_url'] && file_exists($carte['image_url'])) {
            unlink($carte['image_url']);
        }

        $stmt = $pdo->prepare("DELETE FROM carte WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $message = "La carte a été supprimée avec succès.";
    } else {
        $erreur = "Veuillez sélectionner une carte à supprimer.";
    }
}

// Récupération de toutes les cartes pour le select de suppression
$cartes = $pdo->query("SELECT id, nom FROM carte ORDER BY nom ASC")->fetchAll();

require_once 'includes/header.php';
?>

<main class="gestion_main">

    <!-- MESSAGES DE RETOUR -->
    <?php if ($message !== ''): ?>
        <div class="message_succes"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($erreur !== ''): ?>
        <div class="message_erreur"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <!-- =============================== -->
    <!--   FORMULAIRE : AJOUTER          -->
    <!-- =============================== -->
    <h2 class="gestion_titre_section">Ajouter une carte</h2>

    <form class="gestion_formulaire" method="POST" action="gestion.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="ajouter">

        <div class="gestion_champ">
            <label class="gestion_label" for="nom">Nom :</label>
            <input class="gestion_input" type="text" id="nom" name="nom" placeholder="Entrez le nom" required>
        </div>

        <div class="gestion_champ">
            <label class="gestion_label" for="valeur">Valeur :</label>
            <input class="gestion_input" type="number" id="valeur" name="valeur" min="0" step="0.01" placeholder="0.00"
                required>
        </div>

        <div class="gestion_champ">
            <label class="gestion_label" for="rarete">Rareté :</label>
            <select class="gestion_input" id="rarete" name="rarete">
                <option value="commune">Commune</option>
                <option value="peu commune">Peu commune</option>
                <option value="rare">Rare</option>
                <option value="ultra rare">Ultra rare</option>
            </select>
        </div>

        <div class="gestion_champ">
            <label class="gestion_label" for="image">Image :</label>
            <label class="btn_upload" for="image">Choisir une image</label>
            <input type="file" id="image" name="image" accept="image/*" class="gestion_input_file">
        </div>

        <div class="gestion_actions">
            <button type="submit" class="btn_ajouter">Ajouter la carte</button>
        </div>
    </form>

    <hr class="gestion_separateur">

    <!-- =============================== -->
    <!--   FORMULAIRE : SUPPRIMER        -->
    <!-- =============================== -->
    <h2 class="gestion_titre_section">Supprimer une carte</h2>

    <form class="gestion_formulaire" method="POST" action="gestion.php"
        onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette carte ?');">
        <input type="hidden" name="action" value="supprimer">

        <div class="gestion_champ">
            <label class="gestion_label" for="id_carte">Carte :</label>
            <select class="gestion_input" id="id_carte" name="id_carte" required>
                <option value="">-- Choisissez une carte --</option>
                <?php foreach ($cartes as $carte): ?>
                    <option value="<?= $carte['id'] ?>">
                        <?= htmlspecialchars($carte['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="gestion_actions">
            <button type="submit" class="btn_supprimer">Supprimer la carte</button>
        </div>
    </form>

</main>

<?php require_once 'includes/footer.php'; ?>