<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';

header('Content-Type: application/json');

// Vérification de la session
if (!isset($_SESSION['id_utilisateur'])) {
    echo json_encode(['succes' => false, 'message' => 'Vous devez être connecté.']);
    exit;
}

// Accepte uniquement les requêtes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['succes' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

$id_utilisateur = (int) $_SESSION['id_utilisateur'];

// Tirage : une carte aléatoire parmi celles que l'utilisateur ne possède PAS encore
$stmt = $pdo->prepare("
    SELECT * FROM carte
    WHERE id NOT IN (
        SELECT id_carte FROM inventaire WHERE id_utilisateur = ?
    )
    ORDER BY RAND()
    LIMIT 1
");
$stmt->execute([$id_utilisateur]);
$carte = $stmt->fetch();

// Toutes les cartes sont déjà possédées
if (!$carte) {
    echo json_encode([
        'succes' => false,
        'message' => 'Félicitations ! Vous possédez déjà toutes les cartes disponibles.'
    ]);
    exit;
}

// Ajout de la carte dans la collection
$stmt = $pdo->prepare("
    INSERT INTO inventaire (id_utilisateur, id_carte, date_acquisition)
    VALUES (?, ?, CURDATE())
");
$stmt->execute([$id_utilisateur, $carte['id']]);

echo json_encode(['succes' => true, 'carte' => $carte]);
?>