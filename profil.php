<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';

if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: login.php');
    exit;
}

$id_utilisateur = (int) $_SESSION['id_utilisateur'];

// Suppression d'une carte de la collection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'supprimer_carte') {
    $id_carte = (int) ($_POST['id_carte'] ?? 0);
    if ($id_carte > 0) {
        $stmt = $pdo->prepare("DELETE FROM inventaire WHERE id_utilisateur = ? AND id_carte = ? LIMIT 1");
        $stmt->execute([$id_utilisateur, $id_carte]);
        $_SESSION['flash_info'] = 'La carte a été retirée de votre collection.';
    }
    header('Location: profil.php');
    exit;
}

// Messages flash
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_info    = $_SESSION['flash_info']    ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_info']);

// Infos utilisateur
$stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE id = ?");
$stmt->execute([$id_utilisateur]);
$utilisateur = $stmt->fetch();

// Collection
$stmt = $pdo->prepare("
    SELECT c.*, inv.date_acquisition
    FROM inventaire inv
    JOIN carte c ON c.id = inv.id_carte
    WHERE inv.id_utilisateur = ?
    ORDER BY inv.date_acquisition DESC
");
$stmt->execute([$id_utilisateur]);
$mes_cartes = $stmt->fetchAll();

$nb_cartes    = count($mes_cartes);
$valeur_total = array_sum(array_column($mes_cartes, 'valeur'));

$page_active = 'profil';
$titre_page  = 'Mon Profil - CARTE A COLLECTIONNER';
require_once 'includes/header.php';
?>

<main>

    <?php if ($flash_success): ?>
        <div class="message_succes"><?= htmlspecialchars($flash_success) ?></div>
    <?php endif; ?>
    <?php if ($flash_info): ?>
        <div class="message_info"><?= htmlspecialchars($flash_info) ?></div>
    <?php endif; ?>

    <!-- BLOC PROFIL -->
    <div class="profil_bloc">
        <div class="profil_photo">
            <?php if (!empty($utilisateur['photo_url'])): ?>
                <img src="<?= htmlspecialchars($utilisateur['photo_url']) ?>" alt="Avatar">
            <?php else: ?>
                <span class="profil_photo_icon">&#128247;</span>
            <?php endif; ?>
        </div>
        <div class="profil_infos">
            <div>
                <p><span class="profil_label">Nom de profil : </span><?= htmlspecialchars($utilisateur['nom_profil']) ?></p>
                <p><span class="profil_label">Rôle : </span><?= htmlspecialchars($utilisateur['role']) ?></p>
            </div>
            <div>
                <p><span class="profil_label">Cartes : </span><?= $nb_cartes ?></p>
                <p><span class="profil_label">Valeur : </span><?= number_format($valeur_total, 2, ',', ' ') ?> €</p>
            </div>
        </div>
    </div>

    <!-- BOUTON OUVRIR UN PAQUET -->
    <div class="pack-zone">
        <button class="btn-ouvrir-pack" onclick="ouvrirModalPack()">Ouvrir un paquet</button>
    </div>

    <!-- COLLECTION -->
    <div class="profil_cartes_header">
        <span class="filtre_label">Ma Collection</span>
        <hr class="profil_separateur">
    </div>

    <div class="grille_cartes">
        <?php if (empty($mes_cartes)): ?>
            <p class="collection-vide">
                Votre collection est vide. Ouvrez un paquet pour commencer !
            </p>
        <?php else: ?>
            <?php foreach ($mes_cartes as $carte): ?>
                <div class="card"
                     data-nom="<?= htmlspecialchars($carte['nom']) ?>"
                     data-image="<?= htmlspecialchars($carte['image_url'] ?? '') ?>"
                     data-rarete="<?= htmlspecialchars($carte['rarete']) ?>"
                     data-valeur="<?= number_format($carte['valeur'], 2, ',', ' ') ?>">

                    <!-- Bouton suppression -->
                    <form method="POST" action="profil.php"
                          onsubmit="return confirm('Retirer <?= htmlspecialchars(addslashes($carte['nom'])) ?> de votre collection ?');">
                        <input type="hidden" name="action"   value="supprimer_carte">
                        <input type="hidden" name="id_carte" value="<?= $carte['id'] ?>">
                        <button type="submit" class="card-btn-supprimer" title="Retirer de la collection">×</button>
                    </form>

                    <div class="card_nom"><?= htmlspecialchars($carte['nom']) ?></div>
                    <div class="card_image">
                        <img src="<?= htmlspecialchars($carte['image_url'] ?? '') ?>"
                             alt="<?= htmlspecialchars($carte['nom']) ?>">
                    </div>
                    <div class="card_valeur"><?= number_format($carte['valeur'], 2, ',', ' ') ?> €</div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</main>

<!-- =========================================
     MODAL PACK OPENING
     ========================================= -->
<div id="modal-pack" class="modal-overlay" style="display:none;">
    <div class="modal-contenu">
        <h3 class="modal-titre">Ouverture du paquet</h3>
        <div id="pack-canvas"></div>
        <div id="pack-erreur" class="message_erreur" style="display:none; margin-top:16px;"></div>
        <div style="margin-top:20px;">
            <button id="btn-fermer-pack" class="btn_supprimer" style="display:none;" onclick="fermerModalPack()">
                Fermer
            </button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/p5.js/1.9.0/p5.min.js"></script>
<script>
// ==========================================================================
// SKETCH P5.JS — ANIMATION PACK OPENING
// ==========================================================================
const packSketch = (p) => {

    // Dimensions du paquet (phase d'animation)
    const PW = 130, PH = 180;
    // Dimensions de la carte révélée (significativement plus grande)
    const CW = 220, CH = 310;

    let cx, cy;
    let state       = 'idle';
    let frameN      = 0;
    let topOff      = 0;
    let botOff      = 0;
    let cardImg     = null;
    let cardData    = null;
    let pts         = [];
    let closeCalled = false;

    p.setup = () => {
        // Canvas agrandi pour accueillir la grande carte
        p.createCanvas(460, 500).parent('pack-canvas');
        cx = p.width  / 2;
        cy = p.height / 2;
        p.textFont('Georgia, serif');
    };

    p.draw = () => {
        p.background(220, 227, 234);

        if (state === 'idle') {
            const pulse = 1 + Math.sin(p.frameCount * 0.05) * 0.015;
            p.push();
            p.translate(cx, cy);
            p.scale(pulse);
            drawPack();
            p.pop();

        } else if (state === 'opening') {
            frameN++;
            const ease = easeInOut(Math.min(frameN / 55, 1));
            topOff = ease * 200;
            botOff = ease * 200;
            p.push(); p.translate(cx, cy); drawPackHalves(); p.pop();
            if (frameN === 3) spawnPts();
            updatePts(); drawPts();
            if (ease >= 1) { state = 'revealing'; frameN = 0; }

        } else if (state === 'revealing') {
            frameN++;
            const t = Math.min(frameN / 50, 1);
            p.push(); p.translate(cx, cy); drawCard(t); p.pop();
            updatePts(); drawPts();
            if (t >= 1 && !closeCalled) {
                closeCalled = true;
                state = 'done';
                document.getElementById('btn-fermer-pack').style.display = 'inline-block';
            }

        } else if (state === 'done') {
            p.push(); p.translate(cx, cy); drawCard(1); p.pop();
        }
    };

    function easeInOut(t) {
        return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
    }

    // ---- Dessin du paquet fermé ----
    function drawPack() {
        p.noStroke();
        p.fill(0, 0, 0, 25);
        p.rect(-PW / 2 + 3, -PH / 2 + 3, PW, PH, 10);

        p.fill(45, 55, 72);
        p.stroke(232, 160, 32);
        p.strokeWeight(2);
        p.rect(-PW / 2, -PH / 2, PW, PH, 10);

        p.noStroke(); p.fill(232, 160, 32);
        p.textAlign(p.CENTER, p.CENTER); p.textSize(10);
        p.text('PAQUET', 0, -PH / 2 + 16);

        p.stroke(232, 160, 32, 100); p.strokeWeight(0.5);
        p.line(-PW / 2 + 14, -PH / 2 + 28, PW / 2 - 14, -PH / 2 + 28);

        p.noStroke(); p.fill(232, 160, 32);
        p.textSize(58); p.textAlign(p.CENTER, p.CENTER);
        p.text('?', 0, 8);
    }

    // ---- Dessin des moitiés du paquet qui s'écartent ----
    function drawPackHalves() {
        p.fill(45, 55, 72); p.stroke(232, 160, 32); p.strokeWeight(2);
        p.rect(-PW / 2, -PH / 2 - topOff, PW, PH / 2, 10, 10, 0, 0);
        p.noStroke(); p.fill(232, 160, 32);
        p.textAlign(p.CENTER, p.CENTER); p.textSize(10);
        p.text('PAQUET', 0, -PH / 2 - topOff + 16);

        p.fill(45, 55, 72); p.stroke(232, 160, 32); p.strokeWeight(2);
        p.rect(-PW / 2, botOff, PW, PH / 2, 0, 0, 10, 10);
    }

    // ---- Dessin de la carte révélée (grande) ----
    function drawCard(t) {
        const sx = easeInOut(Math.min(t, 1));
        p.push();
        p.scale(sx, 1);

        // Ombre portée
        p.noStroke(); p.fill(0, 0, 0, 30 * sx);
        p.rect(-CW / 2 + 5, -CH / 2 + 5, CW, CH, 12);

        const rc = cardData ? rarityBorderColor(cardData.rarete) : [113, 128, 150];

        if (t <= 0.5) {
            // Dos de la carte
            p.fill(45, 55, 72); p.stroke(rc[0], rc[1], rc[2]); p.strokeWeight(3);
            p.rect(-CW / 2, -CH / 2, CW, CH, 12);
            p.noStroke(); p.fill(232, 160, 32);
            p.textSize(64); p.textAlign(p.CENTER, p.CENTER);
            p.text('★', 0, 0);

        } else if (cardData) {
            // Face de la carte
            p.fill(255); p.stroke(rc[0], rc[1], rc[2]); p.strokeWeight(3);
            p.rect(-CW / 2, -CH / 2, CW, CH, 12);

            // Zone image — 60% de la hauteur de la carte
            const iW = CW - 20;
            const iH = Math.floor((CH - 20) * 0.60);
            const iX = -CW / 2 + 10;
            const iY = -CH / 2 + 10;
            const textBase = iY + iH;

            if (cardImg) {
                p.noStroke();
                p.image(cardImg, iX, iY, iW, iH);
            } else {
                p.fill(200, 210, 219); p.noStroke();
                p.rect(iX, iY, iW, iH, 6);
            }

            // Nom
            p.noStroke(); p.fill(45, 55, 72);
            p.textSize(15); p.textAlign(p.CENTER, p.CENTER); p.textStyle(p.BOLD);
            p.text(cardData.nom, 0, textBase + 18);

            // Badge rareté
            p.fill(rc[0], rc[1], rc[2]); p.noStroke();
            p.rect(-50, textBase + 30, 100, 20, 10);
            p.fill(255); p.textSize(11); p.textStyle(p.NORMAL);
            p.text(cardData.rarete, 0, textBase + 41);

            // Valeur
            p.noStroke(); p.fill(232, 160, 32);
            p.textSize(16); p.textStyle(p.BOLD);
            p.text(parseFloat(cardData.valeur).toFixed(2) + ' €', 0, textBase + 64);
        }
        p.pop();
    }

    function rarityBorderColor(r) {
        const map = {
            'commune'     : [113, 128, 150],
            'peu commune' : [45,  106,  79],
            'rare'        : [26,   82, 118],
            'ultra rare'  : [125,  60, 152]
        };
        return map[r] || [113, 128, 150];
    }

    function spawnPts() {
        const rarete = cardData ? cardData.rarete : 'commune';
        const isRare = rarete === 'rare' || rarete === 'ultra rare';
        const count  = isRare ? 35 : 22;
        for (let i = 0; i < count; i++) {
            const angle = p.random(p.TWO_PI);
            const speed = p.random(1.5, isRare ? 7 : 5);
            pts.push({
                x: cx, y: cy,
                vx: Math.cos(angle) * speed,
                vy: Math.sin(angle) * speed - 1,
                life: 1,
                size: p.random(3, isRare ? 11 : 8),
                gold: p.random() > (isRare ? 0.2 : 0.4)
            });
        }
    }

    function updatePts() {
        for (let i = pts.length - 1; i >= 0; i--) {
            pts[i].x += pts[i].vx; pts[i].y += pts[i].vy;
            pts[i].vy += 0.1;      pts[i].life -= 0.02;
            if (pts[i].life <= 0) pts.splice(i, 1);
        }
    }

    function drawPts() {
        p.noStroke();
        for (const pt of pts) {
            const a = pt.life * 255;
            pt.gold ? p.fill(232, 160, 32, a) : p.fill(255, 255, 255, a);
            p.ellipse(pt.x, pt.y, pt.size * pt.life);
        }
    }

    p.openPack = (data) => {
        cardData = data; cardImg = null;
        if (data.image_url) {
            p.loadImage(data.image_url, img => { cardImg = img; }, () => { cardImg = null; });
        }
        state = 'opening'; frameN = 0; topOff = 0; botOff = 0;
        pts = []; closeCalled = false;
    };

    p.resetSketch = () => {
        state = 'idle'; frameN = 0; topOff = 0; botOff = 0;
        cardImg = null; cardData = null; pts = []; closeCalled = false;
    };
};

let packP5 = null;
document.addEventListener('DOMContentLoaded', () => { packP5 = new p5(packSketch); });

function ouvrirModalPack() {
    const modal     = document.getElementById('modal-pack');
    const erreur    = document.getElementById('pack-erreur');
    const btnFermer = document.getElementById('btn-fermer-pack');

    modal.style.display     = 'flex';
    erreur.style.display    = 'none';
    btnFermer.style.display = 'none';
    if (packP5) packP5.resetSketch();

    fetch('pack.php', { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            if (data.succes) {
                if (packP5) packP5.openPack(data.carte);
            } else {
                erreur.textContent      = data.message;
                erreur.style.display    = 'block';
                btnFermer.style.display = 'inline-block';
            }
        })
        .catch(() => {
            erreur.textContent      = 'Erreur de connexion. Veuillez réessayer.';
            erreur.style.display    = 'block';
            btnFermer.style.display = 'inline-block';
        });
}

function fermerModalPack() {
    document.getElementById('modal-pack').style.display = 'none';
    if (packP5) packP5.resetSketch();
    location.reload();
}
</script>

<?php require_once 'includes/footer.php'; ?>