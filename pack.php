<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_utilisateur'])) {
    echo json_encode(['succes' => false, 'message' => 'Vous devez être connecté.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['succes' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

$id_utilisateur = (int) $_SESSION['id_utilisateur'];

// =========================================
// POIDS PAR RARETÉ
// commune    : 50%
// peu commune: 30%
// rare       : 15%
// ultra rare :  5%
// =========================================
$poids = [
    'commune'     => 50,
    'peu commune' => 30,
    'rare'        => 15,
    'ultra rare'  =>  5,
];

// Cartes disponibles (non encore possédées par l'utilisateur)
$stmt = $pdo->prepare("
    SELECT * FROM carte
    WHERE id NOT IN (
        SELECT id_carte FROM inventaire WHERE id_utilisateur = ?
    )
");
$stmt->execute([$id_utilisateur]);
$disponibles = $stmt->fetchAll();

if (empty($disponibles)) {
    echo json_encode([
        'succes'  => false,
        'message' => 'Félicitations ! Vous possédez déjà toutes les cartes disponibles.'
    ]);
    exit;
}

// Regrouper par rareté
$par_rarete = [];
foreach ($disponibles as $c) {
    $par_rarete[$c['rarete']][] = $c;
}

// =========================================
// TIRAGE PONDÉRÉ AVEC FALLBACK
// Si un palier est vide ses poids sont
// redistribués sur les paliers disponibles.
// =========================================
function tirerRarete(array $poids, array $par_rarete): string
{
    $poids_filtres = array_filter(
        $poids,
        fn($r) => isset($par_rarete[$r]) && count($par_rarete[$r]) > 0,
        ARRAY_FILTER_USE_KEY
    );

    $total = array_sum($poids_filtres);
    $roll  = mt_rand(1, $total);
    $cumul = 0;

    foreach ($poids_filtres as $rarete => $poid) {
        $cumul += $poid;
        if ($roll <= $cumul) return $rarete;
    }

    return array_key_first($poids_filtres);
}

$rarete_tiree = tirerRarete($poids, $par_rarete);
$pool         = $par_rarete[$rarete_tiree];
$carte        = $pool[array_rand($pool)];

// Insertion dans la collection
$stmt = $pdo->prepare("
    INSERT INTO inventaire (id_utilisateur, id_carte, date_acquisition)
    VALUES (?, ?, CURDATE())
");
$stmt->execute([$id_utilisateur, $carte['id']]);

echo json_encode(['succes' => true, 'carte' => $carte]);
?>