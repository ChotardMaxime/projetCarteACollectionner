<?php
require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si déjà connecté, rediriger vers l'accueil
if (isset($_SESSION['id_utilisateur'])) {
    header('Location: index.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_profil = trim($_POST['nom_profil'] ?? '');
    $mot_de_passe = trim($_POST['mot_de_passe'] ?? '');

    if ($nom_profil === '' || $mot_de_passe === '') {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE nom_profil = :nom_profil");
        $stmt->execute([':nom_profil' => $nom_profil]);
        $utilisateur = $stmt->fetch();

        if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
            $_SESSION['id_utilisateur'] = $utilisateur['id'];
            $_SESSION['nom_profil'] = $utilisateur['nom_profil'];
            $_SESSION['role'] = $utilisateur['role'];
            header('Location: index.php');
            exit;
        } else {
            $erreur = "Nom de profil ou mot de passe incorrect.";
        }
    }
}

$page_active = 'login';
$titre_page = 'Connexion - CARTE A COLLECTIONNER';
require_once 'includes/header.php';
?>

<main class="login_main">

    <div class="login_bloc">

        <h2 class="login_titre">Connexion</h2>

        <?php if ($erreur !== ''): ?>
            <div class="message_erreur"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <form class="login_formulaire" method="POST" action="login.php">

            <div class="login_champ">
                <label class="login_label" for="nom_profil">Nom de profil</label>
                <input class="gestion_input" type="text" id="nom_profil" name="nom_profil"
                    placeholder="Entrez votre nom de profil" value="<?= htmlspecialchars($_POST['nom_profil'] ?? '') ?>"
                    required>
            </div>

            <div class="login_champ">
                <label class="login_label" for="mot_de_passe">Mot de passe</label>
                <input class="gestion_input" type="password" id="mot_de_passe" name="mot_de_passe"
                    placeholder="Entrez votre mot de passe" required>
            </div>

            <div class="login_actions">
                <button type="submit" class="btn_ajouter">Se connecter</button>
            </div>

        </form>
    </div>

</main>

<?php require_once 'includes/footer.php'; ?>