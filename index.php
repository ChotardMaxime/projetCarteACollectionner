<?php
require_once 'includes/db.php';

$page_active = 'index';
$titre_page  = 'CARTE A COLLECTIONNER';

$filtre = $_GET['filtre'] ?? '';

$sql = "SELECT * FROM carte";
if ($filtre === 'valeur') {
    $sql .= " ORDER BY valeur DESC";
} elseif ($filtre === 'nom') {
    $sql .= " ORDER BY nom ASC";
} elseif ($filtre === 'rarete') {
    $sql .= " ORDER BY FIELD(rarete, 'ultra rare', 'rare', 'peu commune', 'commune')";
}

$stmt   = $pdo->query($sql);
$cartes = $stmt->fetchAll();

require_once 'includes/header.php';
?>
<main>
    <div class="filtre">
        <span class="filtre_label">Toutes les cartes</span>
        <form method="GET" action="index.php">
            <select class="filtre_select" name="filtre" onchange="this.form.submit()">
                <option value=""       <?= $filtre === ''       ? 'selected' : '' ?>>Sans filtre</option>
                <option value="valeur" <?= $filtre === 'valeur' ? 'selected' : '' ?>>Par valeur</option>
                <option value="nom"    <?= $filtre === 'nom'    ? 'selected' : '' ?>>Par nom</option>
                <option value="rarete" <?= $filtre === 'rarete' ? 'selected' : '' ?>>Par rareté</option>
            </select>
        </form>
    </div>

    <div class="grille_cartes">
        <?php foreach ($cartes as $carte): ?>
            <div class="card"
                 data-nom="<?= htmlspecialchars($carte['nom']) ?>"
                 data-image="<?= htmlspecialchars($carte['image_url'] ?? '') ?>"
                 data-rarete="<?= htmlspecialchars($carte['rarete']) ?>"
                 data-valeur="<?= number_format($carte['valeur'], 2, ',', ' ') ?> €">
                <div class="card_nom"><?= htmlspecialchars($carte['nom']) ?></div>
                <div class="card_image">
                    <img src="<?= htmlspecialchars($carte['image_url']) ?>"
                         alt="<?= htmlspecialchars($carte['nom']) ?>">
                </div>
                <div class="card_valeur"><?= number_format($carte['valeur'], 2, ',', ' ') ?> €</div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>