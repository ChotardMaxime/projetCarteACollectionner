<?php
require_once 'includes/db.php';

$page_active = 'index';
$titre_page = 'CARTE A COLLECTIONNER';

// Récupération du filtre choisi par l'utilisateur
$filtre = $_GET['filtre'] ?? '';

// Construction de la requête selon le filtre
$sql = "SELECT * FROM carte";

if ($filtre === 'valeur') {
    $sql .= " GROUP BY nom ORDER BY valeur DESC";
} elseif ($filtre === 'nom') {
    $sql .= " GROUP BY nom ORDER BY nom ASC";
} elseif ($filtre === 'rarete') {
    $sql .= " GROUP BY nom ORDER BY FIELD(rarete, 'ultra rare', 'rare', 'peu commune', 'commune')";
} 

$stmt = $pdo->query($sql);
$cartes = $stmt->fetchAll();

require_once 'includes/header.php';
?>
<main>
    <div class="filtre">
        <span class="filtre_label">Toutes les cartes</span>
        <form method="GET" action="index.php">
            <select class="filtre_select" name="filtre" onchange="this.form.submit()">
                <option value="valeur" <?= $filtre === 'valeur' ? 'selected' : '' ?>>Par valeur</option>
                <option value="nom" <?= $filtre === 'nom' ? 'selected' : '' ?>>Par nom</option>
                <option value="rarete" <?= $filtre === 'rarete' ? 'selected' : '' ?>>Par rareté</option>
            </select>
        </form>
    </div>

    <div class="grille_cartes">
        <?php foreach ($cartes as $carte): ?>
            <div class="card">
                <div class="card_nom"><?= htmlspecialchars($carte['nom']) ?></div>
                <div class="card_image">
                    <img src="<?= htmlspecialchars($carte['image_url']) ?>" alt="<?= htmlspecialchars($carte['nom']) ?>">
                </div>
                <div class="card_valeur"><?= number_format($carte['valeur'], 2, ',', ' ') ?> €</div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>